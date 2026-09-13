<?php
//Get a guide's row by their user_id
function getGuideByUserId($conn, $userId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM guides WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

//Update guide's own availability status (available / offline). "assigned" is
//set automatically by the system when they claim a tour, not chosen directly.
function updateGuideStatus($conn, $guideId, $status) {
    $stmt = mysqli_prepare($conn, "UPDATE guides SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $status, $guideId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

//Update guide's location / daily rate
function updateGuideProfile($conn, $guideId, $location, $dailyRate) {
    $stmt = mysqli_prepare($conn, "UPDATE guides SET location = ?, daily_rate = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'sdi', $location, $dailyRate, $guideId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

//All user-submitted tour requests still waiting for a guide to claim them.
//Excludes guide-created tour offerings (those have no user_id yet and are
//booked by a user directly, not "claimed" by a guide).
function getPendingTours($conn) {
    $r = mysqli_query($conn, "SELECT tours.*, users.name AS traveler_name, users.phone AS traveler_phone
                               FROM tours
                               JOIN users ON tours.user_id = users.id
                               WHERE tours.status = 'pending' AND tours.user_id IS NOT NULL
                               ORDER BY tours.start_date ASC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

//A guide creates their own tour offering: no user yet, guide sets price
//directly, status starts 'pending' (meaning "available for a user to book").
function createGuideTour($conn, $guideId, $title, $startDate, $endDate, $price) {
    $stmt = mysqli_prepare($conn, "INSERT INTO tours (user_id, guide_id, title, start_date, end_date, price, status)
                                    VALUES (NULL, ?, ?, ?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($stmt, 'isssd', $guideId, $title, $startDate, $endDate, $price);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

//A guide edits one of their own offerings, only while it's still unbooked
function updateGuideTour($conn, $tourId, $guideId, $title, $startDate, $endDate, $price) {
    $stmt = mysqli_prepare($conn, "UPDATE tours SET title = ?, start_date = ?, end_date = ?, price = ?
                                    WHERE id = ? AND guide_id = ? AND user_id IS NULL AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, 'sssdii', $title, $startDate, $endDate, $price, $tourId, $guideId);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected > 0;
}

//A guide removes one of their own offerings, only while it's still unbooked
function deleteGuideTour($conn, $tourId, $guideId) {
    $stmt = mysqli_prepare($conn, "DELETE FROM tours WHERE id = ? AND guide_id = ? AND user_id IS NULL AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, 'ii', $tourId, $guideId);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected > 0;
}

//All tours belonging to a specific guide — both offerings they created and
//user requests they've claimed. LEFT JOIN because guide-created offerings
//with no booking yet have no user_id.
function getToursByGuide($conn, $guideId) {
    $stmt = mysqli_prepare($conn, "SELECT tours.*, users.name AS traveler_name, users.phone AS traveler_phone
                                    FROM tours
                                    LEFT JOIN users ON tours.user_id = users.id
                                    WHERE tours.guide_id = ?
                                    ORDER BY tours.start_date DESC");
    mysqli_stmt_bind_param($stmt, 'i', $guideId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function getTourById($conn, $tourId) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM tours WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $tourId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

//Guide claims a pending tour: assigns guide_id, computes price from
//daily_rate x number of days, flips tour status to 'assigned', and marks
//the guide as 'assigned' too so they're not shown as available elsewhere.
//Uses a transaction so the claim can't race with another guide claiming
//the same tour.
function claimTour($conn, $tourId, $guideId, $dailyRate) {
    mysqli_begin_transaction($conn);
    try {
        // Lock and re-check the tour is still pending
        $stmt = mysqli_prepare($conn, "SELECT start_date, end_date, status FROM tours WHERE id = ? FOR UPDATE");
        mysqli_stmt_bind_param($stmt, 'i', $tourId);
        mysqli_stmt_execute($stmt);
        $tour = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$tour || $tour['status'] !== 'pending') {
            mysqli_rollback($conn);
            return ['success' => false, 'message' => 'This tour is no longer available.'];
        }

        $start = new DateTime($tour['start_date']);
        $end = new DateTime($tour['end_date']);
        $days = max(1, $start->diff($end)->days + 1); // inclusive of both start and end day
        $price = round($dailyRate * $days, 2);

        $stmt = mysqli_prepare($conn, "UPDATE tours SET guide_id = ?, price = ?, status = 'assigned' WHERE id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($stmt, 'idi', $guideId, $price, $tourId);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected === 0) {
            mysqli_rollback($conn);
            return ['success' => false, 'message' => 'This tour was just claimed by another guide.'];
        }

        updateGuideStatus($conn, $guideId, 'assigned');
        mysqli_commit($conn);
        return ['success' => true, 'price' => $price, 'days' => $days];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => 'Something went wrong. Please try again.'];
    }
}

//Guide marks one of their assigned tours as completed
function completeTour($conn, $tourId, $guideId) {
    $stmt = mysqli_prepare($conn, "UPDATE tours SET status = 'completed' WHERE id = ? AND guide_id = ? AND status = 'assigned'");
    mysqli_stmt_bind_param($stmt, 'ii', $tourId, $guideId);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($affected > 0) {
        // Free the guide back up if they have no other active tours
        freeGuideIfNoActiveTours($conn, $guideId);
    }
    return $affected > 0;
}

//Guide cancels one of their assigned tours (kicks it back to pending, unassigned)
function cancelAssignedTour($conn, $tourId, $guideId) {
    $stmt = mysqli_prepare($conn, "UPDATE tours SET status = 'pending', guide_id = NULL, price = 0 WHERE id = ? AND guide_id = ? AND status = 'assigned'");
    mysqli_stmt_bind_param($stmt, 'ii', $tourId, $guideId);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($affected > 0) {
        freeGuideIfNoActiveTours($conn, $guideId);
    }
    return $affected > 0;
}

//Helper: if a guide has no more 'assigned' tours, flip their status back to available
function freeGuideIfNoActiveTours($conn, $guideId) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM tours WHERE guide_id = ? AND status = 'assigned'");
    mysqli_stmt_bind_param($stmt, 'i', $guideId);
    mysqli_stmt_execute($stmt);
    $count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    if ($count == 0) {
        updateGuideStatus($conn, $guideId, 'available');
    }
}

//Ratings for a guide (for their profile / dashboard summary)
function getGuideRatingSummary($conn, $guideId) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count, COALESCE(AVG(rating),0) as avg_rating
                                    FROM ratings WHERE target_type = 'guide' AND target_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $guideId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}
?>
