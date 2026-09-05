<?php
$ticket  = isset($ticket) ? $ticket : NULL;
$replies = isset($replies) ? $replies : array();

$flash_success = $this->session->flashdata('grade_success');
$flash_error   = $this->session->flashdata('grade_error');
?>
<!-- BEGIN PAGE CONTENT -->
  <div class="page-header">
    <div>
      <h2 class="page-title"><?php echo html_escape($ticket->subject); ?></h2>
      <p class="page-subtitle"><?php echo html_escape($ticket->category); ?> · <span class="badge <?php echo $ticket->status === 'Open' ? 'badge-success' : ($ticket->status === 'In Progress' ? '' : 'badge-neutral'); ?>"><?php echo html_escape($ticket->status); ?></span></p>
    </div>
    <a href="<?php echo site_url('teacher/tickets'); ?>" class="btn-secondary" style="display:flex; align-items:center; gap:6px; border-radius:12px; border:none; cursor:pointer; text-decoration:none; font-size:0.8rem;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to My Tickets
    </a>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert-error visible" style="background:#F0FDFA;border-color:#99F6E4;color:#0F766E;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_success); ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert-error visible" style="border-color:#FCA5A5;color:#B91C1C;margin-bottom:16px;border-radius:10px;padding:10px 14px;font-size:0.82rem;"><?php echo html_escape($flash_error); ?></div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:24px;">
    <div style="padding:24px; border-bottom:1px solid #F1F5F9;">
      <div style="display:flex; gap:12px; margin-bottom:12px;">
        <span class="badge" style="background:#F0F9FF;color:#0D9488;font-size:0.75rem;"><?php echo html_escape($ticket->category); ?></span>
        <span class="text-faint" style="font-size:0.75rem;">Created <?php echo date('M j, Y g:i A', strtotime($ticket->created_at)); ?></span>
        <span class="text-faint" style="font-size:0.75rem;">Last updated <?php echo date('M j, Y g:i A', strtotime($ticket->updated_at)); ?></span>
      </div>
      <p style="margin:12px 0 0; color:#334155; font-size:0.9rem; line-height:1.6; white-space:pre-wrap;"><?php echo html_escape($ticket->message); ?></p>
    </div>
  </div>

  <?php if ( ! empty($replies)): ?>
    <div class="card" style="margin-bottom:24px;">
      <div style="padding:16px 24px; border-bottom:1px solid #F1F5F9;">
        <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">Conversation (<?php echo count($replies); ?>)</h3>
      </div>
      <div style="padding:24px; display:flex; flex-direction:column; gap:20px;">
        <?php foreach ($replies as $r): ?>
          <div style="display:flex; gap:12px;">
            <div class="avatar" style="width:36px;height:36px;font-size:0.75rem;"><?php echo strtoupper(substr($r->first_name,0,1) . substr($r->last_name,0,1)); ?></div>
            <div style="flex:1;">
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <span style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?php echo html_escape($r->first_name . ' ' . $r->last_name); ?></span>
                <span class="text-faint" style="font-size:0.7rem;"><?php echo date('M j, Y g:i A', strtotime($r->created_at)); ?></span>
                <?php if ($r->role === 'admin'): ?>
                  <span class="badge" style="background:#FAF5FF;color:#7C3AED;font-size:0.65rem;">Admin</span>
                <?php elseif ($r->role === 'teacher'): ?>
                  <span class="badge" style="background:#F0FDFA;color:#0D9488;font-size:0.65rem;">Teacher</span>
                <?php else: ?>
                  <span class="badge" style="background:#F0F9FF;color:#0D9488;font-size:0.65rem;">Student</span>
                <?php endif; ?>
              </div>
              <p style="margin:0; color:#334155; font-size:0.9rem; line-height:1.6; white-space:pre-wrap;"><?php echo html_escape($r->message); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="card" style="margin-bottom:24px;">
      <div style="padding:24px; text-align:center; color:#94A3B8; font-size:0.85rem;">No replies yet.</div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div style="padding:16px 24px; border-bottom:1px solid #F1F5F9;">
      <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; color:#1E293B; margin:0;">Add a Reply</h3>
    </div>
    <?php echo form_open('teacher/ticket_reply/' . (int) $ticket->id, array('novalidate' => 'novalidate')); ?>
    <div style="padding:24px;">
      <div class="form-group">
        <label class="form-label" for="message">Message <span style="color:#EF4444;">*</span></label>
        <textarea class="form-input" id="message" name="message" rows="4" placeholder="Type your reply..." maxlength="5000" required></textarea>
        <div class="text-faint" style="font-size:0.75rem;margin-top:4px;">Maximum 5000 characters</div>
      </div>
      <div style="display:flex; gap:12px; margin-top:24px; justify-content:flex-end;">
        <button type="submit" class="btn-primary" style="flex:1; max-width:200px;">Send Reply</button>
      </div>
    </div>
    <?php echo form_close(); ?>
  </div>