<?php
$user = $_SESSION['user'];

$statusMessages = [
    'request_updated' => 'Response recorded.',
    'error' => 'Something went wrong.'
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
                <p class="page-sub">Requests submitted by travelers directly to your business</p>
            </div>
            <a href="index.php?page=vendor" class="btn btn-ghost">&larr; Back to Dashboard</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?= $flash ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-toolbar">
            <h3 class="card-title">All Requests</h3>
            <span class="badge"><?= count($requests) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Traveler</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="5" class="empty">No special requests yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($r['user_name']) ?>
                                    <?php if (!empty($r['user_phone'])): ?>
                                        <br><span class="muted"><?= htmlspecialchars($r['user_phone']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['request_type']) ?></td>
                                <td><?= htmlspecialchars($r['details']) ?></td>
                                <td>
                                    <span class="cost-badge <?= $r['status'] === 'approved' ? 'low' : ($r['status'] === 'rejected' ? 'high' : 'medium') ?>">
                                        <?= ucfirst($r['status']) ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <a class="btn-sm btn-edit" href="<?= htmlspecialchars(csrf_url('index.php?page=vendor&action=respond_request&id=' . $r['id'] . '&status=approved')) ?>">Approve</a>
                                        <a class="btn-sm btn-delete" href="<?= htmlspecialchars(csrf_url('index.php?page=vendor&action=respond_request&id=' . $r['id'] . '&status=rejected')) ?>">Reject</a>
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
