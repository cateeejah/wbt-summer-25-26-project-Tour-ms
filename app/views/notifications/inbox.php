<?php
$user = $_SESSION['user'];

$backLink = 'index.php?page=home';
if ($_SESSION['user']['role'] === 'admin') $backLink = 'index.php?page=admin';
elseif ($_SESSION['user']['role'] === 'guide') $backLink = 'index.php?page=guide';
elseif ($_SESSION['user']['role'] === 'vendor') $backLink = 'index.php?page=vendor';
elseif ($_SESSION['user']['role'] === 'user') $backLink = 'index.php?page=user';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications &mdash; <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="app-body">

<?php require 'app/views/layout/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="header-flex">
            <div>
                <h1 class="page-title">Notifications</h1>
                <p class="page-sub">Updates and announcements relevant to your account.</p>
            </div>
            <a href="<?= $backLink ?>" class="btn btn-ghost">&larr; Back</a>
        </div>
    </div>

    <div class="card">
        <div class="card-toolbar">
            <h3 class="card-title">Inbox</h3>
            <div>
                <span class="badge"><?= count($notifications) ?> total</span>
                <a href="<?= htmlspecialchars(csrf_url('index.php?page=notifications&mark_all_read=1')) ?>" class="btn-sm btn-edit">Mark all read</a>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
            <p class="empty" style="padding:1.5rem 0;">You have no notifications yet.</p>
        <?php else: ?>
            <div class="notification-list">
                <?php foreach ($notifications as $n): ?>
                    <div class="card" style="margin-top:0.75rem; <?= $n['is_read'] ? 'opacity:0.7;' : 'border-left:3px solid var(--primary);' ?>">
                        <div class="card-toolbar">
                            <strong><?= htmlspecialchars($n['title']) ?></strong>
                            <?php if (!$n['is_read']): ?>
                                <a href="<?= htmlspecialchars(csrf_url('index.php?page=notifications&mark_read=1&id=' . $n['id'])) ?>" class="btn-sm btn-ghost">Mark read</a>
                            <?php endif; ?>
                        </div>
                        <p><?= htmlspecialchars($n['message']) ?></p>
                        <p class="muted"><?= htmlspecialchars($n['created_at']) ?><?= $n['user_id'] ? '' : ' &middot; Broadcast' ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="assets/js/validation.js"></script>

</body>
</html>
