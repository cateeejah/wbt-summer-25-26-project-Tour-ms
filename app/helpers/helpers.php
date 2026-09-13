<?php
/* ================= CSRF protection =================
   A CSRF token is a random secret stored in the session and echoed into
   every form. A malicious site cannot read this value, so it cannot forge
   a request on the user's behalf. */

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

//Prints the hidden input that every POST form must contain.
function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

//Adds the token to a link (used by Delete / Cancel / Claim / Book links
//that mutate data via a plain GET request).
function csrf_url($url) {
    $separator = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $separator . 'csrf_token=' . csrf_token();
}

//Stops the request if the token is missing or wrong. Call this as the
//very first line of every controller branch that changes data.
function csrf_check() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('Security check failed (invalid or missing CSRF token). Please go back and try again.');
    }
}

/* ================= Session hardening ================= */

//Call once, right after a successful login, to prevent session fixation:
//issuing a brand-new session ID means an attacker who tricked the user
//into using a known session ID before login gains nothing from it.
function regenerate_session() {
    session_regenerate_id(true);
}

//Call at the top of every request (after session_start()). Logs the user
//out automatically after a period of inactivity.
function enforce_session_timeout($timeoutSeconds) {
    if (isset($_SESSION['user'])) {
        if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > $timeoutSeconds) {
            $_SESSION = [];
            session_destroy();
            setcookie('remember_token', '', time() - 3600, '/');
            header('Location: index.php?page=login&msg=session_expired');
            exit;
        }
        $_SESSION['last_active'] = time();
    }
}
/* ================= JSON responses (for AJAX endpoints) ================= */

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
?>
