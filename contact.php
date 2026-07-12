<?php
/**
 * CCFM contact form handler.
 * Emails enquiries to the CCFM inbox via the server's local MTA (Postfix).
 * From = a domain-local address so the message passes SPF/DKIM for ccfmltd.com.ng;
 * Reply-To = the submitter so replies go straight back to them.
 */

// --- Config ---
$RECIPIENT = 'info@ccfmltd.com.ng';
$FROM      = 'no-reply@ccfmltd.com.ng';
$SUBJECT   = '[CCFM Website Enquiry]';
$MAX_LEN   = 5000; // cap message length

function fail($reason = 'send') {
    header('Location: /contact?error=' . rawurlencode($reason) . '#contact-form', true, 303);
    exit;
}
function ok() {
    header('Location: /contact?sent=1#form-status', true, 303);
    exit;
}

// Only accept POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /contact', true, 303);
    exit;
}

// Honeypot: bots fill hidden fields. Pretend success, send nothing.
if (!empty($_POST['website'])) {
    ok();
}

// Time-trap: JS records how long the form was on screen. A near-instant submit
// (< 3s) is almost certainly an automated bot. Only enforced when JS reported a
// value, so no-JS humans are never blocked. Pretend success, send nothing.
$elapsed = isset($_POST['elapsed']) ? (int) $_POST['elapsed'] : 0;
if ($elapsed > 0 && $elapsed < 3000) {
    ok();
}

// Collect + trim
$get = function ($k) { return trim((string)($_POST[$k] ?? '')); };
$full_name     = $get('full_name');
$company       = $get('company');
$sector        = $get('sector');
$facility_type = $get('facility_type');
$location      = $get('location');
$email         = $get('email');
$phone         = $get('phone');
$message        = $get('message');

// Required fields
if ($full_name === '' || $email === '' || $message === '') {
    fail('missing');
}
// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('email');
}
// Length guard
if (strlen($message) > $MAX_LEN) {
    $message = substr($message, 0, $MAX_LEN);
}

// Strip anything that could inject mail headers (CR/LF) from single-line values.
$clean = function ($v) { return str_replace(array("\r", "\n", "%0a", "%0d"), ' ', $v); };
$full_name     = $clean($full_name);
$company       = $clean($company);
$sector        = $clean($sector);
$facility_type = $clean($facility_type);
$location      = $clean($location);
$email         = $clean($email);
$phone         = $clean($phone);

// Build body
$lines = array(
    'New enquiry from the CCFM website',
    '----------------------------------',
    'Full Name:     ' . $full_name,
    'Company:       ' . ($company !== '' ? $company : '—'),
    'Sector:        ' . ($sector !== '' ? $sector : '—'),
    'Facility Type: ' . ($facility_type !== '' ? $facility_type : '—'),
    'Location:      ' . ($location !== '' ? $location : '—'),
    'Email:         ' . $email,
    'Phone:         ' . ($phone !== '' ? $phone : '—'),
    '',
    'Message:',
    $message,
    '',
    '----------------------------------',
    'Submitted: ' . date('Y-m-d H:i:s') . ' (' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ')',
);
$body = implode("\n", $lines);

// Headers
$headers   = array();
$headers[] = 'From: CCFM Website <' . $FROM . '>';
$headers[] = 'Reply-To: ' . $full_name . ' <' . $email . '>';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'X-Mailer: CCFM-Web';

$subject = $SUBJECT . ' ' . $full_name;
if ($sector !== '') { $subject .= ' — ' . $sector; }

// Set envelope sender so bounces + SPF align with the From domain.
$sent = mail($RECIPIENT, $subject, $body, implode("\r\n", $headers), '-f ' . $FROM);

if ($sent) { ok(); }
fail('send');
