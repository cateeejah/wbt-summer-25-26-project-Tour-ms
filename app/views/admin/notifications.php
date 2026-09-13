<?php
$user = $_SESSION['user'];
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
                <h1 class="page-title">Broadcast Notifications</h1>
                <p class="page-sub">Send announcements to all users or a specific role</p>
            </div>
            <a href="index.php?page=admin" class="btn btn-ghost">&larr; Back to Dashboard</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <?php
            $messages = [
                'notification_sent' => 'Notification broadcast successfully',
                'notification_deleted' => 'Notification removed'
            ];
            $msg = $messages[$_GET['msg']] ?? null;
        ?>
        <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php endif; ?>

    <div class="card card-margin-bottom">
        <h3 class="card-title">New Notification</h3>
        <form method="POST" action="index.php?page=admin&action=notifications" class="form form-aligned" onsubmit="return validateForm(this)">
            <?php csrf_field(); ?>
            <input type="hidden" name="add_notification" value="1">
            <div class="field">
                <label>Title</label>
                <input type="text" name="title" required data-label="Title" placeholder="e.g. Scheduled maintenance">
            </div>
            <div class="field">
                <label>Message</label>
                <input type="text" name="message" required data-label="Message" placeholder="Notification details">
            </div>
            <div class="field">
                <label>Audience</label>
                <select name="target_role">
                    <option value="all">Everyone</option>
                    <option value="user">Users</option>
                    <option value="guide">Guides</option>
                    <option value="vendor">Vendors</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Send Notification</button>
        </form>
    </div>

    <div class="card">
        <div class="card-toolbar">
            <h3 class="card-title">Sent Notifications</h3>
            <span class="badge"><?= count($notifications) ?> total</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Audience</th>
                        <th>Sent</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                        <tr><td colspan="5" class="empty">No notifications sent yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <tr>
                                <td><?= htmlspecialchars($n['title']) ?></td>
                                <td><?= htmlspecialchars($n['message']) ?></td>
                                <td><?= ucfirst($n['target_role']) ?></td>
                                <td><?= htmlspecialchars($n['created_at']) ?></td>
                                <td class="text-right">
                                    <a class="btn-sm btn-delete" href="<?= htmlspecialchars(csrf_url('index.php?page=admin&action=notifications&delete=1&id=' . $n['id'])) ?>"
                                       onclick="return confirm('Delete this notification?')">Delete</a>
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
