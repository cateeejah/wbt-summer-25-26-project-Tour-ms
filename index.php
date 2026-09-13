<?php
//session cookie hardening
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

require 'app/helpers/helpers.php';
require 'config/database.php';
require 'config/app.php';
require 'app/models/User.php';
require 'app/controllers/AuthController.php';
require 'app/controllers/HomeController.php';
require 'app/models/AdminModel.php';
require 'app/models/Notification.php';
require 'app/controllers/NotificationController.php';
require 'app/controllers/AdminController.php';

require 'app/models/Guide.php';
require 'app/controllers/GuideController.php';
require 'app/models/Vendor.php';
require 'app/controllers/VendorController.php';
require 'app/models/Tour.php';
require 'app/controllers/TourController.php';
require 'app/controllers/AjaxController.php';

enforce_session_timeout(1800);

$page = $_GET['page'] ?? 'login';

// Remember Me cookie if not logged in
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $token_user = getUserByRememberToken($conn, $_COOKIE['remember_token']);
    if ($token_user) {
        $_SESSION['user'] = [
            'id'    => $token_user['id'],
            'name'  => $token_user['name'],
            'email' => $token_user['email'],
            'role'  => $token_user['role'],
            'is_verified' => $token_user['is_verified'],
            'profile_picture' => $token_user['profile_picture'] ?? ''
        ];
        $_SESSION['user_id'] = $token_user['id'];
        $_SESSION['name']    = $token_user['name'];
        $_SESSION['role']    = $token_user['role'];
    }
}

//Logout
if ($page === 'logout') {
    if (isset($_SESSION['user'])) {
        updateRememberToken($conn, $_SESSION['user']['id'], NULL);
    }
    $_SESSION = [];
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/');
    header('Location: index.php?page=login');
    exit;
}

//Auth gates
$publicPages = ['login', 'registration', 'home'];

//If already logged in.
if (in_array($page, ['login', 'registration']) && isset($_SESSION['user'])) {
    if ($_SESSION['user']['is_verified'] == 1) {
        header('Location: index.php?page=' . $_SESSION['user']['role']);
    } else {
        header('Location: index.php?page=home');
    }
    exit;
}

//Protected pages login & ajax endpoint exception:
if (!in_array($page, $publicPages) && $page !== 'ajax' && !isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}

//Role gates
if ($page === 'admin'  && $_SESSION['user']['role'] !== 'admin')  { header('Location: index.php?page=login'); exit; }
if ($page === 'guide'  && $_SESSION['user']['role'] !== 'guide')  { header('Location: index.php?page=login'); exit; }
if ($page === 'vendor' && $_SESSION['user']['role'] !== 'vendor') { header('Location: index.php?page=login'); exit; }
if ($page === 'user'   && $_SESSION['user']['role'] !== 'user')   { header('Location: index.php?page=login'); exit; }

// Verification gate.
if (isset($_SESSION['user']) && $_SESSION['user']['is_verified'] == 0 && !in_array($page, ['home', 'logout', 'ajax'])) {
    header('Location: index.php?page=home');
    exit;
}

//Dispatch
switch ($page) {
    case 'home':         homeCtrl($conn);     break;
    case 'login':        loginCtrl($conn);    break;
    case 'registration': registerCtrl($conn); break;
    case 'admin':         adminCtrl($conn);   break;
    case 'guide':         guideCtrl($conn);   break;
    case 'vendor':        vendorCtrl($conn);  break;
    case 'user':          userCtrl($conn);    break;
    case 'notifications': notificationsCtrl($conn); break;
    case 'ajax':          ajaxCtrl($conn);    break;

    default:
        header('Location: index.php?page=home');
        exit;
}

mysqli_close($conn);
?>
