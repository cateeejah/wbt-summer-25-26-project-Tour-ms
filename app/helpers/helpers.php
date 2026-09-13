<?php
// ================= CSRF protection =================

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_url($url) {
    $separator = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $separator . 'csrf_token=' . csrf_token();
}

function csrf_check() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('Security check failed (invalid or missing CSRF token). Please go back and try again.');
    }
}

// ================= Session hardening =================

function regenerate_session() {
    session_regenerate_id(true);
}

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
// ================= JSON responses (for AJAX endpoints) ================= 

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
?>
