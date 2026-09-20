<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/toyyibpay_create_bill.php';

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$customerId = (int)($_SESSION['customer_id'] ?? $_SESSION['user_id']);
$amount = round((float)($_GET['amount'] ?? 0), 2);

if ($amount < 5 || $amount > 10000) {
    $_SESSION['wallet_error'] = 'Top ups must be between RM 5.00 and RM 10,000.00.';
    header('Location: wallet.php');
    exit;
}

$customer = $conn->prepare('SELECT name, email, phone_number FROM customers WHERE id = ? LIMIT 1');
$customer->bind_param('i', $customerId);
$customer->execute();
$customerDetails = $customer->get_result()->fetch_assoc();
$customer->close();

if (!$customerDetails || empty($customerDetails['email'])) {
    $_SESSION['wallet_error'] = 'Please complete your profile email before topping up.';
    header('Location: wallet.php');
    exit;
}

$reference = 'WT-TP-' . $customerId . '-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));
$pending = 'pending';
$description = 'Wallet top up via ToyyibPay';

$transaction = $conn->prepare(
    "INSERT INTO wallet_transactions (customer_id, type, amount, reference, status, description)\n     VALUES (?, 'topup', ?, ?, ?, ?)"
);
$transaction->bind_param('idsss', $customerId, $amount, $reference, $pending, $description);

if (!$transaction->execute()) {
    $transaction->close();
    $_SESSION['wallet_error'] = 'Unable to start your top up. Please try again.';
    header('Location: wallet.php');
    exit;
}
$transaction->close();

$bill = createToyyibPayBill(
    $reference,
    (string)($customerDetails['name'] ?? 'SA Design Customer'),
    (string)$customerDetails['email'],
    (string)($customerDetails['phone_number'] ?? ''),
    $amount,
    'FPX'
);

if (!$bill['success']) {
    $failed = 'failed';
    $update = $conn->prepare('UPDATE wallet_transactions SET status = ?, description = ? WHERE reference = ?');
    $failureDescription = 'ToyyibPay bill creation failed';
    $update->bind_param('sss', $failed, $failureDescription, $reference);
    $update->execute();
    $update->close();

    $_SESSION['wallet_error'] = $bill['message'] ?? 'ToyyibPay could not create a payment bill. Please try again.';
    header('Location: wallet.php');
    exit;
}

header('Location: ' . $bill['payment_url']);
exit;
