<?php 
$user = $_SESSION['user']; 
 
$statusMessages = [ 
    'claimed' => ['success', 'Tour claimed successfully!'], 
    'claim_failed' => ['error', 'Could not claim that tour — it may have just been taken.'], 
    'completed' => ['success', 'Tour marked as completed.'], 
    'canceled' => ['success', 'Tour returned to the pending pool.'], 
    'status_updated' => ['success', 'Availability updated.'], 
    'status_blocked' => ['error', "You can't change availability while you have active assigned tours."], 
    'profile_updated' => ['success', 'Profile updated.'], 
    'tour_created' => ['success', 'Tour offering created — it will now show up for users to book.'], 
    'tour_updated' => ['success', 'Tour offering updated.'], 
    'tour_deleted' => ['success', 'Tour offering removed.'], 
    'error' => ['error', 'Something went wrong.'] 
];




$flash = isset($_GET['msg']) ? ($statusMessages[$_GET['msg']] ?? null) : null; 
?>

<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Guide Panel &mdash; <?= APP_NAME ?></title> 
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/admin.css"> 
</head> 

<body class="app-body"> 
 
<?php require 'app/views/layout/navbar.php'; ?> 
 
<main class="main-content">

    <!-- ================= PAGE HEADER ================= -->

    <div class="page-header"> 
        <h1 class="page-title">Guide Panel</h1> 
        <p class="page-sub">
            Claim tour requests, manage your assignments, and update your availability.
        </p> 
    </div> 
 
    <?php if (!empty($error)): ?> 
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div> 
    <?php endif; ?> 

    <?php if ($flash): ?> 
        <div class="alert alert-<?= $flash[0] ?>">
            <?= htmlspecialchars($flash[1]) ?>
        </div> 
    <?php endif; ?> 


    <!-- ===================================================== -->
    <!-- PROFILE / AVAILABILITY CARD -->
    <!-- ===================================================== -->

    <div class="admin-stack"> 

        <div class="card stat-card"> 

            <h3 class="card-title">Your Profile</h3> 

            <p>
                <strong>Location:</strong>
                <?= htmlspecialchars($guide['location']) ?>
            </p> 

            <p>
                <strong>Daily Rate:</strong>
                $<?= number_format($guide['daily_rate'], 2) ?>
            </p> 

            <p> 
                <strong>Status:</strong> 

                <span class="cost-badge <?= 
                    $guide['status'] === 'available' 
                        ? 'low' 
                        : ($guide['status'] === 'assigned' ? 'medium' : 'high') 
                ?>"> 
                    <?= ucfirst($guide['status']) ?> 
                </span> 
            </p> 

            <p class="muted"> 
                <?= (int)$ratingSummary['count'] ?> rating(s), average 
                <?= number_format($ratingSummary['avg_rating'], 1) ?> / 5
            </p> 
 
            <?php if ($guide['status'] !== 'assigned'): ?> 

                <?php if ($guide['status'] === 'available'): ?> 

                    <a href="<?= htmlspecialchars(
                        csrf_url(
                            'index.php?page=guide&action=toggle_status&status=offline'
                        )
                    ) ?>" 
                       class="btn btn-ghost btn-full">
                        Go Offline
                    </a> 

                <?php else: ?> 

                    <a href="<?= htmlspecialchars(
                        csrf_url(
                            'index.php?page=guide&action=toggle_status&status=available'
                        )
                    ) ?>" 
                       class="btn btn-primary btn-full">
                        Go Available
                    </a> 

                <?php endif; ?> 

            <?php else: ?> 

                <p class="muted">
                    Availability is locked while you have an active tour.
                </p> 

            <?php endif; ?> 
 
            <details style="margin-top:1rem;"> 

                <summary style="cursor:pointer;">
                    Edit location / rate
                </summary> 

                <form method="POST" 
                      action="index.php?page=guide&action=update_profile" 
                      class="form form-aligned" 
                      style="margin-top:0.75rem;" 
                      onsubmit="return validateForm(this)"> 

                    <?php csrf_field(); ?> 

                    <div class="field"> 
                        <label>Location</label> 

                        <input type="text" 
                               name="location" 
                               value="<?= htmlspecialchars($guide['location']) ?>" 
                               data-label="Location" 
                               required> 
                    </div> 

                    <div class="field"> 
                        <label>Daily Rate (BDT)</label> 

                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               name="daily_rate" 
                               value="<?= htmlspecialchars($guide['daily_rate']) ?>" 
                               data-label="Daily rate" 
                               required> 
                    </div> 

                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button> 

                </form> 

            </details> 

        </div> 

    </div>


    <!-- ===================================================== -->
    <!-- FEATURE 1: PAYMENT SUMMARY -->
    <!-- ===================================================== -->

    <div class="card card-margin-bottom" style="margin-top:1.5rem;">

        <div class="card-toolbar">

            <h3 class="card-title">
                Payment Summary
            </h3>

            <span class="badge">
                Payments
            </span>

        </div>

        <div class="field-row">

            <div class="stat-card">

                <p class="muted">
                    Total Earnings
                </p>

                <h2>
                    $<?= number_format(
                        $paymentSummary['total_earnings'] ?? 0,
                        2
                    ) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p class="muted">
                    Paid Tours
                </p>

                <h2>
                    <?= (int)($paymentSummary['paid_tours'] ?? 0) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p class="muted">
                    Pending Payments
                </p>

                <h2>
                    <?= (int)($paymentSummary['pending_payments'] ?? 0) ?>
                </h2>

            </div>

        </div>


        <div style="margin-top:1rem;">

            <a href="index.php?page=payment&action=history"
               class="btn btn-primary">

                View Payment History

            </a>

        </div>

    </div>


    <!-- ===================================================== -->
    <!-- FEATURE 2: RATINGS & REVIEWS -->
    <!-- ===================================================== -->

    <div class="card card-margin-bottom" style="margin-top:1.5rem;">

        <div class="card-toolbar">

            <h3 class="card-title">
                Ratings & Reviews
            </h3>

            <span class="cost-badge low">

                <?= number_format(
                    $ratingSummary['avg_rating'] ?? 0,
                    1
                ) ?> / 5

            </span>

        </div>


        <p class="muted">

            <?= (int)($ratingSummary['count'] ?? 0) ?>

            review(s) from travelers

        </p>


        <?php if (!empty($guideReviews)): ?>

            <?php foreach ($guideReviews as $review): ?>

                <div class="card"
                     style="
                        margin-top:1rem;
                        padding:1rem;
                     ">

                    <div style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                    ">

                        <strong>

                            <?= htmlspecialchars(
                                $review['traveler_name']
                            ) ?>

                        </strong>


                        <span class="cost-badge low">

                            <?= (int)$review['rating'] ?> / 5

                        </span>

                    </div>


                    <p style="margin-top:0.5rem;">

                        <?= htmlspecialchars(
                            $review['comment']
                        ) ?>

                    </p>


                    <small class="muted">

                        <?= htmlspecialchars(
                            $review['created_at']
                        ) ?>

                    </small>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p class="empty">
                No reviews yet.
            </p>

        <?php endif; ?>


        <div style="margin-top:1rem;">

            <a href="index.php?page=review&action=guide_reviews"
               class="btn btn-ghost">

                View All Reviews

            </a>

        </div>

    </div>


    <!-- ===================================================== -->
    <!-- FEATURE 3: NOTIFICATIONS -->
    <!-- ===================================================== -->

    <div class="card card-margin-bottom" style="margin-top:1.5rem;">

        <div class="card-toolbar">

            <h3 class="card-title">
                Notifications
            </h3>


            <?php if (!empty($unreadNotifications)): ?>

                <span class="badge">

                    <?= count($unreadNotifications) ?> New

                </span>

            <?php endif; ?>

        </div>


        <?php if (!empty($unreadNotifications)): ?>

            <?php foreach (
                array_slice($unreadNotifications, 0, 5)
                as $notification
            ): ?>

                <div style="
                    padding:0.75rem 0;
                    border-bottom:1px solid #eee;
                ">

                    <strong>

                        <?= htmlspecialchars(
                            $notification['title']
                        ) ?>

                    </strong>


                    <p style="margin:0.25rem 0;">

                        <?= htmlspecialchars(
                            $notification['message']
                        ) ?>

                    </p>


                    <small class="muted">

                        <?= htmlspecialchars(
                            $notification['created_at']
                        ) ?>

                    </small>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p class="empty">
                You have no new notifications.
            </p>

        <?php endif; ?>


        <div style="margin-top:1rem;">

            <a href="index.php?page=notifications"
               class="btn btn-primary">

                View All Notifications

            </a>

        </div>

    </div>


    <!-- ===================================================== -->
    <!-- CREATE / EDIT TOUR OFFERING -->
    <!-- ===================================================== -->

    <div class="card card-margin-bottom" style="margin-top:1.5rem;"> 

        <h3 class="card-title">
            <?= $editingTour
                ? 'Edit Tour Offering'
                : 'Create a Tour Offering'
            ?>
        </h3> 

        <p class="muted">
            List your own guided tour for users to browse and book directly,
            like a hotel or vehicle listing.
        </p> 

        <form method="POST" 
              action="index.php?page=guide&action=<?= 
                  $editingTour ? 'edit_tour' : 'create_tour'
              ?>" 
              class="form form-aligned" 
              onsubmit="return validateForm(this) && validateDateRange(this, 'start_date', 'end_date')"> 

            <?php csrf_field(); ?> 

            <?php if ($editingTour): ?>

                <input type="hidden"
                       name="tour_id"
                       value="<?= $editingTour['id'] ?>">

            <?php endif; ?> 

            <div class="field-row"> 

                <div class="field"> 

                    <label>Tour Title</label> 

                    <input type="text"
                           name="title"
                           required
                           data-label="Tour title"
                           placeholder="e.g. Cox's Bazar Sunset Walk"
                           value="<?= htmlspecialchars(
                               $editingTour['title'] ?? ''
                           ) ?>">

                </div> 


                <div class="field"> 

                    <label>Start Date</label> 

                    <input type="date"
                           name="start_date"
                           required
                           min="<?= date('Y-m-d') ?>"
                           data-label="Start date"
                           value="<?= htmlspecialchars(
                               $editingTour['start_date'] ?? ''
                           ) ?>">

                </div> 


                <div class="field"> 

                    <label>End Date</label> 

                    <input type="date"
                           name="end_date"
                           required
                           min="<?= date('Y-m-d') ?>"
                           data-label="End date"
                           value="<?= htmlspecialchars(
                               $editingTour['end_date'] ?? ''
                           ) ?>">

                </div> 


                <div class="field"> 

                    <label>Price (USD)</label> 

                    <input type="number"
                           step="0.01"
                           min="0"
                           name="price"
                           required
                           data-label="Price"
                           placeholder="e.g. 150.00"
                           value="<?= htmlspecialchars(
                               $editingTour['price'] ?? ''
                           ) ?>">

                </div> 

            </div> 

            <button type="submit" class="btn btn-primary">

                <?= $editingTour
                    ? 'Save Changes'
                    : 'Create Offering'
                ?>

            </button> 

            <?php if ($editingTour): ?>

                <a href="index.php?page=guide"
                   class="btn btn-ghost">

                    Cancel

                </a>

            <?php endif; ?> 

        </form> 

    </div>


    <!-- ===================================================== -->
    <!-- PENDING TOURS TO CLAIM -->
    <!-- ===================================================== -->

    <div class="card card-margin-bottom" style="margin-top:1.5rem;"> 

        <div class="card-toolbar">

            <h3 class="card-title">
                Available Tour Requests
            </h3>

            <span class="badge">
                <?= count($pendingTours) ?> pending
            </span>

        </div> 

        <div class="table-wrap"> 

            <table class="data-table"> 

                <thead> 

                    <tr>

                        <th>Tour</th>
                        <th>Traveler</th>
                        <th>Dates</th>
                        <th>Est. Price</th>
                        <th class="text-right">Actions</th>

                    </tr>

                </thead> 

                <tbody> 

                    <?php if (empty($pendingTours)): ?>

                        <tr>
                            <td colspan="5" class="empty">
                                No pending tour requests right now.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($pendingTours as $t):

                            $start = new DateTime($t['start_date']);
                            $end = new DateTime($t['end_date']);

                            $days = max(
                                1,
                                $start->diff($end)->days + 1
                            );

                            $estPrice =
                                $days * $guide['daily_rate'];

                        ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $t['title']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $t['traveler_name']
                                    ) ?>
                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $t['start_date']
                                    ) ?>

                                    &rarr;

                                    <?= htmlspecialchars(
                                        $t['end_date']
                                    ) ?>

                                    (<?= $days ?>d)

                                </td>

                                <td>
                                    $<?= number_format(
                                        $estPrice,
                                        2
                                    ) ?>
                                </td>

                                <td class="text-right">

                                    <?php if (
                                        $guide['status'] === 'available'
                                    ): ?>

                                        <form method="POST"
                                              action="index.php?page=guide&action=claim"
                                              style="display:inline;">

                                            <?php csrf_field(); ?>

                                            <input type="hidden"
                                                   name="tour_id"
                                                   value="<?= $t['id'] ?>">

                                            <button type="submit"
                                                    class="btn-sm btn-edit">

                                                Claim

                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <span class="muted">
                                            Unavailable
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- ===================================================== -->
    <!-- MY TOURS -->
    <!-- ===================================================== -->

    <div class="card"> 

        <div class="card-toolbar">

            <h3 class="card-title">
                Your Tours
            </h3>

            <span class="badge">
                <?= count($myTours) ?> total
            </span>

        </div> 

        <div class="table-wrap"> 

            <table class="data-table"> 

                <thead> 

                    <tr>

                        <th>Tour</th>
                        <th>Traveler</th>
                        <th>Dates</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>

                    </tr>

                </thead> 

                <tbody> 

                    <?php if (empty($myTours)): ?>

                        <tr>

                            <td colspan="6" class="empty">

                                You haven't created or claimed
                                any tours yet.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($myTours as $t): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $t['title']
                                    ) ?>
                                </td>

                                <td>

                                    <?php if ($t['traveler_name']): ?>

                                        <?= htmlspecialchars(
                                            $t['traveler_name']
                                        ) ?>

                                        <?php if (
                                            !empty($t['traveler_phone'])
                                        ): ?>

                                            <br>

                                            <span class="muted">

                                                <?= htmlspecialchars(
                                                    $t['traveler_phone']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        <span class="muted">
                                            Not yet booked
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $t['start_date']
                                    ) ?>

                                    &rarr;

                                    <?= htmlspecialchars(
                                        $t['end_date']
                                    ) ?>

                                </td>

                                <td>

                                    $<?= number_format(
                                        $t['price'],
                                        2
                                    ) ?>

                                </td>

                                <td>

                                    <span class="cost-badge <?= 
                                        $t['status'] === 'completed'
                                            ? 'low'
                                            : (
                                                $t['status'] === 'assigned'
                                                    ? 'medium'
                                                    : 'high'
                                            )
                                    ?>">

                                        <?= ucfirst(
                                            $t['status']
                                        ) ?>

                                    </span>

                                </td>

                                <td class="text-right">

                                    <?php if (
                                        $t['status'] === 'assigned'
                                    ): ?>

                                        <a class="btn-sm btn-edit"
                                           href="<?= htmlspecialchars(
                                               csrf_url(
                                                   'index.php?page=guide&action=complete&id='
                                                   . $t['id']
                                               )
                                           ) ?>">

                                            Mark Completed

                                        </a>

                                        <a class="btn-sm btn-delete"
                                           href="<?= htmlspecialchars(
                                               csrf_url(
                                                   'index.php?page=guide&action=cancel&id='
                                                   . $t['id']
                                               )
                                           ) ?>"
                                           onclick="return confirm('Cancel this tour? It will go back to the pending pool.')">

                                            Cancel

                                        </a>

                                    <?php elseif (
                                        $t['status'] === 'pending'
                                        && !$t['traveler_name']
                                    ): ?>

                                        <a class="btn-sm btn-edit"
                                           href="index.php?page=guide&action=edit&id=<?= $t['id'] ?>">

                                            Edit

                                        </a>

                                        <a class="btn-sm btn-delete"
                                           href="<?= htmlspecialchars(
                                               csrf_url(
                                                   'index.php?page=guide&action=delete_tour&id='
                                                   . $t['id']
                                               )
                                           ) ?>"
                                           onclick="return confirm('Remove this tour offering?')">

                                            Delete

                                        </a>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main> 
 
<script src="assets/js/validation.js"></script> 

</body> 
</html>
