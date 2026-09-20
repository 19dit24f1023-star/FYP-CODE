<?php
session_start();
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/customer_mail.php";

$order_number = trim($_POST['order_number'] ?? '');
$status = trim($_POST['status'] ?? '');

$allowed = ['Pending', 'Processing', 'Ready', 'Completed'];

if ($order_number === '') {
    die("Order number is missing.");
}

if (!in_array($status, $allowed, true)) {
    die("Invalid order status.");
}

$customerQuery = mysqli_prepare($conn, "SELECT customers.name, customers.email, orders.status AS current_status FROM orders LEFT JOIN customers ON customers.id = orders.user_id WHERE orders.order_number = ? LIMIT 1");
if (!$customerQuery) {
    die("Unable to find order customer: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($customerQuery, "s", $order_number);
mysqli_stmt_execute($customerQuery);
$customer = mysqli_stmt_get_result($customerQuery)->fetch_assoc();
mysqli_stmt_close($customerQuery);

$stmt = mysqli_prepare(
    $conn,
    "UPDATE orders SET status = ? WHERE order_number = ?"
);

if (!$stmt) {
    die("Unable to prepare status update: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "ss", $status, $order_number);

if (!mysqli_stmt_execute($stmt)) {
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);

    if ($customer && strtolower((string)$customer['current_status']) !== strtolower($status)) {
        sendCustomerEmail(
            (string)$customer['email'],
            (string)$customer['name'],
            'Order update - ' . $order_number,
            customerEmailTemplate(
                'Order progress updated',
                'Your order ' . $order_number . ' is now ' . $status . '.'
            )
        );
    }
    die("Failed to update order status: " . $error);
}

mysqli_stmt_close($stmt);

header(
    "Location: ViewPage.php?order_number=" . urlencode($order_number) . "&updated=1"
);
exit;
?>
