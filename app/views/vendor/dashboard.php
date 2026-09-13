<?php
$user = $_SESSION['user'];

$statusMessages = [
    'listing_added' => 'Listing created successfully.',
    'listing_updated' => 'Listing updated.',
    'listing_deleted' => 'Listing removed.',
    'profile_updated' => 'Profile updated.',
    'error' => 'Something went wrong.'
];
$flash = isset($_GET['msg']) ? ($statusMessages[$_GET['msg']] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Panel &mdash; <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="app-body">

<?php require 'app/views/layout/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title">Vendor Panel</h1>
        <p class="page-sub">Manage your listings and view booking activity.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($flash): ?>
        <div class="alert alert-success"><?= $flash ?></div>
    <?php endif; ?>

    <div class="admin-stack">
        <!-- PROFILE CARD -->
        <div class="card stat-card">
            <h3 class="card-title">Company Profile</h3>
            <p><strong>Type:</strong> <?= ucfirst($vendor['type']) ?></p>
            <p><strong>Company:</strong> <?= htmlspecialchars($vendor['company_name']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($vendor['address']) ?></p>

            <details style="margin-top:1rem;">
                <summary style="cursor:pointer;">Edit company info</summary>
                <form method="POST" action="index.php?page=vendor&action=update_profile" class="form form-aligned" style="margin-top:0.75rem;" onsubmit="return validateForm(this)">
                    <?php csrf_field(); ?>
                    <div class="field">
                        <label>Company Name</label>
                        <input type="text" name="company_name" value="<?= htmlspecialchars($vendor['company_name']) ?>" data-label="Company name" required>
                    </div>
                    <div class="field">
                        <label>Address</label>
                        <input type="text" name="address" value="<?= htmlspecialchars($vendor['address']) ?>" data-label="Address" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </details>
        </div>

        <div class="card stat-card">
            <h3 class="card-title">Bookings & Revenue</h3>
            <div class="stat-value">$<?= number_format($bookingSummary['revenue'], 2) ?></div>
            <p class="muted"><?= (int)$bookingSummary['count'] ?> confirmed booking(s)</p>
        </div>

        <div class="card stat-card">
            <h3 class="card-title">Special Requests</h3>
            <div class="stat-value warning"><?= $pendingRequestCount ?></div>
            <p class="muted">Pending requests awaiting your response</p>
            <a href="index.php?page=vendor&action=requests" class="btn btn-primary btn-full">Review Requests</a>
        </div>
    </div>

    <!-- ADD / EDIT LISTING FORM -->
    <div class="card card-margin-bottom" style="margin-top:1.5rem;">
        <h3 class="card-title"><?= $editing ? 'Edit Listing' : 'Add New Listing' ?></h3>
        <form method="POST" action="index.php?page=vendor&action=<?= $editing ? 'edit_listing' : 'add_listing' ?>" class="form form-aligned" onsubmit="return validateForm(this)">
            <?php csrf_field(); ?>
            <?php if ($editing): ?>
                <input type="hidden" name="listing_id" value="<?= $editing['id'] ?>">
            <?php endif; ?>
            <div class="field-row">
                <div class="field">
                    <label>Title</label>
                    <input type="text" name="title" required data-label="Title" placeholder="e.g. Deluxe Sea View Room"
                           value="<?= htmlspecialchars($editing['title'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Category</label>
                    <select name="category">
                        <option value="hotels" <?= (isset($editing['category']) && $editing['category'] === 'hotels') ? 'selected' : '' ?>>Hotel</option>
                        <option value="vehicle" <?= (isset($editing['category']) && $editing['category'] === 'vehicle') ? 'selected' : '' ?>>Vehicle</option>
                    </select>
                </div>
                <div class="field">
                    <label>Price (BDT)</label>
                    <input type="number" step="0.01" min="0" name="price" required data-label="Price"
                           value="<?= htmlspecialchars($editing['price'] ?? '') ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Create Listing' ?></button>
            <?php if ($editing): ?>
                <a href="index.php?page=vendor" class="btn btn-ghost">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- LISTINGS TABLE -->
    <div class="card">
        <div class="card-toolbar">
            <h3 class="card-title">Your Listings</h3>
            <span class="badge" id="listingCount"><?= count($listings) ?> total</span>
        </div>
        <div class="field">
            <input type="text" id="listingSearch" class="search-input" placeholder="Search your listings by title...">
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="listingTable">
                    <?php if (empty($listings)): ?>
                        <tr><td colspan="5" class="empty">You haven't added any listings yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($listings as $l): ?>
                            <tr id="listing-row-<?= $l['id'] ?>">
                                <td><?= htmlspecialchars($l['title']) ?></td>
                                <td><?= $l['category'] === 'hotels' ? 'Hotel' : 'Vehicle' ?></td>
                                <td>$<?= number_format($l['price'], 2) ?></td>
                                <td>
                                    <select onchange="location.href='index.php?page=vendor&action=toggle_listing&id=<?= $l['id'] ?>&status='+this.value+'&csrf_token=<?= csrf_token() ?>'">
                                        <option value="available" <?= $l['availability_status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="booked" <?= $l['availability_status'] === 'booked' ? 'selected' : '' ?>>Booked</option>
                                        <option value="in_maintenance" <?= $l['availability_status'] === 'in_maintenance' ? 'selected' : '' ?>>In Maintenance</option>
                                    </select>
                                </td>
                                <td class="text-right">
                                    <a class="btn-sm btn-edit" href="index.php?page=vendor&action=edit&id=<?= $l['id'] ?>">Edit</a>
                                    <button class="btn-sm btn-delete" onclick="deleteListing(<?= $l['id'] ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="assets/js/search.js"></script>
<script src="assets/js/validation.js"></script>

<script>
const VENDOR_CSRF_TOKEN = '<?= csrf_token() ?>';

function deleteListing(id) {
    if (!confirm('Delete this listing? This cannot be undone.')) return;

    fetch('index.php?page=vendor&action=delete_listing&id=' + id + '&csrf_token=' + VENDOR_CSRF_TOKEN, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('listing-row-' + id).remove();
        } else {
            alert('Error deleting listing.');
        }
    });
}

/* ---------- Live search for vendor ---------- */
function runListingSearch() {
    ajaxTable({
        url:     'index.php?page=ajax&action=search_listings&q=' +
                 encodeURIComponent(document.getElementById('listingSearch').value.trim()),
        tbody:   'listingTable',
        counter: 'listingCount',
        columns: 5,
        word:    'total',
        row: function (l) {
            const statusSelect = '<select onchange="location.href=\'index.php?page=vendor&action=toggle_listing&id=' + l.id +
                '&status=\'+this.value+\'&csrf_token=' + VENDOR_CSRF_TOKEN + '\'">' +
                '<option value="available"' + (l.availability_status === 'available' ? ' selected' : '') + '>Available</option>' +
                '<option value="booked"' + (l.availability_status === 'booked' ? ' selected' : '') + '>Booked</option>' +
                '<option value="in_maintenance"' + (l.availability_status === 'in_maintenance' ? ' selected' : '') + '>In Maintenance</option>' +
                '</select>';

            return '<tr id="listing-row-' + l.id + '">' +
                '<td>' + esc(l.title) + '</td>' +
                '<td>' + (l.category === 'hotels' ? 'Hotel' : 'Vehicle') + '</td>' +
                '<td>$' + parseFloat(l.price).toFixed(2) + '</td>' +
                '<td>' + statusSelect + '</td>' +
                '<td class="text-right">' +
                    '<a class="btn-sm btn-edit" href="index.php?page=vendor&action=edit&id=' + l.id + '">Edit</a> ' +
                    '<button class="btn-sm btn-delete" onclick="deleteListing(' + l.id + ')">Delete</button>' +
                '</td>' +
            '</tr>';
        }
    });
}
liveSearch('listingSearch', runListingSearch);
</script>
</body>
</html>
