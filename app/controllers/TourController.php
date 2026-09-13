<?php
function userCtrl($conn) {
    $userId = $_SESSION['user']['id'];
    $action = $_GET['action'] ?? 'explore';
    $error = '';

    // new tour request creation
    if ($action === 'request_tour' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $title = trim($_POST['title'] ?? '');
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';

        if ($title === '' || $start === '' || $end === '') {
            $error = 'Please fill in all tour details.';
        } elseif (strtotime($end) < strtotime($start)) {
            $error = 'End date must be on or after the start date.';
        } elseif (strtotime($start) < strtotime(date('Y-m-d'))) {
            $error = 'Start date cannot be in the past.';
        } else {
            createTourRequest($conn, $userId, $title, $start, $end);
            header('Location: index.php?page=user&msg=tour_requested');
            exit;
        }
    }

    //Cancelation tour request
    if ($action === 'cancel_tour' && isset($_GET['id'])) {
        csrf_check();
        $ok = cancelOwnTour($conn, intval($_GET['id']), $userId);
        header('Location: index.php?page=user&msg=' . ($ok ? 'tour_canceled' : 'error'));
        exit;
    }

    // guide rating after completed tour
    if ($action === 'rate_guide' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $tourId = intval($_POST['tour_id'] ?? 0);
        $guideId = intval($_POST['guide_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);

        if ($rating < 1 || $rating > 5) {
            $error = 'Please choose a rating between 1 and 5.';
        } else {
            $ok = rateGuide($conn, $userId, $tourId, $guideId, $rating);
            header('Location: index.php?page=user&msg=' . ($ok ? 'rated' : 'already_rated'));
            exit;
        }
    }

    //Book a listing (hotel or vehicle)
    if ($action === 'book_listing' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $listingId = intval($_POST['listing_id'] ?? 0);
        $discountCode = trim($_POST['discount_code'] ?? '');
        $result = bookListing($conn, $userId, $listingId, $discountCode ?: null);

        header('Location: index.php?page=user&action=bookings&msg=' . ($result['success'] ? 'booked' : 'book_failed'));
        exit;
    }

    //Book a guide-created tour offering
    if ($action === 'book_guide_tour' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $tourId = intval($_POST['tour_id'] ?? 0);
        $result = bookGuideTour($conn, $tourId, $userId);

        header('Location: index.php?page=user&action=my_tours&msg=' . ($result['success'] ? 'guide_tour_booked' : 'error'));
        exit;
    }

    //Cancel a confirmed booking
    if ($action === 'cancel_booking' && isset($_GET['id'])) {
        csrf_check();
        $ok = cancelBooking($conn, intval($_GET['id']), $userId);
        header('Location: index.php?page=user&action=bookings&msg=' . ($ok ? 'booking_canceled' : 'error'));
        exit;
    }

    //Rate a listing after a confirmed booking
    if ($action === 'rate_listing' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $bookingId = intval($_POST['booking_id'] ?? 0);
        $listingId = intval($_POST['listing_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);

        if ($rating < 1 || $rating > 5) {
            $error = 'Please choose a rating between 1 and 5.';
        } else {
            $ok = rateListing($conn, $userId, $bookingId, $listingId, $rating);
            header('Location: index.php?page=user&action=bookings&msg=' . ($ok ? 'rated' : 'already_rated'));
            exit;
        }
    }

    //Submit a special request to a vendor
    if ($action === 'special_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $vendorId = intval($_POST['vendor_id'] ?? 0);
        $type = trim($_POST['request_type'] ?? '');
        $details = trim($_POST['details'] ?? '');

        if ($vendorId <= 0 || $type === '' || $details === '') {
            $error = 'Please complete all fields of the request.';
        } else {
            createSpecialRequest($conn, $userId, $vendorId, $type, $details);
            header('Location: index.php?page=user&action=requests&msg=request_sent');
            exit;
        }
    }

    //SUB-PAGES
    if ($action === 'my_tours') {
        $tours = getToursByUser($conn, $userId);
        require 'app/views/user/my_tours.php';
        return;
    }

    if ($action === 'bookings') {
        $bookings = getBookingsByUser($conn, $userId);
        require 'app/views/user/bookings.php';
        return;
    }

    if ($action === 'requests') {
        $vendors = getVendorsForRequestForm($conn);
        require 'app/views/user/special_request.php';
        return;
    }

    //DEFAULT: EXPLORE (browse listings, guide tour offerings + request a tour)
    $categoryFilter = $_GET['category'] ?? null;
    $listings = $categoryFilter === 'tours' ? [] : getAvailableListings($conn, $categoryFilter);
    $guideTours = ($categoryFilter === null || $categoryFilter === 'tours') ? getBookableGuideTours($conn) : [];
    require 'app/views/user/explore.php';
}
?>
