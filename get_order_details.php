<?php
session_start();
include("db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id']) || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$order_id = intval($_GET['id']);
$customer_id = $_SESSION['customer_id'];

$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($order = mysqli_fetch_assoc($result)) {
    $item_price = floatval($order['order_total']);
    $shipping_fee = 5.00; // Set kos penghantaran (cth: RM 5.00)
    $grand_total = $item_price + $shipping_fee;

    echo json_encode([
        'id' => $order['id'],
        'order_number' => $order['order_number'],
        'created_at' => date("d F Y", strtotime($order['created_at'])),
        'status' => $order['status'],
        'product_name' => $order['product_name'],
        'quantity' => $order['quantity'],
        'item_price' => number_format($item_price, 2),
        'shipping_fee' => number_format($shipping_fee, 2),
        'grand_total' => number_format($grand_total, 2)
    ]);
} else {
    echo json_encode(['error' => 'Order not found']);
}
?>