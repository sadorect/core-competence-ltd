<?php
/**
 * CCFM gallery admin — shared auth + CSRF helpers.
 * Included at the top of every admin script. Config + data live OUTSIDE the web
 * root so they are never served and survive repo→docroot deploys.
 */

const GALLERY_DATA_DIR = '/home/ccfmltd/gallery-data';
const CONFIG_FILE      = GALLERY_DATA_DIR . '/config.php';

/** Load the admin config (['pass_hash' => ...]) or null if not set up yet. */
function admin_config() {
    return is_file(CONFIG_FILE) ? (include CONFIG_FILE) : null;
}

/** True once an admin password has been set. */
function admin_is_configured() {
    $c = admin_config();
    return is_array($c) && !empty($c['pass_hash']);
}

/** Start the admin session with hardened cookie settings (idempotent). */
function admin_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // Behind Cloudflare the origin often sees plain HTTP; trust the proxied scheme.
    $https = (($_SERVER['HTTPS'] ?? '') === 'on')
          || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('ccfm_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/admin',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function admin_logged_in() {
    return !empty($_SESSION['admin']);
}

/** Guard: redirect to the login screen unless authenticated. */
function require_login() {
    admin_session_start();
    if (!admin_logged_in()) {
        header('Location: /admin/login.php', true, 302);
        exit;
    }
}

/* ---- IP-based login throttle (file-backed, survives cookie-less attacks) ----
   Keyed on the real client IP (Cloudflare's CF-Connecting-IP), so a scripted
   brute-force that ignores the session cookie is still locked out. REMOTE_ADDR is
   only a fallback — behind Cloudflare it is a shared CF address, so we must not
   key on it (that would lock all visitors out together). */

const THROTTLE_DIR    = GALLERY_DATA_DIR . '/throttle';
const LOGIN_MAX_FAILS = 5;    // failures within the window before a lockout
const LOGIN_WINDOW    = 900;  // seconds: sliding window for counting failures
const LOGIN_LOCK_BASE = 900;  // seconds: first lockout (doubles per repeat lock)
const LOGIN_LOCK_CAP  = 21600;// seconds: max lockout (6h)

function client_ip() {
    // This site is served DIRECTLY by Apache — there is no Cloudflare or reverse
    // proxy in front — so REMOTE_ADDR is the genuine client IP. We deliberately do
    // NOT trust CF-Connecting-IP / X-Forwarded-For: with no trusted proxy to set
    // them, they are attacker-spoofable and would let a brute-forcer dodge the
    // per-IP lockout by sending a fresh fake IP on every request.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * One-time verify of the login image CAPTCHA. Consumes the stored answer so each
 * challenge is single-use (a fresh image is issued on the next page load).
 */
function captcha_verify($input) {
    $ans = $_SESSION['captcha'] ?? '';
    unset($_SESSION['captcha']);
    $input = strtoupper(trim((string) $input));
    return $ans !== '' && $input !== '' && hash_equals($ans, $input);
}

function throttle_file($ip) {
    if (!is_dir(THROTTLE_DIR)) {
        @mkdir(THROTTLE_DIR, 0750, true);
    }
    return THROTTLE_DIR . '/' . hash('sha256', $ip) . '.json';
}

function throttle_read($ip) {
    $base = ['fails' => 0, 'first' => 0, 'until' => 0, 'locks' => 0];
    $f = throttle_file($ip);
    if (!is_file($f)) {
        return $base;
    }
    $d = json_decode((string) file_get_contents($f), true);
    return is_array($d) ? $d + $base : $base;
}

function throttle_write($ip, array $d) {
    $f   = throttle_file($ip);
    $tmp = $f . '.tmp';
    if (file_put_contents($tmp, json_encode($d), LOCK_EX) !== false) {
        @chmod($tmp, 0640);
        rename($tmp, $f);
    }
}

/** Seconds remaining on an active lockout for this IP, or 0 if not locked. */
function login_locked_for($ip) {
    $until = throttle_read($ip)['until'] ?? 0;
    return $until > time() ? $until - time() : 0;
}

function login_record_failure($ip) {
    $d   = throttle_read($ip);
    $now = time();
    if ($now - ($d['first'] ?? 0) > LOGIN_WINDOW) {
        $d['fails'] = 0;
        $d['first'] = $now;
    }
    $d['fails']++;
    if ($d['fails'] >= LOGIN_MAX_FAILS) {
        $d['locks'] = ($d['locks'] ?? 0) + 1;
        $lock = min(LOGIN_LOCK_BASE * (2 ** ($d['locks'] - 1)), LOGIN_LOCK_CAP);
        $d['until'] = $now + $lock;
        $d['fails'] = 0;             // reset the counter; a fresh window begins after the lock
        $d['first'] = $now + $lock;
    }
    throttle_write($ip, $d);
}

function login_clear_failures($ip) {
    $f = throttle_file($ip);
    if (is_file($f)) {
        @unlink($f);
    }
}

/** Per-session CSRF token. */
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="'
         . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/** Verify the CSRF token on a POST, or abort with 400. */
function csrf_check() {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(400);
        exit('Bad request (invalid CSRF token).');
    }
}
