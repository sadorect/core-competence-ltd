<?php
/**
 * Admin login. On a fresh install (no config yet) this becomes a one-time
 * "set admin password" screen — so no password is ever shipped in the repo.
 */
require __DIR__ . '/inc/auth.php';
admin_session_start();

if (admin_logged_in()) {
    header('Location: /admin/');
    exit;
}

$setup = !admin_is_configured();
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_check();

    if ($setup) {
        $p1 = (string) ($_POST['password'] ?? '');
        $p2 = (string) ($_POST['password2'] ?? '');
        if (strlen($p1) < 10) {
            $error = 'Password must be at least 10 characters.';
        } elseif ($p1 !== $p2) {
            $error = 'The two passwords do not match.';
        } else {
            $data = ['pass_hash' => password_hash($p1, PASSWORD_DEFAULT), 'created' => date('c')];
            $php  = "<?php\nreturn " . var_export($data, true) . ";\n";
            if (!is_dir(GALLERY_DATA_DIR)) {
                @mkdir(GALLERY_DATA_DIR, 0750, true);
            }
            $tmp = CONFIG_FILE . '.tmp';
            if (file_put_contents($tmp, $php, LOCK_EX) !== false && rename($tmp, CONFIG_FILE)) {
                @chmod(CONFIG_FILE, 0640);
                session_regenerate_id(true);
                $_SESSION['admin'] = true;
                header('Location: /admin/');
                exit;
            }
            $error = 'Could not write config to ' . GALLERY_DATA_DIR . '. Check permissions.';
        }
    } else {
        $ip   = client_ip();
        $wait = login_locked_for($ip);
        if ($wait > 0) {
            $error = 'Too many failed attempts. Try again in ' . ceil($wait / 60) . ' minute(s).';
        } elseif (!captcha_verify($_POST['captcha'] ?? '')) {
            // Wrong/blank CAPTCHA — counts toward the lockout, and the password is
            // never even checked (defeats scripted brute-force that can't read it).
            login_record_failure($ip);
            $error = 'The characters you typed did not match the image. Please try again.';
        } else {
            $c = admin_config();
            if ($c && password_verify((string) ($_POST['password'] ?? ''), $c['pass_hash'])) {
                login_clear_failures($ip);
                session_regenerate_id(true);
                $_SESSION['admin'] = true;
                header('Location: /admin/');
                exit;
            }
            login_record_failure($ip);
            usleep(400000); // small constant delay on every failure
            $again = login_locked_for($ip);
            $error = $again > 0
                ? 'Too many failed attempts. Locked for ' . ceil($again / 60) . ' minute(s).'
                : 'Incorrect password.';
        }
    }
}

$title = $setup ? 'Set admin password' : 'Admin sign in';
require __DIR__ . '/inc/head.php';
?>
<main class="admin-auth">
  <div class="auth-card">
    <img class="auth-logo" src="/assets/img/logo.svg" alt="CCFM" width="170" height="40">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($setup): ?>
      <p class="auth-note">No admin password is set yet. Choose one now to secure the gallery manager.</p>
    <?php else: ?>
      <p class="auth-note">Sign in to manage the gallery.</p>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert--err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" action="/admin/login.php" autocomplete="off">
      <?= csrf_field() ?>
      <div class="field">
        <label for="password"><?= $setup ? 'New password' : 'Password' ?></label>
        <input type="password" id="password" name="password" required minlength="<?= $setup ? 10 : 1 ?>" autofocus>
      </div>
      <?php if ($setup): ?>
      <div class="field">
        <label for="password2">Confirm password</label>
        <input type="password" id="password2" name="password2" required minlength="10">
      </div>
      <?php else: ?>
      <div class="field">
        <label for="captcha">Type the characters shown</label>
        <div class="captcha-row">
          <img class="captcha-img" src="/admin/captcha.php?<?= time() ?>" alt="CAPTCHA verification image" width="180" height="58">
          <a class="captcha-reload" href="/admin/login.php" title="Load a new image" aria-label="Load a new image">↻</a>
        </div>
        <input type="text" id="captcha" name="captcha" required maxlength="5" autocomplete="off" autocapitalize="characters" spellcheck="false" inputmode="text">
      </div>
      <?php endif; ?>
      <button type="submit" class="btn btn--accent btn--lg"><?= $setup ? 'Set password &amp; continue' : 'Sign in' ?></button>
    </form>
    <p class="auth-back"><a href="/">&larr; Back to site</a></p>
  </div>
</main>
</body>
</html>
