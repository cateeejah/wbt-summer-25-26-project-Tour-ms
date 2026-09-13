<?php
//Get a vendor's row by their user_id
function getVendorByUserId($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM vendors WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

//Update vendor's own company info
function updateVendorProfile($conn, $vendorId, $companyName, $address) {
    $stmt = mysqli_prepare($conn, "UPDATE vendors SET company_name = ?, address = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $companyName, $address, $vendorId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

//LISTINGS CRUD
function getListingsByVendor($conn, $vendorId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM listings WHERE vendor_id = ? ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, 'i', $vendorId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

//Live search over this vendor's own listings only — scoped by vendor_id so
//a vendor can never search or see another vendor's inventory.
function searchListingsByVendor($conn, $vendorId, $term) {
    $like = '%' . $term . '%';
    $stmt = mysqli_prepare($conn, "SELECT * FROM listings WHERE vendor_id = ? AND title LIKE ? ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, 'is', $vendorId, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function getListingById($conn, $listingId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM listings WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $listingId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

function createListing($conn, $vendorId, $title, $category, $price) {
    $stmt = mysqli_prepare($conn, "INSERT INTO listings (vendor_id, title, category, price) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'issd', $vendorId, $title, $category, $price);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function updateListing($conn, $listingId, $vendorId, $title, $category, $price) {
    $stmt = mysqli_prepare($conn, "UPDATE listings SET title = ?, category = ?, price = ? WHERE id = ? AND vendor_id = ?");
    mysqli_stmt_bind_param($stmt, 'ssdii', $title, $category, $price, $listingId, $vendorId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function updateListingStatus($conn, $listingId, $vendorId, $status) {
    $stmt = mysqli_prepare($conn, "UPDATE listings SET availability_status = ? WHERE id = ? AND vendor_id = ?");
    mysqli_stmt_bind_param($stmt, 'sii', $status, $listingId, $vendorId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function deleteListing($conn, $listingId, $vendorId) {
    $stmt = mysqli_prepare($conn, "DELETE FROM listings WHERE id = ? AND vendor_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $listingId, $vendorId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

//SPECIAL REQUESTS directed at this vendor
function getRequestsByVendor($conn, $vendorId) {
    $stmt = mysqli_prepare($conn, "SELECT sr.*, u.name AS user_name, u.phone AS user_phone
                                    FROM special_requests sr
                                    JOIN users u ON sr.user_id = u.id
                                    WHERE sr.vendor_id = ?
                                    ORDER BY sr.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'i', $vendorId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

//Vendor approves/rejects only their own requests (vendor_id must match).
//Returns the request row (including user_id + request_type) on success so
//the caller can notify the requesting user, or false if it wasn't found/theirs.
function respondToRequest($conn, $requestId, $vendorId, $status) {
    $stmt = mysqli_prepare($conn, "SELECT user_id, request_type FROM special_requests WHERE id = ? AND vendor_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $requestId, $vendorId);
    mysqli_stmt_execute($stmt);
    $request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$request) {
        return false;
    }

    $stmt = mysqli_prepare($conn, "UPDATE special_requests SET status = ? WHERE id = ? AND vendor_id = ?");
    mysqli_stmt_bind_param($stmt, 'sii', $status, $requestId, $vendorId);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    return $affected > 0 ? $request : false;
}

//Booking counts per listing (for the vendor's own dashboard summary)
function getVendorBookingSummary($conn, $vendorId) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count, COALESCE(SUM(b.total_amount),0) as revenue
                                    FROM bookings b
                                    JOIN listings l ON b.listing_id = l.id
                                    WHERE l.vendor_id = ? AND b.status = 'confirmed'");
    mysqli_stmt_bind_param($stmt, 'i', $vendorId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}
?>
