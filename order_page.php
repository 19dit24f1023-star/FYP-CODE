<?php
session_start();
require_once 'db.php';
require_once 'toyyibpay_create_bill.php';
require_once 'customer_mail.php';

// Checkout is available to signed-in customers only.
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['login_notice'] = 'Please sign in or create an account before proceeding to checkout.';
    header('Location: login.php');
    exit();
}

$allCart = $_SESSION['cart'] ?? [];
$cart = $allCart;
if (isset($_GET['selected'])) {
    $selectedKeys = array_map('strval', (array) $_GET['selected']);
    $cart = array_intersect_key($allCart, array_flip($selectedKeys));
}
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
}
$shipping = 0.00;
$total = $subtotal;

$values = [
    'name' => '',
    'email' => '',
    'contact' => '',
    'note' => '',
    'collection' => '',
    'delivery_address' => '',
    'payment_method' => ''
];
$errors = [];
$confirmed = false;

// Pre-fill checkout details from the signed-in customer's database record.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $customerStatement = $conn->prepare('SELECT name, email, phone_number FROM customers WHERE id = ? LIMIT 1');
    $customerStatement->bind_param('i', $_SESSION['user_id']);
    $customerStatement->execute();
    $customer = $customerStatement->get_result()->fetch_assoc();
    $customerStatement->close();
    if ($customer) {
        $values['name'] = $customer['name'] ?? '';
        $values['email'] = $customer['email'] ?? '';
        $values['contact'] = $customer['phone_number'] ?? '';
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['confirm_order'])
    && !empty($cart)
) {

    foreach ($values as $field => $empty) {

        $values[$field] =
            trim($_POST[$field] ?? '');
    }

    // Delivery requires a delivery address; self collection does not.
    $values['delivery_address'] = trim($_POST['delivery_address'] ?? '');
    $total = $subtotal;


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($values['name'] === '') {

        $errors['name'] =
            'Please enter your name.';
    }


    if (
        !filter_var(
            $values['email'],
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors['email'] =
            'Please enter a valid email address.';
    }


    if ($values['contact'] === '') {

        $errors['contact'] =
            'Please enter your contact number.';
    }


    if ($values['collection'] === '') {

        $errors['collection'] =
            'Please select a collection method.';
    }

    if ($values['collection'] === 'Delivery' && ($values['delivery_address'] ?? '') === '') {
        $errors['delivery_address'] = 'Please enter your delivery address.';
    }


    if ($values['payment_method'] === '') {

        $errors['payment_method'] =
            'Please select a payment method.';
    }

    if (!in_array($values['payment_method'], ['Wallet', 'Online banking / FPX', 'Cash on collection'], true)) {
        $errors['payment_method'] = 'Please select a valid payment method.';
    }


    if (
        $values['payment_method'] ===
        'Cash on collection'
        &&
        $values['collection'] !==
        'Self collection'
    ) {

        $errors['payment_method'] =
            'Cash payment is available for self collection only.';
    }

    if ($values['payment_method'] === 'Wallet' && $total <= 0) {
        $errors['payment_method'] = 'Wallet payment is not available for this order.';
    }


    /*
    |--------------------------------------------------------------------------
    | PROCESS ORDER
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $userId =
            (int) $_SESSION['user_id'];


        $artwork =
            !empty($_FILES['artwork']['name'])
            ? basename($_FILES['artwork']['name'])
            : null;


        $status = 'Pending';


        $orderNumber =
            'SAD-' .
            date('Ymd') .
            '-' .
            strtoupper(
                substr(
                    uniqid(),
                    -5
                )
            );


        try {

            /*
            |--------------------------------------------------------------------------
            | Start Transaction
            |--------------------------------------------------------------------------
            */

            $conn->begin_transaction();


            /*
            |--------------------------------------------------------------------------
            | Insert Order
            |--------------------------------------------------------------------------
            */

            $statement =
                $conn->prepare(
                    'INSERT INTO orders
                    (
                        order_number,
                        user_id,
                        product_id,
                        product_name,
                        quantity,
                        unit_price,
                        order_total,
                        shipping_fee,
                        size,
                        note,
                        collection_method,
                        payment_method,
                        payment_status,
                        artwork,
                        status
                    )
                    VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );


            foreach ($cart as $cartKey => $item) {

                $productId =
                    (int) (
                        $item['product_id']
                        ??
                        explode(
                            '-',
                            (string) $cartKey
                        )[0]
                    );


                $quantity =
                    max(
                        1,
                        (int) (
                            $item['quantity']
                            ?? 1
                        )
                    );


                $productName =
                    substr(
                        (string) (
                            $item['name']
                            ?? 'Product'
                        ),
                        0,
                        100
                    );


                $unitPrice =
                    (float) (
                        $item['price']
                        ?? 0
                    );


                $lineTotal =
                    $unitPrice *
                    $quantity;


                $size =
                    substr(
                        (string) (
                            $item['size']
                            ?? 'Standard'
                        ),
                        0,
                        255
                    );


                $itemDetails =
                    trim(
                        (string) (
                            $item['details']
                            ?? ''
                        )
                    );


                $note =
                    "Order reference: {$orderNumber}\n" .
                    "Customer note: {$values['note']}\n" .
                    "Item details: {$itemDetails}";

                if ($values['collection'] === 'Delivery' && !empty($values['delivery_address'])) {
                    $note .= "\nDelivery address: {$values['delivery_address']}";
                }


                $paymentStatus =
                    'Pending';


                $statement->bind_param(
                    'siisidddsssssss',
                    $orderNumber,
                    $userId,
                    $productId,
                    $productName,
                    $quantity,
                    $unitPrice,
                    $lineTotal,
                    $shipping,
                    $size,
                    $note,
                    $values['collection'],
                    $values['payment_method'],
                    $paymentStatus,
                    $artwork,
                    $status
                );


                if (!$statement->execute()) {

                    throw new RuntimeException(
                        'Unable to save the order.'
                    );
                }
            }


            $statement->close();

            if ($values['payment_method'] === 'Wallet') {
                $account = $conn->prepare('INSERT IGNORE INTO wallet_accounts (customer_id) VALUES (?)');
                $account->bind_param('i', $userId);
                $account->execute();
                $account->close();
                $debit = $conn->prepare('UPDATE wallet_accounts SET balance = balance - ? WHERE customer_id = ? AND balance >= ?');
                $debit->bind_param('did', $total, $userId, $total);
                if (!$debit->execute() || $debit->affected_rows !== 1) {
                    $debit->close();
                    throw new RuntimeException('Insufficient wallet balance.');
                }
                $debit->close();
                $completed = 'completed';
                $description = 'Payment for order ' . $orderNumber;
                $walletTx = $conn->prepare('INSERT INTO wallet_transactions (customer_id,type,amount,reference,status,description,completed_at) VALUES (?,\'order_payment\',?,?,?, ?, NOW())');
                $walletTx->bind_param('idsss', $userId, $total, $orderNumber, $completed, $description);
                if (!$walletTx->execute()) {
                    $walletTx->close();
                    throw new RuntimeException('Unable to record wallet payment.');
                }
                $walletTx->close();
                $paid = 'Paid';
                $processing = 'Processing';
                $update = $conn->prepare('UPDATE orders SET payment_status = ?, status = ? WHERE order_number = ?');
                $update->bind_param('sss', $paid, $processing, $orderNumber);
                $update->execute();
                $update->close();
                $conn->commit();
                sendCustomerEmail($values['email'], $values['name'], 'Order received - ' . $orderNumber, customerEmailTemplate('Order received', 'Your wallet payment for order ' . $orderNumber . ' was received.'));
                $_SESSION['last_order'] = ['order_number' => $orderNumber, 'customer' => $values, 'items' => $cart, 'total' => $total, 'created_at' => date('d M Y, h:i A')];
                unset($_SESSION['cart']);
                $confirmed = true;
            }


            /*
            |--------------------------------------------------------------------------
            | ONLINE PAYMENT
            |--------------------------------------------------------------------------
            */

            if ($values['payment_method'] === 'Online banking / FPX') {


                /*
                |--------------------------------------------------------------------------
                | Create ToyyibPay Bill
                |--------------------------------------------------------------------------
                */

                $bill =
                    createToyyibPayBill(
                        $orderNumber,
                        $values['name'],
                        $values['email'],
                        $values['contact'],
                        $total,
                        $values['payment_method']
                    );


                if (
                    empty($bill['success'])
                ) {

                    throw new RuntimeException(
                        $bill['message']
                        ?? 'Unable to create payment bill.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Save Bill Code
                |--------------------------------------------------------------------------
                */

                $billcode =
                    $bill['billcode'];


                $update =
                    $conn->prepare(
                        'UPDATE orders
                         SET toyyibpay_billcode = ?
                         WHERE order_number = ?'
                    );


                $update->bind_param(
                    'ss',
                    $billcode,
                    $orderNumber
                );


                $update->execute();

                $update->close();


                /*
                |--------------------------------------------------------------------------
                | Commit Before Redirect
                |--------------------------------------------------------------------------
                */

                $conn->commit();

                sendCustomerEmail(
                    $values['email'],
                    $values['name'],
                    'Order received - ' . $orderNumber,
                    customerEmailTemplate(
                        'Order received',
                        'Your order ' . $orderNumber . ' has been received. We will update you when its progress changes.'
                    )
                );


                /*
                |--------------------------------------------------------------------------
                | Save Session
                |--------------------------------------------------------------------------
                */

                $_SESSION['pending_order'] = [
                    'order_number' =>
                        $orderNumber,

                    'customer' =>
                        $values,

                    'items' =>
                        $cart,

                    'total' =>
                        $total,

                    'billcode' =>
                        $billcode
                ];


                /*
                |--------------------------------------------------------------------------
                | Redirect To ToyyibPay
                |--------------------------------------------------------------------------
                */

                header(
                    'Location: ' .
                    $bill['payment_url']
                );

                exit();
            }


            /*
            |--------------------------------------------------------------------------
            | CASH ON COLLECTION
            |--------------------------------------------------------------------------
            */

            if (
                $values['payment_method']
                === 'Cash on collection'
            ) {

                $paymentStatus =
                    'Pending';


                $orderStatus =
                    'Pending';


                $update =
                    $conn->prepare(
                        'UPDATE orders
                         SET
                            payment_status = ?,
                            status = ?
                         WHERE order_number = ?'
                    );


                $update->bind_param(
                    'sss',
                    $paymentStatus,
                    $orderStatus,
                    $orderNumber
                );


                $update->execute();

                $update->close();


                $conn->commit();

                sendCustomerEmail(
                    $values['email'],
                    $values['name'],
                    'Order received - ' . $orderNumber,
                    customerEmailTemplate(
                        'Order received',
                        'Your order ' . $orderNumber . ' has been received. We will update you when its progress changes.'
                    )
                );


                $_SESSION['last_order'] = [

                    'order_number' =>
                        $orderNumber,

                    'customer' =>
                        $values,

                    'items' =>
                        $cart,

                    'total' =>
                        $total,

                    'created_at' =>
                        date(
                            'd M Y, h:i A'
                        )
                ];


                unset($_SESSION['cart']);


                $confirmed = true;
            }


        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            try {

                $conn->rollback();

            } catch (Throwable $ignore) {
            }


            $errors['database'] =
                'We could not process your order. ' .
                $exception->getMessage();
        }
    }
}



function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | SA Design</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{min-height:100vh;background:#f6e8f2;color:#172033;font-family:'Plus Jakarta Sans',sans-serif}
a{text-decoration:none}

/* NAVBAR */
.navbar{min-height:84px;padding:16px 48px;display:flex;align-items:center;justify-content:space-between;gap:25px;background:#fff;border-bottom:1px solid #e8ebf2;box-shadow:0 4px 18px rgba(28,53,118,.05);position:sticky;top:0;z-index:100}
.logo{display:flex;flex-direction:column;line-height:.95}
.logo-main{display:flex;align-items:baseline;gap:7px;font-size:32px;font-weight:900;letter-spacing:-2px}
.logo-sa{color:#f0208d}.logo-design{color:#1557d6}
.logo-subtitle{margin-top:4px;color:#111827;font-size:9px;font-weight:900;letter-spacing:.9px;text-transform:uppercase}
.nav-links{display:flex;align-items:center;gap:32px}
.nav-links a{color:#111827;font-size:13px;font-weight:800;text-transform:uppercase;transition:.2s}
.nav-links a:hover,.nav-links a.active{color:#f0208d}
.nav-icons{display:flex;align-items:center;gap:10px}
.login-pill{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 15px;border-radius:11px;background:linear-gradient(135deg,#f0208d,#ff3b86);color:#fff;font-size:12px;font-weight:800;text-transform:uppercase;box-shadow:0 8px 18px rgba(239,58,155,.18);transition:.25s}
.login-pill:hover{transform:translateY(-2px)}
.logout-pill{
    border:0;
    cursor:pointer;
    font-family:'Plus Jakarta Sans',sans-serif;
}
.logout-pill:focus{outline:none}
.logout-popup{border-radius:18px!important}
.logout-title{
    color:#102f91!important;
    font-family:'Plus Jakarta Sans',sans-serif!important;
    font-size:24px!important;
    font-weight:800!important;
}
.logout-text{
    color:#64748b!important;
    font-family:'Plus Jakarta Sans',sans-serif!important;
    font-size:13px!important;
}
.logout-confirm,.logout-cancel{
    border-radius:10px!important;
    padding:10px 18px!important;
    font-family:'Plus Jakarta Sans',sans-serif!important;
    font-size:12px!important;
    font-weight:800!important;
}

.icon-btn{width:42px;height:42px;display:flex;align-items:center;justify-content:center;border:1px solid #f0208d;border-radius:50%;background:#fff;color:#f0208d;transition:.2s}
.icon-btn:hover,.icon-btn.active{background:#ffe4f2;transform:translateY(-2px)}
.menu-toggle{display:none;width:42px;height:42px;border:1px solid #dfe4ef;border-radius:11px;background:#fff;color:#172033;cursor:pointer}

/* HERO */
.checkout-hero{padding:52px 22px 56px;text-align:center;background:linear-gradient(120deg,#cbd8ff 0%,#d9b9eb 52%,#ffc0dc 100%);border-bottom:1px solid #b7c8ff}
.badge{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:999px;background:#fff;border:1px solid #b7c8ff;color:#1747c7;font-size:10px;font-weight:800;letter-spacing:.7px}
.checkout-hero h1{margin:14px 0 8px;color:#102f91;font-size:clamp(34px,5vw,48px);font-weight:900;letter-spacing:-2px}
.checkout-hero h1 span{color:#f0208d}
.checkout-hero p{color:#64748b;font-size:13px;line-height:1.7}

/* MAIN */
.container{width:min(1120px,calc(100% - 100px));margin:45px auto 70px}
.heading{margin-bottom:24px}
.eyebrow{margin-bottom:7px;color:#f0208d;font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
.heading h2{color:#102f91;font-size:28px;font-weight:900;letter-spacing:-1px}

/* CHECKOUT */
.checkout-shell{display:grid;grid-template-columns:minmax(0,1fr) 355px;overflow:hidden;border:1px solid #e1d8e5;border-radius:22px;background:#fff;box-shadow:0 14px 35px rgba(28,53,118,.07)}
.form-side{padding:32px}
.receipt{padding:32px 28px;border-left:1px solid #e3dce7;background:#fff3fa}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px}
.form-title,.receipt h2{margin:0 0 18px;color:#102f91;font-size:15px;font-weight:900}
.field{margin-bottom:14px}
label{display:block;margin-bottom:6px;color:#374151;font-size:.75rem;font-weight:700}
input,textarea,select{width:100%;border:1px solid #dfe5ef;border-radius:10px;outline:none;background:#fff;color:#172033;padding:11px 12px;font:400 .78rem 'Plus Jakarta Sans',sans-serif;transition:.2s}
textarea{min-height:126px;resize:vertical}
input:focus,textarea:focus,select:focus{border-color:#1747c7;box-shadow:0 0 0 3px rgba(40,84,197,.09)}
.field-error{display:block;margin-top:5px;color:#c53d42;font-size:.68rem;font-weight:600}
.delivery-hint{margin-top:6px;color:#94a3b8;font-size:.64rem;line-height:1.45}

/* FILE */
.file-box{position:relative;display:flex;align-items:center;min-height:46px;overflow:hidden;border:1px dashed #b8a9bc;border-radius:10px;background:#fff3fa}
.file-box input{position:absolute;inset:0;z-index:2;opacity:0;cursor:pointer}
.file-label{padding:8px 11px;background:#cddaff;color:#1747c7;font-size:.72rem;font-weight:800}
.file-name{padding:0 11px;color:#8a94a6;font-size:.72rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* PAYMENT */
.payment-options{display:grid;gap:8px}
.payment-option{display:flex;align-items:center;gap:10px;margin:0;padding:11px 12px;border:1px solid #dfe5ef;border-radius:10px;background:#fff;cursor:pointer;transition:.2s}
.payment-option:has(input:checked){border-color:#1747c7;background:#e3eaff}
.payment-option input{width:auto;margin:0;accent-color:#1747c7}
.payment-option strong{display:block;color:#172033;font-size:.76rem}
.payment-option small{display:block;margin-top:1px;color:#64748b;font-size:.65rem;font-weight:400}

/* RECEIPT */
.receipt h2{padding-bottom:17px;border-bottom:1px solid #e3dce7;font-size:19px}
.receipt-label{margin:0 0 12px;color:#64748b;font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.receipt-items{max-height:286px;overflow:auto;margin-bottom:18px;padding-right:4px}
.receipt-item{display:flex;gap:11px;padding:11px 0;border-bottom:1px solid #e3dce7}
.receipt-item img,.receipt-placeholder{flex:0 0 45px;width:45px;height:45px;border-radius:9px;object-fit:cover;background:#cddaff}
.receipt-placeholder{display:grid;place-items:center;color:#1747c7;font-size:1rem}
.receipt-item-name{margin:0 0 3px;color:#172033;font-size:.76rem;font-weight:800}
.receipt-item-detail{margin:0;color:#64748b;font-size:.66rem;line-height:1.4}
.receipt-custom-detail{max-height:38px;margin:3px 0 0;overflow:hidden;color:#7b8799;font-size:.61rem;line-height:1.35;white-space:pre-line}
.receipt-item-price{margin-left:auto;color:#102f91;font-size:.73rem;font-weight:800;white-space:nowrap}
.totals{margin-top:16px;padding-top:16px;border-top:1px solid #e3dce7}
.total-row{display:flex;justify-content:space-between;margin-bottom:10px;color:#64748b;font-size:.78rem}
.total-row strong{color:#172033}
.total-row.grand{margin:15px 0 0;padding-top:15px;border-top:1px dashed #b8a9bc;color:#172033;font-size:.95rem;font-weight:800}
.total-row.grand strong{color:#f0208d;font-size:1.08rem}
.confirm-btn{width:100%;margin-top:24px;padding:14px;border:0;border-radius:11px;background:linear-gradient(135deg,#1747c7,#3157d5);color:#fff;cursor:pointer;font:800 .76rem 'Plus Jakarta Sans',sans-serif;letter-spacing:.05em;transition:.25s;box-shadow:0 10px 22px rgba(40,84,197,.18)}
.confirm-btn:hover{transform:translateY(-2px);background:linear-gradient(135deg,#f0208d,#ff3b86)}
.secure{margin:13px 0 0;color:#94a3b8;font-size:.65rem;text-align:center}

/* SUCCESS / EMPTY */
.success{max-width:600px;margin:25px auto;padding:52px 35px;border:1px solid #e1d8e5;border-radius:22px;background:#fff;box-shadow:0 14px 35px rgba(28,53,118,.07);text-align:center}
.success-icon{display:grid;place-items:center;width:64px;height:64px;margin:0 auto 17px;border-radius:50%;background:#e0f4eb;color:#258353;font-size:1.55rem}
.success h2{margin:0 0 9px;color:#102f91;font-size:28px;font-weight:900}
.success p{margin:0 0 12px;color:#64748b;font-size:.84rem}
.order-number{display:inline-block;margin:9px 0 22px;padding:9px 13px;border-radius:9px;background:#ffe4f2;color:#f0208d;font-size:.77rem;font-weight:800}
.success a,.empty a{color:#1747c7;font-weight:800}
.success a:hover,.empty a:hover{color:#f0208d}
.empty{text-align:center;color:#64748b;padding:80px 22px}
.empty-icon{display:grid;place-items:center;width:64px;height:64px;margin:0 auto 15px;border-radius:50%;background:#cddaff;color:#1747c7;font-size:1.4rem}

/* FOOTER */
.footer{padding:38px 70px;background:#0b1d69;color:#fff}
.footer-grid{width:min(1120px,100%);margin:auto;display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:35px}
.footer-brand{font-size:24px;font-weight:900}.footer-brand span{color:#f0208d}
.footer h4{margin-bottom:10px;font-size:12px}
.footer p,.footer a{color:rgba(255,255,255,.72);font-size:11px;line-height:1.7}
.footer a{display:block;margin-bottom:4px}.footer a:hover{color:#fff}
.footer-line{width:min(1120px,100%);margin:24px auto 0;padding-top:14px;border-top:1px solid rgba(255,255,255,.14);text-align:center;color:rgba(255,255,255,.58);font-size:10px}


/* SUCCESS RECEIPT ACTIONS */
.success-actions{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:4px}
.receipt-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:145px;padding:11px 16px;border-radius:11px;border:1px solid #d8e0f1;background:#fff;color:#1747c7;cursor:pointer;font:800 .74rem 'Plus Jakarta Sans',sans-serif;transition:.2s}
.receipt-btn:hover{transform:translateY(-2px);border-color:#f0208d;color:#f0208d;box-shadow:0 8px 18px rgba(239,58,155,.10)}
.receipt-btn.primary{border-color:#1747c7;background:#1747c7;color:#fff}
.receipt-btn.primary:hover{background:#f0208d;border-color:#f0208d;color:#fff}
.receipt-hint{margin:12px 0 18px!important;font-size:.7rem!important;color:#94a3b8!important}
.receipt-modal{display:none;position:fixed;inset:0;z-index:9999;padding:25px 16px;background:rgba(11,29,105,.38);backdrop-filter:blur(5px);overflow:auto}
.receipt-modal.show{display:flex;align-items:center;justify-content:center}
.receipt-card{width:min(560px,100%);max-height:calc(100dvh - 50px);background:#fff;border-radius:20px;overflow:auto;box-shadow:0 24px 70px rgba(16,47,145,.22)}
.receipt-card-head{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:18px 22px;border-bottom:1px solid #edf0f6}
.receipt-brand{color:#102f91;font-size:18px;font-weight:900}.receipt-brand span{color:#f0208d}
.receipt-close{width:36px;height:36px;border:0;border-radius:50%;background:#f6e8f2;color:#102f91;cursor:pointer;font-size:15px}
.receipt-paper{padding:28px 30px}
.receipt-success-mark{display:grid;place-items:center;width:48px;height:48px;margin:0 auto 10px;border-radius:50%;background:#e0f4eb;color:#258353}
.receipt-paper h3{margin:0;text-align:center;color:#102f91;font-size:22px;font-weight:900}
.receipt-paper .receipt-subtitle{margin:6px 0 22px;text-align:center;color:#64748b;font-size:.72rem}
.receipt-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px}
.receipt-meta-box{padding:11px 12px;border:1px solid #edf0f6;border-radius:10px;background:#fafbff}
.receipt-meta-box span{display:block;margin-bottom:4px;color:#94a3b8;font-size:.61rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
.receipt-meta-box strong{color:#172033;font-size:.72rem}
.receipt-table{width:100%;border-collapse:collapse;margin-top:4px}
.receipt-table th{padding:8px 0;border-bottom:1px solid #dfe5ef;color:#94a3b8;font-size:.61rem;text-align:left;text-transform:uppercase}
.receipt-table th:last-child,.receipt-table td:last-child{text-align:right}
.receipt-table td{padding:10px 0;border-bottom:1px solid #edf0f6;color:#172033;font-size:.68rem;vertical-align:top}
.receipt-table .item-name{font-weight:800}.receipt-table .item-detail{display:block;margin-top:2px;color:#94a3b8;font-size:.59rem}
.receipt-summary{margin-top:15px;padding-top:12px;border-top:1px dashed #b8a9bc}
.receipt-summary-row{display:flex;justify-content:space-between;margin-bottom:8px;color:#64748b;font-size:.7rem}
.receipt-summary-row.total{margin-top:10px;padding-top:12px;border-top:1px solid #edf0f6;color:#172033;font-size:.85rem;font-weight:900}
.receipt-summary-row.total strong{color:#f0208d;font-size:1rem}
.receipt-note{margin-top:18px;padding:11px 12px;border-radius:10px;background:#fff3fa;color:#64748b;font-size:.63rem;line-height:1.5}
.receipt-modal-actions{display:flex;gap:9px;padding:0 30px 25px}
.receipt-modal-actions .receipt-btn{flex:1}
@media(max-width:520px){.receipt-meta{grid-template-columns:1fr}.receipt-paper{padding:24px 20px}.receipt-modal-actions{padding:0 20px 20px}}
@media print{
 body *{visibility:hidden!important}
 #receiptModal,#receiptModal *{visibility:visible!important}
 #receiptModal{display:block!important;position:absolute!important;inset:0!important;padding:0!important;background:#fff!important;overflow:visible!important}
 .receipt-card{width:100%!important;max-width:none!important;border-radius:0!important;box-shadow:none!important}
 .receipt-card-head,.receipt-modal-actions,.receipt-close{display:none!important}
 .receipt-paper{padding:24px!important}
 @page{size:A4;margin:12mm}
}

/* RESPONSIVE */
@media(max-width:900px){
 .navbar{padding:15px 28px}
 .checkout-shell{grid-template-columns:1fr}
 .receipt{border-top:1px solid #e3dce7;border-left:0}
 .container{width:min(100% - 56px,720px)}
}
@media(max-width:760px){
 .navbar{min-height:76px;flex-wrap:wrap;padding:15px 20px}
 .logo-main{font-size:28px}
 .menu-toggle{display:flex;align-items:center;justify-content:center}
 .nav-links{display:none;order:5;width:100%;padding:13px 0 2px;flex-direction:column;gap:14px;border-top:1px solid #eef1f6}
 .navbar.menu-open .nav-links{display:flex}
 .login-pill span{display:none}.login-pill{width:40px;height:40px;padding:0}
 .icon-btn{width:40px;height:40px}
 .checkout-hero{padding:48px 20px}
 .checkout-hero h1{font-size:36px}
 .container{width:calc(100% - 40px);margin:38px auto 55px}
 .form-side,.receipt{padding:24px 19px}
 .form-grid{grid-template-columns:1fr;gap:0}
 .footer{padding:35px 20px}.footer-grid{grid-template-columns:1fr;gap:24px}
}
</style>
<link rel="stylesheet" href="ui_polish.css">
</head>

<body>

<nav class="navbar">

<a href="index.php" class="logo">
 <div class="logo-main">
  <span class="logo-sa">SA</span>
  <span class="logo-design">DESIGN</span>
 </div>
 <div class="logo-subtitle">PRINTING &amp; ADVERTISING</div>
</a>

<div class="nav-links">
 <a href="index.php">Home</a>
 <a href="index.php#products-section">Products</a>
 <a href="about.php">About Us</a>
 <a href="custom_request.php">Custom Request</a>
</div>

<div class="nav-icons">
 <?php if(isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
  <a href="cust_profile.php" class="login-pill" title="Profile">
   <i class="fa-regular fa-user"></i><span>Profile</span>
  </a>
  <button type="button" class="login-pill logout-pill" title="Logout" onclick="confirmLogout()">
   <i class="fa-solid fa-right-from-bracket"></i><span>Log out</span>
  </button>
 <?php else: ?>
  <a href="login.php" class="login-pill" title="Login">
   <i class="fa-regular fa-user"></i><span>Login</span>
  </a>
 <?php endif; ?>

 <a href="cart.php" class="icon-btn active" title="Shopping cart">
  <i class="fa-solid fa-bag-shopping"></i>
 </a>
</div>

<button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false">
 <i class="fa-solid fa-bars"></i>
</button>

</nav>


<section class="checkout-hero">
 <span class="badge">
  <i class="fa-solid fa-lock"></i>
  SECURE CHECKOUT
 </span>
 <h1>Complete Your <span>Order</span></h1>
 <p>Review your details, choose your collection and payment method, then confirm your order.</p>
</section>


<main class="container">

<?php if ($confirmed): ?>

 <section class="success">
  <div class="success-icon"><i class="fa-solid fa-check"></i></div>
  <h2>Order Received</h2>
  <p>Thank you, <?= e($values['name']) ?>. We have received your order and will contact you shortly.</p>

  <div class="order-number">
   Order number: <?= e($_SESSION['last_order']['order_number']) ?>
  </div>

  <p>Payment method: <strong><?= e($values['payment_method']) ?></strong></p>
  <p class="receipt-hint">You can view or print your receipt for your records.</p>

  <div class="success-actions">
   <button type="button" class="receipt-btn primary" onclick="openReceipt()">
    <i class="fa-solid fa-receipt"></i> View Receipt
   </button>
   <button type="button" class="receipt-btn" onclick="printReceipt()">
    <i class="fa-solid fa-print"></i> Print Receipt
   </button>
  </div>

  <p style="margin-top:18px;"><a href="index.php">Continue shopping &rarr;</a></p>
 </section>

 <div class="receipt-modal" id="receiptModal" role="dialog" aria-modal="true" aria-labelledby="receiptTitle">
  <div class="receipt-card">
   <div class="receipt-card-head">
    <div class="receipt-brand"><span>SA</span> DESIGN</div>
    <button type="button" class="receipt-close" onclick="closeReceipt()" aria-label="Close receipt">
     <i class="fa-solid fa-xmark"></i>
    </button>
   </div>

   <div class="receipt-paper">
    <div class="receipt-success-mark"><i class="fa-solid fa-check"></i></div>
    <h3 id="receiptTitle">Order Receipt</h3>
    <p class="receipt-subtitle">Thank you for choosing SA Design Printing & Advertising</p>

    <div class="receipt-meta">
     <div class="receipt-meta-box">
      <span>Order Number</span>
      <strong><?= e($_SESSION['last_order']['order_number']) ?></strong>
     </div>
     <div class="receipt-meta-box">
      <span>Date</span>
      <strong><?= e($_SESSION['last_order']['created_at']) ?></strong>
     </div>
     <div class="receipt-meta-box">
      <span>Customer</span>
      <strong><?= e($values['name']) ?></strong>
     </div>
     <div class="receipt-meta-box">
      <span>Payment</span>
      <strong><?= e($values['payment_method']) ?></strong>
     </div>
    </div>

    <table class="receipt-table">
     <thead>
      <tr>
       <th>Item</th>
       <th>Qty</th>
       <th>Total</th>
      </tr>
     </thead>
     <tbody>
      <?php foreach ($_SESSION['last_order']['items'] as $item): ?>
       <tr>
        <td>
         <span class="item-name"><?= e($item['name'] ?? 'Product') ?></span>
         <span class="item-detail"><?= e($item['size'] ?? 'Standard') ?></span>
        </td>
        <td><?= (int)($item['quantity'] ?? 1) ?></td>
        <td>RM <?= number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) ?></td>
       </tr>
      <?php endforeach; ?>
     </tbody>
    </table>

    <div class="receipt-summary">
     <div class="receipt-summary-row">
      <span>Subtotal</span>
      <strong>RM <?= number_format($subtotal, 2) ?></strong>
     </div>
     <div class="receipt-summary-row total">
      <span>Total</span>
      <strong>RM <?= number_format($total, 2) ?></strong>
     </div>
    </div>

    <div class="receipt-note">
     <i class="fa-solid fa-circle-info"></i>
     Please keep this receipt for your records. SA Design will contact you regarding your order.
    </div>
   </div>

   <div class="receipt-modal-actions">
    <button type="button" class="receipt-btn" onclick="closeReceipt()">Close</button>
    <button type="button" class="receipt-btn primary" onclick="printReceipt()">
     <i class="fa-solid fa-print"></i> Print Receipt
    </button>
   </div>
  </div>
 </div>

<?php elseif (empty($cart)): ?>

 <section class="empty">
  <div class="empty-icon"><i class="fa-solid fa-bag-shopping"></i></div>
  <p>Your cart is empty. Add a product before continuing to checkout.</p>
  <p><a href="index.php#products-section">&larr; Browse products</a></p>
 </section>

<?php else: ?>

 <div class="heading">
  <p class="eyebrow">FINAL STEP</p>
  <h2>Complete your order</h2>
 </div>

 <?php if (isset($errors['database'])): ?>
  <p class="field-error"><?= e($errors['database']) ?></p>
 <?php endif; ?>

 <form method="post" enctype="multipart/form-data" class="checkout-shell">

  <section class="form-side">

   <div class="form-grid">

    <div>
     <h2 class="form-title">
      <i class="fa-regular fa-user"></i> Customer Info
     </h2>

     <div class="field">
      <label for="name">Name</label>
      <input id="name" name="name" value="<?= e($values['name']) ?>" placeholder="Enter your name" required>
      <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
     </div>

     <div class="field">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="<?= e($values['email']) ?>" placeholder="Enter your email" required>
      <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
     </div>

     <div class="field">
      <label for="contact">Contact Number</label>
      <input id="contact" name="contact" value="<?= e($values['contact']) ?>" placeholder="Enter your contact number" required>
      <?php if (isset($errors['contact'])): ?><span class="field-error"><?= e($errors['contact']) ?></span><?php endif; ?>
     </div>
    </div>


    <div>
     <h2 class="form-title">
      <i class="fa-regular fa-clipboard"></i> Order Details
     </h2>

     <div class="field">
      <label for="note">Note for SA Design</label>
      <textarea id="note" name="note" placeholder="Add note here..."><?= e($values['note']) ?></textarea>
     </div>

     <div class="field">
      <label for="collection">Collection Method</label>
      <select id="collection" name="collection" required>
       <option value="">Select method</option>
       <option value="Delivery" <?= $values['collection'] === 'Delivery' ? 'selected' : '' ?>>Delivery</option>
       <option value="Self collection" <?= $values['collection'] === 'Self collection' ? 'selected' : '' ?>>Self collection</option>
      </select>
      <?php if (isset($errors['collection'])): ?><span class="field-error"><?= e($errors['collection']) ?></span><?php endif; ?>
     </div>

     <div class="field" id="deliveryAddressField" style="display:none">
      <label for="delivery_address">Delivery Address</label>
      <textarea id="delivery_address" name="delivery_address" placeholder="Enter the full delivery address..."><?= e($values['delivery_address'] ?? '') ?></textarea>
      <p class="delivery-hint">Required for Delivery only. Self Collection does not need an address.</p>
      <?php if (isset($errors['delivery_address'])): ?><span class="field-error"><?= e($errors['delivery_address']) ?></span><?php endif; ?>
     </div>

     <div class="field">
      <label>Payment Method</label>
      <div class="payment-options">

       <label class="payment-option">
        <input type="radio" name="payment_method" value="Wallet" <?= $values['payment_method'] === 'Wallet' ? 'checked' : '' ?>>
        <span><strong>Wallet</strong><small>Pay instantly from your wallet balance</small></span>
       </label>

       <label class="payment-option">
        <input type="radio" name="payment_method" value="Online banking / FPX" <?= $values['payment_method'] === 'Online banking / FPX' ? 'checked' : '' ?>>
        <span><strong>Online Banking / FPX</strong><small>Pay securely with your bank account</small></span>
       </label>

       <label class="payment-option">
        <input type="radio" name="payment_method" value="Cash on collection" <?= $values['payment_method'] === 'Cash on collection' ? 'checked' : '' ?>>
        <span><strong>Cash on Collection</strong><small>Available for self collection only</small></span>
       </label>

      </div>
      <?php if (isset($errors['payment_method'])): ?><span class="field-error"><?= e($errors['payment_method']) ?></span><?php endif; ?>
     </div>

    </div>

   </div>


   <div class="field">
    <label for="artwork">File Upload <span style="font-weight:400;color:#8a94a6">(artwork/logo, optional)</span></label>

    <div class="file-box">
     <input id="artwork" type="file" name="artwork" accept=".jpg,.jpeg,.png,.pdf,.ai">
     <span class="file-label">Choose file</span>
     <span id="file-name" class="file-name">No file chosen</span>
    </div>
   </div>


  </section>


  <aside class="receipt">

   <h2>
    <i class="fa-solid fa-receipt"></i> Order Receipt
   </h2>

   <p class="receipt-label">Products</p>

   <div class="receipt-items">

    <?php foreach ($cart as $item): ?>

     <div class="receipt-item">

      <?php if (!empty($item['image'])): ?>

       <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>">

      <?php else: ?>

       <div class="receipt-placeholder">
        <i class="fa-solid fa-bag-shopping"></i>
       </div>

      <?php endif; ?>

      <div>
       <p class="receipt-item-name"><?= e($item['name']) ?></p>
       <p class="receipt-item-detail"><?= e($item['size'] ?? 'Standard') ?> &times; <?= (int)($item['quantity'] ?? 1) ?></p>

       <?php if (!empty($item['details'])): ?>
        <p class="receipt-custom-detail"><?= e($item['details']) ?></p>
       <?php endif; ?>
      </div>

      <span class="receipt-item-price">
       RM <?= number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) ?>
      </span>

     </div>

    <?php endforeach; ?>

   </div>


   <div class="totals">

    <div class="total-row">
     <span>Subtotal</span>
     <strong>RM <?= number_format($subtotal, 2) ?></strong>
    </div>

    <div class="total-row grand">
     <span>Total</span>
     <strong id="grandTotal">RM <?= number_format($total, 2) ?></strong>
    </div>

   </div>


   <button class="confirm-btn" type="submit" name="confirm_order">
    <i class="fa-solid fa-circle-check"></i>
    CONFIRM ORDER
   </button>

   <p class="secure">
    <i class="fa-solid fa-shield-halved"></i>
    Your information is used only to process this order.
   </p>

  </aside>

 </form>

<?php endif; ?>

</main>


<footer class="footer">

 <div class="footer-grid">

  <div>
   <div class="footer-brand"><span>SA</span> DESIGN</div>
   <p>Printing &amp; Advertising</p>
   <p>Quality printing solutions for business, events, school and personal needs.</p>
  </div>

  <div>
   <h4>QUICK LINKS</h4>
   <a href="index.php">Home</a>
   <a href="index.php#products-section">Products</a>
   <a href="about.php">About Us</a>
   <a href="custom_request.php">Custom Request</a>
  </div>

  <div>
   <h4>CONTACT</h4>
   <a href="https://wa.me/60194184147" target="_blank" rel="noopener">WhatsApp Us</a>
   <a href="mailto:salamakal@gmail.com">Email Us</a>
  </div>

 </div>

 <div class="footer-line">© 2026 Politeknik Muadzam Shah. All rights reserved.</div>

</footer>


<script>
function confirmLogout() {
    Swal.fire({
        title: 'Logout',
        text: 'Are you sure you want to logout?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Logout',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f0208d',
        cancelButtonColor: '#cbd8ff',
        reverseButtons: true,
        background: '#ffffff',
        customClass: {
            popup: 'logout-popup',
            title: 'logout-title',
            htmlContainer: 'logout-text',
            confirmButton: 'logout-confirm',
            cancelButton: 'logout-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    });
}
</script>

<script>
const menuToggle = document.getElementById('menuToggle');
const navbar = document.querySelector('.navbar');

if (menuToggle) {
 menuToggle.addEventListener('click', () => {
  const isOpen = navbar.classList.toggle('menu-open');

  menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

  menuToggle.innerHTML = isOpen
   ? '<i class="fa-solid fa-xmark"></i>'
   : '<i class="fa-solid fa-bars"></i>';
 });
}

document.querySelectorAll('.nav-links a').forEach(link => {
 link.addEventListener('click', () => {
  navbar.classList.remove('menu-open');
  if (menuToggle) {
   menuToggle.setAttribute('aria-expanded','false');
   menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
  }
 });
});

const collectionSelect = document.getElementById('collection');
const deliveryAddressField = document.getElementById('deliveryAddressField');
const deliveryAddressInput = document.getElementById('delivery_address');
const grandTotalEl = document.getElementById('grandTotal');
const subtotalValue = <?= json_encode((float)$subtotal) ?>;

function updateCollectionUI() {
    const isDelivery = collectionSelect && collectionSelect.value === 'Delivery';
    const isSelfCollection = collectionSelect && collectionSelect.value === 'Self collection';

    if (deliveryAddressField) deliveryAddressField.style.display = isDelivery ? 'block' : 'none';

    if (deliveryAddressInput) {
        deliveryAddressInput.required = isDelivery;
        if (!isDelivery) deliveryAddressInput.value = '';
    }

    if (grandTotalEl) grandTotalEl.textContent = 'RM ' + subtotalValue.toFixed(2);

    // Cash on Collection is only valid for Self Collection.
    document.querySelectorAll('input[name="payment_method"]').forEach(input => {
        if (input.value === 'Cash on collection') {
            input.disabled = !isSelfCollection;
            if (!isSelfCollection && input.checked) input.checked = false;
            const card = input.closest('.payment-option');
            if (card) {
                card.style.opacity = isSelfCollection ? '1' : '.55';
                card.style.cursor = isSelfCollection ? 'pointer' : 'not-allowed';
            }
        }
    });
}

collectionSelect?.addEventListener('change', updateCollectionUI);
updateCollectionUI();

document.getElementById('artwork')?.addEventListener('change', function () {
 const fileName = document.getElementById('file-name');
 if (fileName) {
  fileName.textContent = this.files[0]
   ? this.files[0].name
   : 'No file chosen';
 }
});
</script>


<script>
// The shared container animation creates a containing block for fixed elements.
// Move the receipt modal to the document body so it always covers the viewport.
document.addEventListener('DOMContentLoaded', function() {
    const receiptModal = document.getElementById('receiptModal');
    if (receiptModal) document.body.appendChild(receiptModal);
});

function openReceipt() {
    const modal = document.getElementById('receiptModal');
    if (!modal) return;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReceipt() {
    const modal = document.getElementById('receiptModal');
    if (!modal) return;
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

function printReceipt() {
    const modal = document.getElementById('receiptModal');
    if (!modal) return;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
        window.print();
    }, 150);
}

document.getElementById('receiptModal')?.addEventListener('click', function(event) {
    if (event.target === this) closeReceipt();
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') closeReceipt();
});

window.addEventListener('afterprint', function() {
    const modal = document.getElementById('receiptModal');
    if (modal) modal.classList.remove('show');
    document.body.style.overflow = '';
});
</script>

</body>
</html>
