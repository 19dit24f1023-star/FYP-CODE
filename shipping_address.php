<?php

// Start session
session_start();

// Connect database
include("db.php");


// ========================================
// CHECK CUSTOMER LOGIN
// ========================================

if (!isset($_SESSION['customer_id'])) {

    header("Location: login.php");
    exit();

}


// Get logged-in customer ID
$customer_id = $_SESSION['customer_id'];


// ========================================
// CHECK FORM SUBMISSION
// ========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: cust_profile.php?page=address");
    exit();

}


// ========================================
// GET FORM DATA
// ========================================

$action = $_POST['action'] ?? 'edit';

$name = trim($_POST['name'] ?? '');

$phone = trim($_POST['phone'] ?? '');

$address = trim($_POST['address'] ?? '');


// ========================================
// VALIDATION
// ========================================

// Check empty fields
if (empty($name) || empty($phone) || empty($address)) {

    echo "<script>
            alert('Please fill in all address information.');
            window.location.href='cust_profile.php?page=address';
          </script>";

    exit();

}


// ========================================
// UPDATE CUSTOMER ADDRESS
// ========================================
//
// Since your current database stores address
// directly inside the customers table,
// both ADD and EDIT will update that customer's
// address.
//
// ========================================

$sql = "UPDATE customers
        SET name = ?,
            phone_number = ?,
            address = ?
        WHERE id = ?";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die("Database error: " . mysqli_error($conn));

}


mysqli_stmt_bind_param(
    $stmt,
    "sssi",
    $name,
    $phone,
    $address,
    $customer_id
);


// Execute update
if (mysqli_stmt_execute($stmt)) {

    // Success
    echo "<script>

            alert('Shipping address saved successfully!');

            window.location.href='cust_profile.php?page=address';

          </script>";

} else {

    // Error
    echo "<script>

            alert('Failed to save shipping address.');

            window.location.href='cust_profile.php?page=address';

          </script>";

}


// Close statement
mysqli_stmt_close($stmt);

?>