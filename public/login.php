<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

if (session_is_authenticated()) {
    redirect('/dashboard.php');
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip       = client_ip();

    if (is_ip_locked_out($ip)) {
        $minutes = (int) ceil(LOGIN_LOCKOUT_SECONDS / 60);
        $error = "Too many failed attempts. Try again in {$minutes} minutes.";
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } elseif (Auth::attempt($username, $password)) {
        redirect('/dashboard.php');
    } else {
        // Generic message — don't reveal which field is wrong
        $error = 'Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — <?= h(APP_NAME) ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:      #0f1117;
    --surface: #1a1d27;
    --border:  #2a2d3a;
    --accent:  #4f8ef7;
    --accent2: #3a6fd8;
    --text:    #e8eaf0;
    --muted:   #6b7280;
    --error:   #f87171;
    --radius:  12px;
    --font:    'SF Pro Display', -apple-system, 'Segoe UI', sans-serif;
  }

  body {
    font-family: var(--font);
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
  }

  /* Subtle grid background */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(rgba(79,142,247,.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(79,142,247,.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
  }

  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2.5rem;
    width: 100%;
    max-width: 400px;
    position: relative;
    animation: rise .35s cubic-bezier(.22,1,.36,1) both;
  }

  @keyframes rise {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .logo {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-bottom: 1.75rem;
  }

  .logo-icon {
    width: 36px; height: 36px;
    background: var(--accent);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
  }

  .logo-name { font-size: 1.2rem; font-weight: 600; letter-spacing: -.02em; }

  h1 { font-size: 1.4rem; font-weight: 600; letter-spacing: -.02em; margin-bottom: .4rem; }
  .subtitle { color: var(--muted); font-size: .9rem; margin-bottom: 1.75rem; }

  .error-banner {
    background: rgba(248,113,113,.1);
    border: 1px solid rgba(248,113,113,.3);
    color: var(--error);
    border-radius: 8px;
    padding: .75rem 1rem;
    font-size: .875rem;
    margin-bottom: 1.25rem;
  }

  label { display: block; font-size: .8rem; color: var(--muted); margin-bottom: .4rem; letter-spacing: .03em; text-transform: uppercase; }

  input[type="text"],
  input[type="password"] {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: var(--font);
    font-size: .95rem;
    padding: .7rem .9rem;
    outline: none;
    transition: border-color .15s;
    margin-bottom: 1.1rem;
  }

  input:focus { border-color: var(--accent); }

  button[type="submit"] {
    width: 100%;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-family: var(--font);
    font-size: .95rem;
    font-weight: 500;
    padding: .8rem;
    cursor: pointer;
    margin-top: .25rem;
    transition: background .15s, transform .1s;
  }

  button[type="submit"]:hover  { background: var(--accent2); }
  button[type="submit"]:active { transform: scale(.98); }

  .footer { text-align: center; color: var(--muted); font-size: .8rem; margin-top: 1.5rem; }
</style>
</head>
<body>

<div class="card">
  <div class="logo">
    <div class="logo-icon">☁</div>
    <span class="logo-name"><?= h(APP_NAME) ?></span>
  </div>

  <h1>Welcome back</h1>
  <p class="subtitle">Sign in to access your files</p>

  <?php if ($error): ?>
    <div class="error-banner" role="alert"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="" autocomplete="off" novalidate>
    <?= csrf_field() ?>

    <label for="username">Username</label>
    <input
      type="text"
      id="username"
      name="username"
      value="<?= h($username) ?>"
      autofocus
      autocomplete="username"
      spellcheck="false"
      required
    >

    <label for="password">Password</label>
    <input
      type="password"
      id="password"
      name="password"
      autocomplete="current-password"
      required
    >

    <button type="submit">Sign in</button>
  </form>

  <p class="footer">Your data. Your server. Your rules.</p>
</div>

</body>
</html>
