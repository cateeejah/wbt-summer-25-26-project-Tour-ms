<?php
$user = $_SESSION['user'];

$statusMessages = [
    'request_sent' => ['success', 'Your special request has been sent to the vendor.'],
    'error' => ['error', 'Something went wrong.']
];
$flash = isset($_GET['msg']) ? ($statusMessages[$_GET['msg']] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Requests &mdash; <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="app-body">

<?php require 'app/views/layout/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="header-flex">
            <div>
                <h1 class="page-title">Special Requests</h1>
                <p class="page-sub">Ask a vendor for something specific — early check-in, a custom itinerary add-on, etc.</p>
            </div>
            <a href="index.php?page=user" class="btn btn-ghost">&larr; Back to Explore</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash[0] ?>"><?= $flash[1] ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 class="card-title">New Request</h3>
        <form method="POST" action="index.php?page=user&action=special_request" class="form form-aligned" onsubmit="return validateForm(this)">
            <?php csrf_field(); ?>
            <div class="field">
                <label>Vendor</label>
                <select name="vendor_id" required data-label="Vendor">
                    <option value="">Select a vendor...</option>
                    <?php foreach ($vendors as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['company_name']) ?> (<?= ucfirst($v['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Request Type</label>
                <input type="text" name="request_type" required data-label="Request type" placeholder="e.g. Early check-in">
            </div>
            <div class="field">
                <label>Details</label>
                <input type="text" name="details" required data-label="Details" placeholder="Describe what you need">
            </div>
            <button type="submit" class="btn btn-primary">Send Request</button>
        </form>
    </div>
</main>

<script src="assets/js/validation.js"></script>

</body>
</html>
