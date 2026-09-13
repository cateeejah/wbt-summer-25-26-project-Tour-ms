<?php
function guideCtrl($conn) {
    $userId = $_SESSION['user']['id'];
    $guide = getGuideByUserId($conn, $userId);

    if (!$guide) {
        // Shouldn't happen in normal flow, but guard against orphaned guide-role users
        echo "<p style='font-family:sans-serif;padding:2rem;'>No guide profile found for this account. Please contact an administrator.</p>";
        return;
    }

    $guideId = $guide['id'];
    $action = $_GET['action'] ?? 'dashboard';
    $error = '';

    //Create a new tour offering (guide-authored, bookable by any user)
    if ($action === 'create_tour' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $title = trim($_POST['title'] ?? '');
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        $price = floatval($_POST['price'] ?? 0);

        if ($title === '' || $start === '' || $end === '' || $price <= 0) {
            $error = 'Please fill in all tour details with a valid price.';
        } elseif (strtotime($end) < strtotime($start)) {
            $error = 'End date must be on or after the start date.';
        } else {
            createGuideTour($conn, $guideId, $title, $start, $end, $price);
            header('Location: index.php?page=guide&msg=tour_created');
            exit;
        }
    }

    //Edit one of the guide's own unbooked offerings
    if ($action === 'edit_tour' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $tourId = intval($_POST['tour_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        $price = floatval($_POST['price'] ?? 0);

        if ($title === '' || $start === '' || $end === '' || $price <= 0) {
            $error = 'Please fill in all tour details with a valid price.';
        } elseif (strtotime($end) < strtotime($start)) {
            $error = 'End date must be on or after the start date.';
        } else {
            $ok = updateGuideTour($conn, $tourId, $guideId, $title, $start, $end, $price);
            header('Location: index.php?page=guide&msg=' . ($ok ? 'tour_updated' : 'error'));
            exit;
        }
    }

    //Remove one of the guide's own unbooked offerings
    if ($action === 'delete_tour' && isset($_GET['id'])) {
        csrf_check();
        $ok = deleteGuideTour($conn, intval($_GET['id']), $guideId);
        header('Location: index.php?page=guide&msg=' . ($ok ? 'tour_deleted' : 'error'));
        exit;
    }

    //Claim a pending tour
    if ($action === 'claim' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $tourId = intval($_POST['tour_id'] ?? 0);
        $result = claimTour($conn, $tourId, $guideId, $guide['daily_rate']);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode($result);
            exit;
        }
        header('Location: index.php?page=guide&msg=' . ($result['success'] ? 'claimed' : 'claim_failed'));
        exit;
    }

    //Mark an assigned tour completed
    if ($action === 'complete' && isset($_GET['id'])) {
        csrf_check();
        $ok = completeTour($conn, intval($_GET['id']), $guideId);
        header('Location: index.php?page=guide&msg=' . ($ok ? 'completed' : 'error'));
        exit;
    }

    //Cancel an assigned tour (returns it to the pending pool)
    if ($action === 'cancel' && isset($_GET['id'])) {
        csrf_check();
        $ok = cancelAssignedTour($conn, intval($_GET['id']), $guideId);
        header('Location: index.php?page=guide&msg=' . ($ok ? 'canceled' : 'error'));
        exit;
    }

    //Toggle own availability (available <-> offline). Can't self-set 'assigned' -
    //that's system-managed based on active tours.
    if ($action === 'toggle_status' && isset($_GET['status'])) {
        csrf_check();
        $newStatus = $_GET['status'] === 'offline' ? 'offline' : 'available';

        // Don't allow going "available" while they still have an active assigned tour
        $hasActiveTour = false;
        foreach (getToursByGuide($conn, $guideId) as $t) {
            if ($t['status'] === 'assigned') {
                $hasActiveTour = true;
                break;
            }
        }
        if (!$hasActiveTour) {
            updateGuideStatus($conn, $guideId, $newStatus);
        } else {
            $error = "You can't change availability while you have active assigned tours.";
        }
        header('Location: index.php?page=guide&msg=' . (empty($error) ? 'status_updated' : 'status_blocked'));
        exit;
    }

    //Update profile (location / daily rate)
    if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $location = trim($_POST['location'] ?? '');
        $rate = floatval($_POST['daily_rate'] ?? 0);

        if ($location === '' || $rate <= 0) {
            $error = 'Please provide a valid location and daily rate.';
        } else {
            updateGuideProfile($conn, $guideId, $location, $rate);
            header('Location: index.php?page=guide&msg=profile_updated');
            exit;
        }
    }

    //Editing a specific offering (loads it into the form)
    $editingTour = null;
    if ($action === 'edit' && isset($_GET['id'])) {
        $t = getTourById($conn, intval($_GET['id']));
        if ($t && $t['guide_id'] == $guideId && $t['user_id'] === null && $t['status'] === 'pending') {
            $editingTour = $t;
        }
    }

    $guide = getGuideByUserId($conn, $userId); // re-fetch in case profile/status just changed
    $pendingTours = getPendingTours($conn);
    $myTours = getToursByGuide($conn, $guideId);
    $ratingSummary = getGuideRatingSummary($conn, $guideId);

    require 'app/views/guide/dashboard.php';
}
?>
