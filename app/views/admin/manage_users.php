<?php
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users &mdash; <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="app-body">

<?php require 'app/views/layout/navbar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="header-flex">
            <div>
                <h1 class="page-title">User Management</h1>
                <p class="page-sub">Verify new accounts and manage system access</p>
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
                'user_updated' => 'User account updated successfully',
                'user_deleted' => 'User account has been removed',
                'user_added' => 'New user account created successfully'
            ];
            $msg = $messages[$_GET['msg']] ?? null;
        ?>
        <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php endif; ?>

    <!-- user form -->
    <div class="card card-margin-bottom">
        <h3 class="card-title">Add New System User</h3>
        <form method="POST" action="index.php?page=admin&action=users" class="form form-aligned" onsubmit="return validateForm(this)">
            <?php csrf_field(); ?>
            <input type="hidden" name="add_user" value="1">
            <div class="field-row">
                <div class="field">
                    <label>Full Name</label>
                    <input type="text" name="name" required data-label="Full name" placeholder="User name">
                </div>
                <div class="field">
                    <label>Email Address</label>
                    <input type="email" name="email" required data-label="Email address" placeholder="example@email.com">
                </div>
                <div class="field">
                    <label>Phone</label>
                    <input type="text" name="phone" data-label="Phone" data-phone="1" placeholder="Optional">
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" required data-label="Password" data-min="8" placeholder="Minimum 8 characters">
                </div>
                <div class="field">
                    <label>System Role</label>
                    <select name="role" id="add-user-role">
                        <option value="user">General User</option>
                        <option value="guide">Tour Guide</option>
                        <option value="vendor">Vendor</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="field">
                    <label>Initial Status</label>
                    <select name="is_verified">
                        <option value="1">Verified (Active)</option>
                        <option value="0">Pending (Locked)</option>
                    </select>
                </div>
            </div>

            <div class="field-group" id="add-guide-fields" style="display:none;">
                <div class="field-row">
                    <div class="field">
                        <label>Guiding Location</label>
                        <input type="text" name="location" id="add-location" data-label="Guiding location" placeholder="e.g. Cox's Bazar">
                    </div>
                    <div class="field">
                        <label> Daily Rate (BDT)</label>
                        <input type="number" step="0.01" min="0" name="daily_rate" data-label="Daily rate" placeholder="e.g. 50.00">
                    </div>
                </div>
            </div>

            <div class="field-group" id="add-vendor-fields" style="display:none;">
                <div class="field-row">
                    <div class="field">
                        <label>Vendor Type</label>
                        <select name="vendor_type">
                            <option value="hotel">Hotel</option>
                            <option value="transport">Transport</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Company Name</label>
                        <input type="text" name="company_name" id="add-company" data-label="Company name" placeholder="Business name">
                    </div>
                </div>
                <div class="field">
                    <label>Business Address</label>
                    <input type="text" name="address" data-label="Business address" placeholder="Street, city">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
    </div>

    <div class="card">
        <div class="card-toolbar">
            <h3 class="card-title">All Registered Users</h3>
            <span class="badge" id="userCount"><?= count($users) ?> total users</span>
        </div>

        <div class="field">
            <input type="text" id="userSearch" class="search-input" placeholder="Search by name, email, or phone...">
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Verification</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTable">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7" class="empty">No users found in the database.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $i => $u): ?>
                            <tr id="user-row-<?= $u['id'] ?>">
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($u['name']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['phone'] ?? '') ?></td>
                                <td>
                                    <select onchange="location.href='index.php?page=admin&action=users&id=<?= $u['id'] ?>&new_role='+this.value+'&csrf_token=<?= csrf_token() ?>'">
                                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="guide" <?= $u['role'] === 'guide' ? 'selected' : '' ?>>Guide</option>
                                        <option value="vendor" <?= $u['role'] === 'vendor' ? 'selected' : '' ?>>Vendor</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="cost-badge <?= $u['is_verified'] ? 'low' : 'high' ?>">
                                        <?= $u['is_verified'] ? 'Verified' : 'Pending' ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <!-- Toggle Verification -->
                                    <button class="btn-sm btn-edit" id="verify-btn-<?= $u['id'] ?>"
                                       onclick="toggleVerify(<?= $u['id'] ?>, <?= $u['is_verified'] ? '0' : '1' ?>)">
                                        <?= $u['is_verified'] ? 'Unverify' : 'Verify' ?>
                                    </button>
                                    <!-- Delete User -->
                                    <button class="btn-sm btn-delete"
                                       onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
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
const addUserRole = document.getElementById('add-user-role');
const addGuideFields = document.getElementById('add-guide-fields');
const addVendorFields = document.getElementById('add-vendor-fields');
const addLocation = document.getElementById('add-location');
const addCompany = document.getElementById('add-company');

function toggleAddUserFields() {
    const isGuide = addUserRole.value === 'guide';
    const isVendor = addUserRole.value === 'vendor';

    addGuideFields.style.display = isGuide ? 'block' : 'none';
    addVendorFields.style.display = isVendor ? 'block' : 'none';
    addLocation.required = isGuide;
    addCompany.required = isVendor;
}
addUserRole.addEventListener('change', toggleAddUserFields);
toggleAddUserFields();

function toggleVerify(id, status) {
    fetch('index.php?page=admin&action=users&verify=' + status + '&id=' + id + '&csrf_token=<?= csrf_token() ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function deleteUser(id) {
    if (!confirm('Delete this user and all their data?')) return;

    fetch('index.php?page=admin&action=users&delete=1&id=' + id + '&csrf_token=<?= csrf_token() ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('user-row-' + id).remove();
        } else {
            alert(data.message || 'Error deleting user.');
        }
    });
}

/* ---------- Live search ---------- */
const CSRF_TOKEN = '<?= csrf_token() ?>';

function roleOption(user, value, label) {
    return '<option value="' + value + '"' + (user.role === value ? ' selected' : '') + '>' + label + '</option>';
}

function runUserSearch() {
    ajaxTable({
        url:     'index.php?page=ajax&action=search_users&q=' +
                 encodeURIComponent(document.getElementById('userSearch').value.trim()),
        tbody:   'userTable',
        counter: 'userCount',
        columns: 7,
        word:    'total users',
        row: function (u, index) {
            const roleSelect = '<select onchange="location.href=\'index.php?page=admin&action=users&id=' + u.id +
                '&new_role=\'+this.value+\'&csrf_token=' + CSRF_TOKEN + '\'">' +
                roleOption(u, 'user', 'User') + roleOption(u, 'guide', 'Guide') +
                roleOption(u, 'vendor', 'Vendor') + roleOption(u, 'admin', 'Admin') +
                '</select>';

            const verifyBadge = '<span class="cost-badge ' + (u.is_verified == 1 ? 'low' : 'high') + '">' +
                (u.is_verified == 1 ? 'Verified' : 'Pending') + '</span>';

            const verifyBtn = '<button class="btn-sm btn-edit" onclick="toggleVerify(' + u.id + ', ' +
                (u.is_verified == 1 ? 0 : 1) + ')">' + (u.is_verified == 1 ? 'Unverify' : 'Verify') + '</button>';

            return '<tr id="user-row-' + u.id + '">' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + esc(u.name) + '</td>' +
                '<td>' + esc(u.email) + '</td>' +
                '<td>' + esc(u.phone || '') + '</td>' +
                '<td>' + roleSelect + '</td>' +
                '<td>' + verifyBadge + '</td>' +
                '<td class="text-right">' + verifyBtn +
                ' <button class="btn-sm btn-delete" onclick="deleteUser(' + u.id + ')">Delete</button></td>' +
            '</tr>';
        }
    });
}
liveSearch('userSearch', runUserSearch);
</script>
</body>
</html>
