<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$notifications = isset($notifications) ? $notifications : array();
$unread_count  = isset($unread_count) ? (int) $unread_count : 0;

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
			'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
		);
		if (strpos($t, 'announcement') !== FALSE)
		{
			$meta = array('label' => 'Announcement', 'bg' => '#FFF7ED', 'color' => '#F97316', 'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>');
		}
		elseif (strpos($t, 'correction') !== FALSE)
		{
			$meta = array('label' => 'Correction', 'bg' => '#FAF5FF', 'color' => '#8B5CF6', 'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>');
		}
		elseif (strpos($t, 'reminder') !== FALSE)
		{
			$meta = array('label' => 'Reminder', 'bg' => '#FFFBEB', 'color' => '#D97706', 'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>');
		}
		elseif (strpos($t, 'approved') !== FALSE)
		{
			$meta = array('label' => 'Approved', 'bg' => '#F0FDFA', 'color' => '#0D9488', 'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>');
		}
		elseif (strpos($t, 'denied') !== FALSE || strpos($t, 'rejected') !== FALSE)
		{
			$meta = array('label' => 'Denied', 'bg' => '#FEF2F2', 'color' => '#DC2626', 'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>');
		}
		return $meta;
	}
}

function _notif_full_time($dt)
{
	if (empty($dt)) return '';
	$d = date('M j, Y g:i A', strtotime($dt));
	return $d;
}
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title">Notifications</h2>
      <p class="page-subtitle"><?php echo count($notifications); ?> notification<?php echo count($notifications) !== 1 ? 's' : ''; ?><?php echo $unread_count > 0 ? ' · ' . $unread_count . ' unread' : ''; ?></p>
    </div>
    <?php if ($unread_count > 0): ?>
      <?php echo form_open('notifications/read_all', array('style' => 'margin:0;')); ?>
        <button type="submit" class="btn-secondary" style="display:inline-flex; align-items:center; gap:6px; border-radius:10px; cursor:pointer; font-size:0.85rem; font-weight:600;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          Mark all as read
        </button>
      <?php echo form_close(); ?>
    <?php endif; ?>
  </div>

  <?php if (empty($notifications)): ?>
    <div class="card" style="padding:56px 24px; text-align:center;">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px;"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
      <h3 style="font-family:var(--font-display); font-size:1.1rem; font-weight:700; color:#1E293B; margin:0 0 8px;">No notifications yet</h3>
      <p class="text-faint" style="font-size:0.88rem; margin:0;">When something happens — a grade update, an approval, an announcement — it will show up here.</p>
    </div>
  <?php else: ?>
    <div class="card" style="padding:0; overflow:hidden;">
      <?php foreach ($notifications as $i => $n): ?>
        <?php $meta = _notif_type_meta($n); ?>
        <a href="<?php echo site_url('notifications/read/' . (int) $n->id); ?>" style="display:flex; align-items:flex-start; gap:14px; padding:18px 22px; text-decoration:none; border-bottom:1px solid #F1F5F9; background:<?php echo $n->is_read ? '#FFFFFF' : '#F8FFFE'; ?>; transition:background 0.12s;<?php echo $i === count($notifications) - 1 ? ' border-bottom:none;' : ''; ?>" onmouseover="this.style.background='#F0FDFA'" onmouseout="this.style.background='<?php echo $n->is_read ? '#FFFFFF' : '#F8FFFE'; ?>'">
          <div style="width:40px; height:40px; border-radius:11px; background:<?php echo $meta['bg']; ?>; color:<?php echo $meta['color']; ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;" title="<?php echo html_escape($meta['label']); ?>">
            <?php echo $meta['icon']; ?>
          </div>
          <div style="flex:1; min-width:0;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
              <span style="font-size:0.92rem; font-weight:<?php echo $n->is_read ? '600' : '700'; ?>; color:#1E293B; line-height:1.35;"><?php echo html_escape($n->title); ?></span>
              <span style="font-size:0.72rem; color:#94A3B8; white-space:nowrap;"><?php echo html_escape(_notif_full_time($n->created_at)); ?></span>
            </div>
            <?php if ( ! empty($n->body)): ?>
              <div style="font-size:0.84rem; color:#64748B; line-height:1.5; margin-top:5px; white-space:pre-wrap; word-break:break-word;"><?php echo html_escape($n->body); ?></div>
            <?php endif; ?>
            <div style="display:flex; align-items:center; gap:8px; margin-top:8px;">
              <?php if ( ! $n->is_read): ?>
                <span class="badge" style="background:#0D9488; color:#fff; font-size:0.65rem;">New</span>
              <?php endif; ?>
              <span style="font-size:0.72rem; color:#94A3B8;"><?php echo html_escape($meta['label']); ?></span>
            </div>
          </div>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:12px;"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<!-- END PAGE CONTENT -->