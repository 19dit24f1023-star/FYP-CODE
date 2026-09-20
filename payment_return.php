<?php

session_start();

require_once 'db.php';

$orderNumber = $_GET['order_id'] ?? '';

$statusId = $_GET['status_id'] ?? '';

$billcode = $_GET['billcode'] ?? '';


$order = null;
$customRequest = null;
$walletTransaction = null;

if (strpos($orderNumber, 'WT-') === 0) {
    $statement = $conn->prepare('SELECT status, amount FROM wallet_transactions WHERE reference = ? LIMIT 1');
    if ($statement) {
        $statement->bind_param('s', $orderNumber);
        $statement->execute();
        $walletTransaction = $statement->get_result()->fetch_assoc();
        $statement->close();
    }
} elseif (strpos($orderNumber, 'CR') === 0) {
    $statement = $conn->prepare(
        'SELECT id, product_name, price, payment_status
         FROM custom_request
         WHERE CONCAT("CR", LPAD(id, 5, "0")) = ?
         LIMIT 1'
    );
    if ($statement) {
        $statement->bind_param('s', $orderNumber);
        $statement->execute();
        $customRequest = $statement->get_result()->fetch_assoc();
        $statement->close();
    }
}


if ($walletTransaction) {
    $paymentStatus = $walletTransaction['status'] === 'completed' ? 'Paid' : 'Pending';
    $title = $paymentStatus === 'Paid' ? 'Wallet Top Up Successful' : 'Wallet Top Up Processing';
    $message = $paymentStatus === 'Paid' ? 'Your wallet has been credited.' : 'Your top up is being verified.';
    $icon = $paymentStatus === 'Paid' ? 'success' : 'info';
} elseif ($customRequest) {
    $paymentStatus = $customRequest['payment_status'] ?? 'Pending';
    $title = $paymentStatus === 'Paid' ? 'Payment Successful' : 'Payment Processing';
    $message = $paymentStatus === 'Paid'
        ? 'Your custom request payment has been successfully received.'
        : 'Your custom request payment is being verified.';
    $icon = $paymentStatus === 'Paid' ? 'success' : 'info';
} elseif ($orderNumber !== '') {

    $statement = $conn->prepare(
        'SELECT
            order_number,
            order_total,
            payment_method,
            payment_status,
            status
         FROM orders
         WHERE order_number = ?
         LIMIT 1'
    );

    $statement->bind_param(
        's',
        $orderNumber
    );

    $statement->execute();

    $order =
        $statement
        ->get_result()
        ->fetch_assoc();

    $statement->close();
}


$paymentStatus = $walletTransaction
    ? ($walletTransaction['status'] === 'completed' ? 'Paid' : 'Pending')
    : ($customRequest
        ? ($customRequest['payment_status'] ?? 'Pending')
        : ($order['payment_status'] ?? 'Pending'));


if (!$customRequest && $paymentStatus === 'Failed') {
    $title = 'Payment Failed';
    $message = 'Your payment was not successful.';
    $icon = 'error';
} elseif ($paymentStatus === 'Paid') {

    $title = 'Payment Successful';

    $message =
        'Your payment has been successfully received.';

    $icon = 'success';

} else {

    $title = 'Payment Processing';

    $message =
        'Your payment is being verified. Please check your order tracking shortly.';

    $icon = 'info';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($title) ?> | SA Design</title>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f6e8f2;
    font-family: Arial, sans-serif;
}

.card {
    width: min(500px, calc(100% - 40px));
    background: white;
    border-radius: 22px;
    padding: 45px 30px;
    text-align: center;
    box-shadow: 0 15px 40px rgba(28,53,118,.12);
}

h1 {
    color: #102f91;
    margin-bottom: 10px;
}

p {
    color: #64748b;
    line-height: 1.6;
}

.order {
    display: inline-block;
    margin: 20px 0;
    padding: 10px 15px;
    border-radius: 10px;
    background: #ffe4f2;
    color: #f0208d;
    font-weight: bold;
}

.btn {
    display: inline-block;
    padding: 12px 20px;
    border-radius: 10px;
    background: #1747c7;
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.btn:hover {
    background: #f0208d;
}

</style>

<link rel="stylesheet" href="ui_polish.css">
</head>

<body>

<div class="card">

    <h1>
        <?= htmlspecialchars($title) ?>
    </h1>

    <p>
        <?= htmlspecialchars($message) ?>
    </p>

    <?php if ($orderNumber !== ''): ?>

        <div class="order">
            Order Number:
            <?= htmlspecialchars($orderNumber) ?>
        </div>

    <?php endif; ?>

    <br>

    <a href="cust_profile.php"
       class="btn">
        View My Order
    </a>

</div>

</body>

</html>
