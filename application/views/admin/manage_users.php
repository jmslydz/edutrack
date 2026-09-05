<?php
$users = isset($users) ? $users : array();
$student_section = isset($student_section) ? $student_section : array();
$student_nos = isset($student_nos) ? $student_nos : array();
$assign_counts = isset($assign_counts) ? $assign_counts : array();
$active_tab = isset($active_tab) ? $active_tab : 'all';
$tiles = isset($tiles) ? $tiles : array('total' => 0, 'admins' => 0, 'teachers' => 0, 'students' => 0, 'active' => 0);
$edit_user = isset($edit_user) ? $edit_user : NULL;
$filters = isset($filters) ? $filters : array('search' => '', 'role' => '', 'status' => '');

$flash_success = $this->session->flashdata('admin_success');
$flash_error   = $this->session->flashdata('admin_error');

$section_list = isset($sections) ? $sections : array();

function _initials_for($u)
{
	return mb_strtoupper(mb_substr(trim($u->first_name), 0, 1) . mb_substr(trim($u->last_name), 0, 1));
}
function _badge_class($role)
{
	switch ($role)
	{
		case 'admin':   return 'badge-admin';
		case 'teacher': return 'badge-teacher';
		default:        return 'badge-student';
	}
}
function _rel_last_login($dt)
{
	if (empty($dt)) return 'Never';
	$diff = time() - strtotime($dt);
	if ($diff < 3600) return floor($diff / 60) . ' minute' . (floor($diff / 60) === 1 ? '' : 's') . ' ago';
	if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) === 1 ? '' : 's') . ' ago';
	if ($diff < 86400 * 30) return floor($diff / 86400) . ' day' . (floor($diff / 86400) === 1 ? '' : 's') . ' ago';
	return floor($diff / (86400 * 30)) . ' month' . (floor($diff / (86400 * 30)) === 1 ? '' : 's') . ' ago';
}
?>
<!-- BEGIN PAGE CONTENT (everything below belongs inside .content-area) -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Manage Users</h2>
      <p class="page-subtitle">Manage system accounts and permissions</p>
    </div>
    <button type="button" class="btn-primary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer;" onclick="openModal('userModal')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
      Add New User
    </button>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="tile-strip tile-strip--5">
    <div class="tile" style="background:#F0FDFA; border-color:#0D948822;"><div class="tile-value" style="color:#0D9488;"><?php echo $tiles['total']; ?></div><div class="tile-label">Total Users</div></div>
    <div class="tile" style="background:#FAF5FF; border-color:#7C3AED22;"><div class="tile-value" style="color:#7C3AED;"><?php echo $tiles['admins']; ?></div><div class="tile-label">Admins</div></div>
    <div class="tile" style="background:#EFF6FF; border-color:#1D4ED822;"><div class="tile-value" style="color:#1D4ED8;"><?php echo $tiles['teachers']; ?></div><div class="tile-label">Teachers</div></div>
    <div class="tile" style="background:#F0FDFA; border-color:#0D948822;"><div class="tile-value" style="color:#0D9488;"><?php echo $tiles['students']; ?></div><div class="tile-label">Students</div></div>
    <div class="tile" style="background:#DCFCE7; border-color:#16A34A22;"><div class="tile-value" style="color:#16A34A;"><?php echo $tiles['active']; ?></div><div class="tile-label">Active</div></div>
  </div>

  <?php
  // Tab bar. Links carry the current search/status so switching tabs does not
  // lose the active query (page is intentionally dropped so each tab starts
  // at its own page 1).
  $tab_links = array('all' => 'All', 'teacher' => 'Teachers', 'student' => 'Students', 'admin' => 'Admins');
  $tab_qs = '';
  if ($filters['search'] !== '') { $tab_qs .= '&search=' . rawurlencode($filters['search']); }
  if ($filters['status'] !== '')  { $tab_qs .= '&status=' . rawurlencode($filters['status']); }
  ?>
  <div class="inpage-tabs" role="tablist" aria-label="Filter users by role">
    <?php foreach ($tab_links as $key => $label): ?>
      <a class="inpage-tab<?php echo $active_tab === $key ? ' active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === $key ? 'true' : 'false'; ?>" href="<?php echo site_url('admin/users?tab=' . $key . $tab_qs); ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
  </div>

  <div class="card" style="overflow:hidden;">
    <form method="get" action="<?php echo site_url('admin/users'); ?>">
    <div class="list-toolbar">
      <input type="hidden" name="tab" value="<?php echo html_escape($active_tab); ?>">
      <div class="search-wrap">
        <span class="field-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>
        <input class="form-input" placeholder="Search users..." aria-label="Search users" name="search" value="<?php echo html_escape($filters['search']); ?>">
      </div>
      <?php if ($active_tab === 'all'): ?>
      <select class="form-input filter-select" name="role" onchange="this.form.submit()">
        <option value="">All Roles</option>
        <option value="admin" <?php echo $filters['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
        <option value="teacher" <?php echo $filters['role'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
        <option value="student" <?php echo $filters['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
      </select>
      <?php endif; ?>
      <select class="form-input filter-select" name="status" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
      </select>
    </div>
    </form>

    <?php
    switch ($active_tab)
    {
      case 'teacher':
        $headers = array('Name', 'Username', 'Role', 'Classes (Active Term)', 'Status', 'Last Login', 'Actions');
        break;
      case 'student':
        $headers = array('Name', 'Username', 'Role', 'Student No.', 'Section', 'Status', 'Last Login', 'Actions');
        break;
      case 'admin':
        $headers = array('Name', 'Username', 'Role', 'Status', 'Last Login', 'Actions');
        break;
      default:
        $headers = array('Name', 'Username', 'Role', 'Section', 'Status', 'Last Login', 'Actions');
        break;
    }
    ?>

    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead><tr><?php foreach ($headers as $h): ?><th><?php echo html_escape($h); ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr class="table-row"><td colspan="<?php echo count($headers); ?>" style="text-align:center; color:#94A3B8; padding:24px;">No users match your filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
        <tr class="table-row">
          <td><div style="display:flex; align-items:center; gap:10px;"><div style="width:34px;height:34px;border-radius:10px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:0.85rem;color:#64748B;"><?php echo html_escape(_initials_for($u)); ?></div><span style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($u->first_name . ' ' . $u->last_name); ?></span></div></td>
          <td style="font-family:monospace;font-size:0.8rem;color:#64748B;"><?php echo html_escape($u->username); ?></td>
          <td><span class="badge <?php echo _badge_class($u->role); ?>"><?php echo html_escape(ucfirst($u->role)); ?></span></td>
          <?php if ($active_tab === 'teacher'): ?>
            <td><span class="badge badge-teacher"><?php echo isset($assign_counts[$u->id]) ? (int) $assign_counts[$u->id] : 0; ?> assigned</span></td>
          <?php endif; ?>
          <?php if ($active_tab === 'student'): ?>
            <td><span style="font-family:monospace;font-weight:700;color:#0D9488;background:#F0FDFA;padding:2px 8px;border-radius:6px;font-size:0.78rem;"><?php echo html_escape(isset($student_nos[$u->id]) ? $student_nos[$u->id] : ''); ?></span></td>
          <?php endif; ?>
          <?php if ($active_tab === 'all' || $active_tab === 'student'): ?>
            <td style="color:#64748B;font-size:0.85rem;"><?php echo $u->role === 'student' ? html_escape(isset($student_section[$u->id]) ? $student_section[$u->id] : '—') : '—'; ?></td>
          <?php endif; ?>
          <td><span class="badge <?php echo $u->status === 'active' ? 'badge-success' : 'badge-neutral'; ?>"><?php echo html_escape(ucfirst($u->status)); ?></span></td>
          <td style="color:#94A3B8;font-size:0.8rem;"><?php echo html_escape(_rel_last_login($u->last_login_at)); ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a class="icon-btn icon-btn--edit" href="<?php echo site_url('admin/users?edit=' . $u->id); ?>" aria-label="Edit" title="Edit" onclick="openModal('userModal')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
              </a>
              <?php echo form_open('admin/users/reset_password/' . $u->id, array('style' => 'display:inline;')); ?>
                <button type="submit" class="icon-btn icon-btn--key" aria-label="Reset Password" title="Reset Password" onclick="return confirm('Reset this user\\'s password? They will be required to set a new one on next login.');">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5 18 10"/></svg>
                </button>
              <?php echo form_close(); ?>
              <?php echo form_open('admin/users/delete/' . $u->id, array('style' => 'display:inline;')); ?>
                <button type="submit" class="btn-danger" style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:8px;" aria-label="Delete" title="Delete" onclick="return confirm('Delete this user account? This cannot be undone.');">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                </button>
              <?php echo form_close(); ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="table-footer">
      <span class="text-faint" style="font-size:0.78rem;">Showing <?php echo count($users); ?> of <?php echo (int) $filtered_total; ?> users</span>
      <?php echo $pagination; ?>
    </div>
  </div>

  <!-- Add/Edit User Modal -->
  <div class="modal-overlay<?php echo ($edit_user ? ' visible' : ''); ?>" id="userModal">
    <div class="modal">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h3 style="font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:#1E293B; margin:0;"><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h3>
        <button style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;" onclick="closeModal('userModal')" aria-label="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <?php
      $form_action = $edit_user ? 'admin/users/update/' . $edit_user->id : 'admin/users/store';
      echo form_open($form_action, array('novalidate' => 'novalidate'));
      ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="firstName">First Name</label>
            <input class="form-input" id="firstName" name="first_name" placeholder="Juan" value="<?php echo html_escape($edit_user ? $edit_user->first_name : ''); ?>" required>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="lastName">Last Name</label>
            <input class="form-input" id="lastName" name="last_name" placeholder="Dela Cruz" value="<?php echo html_escape($edit_user ? $edit_user->last_name : ''); ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input class="form-input" id="username" name="username" placeholder="juan.delacruz" value="<?php echo html_escape($edit_user ? $edit_user->username : ''); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="userEmail">Email Address</label>
          <input class="form-input" type="email" id="userEmail" name="email" placeholder="user@school.edu" value="<?php echo html_escape($edit_user ? $edit_user->email : ''); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="userRole">Role</label>
          <select class="form-input" id="userRole" name="role">
            <option value="admin" <?php echo $edit_user && $edit_user->role === 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="teacher" <?php echo $edit_user && $edit_user->role === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
            <option value="student" <?php echo ( ! $edit_user) || $edit_user->role === 'student' ? 'selected' : ''; ?>>Student</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="userSection">Section <span class="text-faint">(students only)</span></label>
          <select class="form-input" id="userSection" name="section">
            <?php foreach ($section_list as $sec): ?>
              <option value="<?php echo (int) $sec->id; ?>" <?php echo ($edit_user && $edit_user->role === 'student' && isset($student_section[$edit_user->id]) && $student_section[$edit_user->id] === $sec->name) ? 'selected' : ''; ?>><?php echo html_escape($sec->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Account Status</label>
          <?php $is_active = $edit_user ? ($edit_user->status === 'active') : TRUE; ?>
          <div style="display:flex; align-items:center; gap:12px;">
            <button type="button" id="statusToggle" data-active="<?php echo $is_active ? 'true' : 'false'; ?>" data-label-target="statusLabel"
              class="status-switch<?php echo $is_active ? ' is-active' : ''; ?>" onclick="toggleStatusSwitch(this)"
              style="width:48px;height:26px;border-radius:13px;border:none;cursor:pointer;background:<?php echo $is_active ? '#0D9488' : '#CBD5E1'; ?>;position:relative;">
              <span style="position:absolute;top:3px;<?php echo $is_active ? 'left:24px;' : 'left:4px;'; ?>width:20px;height:20px;border-radius:50%;background:white;box-shadow:0 1px 4px rgba(0,0,0,0.2);transition:left 0.2s;"></span>
            </button>
            <span id="statusLabel" style="font-size:0.85rem;font-weight:500;color:<?php echo $is_active ? '#16A34A' : '#64748B'; ?>;"><?php echo $is_active ? 'Active' : 'Inactive'; ?></span>
            <input type="hidden" name="status" id="statusInput" value="<?php echo $is_active ? 'active' : 'inactive'; ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="tempPassword"><?php echo $edit_user ? 'New Password <span class="text-faint">(optional)</span>' : 'Temporary Password'; ?></label>
          <input class="form-input" type="password" id="tempPassword" name="temp_password" placeholder="<?php echo $edit_user ? 'Leave blank to keep current password' : 'Set initial password'; ?>" minlength="8">
          <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">User will be required to change this on first login.</div>
        </div>
        <div style="display:flex; gap:12px; margin-top:20px;">
          <button type="button" class="btn-secondary" style="flex:1;" onclick="closeModal('userModal')">Cancel</button>
          <button type="submit" class="btn-primary" style="flex:1;"><?php echo $edit_user ? 'Save Changes' : 'Create User'; ?></button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
<!-- END PAGE CONTENT -->