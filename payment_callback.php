<?php

require_once 'db.php';
require_once 'toyyibpay_config.php';
require_once 'customer_mail.php';


/*
|--------------------------------------------------------------------------
| Get Callback Data
|--------------------------------------------------------------------------
*/

$status = $_POST['status'] ?? '';

$orderNumber = $_POST['order_id'] ?? '';

$refno = $_POST['refno'] ?? '';

$billcode = $_POST['billcode'] ?? '';

$amount = $_POST['amount'] ?? '';

$receivedHash = $_POST['hash'] ?? '';


/*
|--------------------------------------------------------------------------
| Validate Required Data
|--------------------------------------------------------------------------
*/

if (
    $status === '' ||
    $orderNumber === '' ||
    $refno === '' ||
    $billcode === '' ||
    $receivedHash === ''
) {

    http_response_code(400);

    exit('Invalid callback data.');
}


/*
|--------------------------------------------------------------------------
| Verify Hash
|--------------------------------------------------------------------------
|
| ToyyibPay:
|
| MD5(
|   userSecretKey
|   + status
|   + order_id
|   + refno
|   + "ok"
| )
|
|--------------------------------------------------------------------------
*/

$expectedHash = md5(
    TOYYIBPAY_SECRET_KEY .
    $status .
    $orderNumber .
    $refno .
    'ok'
);


if (
    !hash_equals(
        $expectedHash,
        $receivedHash
    )
) {

    http_response_code(403);

    exit('Invalid hash.');
}


/*
|--------------------------------------------------------------------------
| Payment SUCCESS
|--------------------------------------------------------------------------
*/

if ($status === '1') {
    if (strpos($orderNumber, 'WT-') === 0) {
        $conn->begin_transaction();
        try {
            $tx = $conn->prepare('SELECT id, customer_id, amount, status FROM wallet_transactions WHERE reference = ? FOR UPDATE');
            $tx->bind_param('s', $orderNumber);
            $tx->execute();
            $walletTx = $tx->get_result()->fetch_assoc();
            $tx->close();
            if (!$walletTx) {
                throw new RuntimeException('Wallet transaction not found.');
            }
            if ($walletTx['status'] !== 'completed') {
                $customerId = (int)$walletTx['customer_id'];
                $amountValue = (float)$walletTx['amount'];
                $account = $conn->prepare('INSERT IGNORE INTO wallet_accounts (customer_id) VALUES (?)');
                $account->bind_param('i', $customerId);
                $account->execute();
                $account->close();
                $credit = $conn->prepare('UPDATE wallet_accounts SET balance = balance + ? WHERE customer_id = ?');
                $credit->bind_param('di', $amountValue, $customerId);
                $credit->execute();
                $credit->close();
                $completed = 'completed';
                $done = $conn->prepare('UPDATE wallet_transactions SET status = ?, completed_at = NOW(), description = ? WHERE id = ?');
                $description = 'Wallet top up via ToyyibPay (' . $refno . ')';
                $done->bind_param('ssi', $completed, $description, $walletTx['id']);
                $done->execute();
                $done->close();
            }
            $conn->commit();
            echo 'Wallet top up successful.';
        } catch (Throwable $exception) {
            $conn->rollback();
            http_response_code(500);
            echo 'Unable to credit wallet.';
        }
        exit();
    }
    if (strpos($orderNumber, 'CR') === 0) {
        $customerStatement = $conn->prepare('SELECT fullname, email, product_name FROM custom_request WHERE CONCAT("CR", LPAD(id, 5, "0")) = ? LIMIT 1');
        $customerStatement->bind_param('s', $orderNumber);
        $customerStatement->execute();
        $customCustomer = $customerStatement->get_result()->fetch_assoc();
        $customerStatement->close();
        $paymentStatus = 'Paid';
        $statement = $conn->prepare(
            'UPDATE custom_request
             SET payment_status = ?, toyyibpay_billcode = ?, payment_reference = ?
             WHERE CONCAT("CR", LPAD(id, 5, "0")) = ?'
        );
        if (!$statement) {
            http_response_code(500);
            exit('Unable to update custom request payment.');
        }
        $statement->bind_param('ssss', $paymentStatus, $billcode, $refno, $orderNumber);
        $statement->execute();
        $statement->close();
        if ($customCustomer) {
            sendCustomerEmail(
                (string)$customCustomer['email'],
                (string)$customCustomer['fullname'],
                'Custom request payment received - ' . $orderNumber,
                customerEmailTemplate('Payment received', 'Payment for your custom request ' . $orderNumber . ' has been received successfully.')
            );
        }
        http_response_code(200);
        echo 'Payment successful.';
        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Payment Status
    |--------------------------------------------------------------------------
    */

    $statement = $conn->prepare(
        'UPDATE orders
         SET
            payment_status = ?,
            toyyibpay_billcode = ?,
            payment_reference = ?,
            status = ?
         WHERE order_number = ?'
    );


    $paymentStatus = 'Paid';

    /*
    |--------------------------------------------------------------------------
    | Order Status
    |--------------------------------------------------------------------------
    |
    | Lepas payment berjaya:
    | Pending -> Processing
    |
    |--------------------------------------------------------------------------
    */

    $orderStatus = 'Processing';


    $statement->bind_param(
        'sssss',
        $paymentStatus,
        $billcode,
        $refno,
        $orderStatus,
        $orderNumber
    );


    $statement->execute();

    $statement->close();


    http_response_code(200);

    echo 'Payment successful.';
    exit();
}


/*
|--------------------------------------------------------------------------
| Payment PENDING
|--------------------------------------------------------------------------
*/

if ($status === '2') {
    if (strpos($orderNumber, 'WT-') === 0) {
        $pending = 'pending';
        $statement = $conn->prepare('UPDATE wallet_transactions SET status = ? WHERE reference = ? AND status <> \'completed\'');
        $statement->bind_param('ss', $pending, $orderNumber);
        $statement->execute();
        $statement->close();
        http_response_code(200);
        echo 'Wallet top up pending.';
        exit();
    }

    $paymentStatus = 'Pending';

    $statement = $conn->prepare(
        'UPDATE orders
         SET
            payment_status = ?,
            toyyibpay_billcode = ?,
            payment_reference = ?
         WHERE order_number = ?'
    );

    $statement->bind_param(
        'ssss',
        $paymentStatus,
        $billcode,
        $refno,
        $orderNumber
    );

    $statement->execute();

    $statement->close();

    http_response_code(200);

    echo 'Payment pending.';
    exit();
}


/*
|--------------------------------------------------------------------------
| Payment FAILED
|--------------------------------------------------------------------------
*/

if ($status === '3') {
    if (strpos($orderNumber, 'WT-') === 0) {
        $failed = 'failed';
        $statement = $conn->prepare('UPDATE wallet_transactions SET status = ? WHERE reference = ? AND status <> \'completed\'');
        $statement->bind_param('ss', $failed, $orderNumber);
        $statement->execute();
        $statement->close();
        http_response_code(200);
        echo 'Wallet top up failed.';
        exit();
    }

    $paymentStatus = 'Failed';

    $orderStatus = 'Pending';

    $statement = $conn->prepare(
        'UPDATE orders
         SET
            payment_status = ?,
            toyyibpay_billcode = ?,
            payment_reference = ?,
            status = ?
         WHERE order_number = ?'
    );

    $statement->bind_param(
        'sssss',
        $paymentStatus,
        $billcode,
        $refno,
        $orderStatus,
        $orderNumber
    );

    $statement->execute();

    $statement->close();

    http_response_code(200);

    echo 'Payment failed.';
    exit();
}


http_response_code(200);

echo 'Callback received.';