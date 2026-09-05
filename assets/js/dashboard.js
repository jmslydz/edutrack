/*
 * EduTrack — Dashboard UI interactions
 * UI/UX behavior only. No fetch(), no form submission handling, no data
 * persistence. The dev AI will layer real behavior (AJAX or full-page
 * submits) on top of these hooks where noted.
 */

// Notification bell dropdown (topbar)
function toggleNotifications(btn) {
  var wrap = btn.closest('.notif-wrap');
  if (wrap) wrap.classList.toggle('open');
}
document.addEventListener('click', function (e) {
  var wrap = document.querySelector('.notif-wrap');
  if (wrap && !wrap.contains(e.target)) {
    wrap.classList.remove('open');
  }
});

// Generic modal open/close (used by Manage Users "Add/Edit" modal)
function openModal(modalId) {
  var modal = document.getElementById(modalId);
  if (modal) modal.classList.add('visible');
}
function closeModal(modalId) {
  var modal = document.getElementById(modalId);
  if (modal) modal.classList.remove('visible');
}
document.addEventListener('click', function (e) {
  if (e.target.classList && e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('visible');
  }
});

// Per-type icon + label for the notification popup header (SVG only — no emoji).
var NOTIF_ICONS = {
  announcement: { label: 'Announcement', color: '#F97316', bg: '#FFF7ED', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>' },
  correction: { label: 'Correction', color: '#8B5CF6', bg: '#FAF5FF', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>' },
  reminder: { label: 'Reminder', color: '#D97706', bg: '#FFFBEB', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
  approved: { label: 'Approved', color: '#0D9488', bg: '#F0FDFA', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' },
  denied: { label: 'Denied', color: '#DC2626', bg: '#FEF2F2', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' },
  rejected: { label: 'Rejected', color: '#DC2626', bg: '#FEF2F2', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' },
  system: { label: 'Notification', color: '#0D9488', bg: '#F0FDFA', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>' }
};

function applyNotifMeta(type) {
  var iconEl = document.getElementById('notifModalIcon');
  var typeEl = document.getElementById('notifModalType');
  if (!iconEl || !typeEl) return;
  var meta = NOTIF_ICONS[type] || NOTIF_ICONS.system;
  iconEl.innerHTML = meta.icon;
  iconEl.style.background = meta.bg;
  iconEl.style.color = meta.color;
  typeEl.textContent = meta.label;
  typeEl.style.color = meta.color;
}

// Notification bell items: tapping one opens its full message in a popup
// instead of leaving the page. The message is fetched from
// notifications/view/{id} (AJAX). The item's own href (notifications/read/{id})
// is kept as a data attribute fallback, so without JavaScript the original
// click-through still works unchanged.
document.addEventListener('click', function (e) {
  var item = e.target && e.target.closest ? e.target.closest('.notif-item') : null;
  if (!item) return;
  var viewUrl = item.getAttribute('data-view-url');
  if (!viewUrl) return;

  e.preventDefault();

  var modal  = document.getElementById('notifModal');
  var titleEl = document.getElementById('notifModalTitle');
  var timeEl  = document.getElementById('notifModalTime');
  var bodyEl  = document.getElementById('notifModalBody');
  if (!modal || !titleEl || !bodyEl) return;

  var listTitle = item.querySelector('.notif-title');
  applyNotifMeta(item.getAttribute('data-notif-type'));
  titleEl.textContent = listTitle ? listTitle.textContent : 'Notification';
  timeEl.textContent = '';
  bodyEl.textContent = 'Loading…';
  openModal('notifModal');

  fetch(viewUrl, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin'
  }).then(function (resp) {
    return resp.json().catch(function () { return null; }).then(function (payload) {
      if (!resp.ok || !payload || !payload.ok) {
        throw new Error(payload && payload.message ? payload.message : 'Could not load this notification.');
      }
      titleEl.textContent = payload.title;
      applyNotifMeta(payload.type);
      timeEl.textContent = payload.time;
      bodyEl.textContent = payload.body ? payload.body : '(No message body.)';
      item.classList.remove('unread');
      updateNotifBadge(-1);
    }).catch(function (err) {
      bodyEl.textContent = err.message || 'Could not load this notification.';
    });
  }).catch(function () {
    bodyEl.textContent = 'Could not load this notification.';
  });
});

function updateNotifBadge(delta) {
  var badge = document.querySelector('.notif-dot');
  if (!badge) return;
  var current = parseInt(badge.textContent, 10) || 0;
  var next = Math.max(0, current + delta);
  if (next === 0) {
    badge.style.display = 'none';
    badge.textContent = '';
  } else {
    badge.style.display = '';
    badge.textContent = next;
  }
  // Also update "Mark all as read" button visibility
  var markAllBtn = document.querySelector('.notif-panel form');
  if (markAllBtn) {
    markAllBtn.style.display = next === 0 ? 'none' : '';
  }
}

// Expand/collapse a section row to show the classes inside it
function toggleSectionRows(sectionId) {
  var row = document.querySelector('[data-detail-row="' + sectionId + '"]');
  var caret = document.querySelector('[data-caret="' + sectionId + '"]');
  if (!row) return;
  var hidden = row.style.display === 'none';
  row.style.display = hidden ? '' : 'none';
  if (caret) caret.style.transform = hidden ? 'rotate(90deg)' : '';
}

// Account status toggle switch (Manage Users modal)
function toggleStatusSwitch(el) {
  var isActive = el.getAttribute('data-active') === 'true';
  el.setAttribute('data-active', (!isActive).toString());
  el.classList.toggle('is-active', !isActive);
  var labelEl = document.getElementById(el.getAttribute('data-label-target'));
  if (labelEl) {
    labelEl.textContent = !isActive ? 'Active' : 'Inactive';
    labelEl.style.color = !isActive ? '#16A34A' : '#64748B';
  }
  el.style.background = !isActive ? '#0D9488' : '#CBD5E1';
  el.querySelector('span').style.left = !isActive ? '24px' : '4px';
  // Backend integration: keep the hidden "status" field in sync so the
  // controller receives the toggled value.
  var statusInput = document.getElementById('statusInput');
  if (statusInput) statusInput.value = !isActive ? 'active' : 'inactive';
}

/*
 * Grade input live color-coding (Encode Grades page).
 * UX feedback only — recomputes color/remarks/status client-side as the
 * teacher types. The saved value and its validation MUST be re-checked
 * server-side before it ever reaches the database (see comments in
 * teacher/encode_grades.php).
 */
function gradeColorFor(value) {
  var n = parseFloat(value);
  if (!n) return { color: '#94A3B8', bg: 'transparent' };
  if (n <= 1.5) return { color: '#16A34A', bg: '#DCFCE7' };
  if (n <= 2.5) return { color: '#0891B2', bg: '#E0F2FE' };
  if (n <= 3.0) return { color: '#F97316', bg: '#FFF7ED' };
  return { color: '#EF4444', bg: '#FEF2F2' };
}

function onGradeInputChange(input) {
  var row = input.closest('tr');
  var value = input.value.trim();
  var c = gradeColorFor(value);
  input.style.color = c.color;
  input.style.background = value ? c.bg : 'white';

  var missingTag = row.querySelector('.missing-tag');
  if (missingTag) missingTag.style.display = value ? 'none' : 'inline-flex';

  var remarksCell = row.querySelector('.remarks-cell');
  if (remarksCell) {
    var n = parseFloat(value);
    if (!value) {
      remarksCell.innerHTML = '';
    } else if (n <= 3.0) {
      remarksCell.innerHTML = '<span class="badge" style="background:#DCFCE7;color:#16A34A;">Passed</span>';
    } else {
      remarksCell.innerHTML = '<span class="badge" style="background:#FEF2F2;color:#EF4444;">Failed</span>';
    }
  }

  var statusDot = row.querySelector('.status-dot');
  var statusText = row.querySelector('.status-text');
  if (statusDot && statusText) {
    if (value) {
      statusDot.style.background = '#10B981';
      statusText.textContent = 'Encoded';
      statusText.style.color = '#16A34A';
    } else {
      statusDot.style.background = '#CBD5E1';
      statusText.textContent = 'Pending';
      statusText.style.color = '#94A3B8';
    }
  }

  updateEncodedCount();
}

function updateEncodedCount() {
  var inputs = document.querySelectorAll('.grade-input');
  var encoded = 0;
  inputs.forEach(function (i) { if (i.value.trim()) encoded++; });
  var counter = document.getElementById('encodedCount');
  if (counter) counter.textContent = encoded;
  var incompleteBanner = document.getElementById('incompleteBanner');
  var incompleteCountEl = document.getElementById('incompleteCount');
  var incomplete = inputs.length - encoded;
  if (incompleteBanner && incompleteCountEl) {
    incompleteCountEl.textContent = incomplete;
    incompleteBanner.style.display = incomplete > 0 ? 'flex' : 'none';
  }
}

// "Clear All" button on Encode Grades — UI only, clears the visible form
function clearAllGrades() {
  document.querySelectorAll('.grade-input').forEach(function (input) {
    input.value = '';
    onGradeInputChange(input);
  });
}

// Simple "Saved" confirmation banner (client-side only placeholder —
// BACKEND TODO: replace with real save handling / AJAX response)
function showSavedBanner() {
  var el = document.getElementById('savedBanner');
  if (!el) return;
  el.style.display = 'flex';
  setTimeout(function () { el.style.display = 'none'; }, 3000);
}

function codeFromText(text) {
  return String(text || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
}

function suggestShortCodeFromText(text) {
  var t = codeFromText(text);
  return t ? t.substring(0, 10) : null;
}

// Manage Strands: pre-fill the Short Code field as a convenience while the
// admin types the Strand Code / Strand Name. For senior high school the short
// code IS the strand name, so the code itself is the suggestion. Only fills
// while the field is empty — the field itself stays a normal, editable input.
function bindShortCodeSuggestion() {
  var codeInput = document.getElementById('progCode');
  var nameInput = document.getElementById('progName');
  var shortInput = document.getElementById('progShort');
  if (!codeInput || !nameInput || !shortInput) return;

  function suggestIfEmpty() {
    if (shortInput.value.trim() !== '') return;
    var s = suggestShortCodeFromText(codeInput.value || nameInput.value);
    if (s) shortInput.value = s;
  }
  codeInput.addEventListener('input', suggestIfEmpty);
  nameInput.addEventListener('input', suggestIfEmpty);
}

// Sections: auto-fill the Section Name as {grade_level}-{short_code}-{sequence},
// e.g. "11-STEM-1", when the admin picks a Strand + Grade Level on Add Section.
// The sequence is the highest existing sequence for that grade+strand combo
// (gap-safe: recomputed from pattern matches, never a plain COUNT), so a
// mid-sequence delete does not produce a duplicate name. The name field
// stays fully editable — once the admin types, auto-fill stops.
function bindSectionNameSuggestion() {
  var progSel = document.getElementById('secProgram');
  var yearSel = document.getElementById('secYear');
  var nameInput = document.getElementById('secName');
  if (!progSel || !yearSel || !nameInput) return;

  var dataEl = document.getElementById('secAutoData');
  var autodata = dataEl ? JSON.parse(dataEl.textContent || '{"programs":[],"sections":[]}') : { programs: [], sections: [] };

  var progData = {};
  (autodata.programs || []).forEach(function (p) { progData[p.id] = (p.short_code || p.program_code || ''); });
  var sections = autodata.sections || [];

  var lastAuto = null;

  function fill(name) {
    nameInput.value = name;
    nameInput.setAttribute('data-auto-name', '1');
    lastAuto = name;
  }

  nameInput.addEventListener('input', function () {
    if (lastAuto !== null && nameInput.value !== lastAuto) {
      nameInput.removeAttribute('data-auto-name');
      lastAuto = null;
    }
  });

  function suggest() {
    var pid = parseInt(progSel.value, 10);
    var yl = parseInt(yearSel.value, 10);
    if (!pid || !yl) return;
    var prefix = (progData[pid] || '').toUpperCase();
    if (!prefix) return;

    var current = nameInput.value.trim();
    var wasAuto = nameInput.getAttribute('data-auto-name') === '1';
    if (current !== '' && !wasAuto) return; // respect a manual/edited value

    var rx = new RegExp('^' + yl + '-' + prefix + '-(\\d+)$', 'i');
    var maxSeq = 0;
    sections.forEach(function (sec) {
      if (sec.program_id !== pid || sec.year_level !== yl) return;
      var m = rx.exec(sec.name || '');
      if (m) {
        var n = parseInt(m[1], 10);
        if (n > maxSeq) maxSeq = n;
      }
    });
    fill(yl + '-' + prefix + '-' + (maxSeq + 1));
  }

  progSel.addEventListener('change', suggest);
  yearSel.addEventListener('change', suggest);
}

bindShortCodeSuggestion();
bindSectionNameSuggestion();

/*
 * Admin sidebar — collapsible "Academic Setup" group.
 * Defaults to EXPANDED whenever the current page is one of the 6 academic
 * sub-pages; otherwise the admin's manual expand/collapse choice is
 * persisted in localStorage so the group does not reset on every page load.
 */
var ACAD_GROUP_KEY = 'edutrack_academic_group';
var ACAD_PAGES = ['school_years', 'semesters', 'programs', 'sections'];

function academicGroupEls() {
  return {
    group: document.getElementById('acadGroup'),
    items: document.getElementById('acadGroupItems'),
    toggle: document.getElementById('acadGroupToggle')
  };
}

function setAcademicGroup(open, persist) {
  var els = academicGroupEls();
  if (!els.items) return;
  els.items.style.display = open ? '' : 'none';
  if (els.group) els.group.classList.toggle('open', open);
  if (els.toggle) els.toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  // A forced open (academic sub-page) must not overwrite the admin's manual
  // expand/collapse choice, so only persist when the change is a real toggle.
  if (persist !== false) {
    try { localStorage.setItem(ACAD_GROUP_KEY, open ? 'open' : 'closed'); } catch (e) { /* private mode */ }
  }
}

function toggleAcademicGroup() {
  var els = academicGroupEls();
  var isOpen = els.group ? els.group.classList.contains('open') : false;
  setAcademicGroup(!isOpen);
}

(function initAcademicGroup() {
  var sidebar = document.querySelector('.sidebar');
  var active = sidebar ? (sidebar.getAttribute('data-page') || '') : '';
  var forcingAcademic = ACAD_PAGES.indexOf(active) !== -1;
  var open;
  if (forcingAcademic) {
    open = true; // never let an academic sub-page hide its own group
  } else {
    try { open = localStorage.getItem(ACAD_GROUP_KEY) === 'open'; } catch (e) { open = false; }
  }
  setAcademicGroup(open, !forcingAcademic);
})();
