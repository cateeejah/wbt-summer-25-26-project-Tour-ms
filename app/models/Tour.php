<?php
function createTourRequest($conn, $userId, $title, $startDate, $endDate) {
    $stmt = mysqli_prepare($conn, "INSERT INTO tours (user_id, title, start_date, end_date, price, status) VALUES (?, ?, ?, ?, 0, 'pending')");
    mysqli_stmt_bind_param($stmt, 'isss', $userId, $title, $startDate, $endDate);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function getToursByUser($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT tours.*, g.location AS guide_location, u.name AS guide_name
                                    FROM tours
                                    LEFT JOIN guides g ON tours.guide_id = g.id
                                    LEFT JOIN users u ON g.user_id = u.id
                                    WHERE tours.user_id = ?
                                    ORDER BY tours.start_date DESC");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function cancelOwnTour($conn, $tourId, $userId) {
    $stmt = mysqli_prepare($conn, "UPDATE tours SET status = 'canceled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, 'ii', $tourId, $userId);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected > 0;
}

function getBookableGuideTours($conn) {
    $r = mysqli_query($conn, "SELECT tours.*, g.location AS guide_location, u.name AS guide_name
                               FROM tours
                               JOIN guides g ON tours.guide_id = g.id
                               JOIN users u ON g.user_id = u.id
                               WHERE tours.status = 'pending' AND tours.user_id IS NULL
                               ORDER BY tours.start_date ASC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function bookGuideTour($conn, $tourId, $userId) {
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "SELECT user_id, guide_id, status FROM tours WHERE id = ? FOR UPDATE");
        mysqli_stmt_bind_param($stmt, 'i', $tourId);
        mysqli_stmt_execute($stmt);
        $tour = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$tour || $tour['status'] !== 'pending' || $tour['user_id'] !== null) {
            mysqli_rollback($conn);
            return ['success' => false, 'message' => 'This tour is no longer available.'];
        }

        $stmt = mysqli_prepare($conn, "UPDATE tours SET user_id = ?, status = 'assigned' WHERE id = ? AND user_id IS NULL AND status = 'pending'");
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $tourId);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected === 0) {
            mysqli_rollback($conn);
            return ['success' => false, 'message' => 'This tour was just booked by someone else.'];
        }

        updateGuideStatus($conn, $tour['guide_id'], 'assigned');
        mysqli_commit($conn);
        return ['success' => true];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => 'Something went wrong. Please try again.'];
    }
}

function getAvailableListings($conn, $category = null) {
    if ($category && in_array($category, ['hotels', 'vehicle'])) {
        $stmt = mysqli_prepare($conn, "SELECT l.*, v.company_name, v.address, v.type
                                        FROM listings l
                                        JOIN vendors v ON l.vendor_id = v.id
                                        WHERE l.availability_status = 'available' AND l.category = ?
                                        ORDER BY l.price ASC");
        mysqli_stmt_bind_param($stmt, 's', $category);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }

    $r = mysqli_query($conn, "SELECT l.*, v.company_name, v.address, v.type
                               FROM listings l
                               JOIN vendors v ON l.vendor_id = v.id
                               WHERE l.availability_status = 'available'
                               ORDER BY l.price ASC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function searchAvailableListings($conn, $term) {
    $like = '%' . $term . '%';
    $stmt = mysqli_prepare($conn, "SELECT l.*, v.company_name, v.address, v.type
                                    FROM listings l
                                    JOIN vendors v ON l.vendor_id = v.id
                                    WHERE l.availability_status = 'available'
                                      AND (l.title LIKE ? OR v.company_name LIKE ?)
                                    ORDER BY l.price ASC");
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function getActiveDiscountByCode($conn, $code) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM discounts WHERE code = ? AND status = 'active' AND valid_till >= CURDATE()");
    mysqli_stmt_bind_param($stmt, 's', $code);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

function bookListing($conn, $userId, $listingId, $discountCode = null) {
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "SELECT price, availability_status FROM listings WHERE id = ? FOR UPDATE");
        mysqli_stmt_bind_param($stmt, 'i', $listingId);
        mysqli_stmt_execute($stmt);
        $listing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$listing || $listing['availability_status'] !== 'available') {
            mysqli_rollback($conn);
            return ['success' => false, 'message' => 'This listing is no longer available.'];
        }

        $total = $listing['price'];
        $discountId = null;

        if (!empty($discountCode)) {
            $discount = getActiveDiscountByCode($conn, $discountCode);
            if (!$discount) {
                mysqli_rollback($conn);
                return ['success' => false, 'message' => 'That discount code is invalid or expired.'];
            }
            $discountId = $discount['id'];
            $total = round($total * (1 - ($discount['discount_pct'] / 100)), 2);
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO bookings (user_id, listing_id, discount_id, total_amount, status) VALUES (?, ?, ?, ?, 'confirmed')");
        mysqli_stmt_bind_param($stmt, 'iiid', $userId, $listingId, $discountId, $total);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE listings SET availability_status = 'booked' WHERE id = ? AND availability_status = 'available'");
        mysqli_stmt_bind_param($stmt, 'i', $listingId);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected === 0) {
            mysqli_rollback($conn);
            return ['success' => false, 'message' => 'This listing was just booked by someone else.'];
        }

        // Record the user's payment for this booking
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, amount, type) VALUES (?, ?, 'user_payment')");
        mysqli_stmt_bind_param($stmt, 'id', $userId, $total);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        return ['success' => true, 'total' => $total];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => 'Something went wrong. Please try again.'];
    }
}

function getBookingsByUser($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT b.*, l.title, l.category, v.company_name
                                    FROM bookings b
                                    JOIN listings l ON b.listing_id = l.id
                                    JOIN vendors v ON l.vendor_id = v.id
                                    WHERE b.user_id = ?
                                    ORDER BY b.booking_date DESC");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function cancelBooking($conn, $bookingId, $userId) {
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "SELECT listing_id FROM bookings WHERE id = ? AND user_id = ? AND status = 'confirmed' FOR UPDATE");
        mysqli_stmt_bind_param($stmt, 'ii', $bookingId, $userId);
        mysqli_stmt_execute($stmt);
        $booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$booking) {
            mysqli_rollback($conn);
            return false;
        }

        $stmt = mysqli_prepare($conn, "UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $bookingId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE listings SET availability_status = 'available' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $booking['listing_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

function createSpecialRequest($conn, $userId, $vendorId, $requestType, $details) {
    $stmt = mysqli_prepare($conn, "INSERT INTO special_requests (user_id, vendor_id, request_type, details) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iiss', $userId, $vendorId, $requestType, $details);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function getVendorsForRequestForm($conn) {
    $r = mysqli_query($conn, "SELECT id, company_name, type FROM vendors ORDER BY company_name ASC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function hasRatedTourGuide($conn, $userId, $tourId) {
    $stmt = mysqli_prepare($conn, "SELECT tours.guide_id FROM tours WHERE tours.id = ? AND tours.user_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $tourId, $userId);
    mysqli_stmt_execute($stmt);
    $tour = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$tour || !$tour['guide_id']) return true; // nothing to rate, treat as blocked

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM tours WHERE user_id = ? AND guide_id = ? AND status = 'completed'");
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $tour['guide_id']);
    mysqli_stmt_execute($stmt);
    $completedCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM ratings WHERE user_id = ? AND target_type = 'guide' AND target_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $tour['guide_id']);
    mysqli_stmt_execute($stmt);
    $ratedCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    return $ratedCount >= $completedCount;
}

function rateGuide($conn, $userId, $tourId, $guideId, $rating) {
    if (hasRatedTourGuide($conn, $userId, $tourId)) {
        return false;
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO ratings (user_id, target_type, target_id, rating) VALUES (?, 'guide', ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iii', $userId, $guideId, $rating);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function hasRatedBooking($conn, $userId, $bookingId, $listingId) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM bookings WHERE id = ? AND user_id = ? AND listing_id = ? AND status = 'confirmed'");
    mysqli_stmt_bind_param($stmt, 'iii', $bookingId, $userId, $listingId);
    mysqli_stmt_execute($stmt);
    $validBooking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    if ($validBooking == 0) return true; // not a valid confirmed booking, block rating

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id = ? AND listing_id = ? AND status = 'confirmed'");
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $listingId);
    mysqli_stmt_execute($stmt);
    $bookingCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM ratings WHERE user_id = ? AND target_type = 'hotel' AND target_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $listingId);
    mysqli_stmt_execute($stmt);
    $ratedCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    return $ratedCount >= $bookingCount;
}

function rateListing($conn, $userId, $bookingId, $listingId, $rating) {
    if (hasRatedBooking($conn, $userId, $bookingId, $listingId)) {
        return false;
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO ratings (user_id, target_type, target_id, rating) VALUES (?, 'hotel', ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iii', $userId, $listingId, $rating);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
?>
