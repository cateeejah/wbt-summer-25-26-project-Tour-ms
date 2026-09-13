<?php
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ratings &mdash; <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="app-body">

<?php require 'app/views/layout/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="header-flex">
            <div>
                <h1 class="page-title">Ratings & Reviews</h1>
                <p class="page-sub">Oversight view of user reviews left for guides and hotel listings</p>
            </div>
            <a href="index.php?page=admin" class="btn btn-ghost">&larr; Back to Dashboard</a>
        </div>
    </div>

    <div class="card">
        <div class="card-toolbar">
            <h3 class="card-title">All Ratings</h3>
            <span class="badge"><?= count($ratings) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reviewer</th>
                        <th>Target Type</th>
                        <th>Target</th>
                        <th>Rating</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ratings)): ?>
                        <tr><td colspan="5" class="empty">No ratings submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ratings as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['rater_name']) ?></td>
                                <td><?= $r['target_type'] === 'guide' ? 'Guide' : 'Hotel Listing' ?></td>
                                <td><?= htmlspecialchars($r['target_name'] ?? 'Unknown') ?></td>
                                <td>
                                    <span class="cost-badge <?= $r['rating'] >= 4 ? 'low' : ($r['rating'] <= 2 ? 'high' : 'medium') ?>">
                                        <?= $r['rating'] ?> / 5
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($r['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>
</html>
