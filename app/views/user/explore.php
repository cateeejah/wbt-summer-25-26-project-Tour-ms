<?php
$user = $_SESSION['user'];

$statusMessages = [
    'tour_requested' => ['success', 'Your tour request has been submitted. A guide will claim it soon.'],
    'tour_canceled' => ['success', 'Tour request canceled.'],
    'guide_tour_booked' => ['success', 'Tour booked! Check My Tours for details.'],
    'rated' => ['success', 'Thanks for your rating!'],
    'already_rated' => ['error', "You've already rated this, or it isn't eligible yet."],
    'error' => ['error', 'Something went wrong, or that tour was just booked by someone else.']
];
$flash = isset($_GET['msg']) ? ($statusMessages[$_GET['msg']] ?? null) : null;
$activeCategory = $_GET['category'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore &mdash; <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="app-body">

<?php require 'app/views/layout/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="header-flex">
            <div>
                <h1 class="page-title">Explore</h1>
                <p class="page-sub">Request a custom guided tour, book a guide's tour offering, or browse hotels and vehicles.</p>
            </div>
            <div>
                <a href="index.php?page=user&action=my_tours" class="btn btn-ghost">My Tours</a>
                <a href="index.php?page=user&action=bookings" class="btn btn-ghost">My Bookings</a>
                <a href="index.php?page=user&action=requests" class="btn btn-ghost">Special Requests</a>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash[0] ?>"><?= $flash[1] ?></div>
    <?php endif; ?>

    <!-- REQUEST A TOUR -->
    <div class="card card-margin-bottom">
        <h3 class="card-title">Request a Guided Tour</h3>
        <p class="muted">Tell us where and when — available guides will be able to claim your request.</p>
        <form method="POST" action="index.php?page=user&action=request_tour" class="form form-aligned"
              onsubmit="return validateForm(this) && validateDateRange(this, 'start_date', 'end_date')">
            <?php csrf_field(); ?>
            <div class="field-row">
                <div class="field">
                    <label>Tour Title</label>
                    <input type="text" name="title" required data-label="Tour title" placeholder="e.g. Sundarbans 3-Day Trip">
                </div>
                <div class="field">
                    <label>Start Date</label>
                    <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>" data-label="Start date">
                </div>
                <div class="field">
                    <label>End Date</label>
                    <input type="date" name="end_date" required min="<?= date('Y-m-d') ?>" data-label="End date">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit Request</button>
        </form>
    </div>

    <!-- BROWSE LISTINGS & GUIDED TOURS -->
    <div class="card">
        <div class="card-toolbar">
            <h3 class="card-title">Available Listings & Tours</h3>
            <div>
                <a href="index.php?page=user" class="btn-sm <?= $activeCategory === '' ? 'btn-edit' : 'btn-ghost' ?>">All</a>
                <a href="index.php?page=user&category=hotels" class="btn-sm <?= $activeCategory === 'hotels' ? 'btn-edit' : 'btn-ghost' ?>">Hotels</a>
                <a href="index.php?page=user&category=vehicle" class="btn-sm <?= $activeCategory === 'vehicle' ? 'btn-edit' : 'btn-ghost' ?>">Vehicles</a>
                <a href="index.php?page=user&category=tours" class="btn-sm <?= $activeCategory === 'tours' ? 'btn-edit' : 'btn-ghost' ?>">Guided Tours</a>
            </div>
        </div>

        <?php if ($activeCategory !== 'tours'): ?>
            <div class="field">
                <input type="text" id="listingSearch" class="search-input" placeholder="Search by title or vendor name...">
            </div>
        <?php endif; ?>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Provided By</th>
                        <th>Price</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="listingTable">
                    <?php if (empty($listings) && empty($guideTours)): ?>
                        <tr><td colspan="5" class="empty">Nothing available right now.</td></tr>
                    <?php else: ?>
                        <?php foreach ($listings as $l): ?>
                            <tr>
                                <td><?= htmlspecialchars($l['title']) ?></td>
                                <td><?= $l['category'] === 'hotels' ? 'Hotel' : 'Vehicle' ?></td>
                                <td><?= htmlspecialchars($l['company_name']) ?></td>
                                <td>$<?= number_format($l['price'], 2) ?></td>
                                <td class="text-right">
                                    <button class="btn-sm btn-edit" onclick="openBookModal(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['title'])) ?>', <?= $l['price'] ?>)">Book</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($guideTours as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['title']) ?></td>
                                <td>Guided Tour</td>
                                <td><?= htmlspecialchars($t['guide_name']) ?> (<?= htmlspecialchars($t['guide_location']) ?>)</td>
                                <td>$<?= number_format($t['price'], 2) ?></td>
                                <td class="text-right">
                                    <form method="POST" action="index.php?page=user&action=book_guide_tour" style="display:inline;">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="tour_id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn-sm btn-edit"
                                                onclick="return confirm('Book this tour (<?= htmlspecialchars(addslashes($t['start_date'])) ?> to <?= htmlspecialchars(addslashes($t['end_date'])) ?>)?')">Book</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- BOOKING MODAL (simple inline form, no framework) -->
<div id="book-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:100;">
    <div class="card" style="max-width:420px; width:90%;">
        <h3 class="card-title">Confirm Booking</h3>
        <p id="book-modal-title" class="muted"></p>
        <form method="POST" action="index.php?page=user&action=book_listing" class="form form-aligned">
            <?php csrf_field(); ?>
            <input type="hidden" name="listing_id" id="book-listing-id">
            <div class="field">
                <label>Discount Code (optional)</label>
                <input type="text" name="discount_code" placeholder="e.g. SUMMER25">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Confirm Booking</button>
            <button type="button" class="btn btn-ghost btn-full" onclick="closeBookModal()">Cancel</button>
        </form>
    </div>
</div>

<script src="assets/js/search.js"></script>

<script>
function openBookModal(id, title, price) {
    document.getElementById('book-listing-id').value = id;
    document.getElementById('book-modal-title').textContent = title + ' — $' + price.toFixed(2);
    document.getElementById('book-modal').style.display = 'flex';
}
function closeBookModal() {
    document.getElementById('book-modal').style.display = 'none';
}

/* ---------- Live search over hotel/vehicle listings ---------- */
//Only wired up when the category filter isn't "tours" — guide tour
//offerings come from a different data source and keep using the
//normal tab-click filter above instead of live search.
const listingSearchBox = document.getElementById('listingSearch');
if (listingSearchBox) {
    function runListingSearch() {
        ajaxTable({
            url:     'index.php?page=ajax&action=search_available_listings&q=' +
                     encodeURIComponent(listingSearchBox.value.trim()),
            tbody:   'listingTable',
            columns: 5,
            word:    'listings',
            row: function (l) {
                return '<tr>' +
                    '<td>' + esc(l.title) + '</td>' +
                    '<td>' + (l.category === 'hotels' ? 'Hotel' : 'Vehicle') + '</td>' +
                    '<td>' + esc(l.company_name) + '</td>' +
                    '<td>$' + parseFloat(l.price).toFixed(2) + '</td>' +
                    '<td class="text-right">' +
                        '<button class="btn-sm btn-edit" onclick="openBookModal(' + l.id + ', \'' +
                        esc(l.title).replace(/'/g, "\\'") + '\', ' + l.price + ')">Book</button>' +
                    '</td>' +
                '</tr>';
            }
        });
    }
    liveSearch('listingSearch', runListingSearch);
}
</script>

<script src="assets/js/validation.js"></script>

</body>
</html>
