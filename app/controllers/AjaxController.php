<?php
// ================================================================
// AJAX / JSON endpoints. Dashboards call these with fetch() and
// redraw a table without reloading the page. Every action checks
// the caller's role before touching any data.
// ================================================================

function ajaxCtrl($conn) {
    if (!isset($_SESSION['user'])) {
        json_out(['error' => 'Please sign in first.'], 401);
    }

    $action = $_GET['action'] ?? '';
    $term   = trim($_GET['q'] ?? '');
    $role   = $_SESSION['user']['role'];
    $userId = $_SESSION['user']['id'];

    switch ($action) {

        //----------- Admin: search the user list -----------
        case 'search_users':
            if ($role !== 'admin') break;
            json_out($term === '' ? getAllUsers($conn) : searchUsers($conn, $term));

        //----------- Vendor: search own listings -----------
        case 'search_listings':
            if ($role !== 'vendor') break;
            $vendor = getVendorByUserId($conn, $userId);
            if (!$vendor) break;
            json_out($term === ''
                ? getListingsByVendor($conn, $vendor['id'])
                : searchListingsByVendor($conn, $vendor['id'], $term));

        //----------- User: search available listings (Explore page) -----------
        case 'search_available_listings':
            if ($role !== 'user') break;
            json_out($term === '' ? getAvailableListings($conn) : searchAvailableListings($conn, $term));
    }

    // Wrong role, or an action that doesn't exist.
    json_out(['error' => 'You are not allowed to use this endpoint.'], 403);
}
?>
