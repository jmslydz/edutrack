<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * Identity info and notifications here come from the session-derived user
 * row that MY_Controller passes in — never from request input.
 */
?><?php
function _notif_time($dt)
{
	if (empty($dt)) return '';
	$diff = time() - strtotime($dt);
	if ($diff < 60) return 'just now';
	if ($diff < 3600) return floor($diff / 60) . 'm ago';
	if ($diff < 86400) return floor($diff / 3600) . 'h ago';
	if ($diff < 86400 * 7) return floor($diff / 86400) . 'd ago';
	return date('M j', strtotime($dt));
}

/**
 * Per-type icon + colors for a notification (SVG only — no emoji).
 * @param object $n
 * @return array{label:string, bg:string, color:string, icon:string}
 */
if ( ! function_exists('_notif_type_meta'))
{
	function _notif_type_meta($n)
	{
		$type  = isset($n->type) ? $n->type : 'system';
		$title = isset($n->title) ? $n->title : '';
		$t = strtolower($type . ' ' . $title);
		$meta = array(
			'label' => 'Notification',
			'bg'    => '#F0FDFA',
			'color' => '#0D9488',
			'icon'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
		);
		if (strpos($t, 'announcement') !== FALSE)
		{
			$meta = array('label' => 'Announcement', 'bg' => '#FFF7ED', 'color' => '#F97316', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>');
		}
		elseif (strpos($t, 'correction') !== FALSE)
		{
			$meta = array('label' => 'Correction', 'bg' => '#FAF5FF', 'color' => '#8B5CF6', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>');
		}
		elseif (strpos($t, 'reminder') !== FALSE)
		{
			$meta = array('label' => 'Reminder', 'bg' => '#FFFBEB', 'color' => '#D97706', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>');
		}
		elseif (strpos($t, 'approved') !== FALSE)
		{
			$meta = array('label' => 'Approved', 'bg' => '#F0FDFA', 'color' => '#0D9488', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>');
		}
		elseif (strpos($t, 'denied') !== FALSE || strpos($t, 'rejected') !== FALSE)
		{
			$meta = array('label' => 'Denied', 'bg' => '#FEF2F2', 'color' => '#DC2626', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>');
		}
		return $meta;
	}
}
?><div class="main-content">
<header class="topbar">
  <div>
    <span class="text-faint" style="font-size:0.75rem; font-weight:500;" data-subtitle><?php echo html_escape(isset($subtitle) ? $subtitle : ''); ?></span>
  </div>
  <div class="topbar-actions">
    <div class="notif-wrap">
      <button class="notif-btn" aria-label="Notifications" type="button" title="Notifications" onclick="toggleNotifications(this)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        <?php if ( ! empty($notif_unread)): ?>
          <span class="notif-dot" aria-hidden="true"><?php echo (int) $notif_unread; ?></span>
        <?php endif; ?>
      </button>
      <div class="notif-panel" id="notifPanel">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid #F1F5F9;">
          <div style="display:flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1E293B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            <span style="font-family:var(--font-display); font-weight:700; font-size:0.9rem; color:#1E293B;">Notifications</span>
            <?php if ( ! empty($notif_unread)): ?>
              <span style="background:#EF4444; color:white; font-size:0.65rem; font-weight:700; padding:2px 7px; border-radius:10px; line-height:1;">
                <?php echo (int) $notif_unread; ?>
              </span>
            <?php endif; ?>
          </div>
          <?php if ( ! empty($notif_unread)): ?>
            <?php echo form_open('notifications/read_all', array('style' => 'margin:0;')); ?>
              <button type="submit" style="background:none;border:none;color:#0D9488;font-size:0.78rem;font-weight:600;cursor:pointer;padding:0;">Mark all read</button>
            <?php echo form_close(); ?>
          <?php endif; ?>
        </div>
        <div style="max-height:380px; overflow-y:auto;">
          <?php if (empty($notifications)): ?>
            <div style="padding:40px 20px; text-align:center;">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:10px;"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
              <div style="color:#94A3B8; font-size:0.82rem; font-weight:500;">No notifications yet</div>
              <div style="color:#CBD5E1; font-size:0.72rem; margin-top:2px;">You're all caught up!</div>
            </div>
          <?php else: ?>
            <?php foreach ($notifications as $n): ?>
              <?php $meta = _notif_type_meta($n); ?>
              <a href="<?php echo site_url('notifications/read/' . (int) $n->id); ?>" class="notif-item<?php echo $n->is_read ? '' : ' unread'; ?>" data-notif-id="<?php echo (int) $n->id; ?>" data-notif-type="<?php echo html_escape(isset($n->type) ? $n->type : 'system'); ?>" data-view-url="<?php echo site_url('notifications/view/' . (int) $n->id); ?>" style="display:flex; align-items:flex-start; gap:12px; padding:14px 18px; border-bottom:1px solid #F1F5F9; text-decoration:none; transition:background 0.12s;">
                <div style="width:36px; height:36px; border-radius:10px; background:<?php echo $meta['bg']; ?>; color:<?php echo $meta['color']; ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;" title="<?php echo html_escape($meta['label']); ?>">
                  <?php echo $meta['icon']; ?>
                </div>
                <div style="flex:1; min-width:0;">
                  <div class="notif-title" style="font-size:0.85rem; font-weight:<?php echo $n->is_read ? '600' : '700'; ?>; color:#1E293B; line-height:1.35;"><?php echo html_escape($n->title); ?></div>
                  <div style="font-size:0.7rem; color:#94A3B8; margin-top:2px;"><?php echo html_escape(_notif_time($n->created_at)); ?></div>
                  <?php if ( ! empty($n->body)): ?>
                    <div style="font-size:0.78rem; color:#64748B; line-height:1.4; margin-top:4px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                      <?php echo html_escape($n->body); ?>
                    </div>
                  <?php endif; ?>
                </div>
                <?php if ( ! $n->is_read): ?>
                  <div style="width:8px; height:8px; border-radius:50%; background:#0D9488; flex-shrink:0; margin-top:5px;"></div>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <?php if ( ! empty($notifications)): ?>
          <div style="padding:10px 16px; border-top:1px solid #F1F5F9; text-align:center;">
            <a href="<?php echo site_url('notifications'); ?>" style="font-size:0.78rem; color:#0D9488; font-weight:600; text-decoration:none;">View all notifications</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="topbar-divider" aria-hidden="true"></div>
    <div class="user-block">
      <div>
        <div class="name" data-user-name><?php echo html_escape(isset($user_name) ? $user_name : 'User Name'); ?></div>
        <div class="role" data-user-role><?php echo html_escape(isset($user_role_label) ? $user_role_label : 'Role'); ?></div>
      </div>
      <div class="avatar" data-avatar-initials aria-hidden="true"><?php echo html_escape(isset($avatar_initials) ? $avatar_initials : '--'); ?></div>
    </div>
  </div>

  <!-- Notification message popup (clean: full info, no action buttons) -->
  <div class="modal-overlay" id="notifModal">
    <div class="modal" style="max-width:540px;">
      <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:20px;">
        <div id="notifModalIcon" style="width:46px; height:46px; border-radius:13px; background:#F0FDFA; color:#0D9488; display:flex; align-items:center; justify-content:center; flex-shrink:0;"></div>
        <div style="flex:1; min-width:0;">
          <div id="notifModalType" style="font-size:0.72rem; font-weight:700; color:#0D9488; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:4px;">Notification</div>
          <h3 id="notifModalTitle" style="font-family:var(--font-display); font-size:1.15rem; font-weight:700; color:#1E293B; margin:0; line-height:1.4;">Notification</h3>
          <div style="display:flex; align-items:center; gap:6px; font-size:0.78rem; color:#94A3B8; margin-top:8px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span id="notifModalTime"></span>
          </div>
        </div>
        <button type="button" style="background:#F1F5F9;border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748B;flex-shrink:0;" onclick="closeModal('notifModal')" aria-label="Close" title="Close">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div style="height:1px; background:#F1F5F9; margin-bottom:20px;"></div>
      <p id="notifModalBody" style="margin:0; color:#334155; font-size:0.95rem; line-height:1.7; white-space:pre-wrap; word-break:break-word;"></p>
    </div>
  </div>
</header>
    <div class="content-area">