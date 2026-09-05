<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Strip internal absolute paths (e.g. C:/Users/..., /var/www/...) and the
// Filename/Line Number footer from the raw message before it reaches a
// visitor. The full details are already written to application/logs/.
$clean = preg_replace('#[A-Za-z]:[\\\\/][^\s<]+#', '[server path]', (string) $message);
$clean = preg_replace('/(Filename|Line Number):[^\n<]*/i', '', $clean);
$clean = trim(preg_replace('/\n{2,}/', "\n", $clean));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Something went wrong</title>
<style type="text/css">
body { background-color:#F8FAFC; margin:0; font:14px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; color:#334155; }
#container { max-width:560px; margin:80px auto; background:#fff; border:1px solid #E2E8F0; border-radius:16px; padding:36px; box-shadow:0 10px 30px rgba(15,23,42,.06); }
h1 { font-size:1.25rem; color:#B91C1C; margin:0 0 10px; }
p { margin:0 0 14px; }
code { display:block; background:#F1F5F9; border-radius:8px; padding:12px 14px; font-size:.8rem; color:#475569; white-space:pre-wrap; word-break:break-word; margin:12px 0; }
a { color:#0D9488; font-weight:600; }
</style>
</head>
<body>
	<div id="container">
		<h1>Something went wrong</h1>
		<p>The system hit an unexpected problem while processing your request. The technical team has been notified.</p>
		<?php if ($clean !== '' && $clean !== 'Something went wrong'): ?>
			<code><?php echo htmlspecialchars($clean, ENT_QUOTES, 'UTF-8'); ?></code>
		<?php endif; ?>
		<p><a href="javascript:history.back()">&larr; Go back</a></p>
	</div>
</body>
</html>