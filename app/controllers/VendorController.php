<?php
function vendorCtrl($conn) {
    $userId = $_SESSION['user']['id'];
    $vendor = getVendorByUserId($conn, $userId);

    if (!$vendor) {
        echo "<p style='font-family:sans-serif;padding:2rem;'>No vendor profile found for this account. Please contact an administrator.</p>";
        return;
    }

    $vendorId = $vendor['id'];
    $action = $_GET['action'] ?? 'dashboard';
    $error = '';

    if ($action === 'add_listing' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $title = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? '';
        $price = floatval($_POST['price'] ?? 0);

        if ($title === '' || !in_array($category, ['hotels', 'vehicle']) || $price <= 0) {
            $error = 'Please provide a valid title, category, and price.';
        } else {
            createListing($conn, $vendorId, $title, $category, $price);
            header('Location: index.php?page=vendor&msg=listing_added');
            exit;
        }
    }

    if ($action === 'edit_listing' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $listingId = intval($_POST['listing_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? '';
        $price = floatval($_POST['price'] ?? 0);

        if ($title === '' || !in_array($category, ['hotels', 'vehicle']) || $price <= 0) {
            $error = 'Please provide a valid title, category, and price.';
        } else {
            updateListing($conn, $listingId, $vendorId, $title, $category, $price);
            header('Location: index.php?page=vendor&msg=listing_updated');
            exit;
        }
    }

    if ($action === 'toggle_listing' && isset($_GET['id']) && isset($_GET['status'])) {
        csrf_check();
        $allowed = ['available', 'booked', 'in_maintenance'];
        $status = in_array($_GET['status'], $allowed) ? $_GET['status'] : 'available';
        updateListingStatus($conn, intval($_GET['id']), $vendorId, $status);
        header('Location: index.php?page=vendor&msg=listing_updated');
        exit;
    }

    if ($action === 'delete_listing' && isset($_GET['id'])) {
        csrf_check();
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $ok = deleteListing($conn, intval($_GET['id']), $vendorId);
            echo json_encode(['success' => $ok]);
            exit;
        }
        deleteListing($conn, intval($_GET['id']), $vendorId);
        header('Location: index.php?page=vendor&msg=listing_deleted');
        exit;
    }

    if ($action === 'respond_request' && isset($_GET['id']) && isset($_GET['status'])) {
        csrf_check();
        $status = $_GET['status'] === 'approved' ? 'approved' : 'rejected';
        $request = respondToRequest($conn, intval($_GET['id']), $vendorId, $status);

        if ($request) {
            $title = 'Special Request ' . ucfirst($status);
            $message = $vendor['company_name'] . ' has ' . $status . ' your "' . $request['request_type'] . '" request.';
            notifyUser($conn, $request['user_id'], $title, $message);
        }

        header('Location: index.php?page=vendor&action=requests&msg=' . ($request ? 'request_updated' : 'error'));
        exit;
    }

    if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $company = trim($_POST['company_name'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($company === '' || $address === '') {
            $error = 'Please provide a company name and address.';
        } else {
            updateVendorProfile($conn, $vendorId, $company, $address);
            header('Location: index.php?page=vendor&msg=profile_updated');
            exit;
        }
    }

    if ($action === 'requests') {
        $requests = getRequestsByVendor($conn, $vendorId);
        require 'app/views/vendor/requests.php';
        return;
    }

    $editing = null;
    if ($action === 'edit' && isset($_GET['id'])) {
        $listing = getListingById($conn, intval($_GET['id']));
        if ($listing && $listing['vendor_id'] == $vendorId) {
            $editing = $listing;
        }
    }

    $vendor = getVendorByUserId($conn, $userId);
    $listings = getListingsByVendor($conn, $vendorId);
    $bookingSummary = getVendorBookingSummary($conn, $vendorId);
    $pendingRequestCount = 0;
    foreach (getRequestsByVendor($conn, $vendorId) as $r) {
        if ($r['status'] === 'pending') $pendingRequestCount++;
    }

    require 'app/views/vendor/dashboard.php';
}
?>
