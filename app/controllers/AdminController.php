<?php
function adminCtrl($conn) {
    $action = $_GET['action'] ?? 'dashboard';
    $error = '';

    //USER MANAGEMENT
    if ($action === 'users') {
        //Handle user deletion
        if (isset($_GET['delete']) && isset($_GET['id'])) {
            csrf_check();
            $userId = intval($_GET['id']);

            // Prevent self-deletion
            if ($userId === $_SESSION['user']['id']) {
                $error = "You can't delete your own account";
            } else {
                deleteUser($conn, $userId);
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    echo json_encode(['success' => true]);
                    exit;
                }
                header("Location: index.php?page=admin&action=users&msg=user_deleted");
                exit;
            }
        }

        //Handle new user creation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
            csrf_check();
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $pass = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $v = intval($_POST['is_verified'] ?? 0);

            //Role-specific fields (only relevant when role is guide or vendor)
            $guideLocation = trim($_POST['location'] ?? '');
            $guideRate = $_POST['daily_rate'] ?? 0;
            $vendorType = $_POST['vendor_type'] ?? 'hotel';
            $vendorCompany = trim($_POST['company_name'] ?? '');
            $vendorAddress = trim($_POST['address'] ?? '');

            if (emailExists($conn, $email)) {
                $error = "This email is already registered.";
            } elseif ($role === 'guide' && $guideLocation === '') {
                $error = 'Please provide a guiding location for this guide account.';
            } elseif ($role === 'vendor' && $vendorCompany === '') {
                $error = 'Please provide a company name for this vendor account.';
            } else {
                $newId = adminAddUser($conn, $name, $email, $pass, $role, $v, $phone);
                if ($newId) {
                    if ($role === 'guide') {
                        createRoleProfile($conn, $newId, 'guide', [
                            'location' => $guideLocation,
                            'daily_rate' => $guideRate
                        ]);
                    } elseif ($role === 'vendor') {
                        createRoleProfile($conn, $newId, 'vendor', [
                            'type' => $vendorType,
                            'company_name' => $vendorCompany,
                            'address' => $vendorAddress
                        ]);
                    }
                    header("Location: index.php?page=admin&action=users&msg=user_added");
                    exit;
                }
                $error = 'Could not create the account. Please try again.';
            }
        }

        //Handle verification toggle
        if (isset($_GET['verify']) && isset($_GET['id'])) {
            csrf_check();
            $userId = intval($_GET['id']);
            $status = intval($_GET['verify']);
            if (verifyUser($conn, $userId, $status)) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    echo json_encode(['success' => true]);
                    exit;
                }
                header("Location: index.php?page=admin&action=users&msg=user_updated");
                exit;
            }
        }

        //Handle role change. If promoting someone into guide/vendor, create the
        //matching role-specific row too (with placeholder details they can
        //fill in from their own dashboard) — otherwise they'd hit "no
        //profile found" the moment they land on their new dashboard.
        if (isset($_GET['new_role']) && isset($_GET['id'])) {
            csrf_check();
            $userId = intval($_GET['id']);
            $role = $_GET['new_role'];
            if (updateUserRole($conn, $userId, $role)) {
                if ($role === 'guide' && !getGuideByUserId($conn, $userId)) {
                    createRoleProfile($conn, $userId, 'guide', [
                        'location' => 'Not set',
                        'daily_rate' => 0
                    ]);
                } elseif ($role === 'vendor' && !getVendorByUserId($conn, $userId)) {
                    createRoleProfile($conn, $userId, 'vendor', [
                        'type' => 'hotel',
                        'company_name' => 'Not set',
                        'address' => 'Not set'
                    ]);
                }
                header("Location: index.php?page=admin&action=users&msg=user_updated");
                exit;
            }
        }

        $users = getAllUsers($conn);
        require 'app/views/admin/manage_users.php';
        return;
    }

    //NOTIFICATIONS (admin broadcast)
    if ($action === 'notifications') {
        if (isset($_GET['delete']) && isset($_GET['id'])) {
            csrf_check();
            deleteNotification($conn, intval($_GET['id']));
            header("Location: index.php?page=admin&action=notifications&msg=notification_deleted");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_notification'])) {
            csrf_check();
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $targetRole = $_POST['target_role'] ?? 'all';

            if ($title === '' || $message === '') {
                $error = 'Title and message are required.';
            } else {
                createNotification($conn, $title, $message, $targetRole);
                header("Location: index.php?page=admin&action=notifications&msg=notification_sent");
                exit;
            }
        }

        $notifications = getAllNotifications($conn);
        require 'app/views/admin/notifications.php';
        return;
    }

    //DISCOUNTS (revenue management)
    if ($action === 'discounts') {
        if (isset($_GET['toggle']) && isset($_GET['id'])) {
            csrf_check();
            $status = $_GET['toggle'] === 'active' ? 'active' : 'expired';
            toggleDiscountStatus($conn, intval($_GET['id']), $status);
            header("Location: index.php?page=admin&action=discounts&msg=discount_updated");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_discount'])) {
            csrf_check();
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $pct = floatval($_POST['discount_pct'] ?? 0);
            $validTill = $_POST['valid_till'] ?? '';

            if ($code === '' || $validTill === '') {
                $error = 'Code and valid-till date are required.';
            } else {
                if (createDiscount($conn, $code, $pct, $validTill)) {
                    header("Location: index.php?page=admin&action=discounts&msg=discount_added");
                    exit;
                } else {
                    $error = 'That discount code already exists.';
                }
            }
        }

        $discounts = getAllDiscounts($conn);
        require 'app/views/admin/discounts.php';
        return;
    }

    //SPECIAL REQUEST OVERSIGHT (view-only — vendors approve/reject their own)
    if ($action === 'requests') {
        $specialRequests = getAllSpecialRequests($conn);
        require 'app/views/admin/moderate_requests.php';
        return;
    }

    //RATINGS OVERSIGHT (view-only — user reviews of guides & listings)
    if ($action === 'ratings') {
        $ratings = getAllRatings($conn);
        require 'app/views/admin/ratings.php';
        return;
    }

    //ADMIN DASHBOARD
    $stats = getDashboardStats($conn);
    require 'app/views/admin/dashboard.php';
}
?>
