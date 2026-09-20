<?php
ob_start();
session_start();
include("db.php");

header('Content-Type: application/json; charset=UTF-8');

function send_response($success, $message)
{
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode([
        'status'  => $success ? 'success' : 'error',
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(false, 'Invalid request method.');
}

if (!isset($_SESSION['customer_id'])) {
    send_response(false, 'Session expired. Please login again.');
}

$customer_id = (int) $_SESSION['customer_id'];

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone_number'] ?? '');

$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

/* =========================
   BASIC VALIDATION
========================= */
if ($name === '' || $email === '' || $phone === '') {
    send_response(false, 'Please fill in all required fields.');
}

if (strlen($name) > 100 || strlen($email) > 150 || strlen($phone) > 30) {
    send_response(false, 'One or more fields are too long.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_response(false, 'Please enter a valid email address.');
}

/*
 * Password is OPTIONAL.
 * Only validate it when the customer actually enters a new password.
 */
if ($new_password !== '') {

    if ($confirm_password === '') {
        send_response(false, 'Please confirm your new password.');
    }

    if ($new_password !== $confirm_password) {
        send_response(false, 'Passwords do not match.');
    }

    if (strlen($new_password) < 6) {
        send_response(false, 'Password must be at least 6 characters.');
    }
}

try {
/* =========================
   CHECK DUPLICATE EMAIL
========================= */
$check = mysqli_prepare(
    $conn,
    "SELECT id FROM customers WHERE email = ? AND id != ? LIMIT 1"
);

if (!$check) {
    send_response(false, 'Unable to check email.');
}

mysqli_stmt_bind_param($check, "si", $email, $customer_id);
mysqli_stmt_execute($check);
$check_result = mysqli_stmt_get_result($check);

if ($check_result && mysqli_num_rows($check_result) > 0) {
    mysqli_stmt_close($check);
    send_response(false, 'This email is already registered.');
}

mysqli_stmt_close($check);

/* =========================
   GET CURRENT PROFILE IMAGE
========================= */
$current = mysqli_prepare(
    $conn,
    "SELECT profile_image FROM customers WHERE id = ? LIMIT 1"
);

if (!$current) {
    send_response(false, 'Unable to retrieve customer information.');
}

mysqli_stmt_bind_param($current, "i", $customer_id);
mysqli_stmt_execute($current);
$current_result = mysqli_stmt_get_result($current);
$current_customer = mysqli_fetch_assoc($current_result);
mysqli_stmt_close($current);

if (!$current_customer) {
    send_response(false, 'Customer not found.');
}

$current_image = $current_customer['profile_image'] ?? '';
$profile_image = $current_image;

/* =========================
   PROFILE PICTURE
========================= */
if (
    isset($_FILES['profile_picture']) &&
    isset($_FILES['profile_picture']['error']) &&
    $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        send_response(false, 'Profile picture upload failed.');
    }

    if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
        send_response(false, 'Profile picture must be 5MB or smaller.');
    }

    $extension = strtolower(
        pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION)
    );

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        send_response(
            false,
            'Only JPG, JPEG, PNG, GIF or WEBP images are allowed.'
        );
    }

    $upload_dir = __DIR__ . '/images/profile/';

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            send_response(false, 'Unable to create profile image folder.');
        }
    }

    $new_filename =
        'profile_' .
        $customer_id .
        '_' .
        time() .
        '_' .
        bin2hex(random_bytes(3)) .
        '.' .
        $extension;

    $target = $upload_dir . $new_filename;

    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
        send_response(false, 'Unable to save profile picture.');
    }

    $profile_image = $new_filename;

}

/* =========================
   UPDATE CUSTOMER
========================= */
if ($new_password !== '') {

    $hashed_password = password_hash(
        $new_password,
        PASSWORD_DEFAULT
    );

    $sql = "
        UPDATE customers
        SET
            name = ?,
            email = ?,
            phone_number = ?,
            password = ?,
            profile_image = ?
        WHERE id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        send_response(false, 'Failed to prepare profile update.');
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssi",
        $name,
        $email,
        $phone,
        $hashed_password,
        $profile_image,
        $customer_id
    );

} else {

    /*
     * No new password:
     * keep the existing password unchanged.
     */
    $sql = "
        UPDATE customers
        SET
            name = ?,
            email = ?,
            phone_number = ?,
            profile_image = ?
        WHERE id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        send_response(false, 'Failed to prepare profile update.');
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssssi",
        $name,
        $email,
        $phone,
        $profile_image,
        $customer_id
    );
}

/* =========================
   EXECUTE
========================= */
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    send_response(false, 'Failed to update profile. Please try again.');
}

mysqli_stmt_close($stmt);

/* Remove the old image only after the database update succeeds. */
if ($profile_image !== $current_image && !empty($current_image) && $current_image !== 'default.png') {
    $old_file = $upload_dir . basename($current_image);
    if (is_file($old_file)) {
        @unlink($old_file);
    }
}

/* =========================
   UPDATE SESSION
========================= */
$_SESSION['customer_id'] = $customer_id;
$_SESSION['fullname'] = $name;
$_SESSION['user_email'] = $email;

send_response(
    true,
    'Profile updated successfully!'
);
} catch (Throwable $exception) {
    error_log('Profile update error: ' . $exception->getMessage());
    send_response(false, 'Unable to update your profile right now. Please try again.');
}
?>
