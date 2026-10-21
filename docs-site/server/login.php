<?php
require __DIR__ . '/auth.php';
docs_boot();

$error = '';
$next  = $_GET['next'] ?? $_POST['next'] ?? '/';
if (!preg_match('#^/[A-Za-z0-9._/-]*$#', $next)) {
    $next = '/';
}

if (docs_is_signed_in()) {
    header('Location: ' . $next);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (docs_sign_in($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: ' . $next);
        exit;
    }
    // One message for both failures: naming which half was wrong tells an
    // outsider which usernames exist.
    $error = 'That username and password did not match.';
    usleep(400000);
}

http_response_code($error ? 401 : 200);
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sign in — Crystal Art School site guide</title>
<style>
:root{--ink:#2b2320;--muted:#6d615a;--line:#e2d9d1;--bg:#fbf8f5;--panel:#fff;--accent:#8a5a3b;--bad:#a6362b}
@media(prefers-color-scheme:dark){:root{--ink:#efe7e1;--muted:#a5968c;--line:#3a302b;--bg:#1c1715;--panel:#231d1a;--accent:#d0a077;--bad:#e08b7e}}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);color:var(--ink);
 font:16px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",sans-serif;padding:24px}
.card{width:100%;max-width:388px;background:var(--panel);border:1px solid var(--line);
 border-radius:14px;padding:38px 34px 32px;box-shadow:0 14px 40px rgba(0,0,0,.07)}
h1{font-size:21px;margin:0 0 6px}
.sub{color:var(--muted);font-size:14px;margin:0 0 26px}
label{display:block;font-size:13px;font-weight:600;margin:16px 0 6px}
input{width:100%;padding:11px 13px;font-size:15px;color:var(--ink);background:var(--bg);
 border:1px solid var(--line);border-radius:8px}
input:focus{outline:2px solid var(--accent);outline-offset:1px;border-color:var(--accent)}
button{width:100%;margin-top:24px;padding:12px;font-size:15px;font-weight:600;color:#fff;
 background:var(--accent);border:0;border-radius:8px;cursor:pointer}
button:hover{filter:brightness(1.08)}
.err{margin:18px 0 0;padding:10px 13px;border:1px solid var(--bad);border-radius:8px;
 color:var(--bad);font-size:14px}
.foot{margin:26px 0 0;padding-top:16px;border-top:1px solid var(--line);color:var(--muted);font-size:12.5px}
</style></head><body>
<form class="card" method="post" autocomplete="on">
  <h1>Crystal Art School</h1>
  <p class="sub">Sign in to read the site guide.</p>
  <?php if ($error): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES) ?></p><?php endif; ?>
  <label for="u">Username</label>
  <input id="u" name="username" autocapitalize="none" autocomplete="username" required autofocus>
  <label for="p">Password</label>
  <input id="p" name="password" type="password" autocomplete="current-password" required>
  <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES) ?>">
  <button type="submit">Sign in</button>
  <p class="foot">Lost your password? Ask Herman — there is no reset email on this site.</p>
</form>
</body></html>
