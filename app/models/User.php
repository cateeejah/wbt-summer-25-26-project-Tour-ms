<?php
function addUser($conn, $name, $email, $password, $role, $phone = null) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password_hash, phone, role, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmt, 'sssss', $name, $email, $hash, $phone, $role);
    $ok = mysqli_stmt_execute($stmt);
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $ok ? $newId : false;
}

function createRoleProfile($conn, $userId, $role, $extra = []) {
    if ($role === 'guide') {
        $location = $extra['location'] ?? '';
        $rate = $extra['daily_rate'] ?? 0;
        $stmt = mysqli_prepare($conn, "INSERT INTO guides (user_id, location, daily_rate) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isd', $userId, $location, $rate);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
    if ($role === 'vendor') {
        $type = $extra['type'] ?? 'hotel';
        $company = $extra['company_name'] ?? '';
        $address = $extra['address'] ?? '';
        $stmt = mysqli_prepare($conn, "INSERT INTO vendors (user_id, type, company_name, address) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isss', $userId, $type, $company, $address);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
    return true;
}

function authUser($conn, $email, $password) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password_hash, phone, role, is_verified, profile_picture FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row && password_verify($password, $row['password_hash'])) {
        return $row;
    }
    return false;
}

function emailExists($conn, $email, $excludeId = null) {
    if ($excludeId) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, 'si', $email, $excludeId);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

function updateRememberToken($conn, $id, $token) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET remember_token = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $token, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function getUserByRememberToken($conn, $token) {
    $hashed_token = hash('sha256', $token);
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, role, is_verified, profile_picture FROM users WHERE remember_token = ?");
    mysqli_stmt_bind_param($stmt, 's', $hashed_token);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}
?>
