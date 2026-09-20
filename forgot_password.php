<?php

include 'db.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$mailConfig = require __DIR__ . '/mail_config.php';

$message = '';
$message_type = '';

// Dynamic Base URL Detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');

// =========================================================
// CHECK RESET TOKEN
// =========================================================
$reset_mode = false;
$reset_token = '';
$reset_email = '';

if (isset($_GET['reset']) && $_GET['reset'] !== '') {
    $reset_token = trim($_GET['reset']);
    $hashed_token = hash('sha256', $reset_token);

    $stmt = $conn->prepare(
        "SELECT id, email
         FROM customers
         WHERE reset_token = ?
         AND reset_expires IS NOT NULL
         AND reset_expires > NOW()
         LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param("s", $hashed_token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $reset_user = $result->fetch_assoc();
            $reset_mode = true;
            $reset_email = $reset_user['email'];
        } else {
            $message = 'This password reset link is invalid or has expired.';
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// =========================================================
// HANDLE POST
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // FORGOT PASSWORD
    if ($action === 'forgot_password') {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'warning';
        } else {
            $stmt = $conn->prepare(
                "SELECT id, name, email FROM customers WHERE LOWER(email) = LOWER(?) LIMIT 1"
            );

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $customer = $result->fetch_assoc();
                    $raw_token = bin2hex(random_bytes(32));
                    $hashed_token = hash('sha256', $raw_token);
                    $expires = date('Y-m-d H:i:s', time() + 3600);

                    $update = $conn->prepare(
                        "UPDATE customers SET reset_token = ?, reset_expires = ? WHERE id = ?"
                    );
                    $customer_id = (int)$customer['id'];
                    $update->bind_param("ssi", $hashed_token, $expires, $customer_id);

                    if ($update->execute()) {
                        $reset_link = $base_url . '?reset=' . urlencode($raw_token);

                        // Send Mail Logic
                        try {
                            $mail = new PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = $mailConfig['host'];
                            $mail->SMTPAuth = true;
                            $mail->Username = $mailConfig['username'];
                            $mail->Password = $mailConfig['password'];

                            $mail->SMTPSecure = (isset($mailConfig['encryption']) && strtolower($mailConfig['encryption']) === 'tls')
                                ? PHPMailer::ENCRYPTION_STARTTLS 
                                : PHPMailer::ENCRYPTION_SMTPS;

                            $mail->Port = $mailConfig['port'];
                            $mail->CharSet = 'UTF-8';
                            $mail->setFrom($mailConfig['username'], 'SA Design');
                            $mail->addAddress($customer['email'], $customer['name']);
                            $mail->isHTML(true);
                            $mail->Subject = 'SA Design - Password Reset';
                            
                            // Email body template stays the same
                            $mail->Body = "Hello " . htmlspecialchars($customer['name']) . ",<br><br>Click the link to reset your password: <a href='" . htmlspecialchars($reset_link) . "'>Reset Password</a>";
                            $mail->AltBody = "Hello " . $customer['name'] . ",\n\nReset link: " . $reset_link;

                            $mail->send();
                            $message = 'If that email is registered, a password reset link has been sent. Please check your inbox.';
                            $message_type = 'success';
                        } catch (Exception $e) {
                            $clear = $conn->prepare("UPDATE customers SET reset_token = NULL, reset_expires = NULL WHERE id = ?");
                            $clear->bind_param("i", $customer_id);
                            $clear->execute();
                            $clear->close();

                            $message = 'The password reset email could not be sent. Please try again later.';
                            $message_type = 'error';
                            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
                        }
                    }
                    $update->close();
                } else {
                    $message = 'If that email is registered, a password reset link has been sent. Please check your inbox.';
                    $message_type = 'success';
                }
                $stmt->close();
            }
        }
    }

    // RESET PASSWORD
    elseif ($action === 'reset_password') {
        $token = trim($_POST['reset_token'] ?? '');
        $hashed_token = hash('sha256', $token);
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($token === '') {
            $message = 'Invalid password reset request.';
            $message_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = 'Password must contain at least 6 characters.';
            $message_type = 'warning';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Passwords do not match.';
            $message_type = 'warning';
        } else {
            $stmt = $conn->prepare(
                "SELECT id FROM customers WHERE reset_token = ? AND reset_expires IS NOT NULL AND reset_expires > NOW() LIMIT 1"
            );
            $stmt->bind_param("s", $hashed_token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $user_id = (int)$user['id'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update = $conn->prepare(
                    "UPDATE customers SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?"
                );
                $update->bind_param("si", $hashed_password, $user_id);

                if ($update->execute()) {
                    $message = 'Your password has been reset successfully. You can now login with your new password.';
                    $message_type = 'success';
                    $reset_mode = false;
                } else {
                    $message = 'Unable to reset your password. Please try again.';
                    $message_type = 'error';
                }
                $update->close();
            } else {
                $message = 'This password reset link is invalid or has expired.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>SA Design Password Reset</title>
</head>

<body style="
    margin:0;
    padding:0;
    background:#f4f7fc;
    font-family:Arial, sans-serif;
">

<div style="
    width:100%;
    padding:40px 0;
">

    <div style="
        max-width:600px;
        margin:auto;
        background:#ffffff;
        border-radius:15px;
        overflow:hidden;
        box-shadow:0 10px 30px rgba(0,0,0,0.08);
    ">

        <!-- HEADER -->

        <div style="
            background:linear-gradient(135deg,#142a83,#2854c5);
            padding:30px;
            text-align:center;
        ">

            <h1 style="
                margin:0;
                color:#ffffff;
                font-size:28px;
            ">
                SA <span style="color:#ff72bb;">DESIGN</span>
            </h1>

            <p style="
                margin:8px 0 0;
                color:#dce5ff;
                font-size:13px;
            ">
                Printing & Advertising
            </p>

        </div>


        <!-- CONTENT -->

        <div style="
            padding:35px;
        ">

            <h2 style="
                color:#142a83;
                margin-top:0;
            ">
                Password Reset Request
            </h2>


            <p style="
                color:#475569;
                font-size:14px;
                line-height:1.7;
            ">

                Hello <strong>' . htmlspecialchars($customer['name']) . '</strong>,

            </p>


            <p style="
                color:#475569;
                font-size:14px;
                line-height:1.7;
            ">

                We received a request to reset your SA Design account password.

            </p>


            <p style="
                color:#475569;
                font-size:14px;
                line-height:1.7;
            ">

                Click the button below to create a new password:

            </p>


            <!-- BUTTON -->

            <div style="
                text-align:center;
                margin:30px 0;
            ">

                <a href="' . htmlspecialchars($reset_link) . '"
                   style="
                       display:inline-block;
                       padding:14px 25px;
                       background:linear-gradient(135deg,#2854c5,#4b68d8);
                       color:#ffffff;
                       text-decoration:none;
                       border-radius:10px;
                       font-size:14px;
                       font-weight:bold;
                   ">

                    Reset My Password

                </a>

            </div>


            <p style="
                color:#64748b;
                font-size:12px;
                line-height:1.6;
            ">

                This password reset link will expire in
                <strong>1 hour</strong>.

            </p>


            <p style="
                color:#64748b;
                font-size:12px;
                line-height:1.6;
            ">

                If you did not request a password reset, you can safely
                ignore this email.

            </p>


            <hr style="
                border:0;
                border-top:1px solid #e2e8f0;
                margin:25px 0;
            ">


            <p style="
                color:#94a3b8;
                font-size:11px;
                line-height:1.6;
            ">

                If the button does not work, copy and paste this link
                into your browser:

            </p>


            <p style="
                color:#2854c5;
                font-size:11px;
                word-break:break-all;
            ">

                ' . htmlspecialchars($reset_link) . '

            </p>

        </div>


        <!-- FOOTER -->

        <div style="
            background:#f8fafc;
            padding:20px;
            text-align:center;
        ">

            <p style="
                margin:0;
                color:#94a3b8;
                font-size:11px;
            ">

                © ' . date('Y') . ' SA Design.
                All rights reserved.

            </p>

        </div>

    </div>

</div>

</body>
</html>
';


                        // -------------------------------------------------
                        // SEND EMAIL USING PHPMailer
                        // -------------------------------------------------

                        try {

                            $mail = new PHPMailer(true);


                            // SMTP
                            $mail->isSMTP();

                            $mail->Host =
                                $mailConfig['host'];

                            $mail->SMTPAuth = true;

                            $mail->Username =
                                $mailConfig['username'];

                            $mail->Password =
                                $mailConfig['password'];


                            // -------------------------------------------------
                            // ENCRYPTION
                            // -------------------------------------------------

                            if (
                                isset($mailConfig['encryption']) &&
                                strtolower($mailConfig['encryption']) === 'tls'
                            ) {

                                $mail->SMTPSecure =
                                    PHPMailer::ENCRYPTION_STARTTLS;

                            } else {

                                $mail->SMTPSecure =
                                    PHPMailer::ENCRYPTION_SMTPS;
                            }


                            $mail->Port =
                                $mailConfig['port'];


                            // -------------------------------------------------
                            // CHARACTER SET
                            // -------------------------------------------------

                            $mail->CharSet = 'UTF-8';


                            // -------------------------------------------------
                            // FROM
                            // -------------------------------------------------

                            $mail->setFrom(
                                $mailConfig['username'],
                                'SA Design'
                            );


                            // -------------------------------------------------
                            // TO
                            // -------------------------------------------------

                            $mail->addAddress(
                                $customer['email'],
                                $customer['name']
                            );


                            // -------------------------------------------------
                            // EMAIL FORMAT
                            // -------------------------------------------------

                            $mail->isHTML(true);

                            $mail->Subject =
                                $subject;

                            $mail->Body =
                                $email_body;


                            // Plain text fallback
                            $mail->AltBody =
                                "Hello " .
                                $customer['name'] .
                                ",\n\n" .

                                "We received a request to reset your SA Design password.\n\n" .

                                "Click the link below to reset your password:\n" .

                                $reset_link .

                                "\n\n" .

                                "This link will expire in 1 hour.\n\n" .

                                "If you did not request a password reset, you can safely ignore this email.\n\n" .

                                "Regards,\n" .
                                "SA Design";


                            // -------------------------------------------------
                            // SEND
                            // -------------------------------------------------

                            $mail->send();


                            $message =
                                'Password reset link has been sent to your email address. Please check your inbox.';

                            $message_type =
                                'success';


                        } catch (Exception $e) {

                            // If email fails, remove token
                            $clear = $conn->prepare(
                                "UPDATE customers
                                 SET reset_token = NULL,
                                     reset_expires = NULL
                                 WHERE id = ?"
                            );

                            $clear->bind_param(
                                "i",
                                $customer_id
                            );

                            $clear->execute();
                            $clear->close();


                            $message =
                                'The password reset email could not be sent. Please try again later.';

                            $message_type =
                                'error';


                            error_log(
                                'SA Design PHPMailer Error: ' .
                                $mail->ErrorInfo
                            );
                        }

                    } else {

                        $message =
                            'Unable to create password reset request. Please try again.';

                        $message_type =
                            'error';
                    }

                    $update->close();

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Do not reveal whether email exists
                    |--------------------------------------------------------------------------
                    */

                    $message =
                        'If that email is registered, a password reset link has been sent. Please check your inbox.';

                    $message_type =
                        'success';
                }

                $stmt->close();
            }
        }
    }


    // =====================================================
    // RESET PASSWORD
    // =====================================================

    elseif ($action === 'reset_password') {

        $token =
            trim($_POST['reset_token'] ?? '');

        $new_password =
            $_POST['new_password'] ?? '';

        $confirm_password =
            $_POST['confirm_password'] ?? '';


        // -------------------------------------------------
        // VALIDATION
        // -------------------------------------------------

        if ($token === '') {

            $message =
                'Invalid password reset request.';

            $message_type =
                'error';

        } elseif (strlen($new_password) < 6) {

            $message =
                'Password must contain at least 6 characters.';

            $message_type =
                'warning';

        } elseif ($new_password !== $confirm_password) {

            $message =
                'Passwords do not match.';

            $message_type =
                'warning';

        } else {


            // -------------------------------------------------
            // CHECK TOKEN
            // -------------------------------------------------

            $stmt = $conn->prepare(
                "SELECT id, email
                 FROM customers
                 WHERE reset_token = ?
                 AND reset_expires IS NOT NULL
                 AND reset_expires > NOW()
                 LIMIT 1"
            );


            $stmt->bind_param(
                "s",
                $token
            );

            $stmt->execute();

            $result =
                $stmt->get_result();


            if ($result->num_rows === 1) {

                $user =
                    $result->fetch_assoc();

                $user_id =
                    (int)$user['id'];


                // -------------------------------------------------
                // HASH NEW PASSWORD
                // -------------------------------------------------

                $hashed_password =
                    password_hash(
                        $new_password,
                        PASSWORD_DEFAULT
                    );


                // -------------------------------------------------
                // UPDATE PASSWORD
                // -------------------------------------------------

                $update = $conn->prepare(
                    "UPDATE customers
                     SET password = ?,
                         reset_token = NULL,
                         reset_expires = NULL
                     WHERE id = ?"
                );


                $update->bind_param(
                    "si",
                    $hashed_password,
                    $user_id
                );


                if ($update->execute()) {

                    $message =
                        'Your password has been reset successfully. You can now login with your new password.';

                    $message_type =
                        'success';

                    $reset_mode =
                        false;

                    $reset_token =
                        '';

                    $reset_email =
                        '';

                } else {

                    $message =
                        'Unable to reset your password. Please try again.';

                    $message_type =
                        'error';
                }


                $update->close();

            } else {

                $message =
                    'This password reset link is invalid or has expired.';

                $message_type =
                    'error';
            }


            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Forgot Password | SA Design</title>


<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap"
      rel="stylesheet">


<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}


body{

    font-family:'Plus Jakarta Sans',sans-serif;

    min-height:100vh;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:20px;

    background:
        linear-gradient(
            120deg,
            #eef4ff,
            #f8f3ff 52%,
            #fff1f7
        );

    color:#172033;
}


/* =========================================================
   CONTAINER
========================================================= */

.reset-container{

    width:min(430px,100%);

    background:#ffffff;

    border:1px solid #e2e7f1;

    border-radius:24px;

    padding:35px 30px;

    box-shadow:
        0 20px 50px rgba(28,53,118,.12);

}


/* =========================================================
   LOGO
========================================================= */

.logo{

    text-align:center;

    font-size:28px;

    font-weight:900;

    letter-spacing:-2px;

    color:#142a83;

    margin-bottom:25px;
}


.logo span{

    color:#ef3a9b;
}


/* =========================================================
   ICON
========================================================= */

.icon{

    width:65px;

    height:65px;

    margin:0 auto 18px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    background:#eef3ff;

    color:#2854c5;

    font-size:27px;
}


/* =========================================================
   TITLE
========================================================= */

h1{

    text-align:center;

    color:#142a83;

    font-size:23px;

    font-weight:900;

    margin-bottom:8px;
}


.subtitle{

    text-align:center;

    color:#64748b;

    font-size:11px;

    line-height:1.7;

    margin-bottom:25px;
}


/* =========================================================
   INPUT
========================================================= */

.input-group{

    margin-bottom:16px;
}


.input-group label{

    display:block;

    margin-bottom:7px;

    color:#334155;

    font-size:10px;

    font-weight:800;
}


.input-group input{

    width:100%;

    padding:12px;

    border:1px solid #dce2ef;

    border-radius:10px;

    outline:none;

    background:#fbfcff;

    font-family:inherit;

    font-size:11px;
}


.input-group input:focus{

    border-color:#2854c5;

    box-shadow:
        0 0 0 3px rgba(40,84,197,.08);
}


/* =========================================================
   BUTTON
========================================================= */

.submit{

    width:100%;

    border:0;

    border-radius:11px;

    padding:13px;

    background:
        linear-gradient(
            135deg,
            #2854c5,
            #4b68d8
        );

    color:#ffffff;

    font-size:11px;

    font-weight:800;

    cursor:pointer;

    box-shadow:
        0 9px 20px rgba(40,84,197,.18);

    transition:.25s;
}


.submit:hover{

    transform:translateY(-2px);

    background:
        linear-gradient(
            135deg,
            #ef3a9b,
            #f45689
        );
}


/* =========================================================
   BACK LINK
========================================================= */

.back-link{

    display:block;

    margin-top:18px;

    text-align:center;

    color:#2854c5;

    font-size:10px;

    font-weight:800;

    text-decoration:none;
}


.back-link:hover{

    color:#ef3a9b;
}


/* =========================================================
   MESSAGE
========================================================= */

.message{

    padding:12px;

    border-radius:10px;

    margin-bottom:20px;

    font-size:11px;

    line-height:1.6;

    text-align:center;
}


.message.success{

    background:#ecfdf5;

    color:#047857;

    border:1px solid #a7f3d0;
}


.message.warning{

    background:#fffbeb;

    color:#b45309;

    border:1px solid #fde68a;
}


.message.error{

    background:#fff1f2;

    color:#be123c;

    border:1px solid #fecdd3;
}


/* =========================================================
   PASSWORD
========================================================= */

.password-wrap{

    position:relative;
}


.password-wrap input{

    padding-right:45px;
}


.password-toggle{

    position:absolute;

    top:50%;

    right:10px;

    transform:translateY(-50%);

    border:0;

    background:transparent;

    color:#2854c5;

    cursor:pointer;

    width:30px;

    height:30px;
}


.note{

    margin-top:14px;

    text-align:center;

    color:#94a3b8;

    font-size:9px;

    line-height:1.6;
}

</style>

</head>


<body>


<div class="reset-container">


    <!-- LOGO -->

    <div class="logo">

        <span>SA</span> DESIGN

    </div>


    <?php if (!empty($message)): ?>

        <div class="message <?= htmlspecialchars($message_type) ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <?php if ($reset_mode): ?>


        <!-- =================================================
             RESET PASSWORD
        ================================================== -->

        <div class="icon">

            <i class="fa-solid fa-key"></i>

        </div>


        <h1>

            Reset Your Password

        </h1>


        <p class="subtitle">

            Create a new password for your SA Design account.

            <br>

            <strong>
                <?= htmlspecialchars($reset_email) ?>
            </strong>

        </p>


        <form method="POST">

            <input type="hidden"
                   name="action"
                   value="reset_password">


            <input type="hidden"
                   name="reset_token"
                   value="<?= htmlspecialchars($reset_token) ?>">


            <div class="input-group">

                <label for="new_password">

                    NEW PASSWORD

                </label>


                <div class="password-wrap">

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="Enter new password"
                        minlength="6"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('new_password', this)"
                    >

                        <i class="fa-regular fa-eye"></i>

                    </button>

                </div>

            </div>


            <div class="input-group">

                <label for="confirm_password">

                    CONFIRM PASSWORD

                </label>


                <div class="password-wrap">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        minlength="6"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('confirm_password', this)"
                    >

                        <i class="fa-regular fa-eye"></i>

                    </button>

                </div>

            </div>


            <button type="submit"
                    class="submit">

                <i class="fa-solid fa-lock"></i>

                RESET PASSWORD

            </button>


        </form>


        <p class="note">

            Password must contain at least 6 characters.

        </p>


    <?php else: ?>


        <!-- =================================================
             FORGOT PASSWORD
        ================================================== -->

        <div class="icon">

            <i class="fa-solid fa-lock"></i>

        </div>


        <h1>

            Forgot Password?

        </h1>


        <p class="subtitle">

            Enter your registered email address and
            we'll send you a password reset link.

        </p>


        <form method="POST">

            <input type="hidden"
                   name="action"
                   value="forgot_password">


            <div class="input-group">

                <label for="email">

                    EMAIL ADDRESS

                </label>


                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your registered email"
                    required
                >

            </div>


            <button type="submit"
                    class="submit">

                <i class="fa-solid fa-paper-plane"></i>

                SEND RESET LINK

            </button>


        </form>


        <a href="login.php"
           class="back-link">

            <i class="fa-solid fa-arrow-left"></i>

            Back to Login

        </a>


    <?php endif; ?>


</div>


<script>

function togglePassword(id, button){

    const input =
        document.getElementById(id);

    const icon =
        button.querySelector('i');


    if(input.type === 'password'){

        input.type = 'text';

        icon.className =
            'fa-regular fa-eye-slash';

    }else{

        input.type = 'password';

        icon.className =
            'fa-regular fa-eye';

    }

}

</script>


</body>

</html>