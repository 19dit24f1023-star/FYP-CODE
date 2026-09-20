<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

session_start();

include 'db.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$mailConfig = require __DIR__ . '/mail_config.php';

$message = '';
$message_type = 'error';
$active_tab = 'login';
$auth_redirect = '';
$selected_role = ($_GET['role'] ?? 'customer') === 'admin'
    ? 'admin'
    : 'customer';

$reset_mode = false;
$reset_token = '';
$reset_email = '';
$reset_role = 'customer';

$base_url = $mailConfig['base_url'] ?? 'http://localhost/Fyp/';

// =========================================================
// HELPER: SEND PASSWORD RESET EMAIL
// =========================================================
function sendPasswordResetEmail($mailConfig, $user, $resetLink, $role)
{
    $requiredKeys = ['host', 'username', 'password', 'port'];
    foreach ($requiredKeys as $key) {
        if (empty($mailConfig[$key])) {
            throw new RuntimeException("Missing mail configuration: {$key}");
        }
    }

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $mailConfig['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $mailConfig['username'];
    $mail->Password = $mailConfig['password'];
    $mail->Port = $mailConfig['port'];
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    $encryption = strtolower($mailConfig['encryption'] ?? 'ssl');
    if ($encryption === 'tls' || $encryption === 'starttls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($encryption === 'ssl' || $encryption === 'smtps') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        throw new RuntimeException('SMTP encryption must be ssl or tls.');
    }

    $mail->setFrom($mailConfig['username'], $mailConfig['from_name'] ?? 'SA Design');
    $mail->addAddress($user['email'], $user['name']);
    $mail->isHTML(false);

    $isAdmin = $role === 'admin';
    $mail->Subject = $isAdmin
        ? 'SA Design - Admin Password Reset'
        : 'SA Design - Password Reset';
    $name = $user['name'] ?: 'User';
    $accountLabel = $isAdmin ? 'administrator ' : '';
    $mail->Body =
        "Hello {$name},\n\n" .
        "We received a request to reset your SA Design {$accountLabel}password.\n\n" .
        "Click the link below to create a new password:\n" .
        $resetLink . "\n\n" .
        "This link will expire in 1 hour.\n\n" .
        "If you did not request a password reset, you can safely ignore this email.\n\n" .
        "Regards,\nSA Design";

    $mail->send();
}

// =========================================================
// PASSWORD RESET - VERIFY TOKEN
// =========================================================
if (isset($_GET['reset']) && $_GET['reset'] !== '') {

    $reset_token = trim($_GET['reset']);
    $reset_role = ($_GET['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';

    if ($reset_role === 'admin') {
        $stmt = $conn->prepare(
            "SELECT id, email
             FROM admin
             WHERE reset_token = ?
             AND reset_expires IS NOT NULL
             AND reset_expires > NOW()
             LIMIT 1"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT id, email
             FROM customers
             WHERE reset_token = ?
             AND reset_expires IS NOT NULL
             AND reset_expires > NOW()
             LIMIT 1"
        );
    }

    if ($stmt) {
        $stmt->bind_param('s', $reset_token);
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
// FLASH NOTIFICATION
// =========================================================
if (!empty($_SESSION['auth_notice'])) {

    $message = $_SESSION['auth_notice'];
    $message_type = $_SESSION['auth_notice_type'] ?? 'success';

    $active_tab = $_SESSION['auth_active_tab'] ?? 'login';
    $selected_role = $_SESSION['auth_role'] ?? $selected_role;

    // Simpan destination untuk success popup
    $auth_redirect = $_SESSION['auth_redirect'] ?? '';

    unset(
        $_SESSION['auth_notice'],
        $_SESSION['auth_notice_type'],
        $_SESSION['auth_active_tab'],
        $_SESSION['auth_role'],
        $_SESSION['auth_redirect']
    );
}

// =========================================================
// POST REQUEST
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $role = ($_POST['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';

    // =====================================================
    // FORGOT PASSWORD
    // =====================================================
    if ($action === 'forgot_password') {

        $email = trim($_POST['email'] ?? '');
        $active_tab = 'login';
        $selected_role = $role;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'warning';
        } else {

            if ($role === 'admin') {
                $stmt = $conn->prepare(
                    "SELECT id, name, email
                     FROM admin
                     WHERE LOWER(email) = LOWER(?)
                     LIMIT 1"
                );
            } else {
                $stmt = $conn->prepare(
                    "SELECT id, name, email
                     FROM customers
                     WHERE LOWER(email) = LOWER(?)
                     LIMIT 1"
                );
            }

            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 3600);
                    $user_id = (int)$user['id'];

                    if ($role === 'admin') {
                        $update = $conn->prepare(
                            "UPDATE admin
                             SET reset_token = ?, reset_expires = ?
                             WHERE id = ?"
                        );
                    } else {
                        $update = $conn->prepare(
                            "UPDATE customers
                             SET reset_token = ?, reset_expires = ?
                             WHERE id = ?"
                        );
                    }

                    if ($update) {
                        $update->bind_param('ssi', $token, $expires, $user_id);

                        if ($update->execute()) {
                            // Password-reset UI is rendered by login.php; the site root is now index.php.
                            $reset_link = rtrim($base_url, '/') . '/login.php?reset=' . urlencode($token) . '&role=' . urlencode($role);

                            try {
                                sendPasswordResetEmail($mailConfig, $user, $reset_link, $role);
                                $message = 'A password reset link has been sent to your email address. Please check your inbox.';
                                $message_type = 'success';
                            } catch (Throwable $e) {
                                $message = 'The reset link was created, but the email could not be sent. Please check the SMTP settings.';
                                $message_type = 'error';
                                error_log('PHPMailer Error: ' . $e->getMessage());
                            }
                        } else {
                            $message = 'Unable to create a password reset request. Please try again.';
                            $message_type = 'error';
                        }

                        $update->close();
                    } else {
                        $message = 'Unable to create a password reset request. Please try again.';
                        $message_type = 'error';
                    }
                } else {
                    // Do not reveal whether the email exists.
                    $message = 'If that email is registered, a password reset link has been sent. Please check your inbox.';
                    $message_type = 'success';
                }

                $stmt->close();
            }
        }
    }

    // =====================================================
    // RESET PASSWORD
    // =====================================================
    elseif ($action === 'reset_password') {

        $token = trim($_POST['reset_token'] ?? '');
        $reset_role = ($_POST['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';
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

            if ($reset_role === 'admin') {
                $stmt = $conn->prepare(
                    "SELECT id
                     FROM admin
                     WHERE reset_token = ?
                     AND reset_expires IS NOT NULL
                     AND reset_expires > NOW()
                     LIMIT 1"
                );
            } else {
                $stmt = $conn->prepare(
                    "SELECT id
                     FROM customers
                     WHERE reset_token = ?
                     AND reset_expires IS NOT NULL
                     AND reset_expires > NOW()
                     LIMIT 1"
                );
            }

            if ($stmt) {
                $stmt->bind_param('s', $token);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $reset_user = $result->fetch_assoc();
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $user_id = (int)$reset_user['id'];

                    if ($reset_role === 'admin') {
                        $update = $conn->prepare(
                            "UPDATE admin
                             SET password = ?, reset_token = NULL, reset_expires = NULL
                             WHERE id = ?"
                        );
                    } else {
                        $update = $conn->prepare(
                            "UPDATE customers
                             SET password = ?, reset_token = NULL, reset_expires = NULL
                             WHERE id = ?"
                        );
                    }

                    if ($update) {
                        $update->bind_param('si', $hashed_password, $user_id);

                        if ($update->execute()) {
                            $message = $reset_role === 'admin'
                                ? 'Admin password has been reset successfully. You can now login as Admin.'
                                : 'Your password has been reset successfully. You can now login with your new password.';
                            $message_type = 'success';
                            $reset_mode = false;
                            $reset_token = '';
                            $reset_email = '';
                            $selected_role = $reset_role;
                            $active_tab = 'login';
                        } else {
                            $message = 'Unable to reset your password. Please try again.';
                            $message_type = 'error';
                        }

                        $update->close();
                    }
                } else {
                    $message = 'This password reset link is invalid or has expired.';
                    $message_type = 'error';
                }

                $stmt->close();
            }
        }
    }

    // =====================================================
    // LOGIN
    // =====================================================
    elseif ($action === 'login') {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $selected_role = $role;
        $active_tab = 'login';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'warning';
        } elseif ($password === '') {
            $message = 'Please enter your password.';
            $message_type = 'warning';
        } else {

            // =================================================
            // ADMIN LOGIN
            // =================================================
            if ($role === 'admin') {

                $stmt = $conn->prepare(
                    "SELECT id, name, email, password
                     FROM admin
                     WHERE LOWER(email) = LOWER(?)
                     LIMIT 1"
                );

                if ($stmt) {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $admin = $result->fetch_assoc();

                        // Supports old plain-text admin passwords once.
                        $password_ok = password_verify($password, $admin['password']);

                        if (!$password_ok && hash_equals((string)$admin['password'], (string)$password)) {
                            $password_ok = true;

                            // Upgrade old plain-text password to a secure hash.
                            $new_hash = password_hash($password, PASSWORD_DEFAULT);
                            $upgrade = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
                            if ($upgrade) {
                                $admin_id = (int)$admin['id'];
                                $upgrade->bind_param('si', $new_hash, $admin_id);
                                $upgrade->execute();
                                $upgrade->close();
                            }
                        }

                        if ($password_ok) {
                            session_regenerate_id(true);

                            unset(
                                $_SESSION['customer_id'],
                                $_SESSION['user_id'],
                                $_SESSION['fullname'],
                                $_SESSION['user_email'],
                                $_SESSION['user_role'],
                                $_SESSION['is_admin']
                            );

                            $_SESSION['user_id'] = (int)$admin['id'];
                            $_SESSION['admin_id'] = (int)$admin['id'];
                            $_SESSION['fullname'] = $admin['name'];
                            $_SESSION['user_email'] = $admin['email'];
                            $_SESSION['is_logged_in'] = true;
                            $_SESSION['user_role'] = 'admin';
                            $_SESSION['is_admin'] = true;

                            $_SESSION['auth_notice'] = 'Welcome back, ' . $admin['name'] . '! You are now logged in as Admin.';
                            $_SESSION['auth_notice_type'] = 'success';
                            $_SESSION['auth_redirect'] = 'admin_dashboard.php';

                            $stmt->close();

                            header('Location: login.php?role=admin');
                            exit();
                        } else {
                            $message = 'Wrong admin password. Please try again.';
                            $message_type = 'error';
                        }
                    } else {
                        $message = 'Admin email cannot be found. Please register an admin account first.';
                        $message_type = 'error';
                    }

                    $stmt->close();
                }
            }

            // =================================================
            // CUSTOMER LOGIN
            // =================================================
            else {

                $stmt = $conn->prepare(
                    "SELECT id, name, email, password
                     FROM customers
                     WHERE LOWER(email) = LOWER(?)
                     LIMIT 1"
                );

                if ($stmt) {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();

                        if (password_verify($password, $user['password'])) {
                            session_regenerate_id(true);

                            unset(
                                $_SESSION['admin_id'],
                                $_SESSION['is_admin']
                            );

                            $_SESSION['user_id'] = (int)$user['id'];
                            $_SESSION['customer_id'] = (int)$user['id'];
                            $_SESSION['fullname'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['is_logged_in'] = true;
                            $_SESSION['user_role'] = 'customer';
                            $_SESSION['is_admin'] = false;

                            $_SESSION['auth_notice'] = 'Welcome back, ' . $user['name'] . '! You are now logged in.';
                            $_SESSION['auth_notice_type'] = 'success';
                            $_SESSION['auth_redirect'] = 'index.php';

                            $stmt->close();

                            header('Location: login.php');
                            exit();
                        } else {
                            $message = 'Wrong password. Please try again.';
                            $message_type = 'error';
                        }
                    } else {
                        $message = 'Email cannot be found. Please check your email or register.';
                        $message_type = 'error';
                    }

                    $stmt->close();
                }
            }
        }
    }

    // =====================================================
    // REGISTER
    // =====================================================
    elseif ($action === 'signup') {

        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone_number = trim($_POST['phone_number'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $active_tab = 'signup';
        $selected_role = $role;

        if ($fullname === '' || mb_strlen($fullname) < 2) {
            $message = 'Please enter your full name.';
            $message_type = 'warning';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'warning';
        } elseif (strlen($password) < 6) {
            $message = 'Password must contain at least 6 characters.';
            $message_type = 'warning';
        }

        // =================================================
        // ADMIN REGISTER
        // =================================================
        elseif ($role === 'admin') {

            $admin_count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM admin");

            if ($admin_count_stmt) {
                $admin_count_stmt->execute();
                $admin_count_result = $admin_count_stmt->get_result();
                $admin_count = (int)$admin_count_result->fetch_assoc()['total'];
                $admin_count_stmt->close();
            } else {
                $admin_count = 0;
            }

            if ($admin_count >= 1) {
                $message = 'An admin account already exists. Only one admin account is allowed.';
                $message_type = 'error';
            } else {

                $check_stmt = $conn->prepare(
                    "SELECT id FROM admin WHERE LOWER(email) = LOWER(?) LIMIT 1"
                );

                if ($check_stmt) {
                    $check_stmt->bind_param('s', $email);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result->num_rows > 0) {
                        $message = 'This email is already registered as an admin.';
                        $message_type = 'warning';
                    } else {

                        // Admin passwords are now stored securely using password_hash().
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        $stmt = $conn->prepare(
                            "INSERT INTO admin (name, email, password)
                             VALUES (?, ?, ?)"
                        );

                        if ($stmt) {
                            $stmt->bind_param('sss', $fullname, $email, $hashed_password);

                            if ($stmt->execute()) {
                                unset(
                                    $_SESSION['user_id'],
                                    $_SESSION['admin_id'],
                                    $_SESSION['customer_id'],
                                    $_SESSION['fullname'],
                                    $_SESSION['user_email'],
                                    $_SESSION['is_logged_in'],
                                    $_SESSION['user_role'],
                                    $_SESSION['is_admin']
                                );

                                $_SESSION['auth_notice'] = 'Admin account created successfully! Please login as Admin.';
                                $_SESSION['auth_notice_type'] = 'success';
                                $_SESSION['auth_active_tab'] = 'login';
                                $_SESSION['auth_role'] = 'admin';

                                $stmt->close();
                                $check_stmt->close();

                                header('Location: login.php');
                                exit();
                            } else {
                                $message = 'Admin registration failed. Please try again.';
                                $message_type = 'error';
                            }

                            $stmt->close();
                        }
                    }

                    $check_stmt->close();
                }
            }
        }

        // =================================================
        // CUSTOMER REGISTER
        // =================================================
        else {

            if ($phone_number === '') {
                $message = 'Please enter your phone number.';
                $message_type = 'warning';
            } elseif ($address === '') {
                $message = 'Please enter your address.';
                $message_type = 'warning';
            } else {

                $check_stmt = $conn->prepare(
                    "SELECT id FROM customers WHERE LOWER(email) = LOWER(?) LIMIT 1"
                );

                if ($check_stmt) {
                    $check_stmt->bind_param('s', $email);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result->num_rows > 0) {
                        $message = 'This email address is already registered as a customer. Please login.';
                        $message_type = 'warning';
                        $active_tab = 'login';
                    } else {

                        $admin_email_stmt = $conn->prepare(
                            "SELECT id FROM admin WHERE LOWER(email) = LOWER(?) LIMIT 1"
                        );

                        if ($admin_email_stmt) {
                            $admin_email_stmt->bind_param('s', $email);
                            $admin_email_stmt->execute();
                            $admin_email_result = $admin_email_stmt->get_result();

                            if ($admin_email_result->num_rows > 0) {
                                $message = 'This email is already registered as an admin. Please use Admin Login.';
                                $message_type = 'warning';
                                $active_tab = 'login';
                                $selected_role = 'admin';
                            } else {

                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                                $stmt = $conn->prepare(
                                    "INSERT INTO customers
                                    (name, email, password, phone_number, address)
                                    VALUES (?, ?, ?, ?, ?)"
                                );

                                if ($stmt) {
                                    $stmt->bind_param(
                                        'sssss',
                                        $fullname,
                                        $email,
                                        $hashed_password,
                                        $phone_number,
                                        $address
                                    );

                                    if ($stmt->execute()) {
                                        $_SESSION['auth_notice'] = 'Account created successfully! Please login to continue.';
                                        $_SESSION['auth_notice_type'] = 'success';
                                        $_SESSION['auth_active_tab'] = 'login';
                                        $_SESSION['auth_role'] = 'customer';

                                        $stmt->close();
                                        $admin_email_stmt->close();
                                        $check_stmt->close();

                                        header('Location: login.php');
                                        exit();
                                    } else {
                                        $message = 'Registration failed. Please try again.';
                                        $message_type = 'error';
                                    }

                                    $stmt->close();
                                }
                            }

                            $admin_email_stmt->close();
                        }
                    }

                    $check_stmt->close();
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Login / Register | SA Design</title>


<!-- Font Awesome -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
>


<!-- Google Font -->

<link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   RESET
========================================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}


/* =========================================================
   BODY
========================================================= */

body{

    font-family:'Plus Jakarta Sans',sans-serif;

    min-height:100vh;

    background:
        linear-gradient(
            120deg,
            #eef4ff,
            #f8f3ff 52%,
            #fff1f7
        );

    color:#172033;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:28px;
}


/* =========================================================
   MAIN WRAPPER
========================================================= */

.auth-wrapper{

    width:min(1050px,100%);

    display:grid;

    grid-template-columns:1fr 1fr;

    background:#fff;

    border:1px solid #e2e7f1;

    border-radius:26px;

    overflow:hidden;

    box-shadow:
        0 20px 50px rgba(28,53,118,.10);
}


/* =========================================================
   BRAND PANEL
========================================================= */

.brand-panel{

    position:relative;

    overflow:hidden;

    min-height:650px;

    padding:48px;

    display:flex;

    flex-direction:column;

    justify-content:space-between;

    background:
        linear-gradient(
            145deg,
            #142a83,
            #2854c5 60%,
            #4b68d8
        );

    color:#fff;
}


.brand-panel:before,
.brand-panel:after{

    content:"";

    position:absolute;

    border-radius:50%;
}


.brand-panel:before{

    width:330px;

    height:330px;

    right:-150px;

    top:-130px;

    background:
        rgba(239,58,155,.28);
}


.brand-panel:after{

    width:260px;

    height:260px;

    left:-150px;

    bottom:-120px;

    background:
        rgba(255,255,255,.08);
}


/* =========================================================
   LOGO
========================================================= */

.logo{

    position:relative;

    z-index:2;

    font-size:31px;

    font-weight:900;

    letter-spacing:-2px;
}


.logo span{

    color:#ef3a9b;
}


/* =========================================================
   BRAND CONTENT
========================================================= */

.brand-content{

    position:relative;

    z-index:2;

    max-width:390px;
}


.badge{

    display:inline-flex;

    gap:7px;

    align-items:center;

    padding:8px 13px;

    border-radius:999px;

    background:
        rgba(255,255,255,.12);

    border:
        1px solid rgba(255,255,255,.2);

    font-size:10px;

    font-weight:800;

    letter-spacing:.7px;
}


.brand-content h1{

    font-size:42px;

    line-height:1.08;

    letter-spacing:-2px;

    margin:18px 0 13px;

    font-weight:900;
}


.brand-content h1 span{

    color:#ff72bb;
}


.brand-content p{

    font-size:12px;

    line-height:1.8;

    color:
        rgba(255,255,255,.78);
}


/* =========================================================
   FEATURES
========================================================= */

.features{

    position:relative;

    z-index:2;

    display:grid;

    gap:10px;
}


.feature{

    display:flex;

    align-items:center;

    gap:10px;

    font-size:11px;

    font-weight:700;

    color:
        rgba(255,255,255,.9);
}


.feature i{

    width:28px;

    height:28px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:9px;

    background:
        rgba(255,255,255,.12);

    color:#ff72bb;
}


/* =========================================================
   FORM PANEL
========================================================= */

.form-panel{

    padding:42px;

    display:flex;

    flex-direction:column;

    justify-content:center;
}


/* =========================================================
   TOP LINK
========================================================= */

.top-link{

    align-self:flex-end;

    color:#64748b;

    font-size:10px;

    font-weight:800;

    text-transform:uppercase;

    margin-bottom:20px;

    text-decoration:none;
}


.top-link:hover{

    color:#ef3a9b;
}


/* =========================================================
   LOGIN / REGISTER SWITCH
========================================================= */

.switch{

    display:flex;

    padding:4px;

    background:#eef3ff;

    border:1px solid #dce5ff;

    border-radius:999px;

    margin-bottom:20px;
}


.switch button{

    flex:1;

    border:0;

    background:transparent;

    padding:11px;

    border-radius:999px;

    color:#64748b;

    font-size:12px;

    font-weight:800;

    cursor:pointer;
}


.switch button.active{

    background:
        linear-gradient(
            135deg,
            #ef3a9b,
            #f45689
        );

    color:#fff;

    box-shadow:
        0 8px 18px rgba(239,58,155,.2);
}

/* =========================================================
   USER ICON
========================================================= */

.user-icon{

    width:58px;

    height:58px;

    margin:0 auto 13px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    background:
        linear-gradient(
            135deg,
            #2854c5,
            #4b68d8
        );

    color:#fff;

    font-size:21px;

    box-shadow:
        0 10px 22px rgba(40,84,197,.18);
}


/* =========================================================
   TITLE
========================================================= */

.title{

    text-align:center;

    color:#142a83;

    font-size:23px;

    font-weight:900;

    margin-bottom:5px;
}


.subtitle{

    text-align:center;

    color:#64748b;

    font-size:11px;

    margin-bottom:20px;

    line-height:1.6;
}


/* =========================================================
   HIDDEN
========================================================= */

.hidden{

    display:none!important;
}


/* =========================================================
   FORM
========================================================= */

.input-group{

    margin-bottom:13px;
}


.input-group label{

    display:block;

    margin-bottom:6px;

    color:#334155;

    font-size:10px;

    font-weight:800;
}


.input-group input,
.input-group textarea{

    width:100%;

    padding:11px 12px;

    border:1px solid #dce2ef;

    border-radius:10px;

    outline:0;

    background:#fbfcff;

    color:#172033;

    font-size:11px;
}


.input-group textarea{

    resize:vertical;

    min-height:72px;
}


.input-group input:focus,
.input-group textarea:focus{

    border-color:#2854c5;

    box-shadow:
        0 0 0 3px rgba(40,84,197,.08);
}


/* =========================================================
   LOGIN OPTIONS
========================================================= */

.login-options{

    display:flex;

    align-items:center;

    justify-content:space-between;

    margin:3px 0 15px;
}


/* =========================================================
   FORGOT LINK
========================================================= */

.forgot-link{

    color:#2854c5;

    font-size:10px;

    font-weight:800;

    text-decoration:none;

    cursor:pointer;

    transition:.2s;
}


.forgot-link:hover{

    color:#ef3a9b;
}


/* =========================================================
   SUBMIT BUTTON
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

    color:#fff;

    font-size:11px;

    font-weight:800;

    letter-spacing:.05em;

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
   NOTE
========================================================= */

.note{

    margin-top:13px;

    text-align:center;

    color:#94a3b8;

    font-size:9px;
}


/* =========================================================
   PASSWORD
========================================================= */

.password-wrap{

    position:relative;
}


.password-wrap input{

    padding-right:44px;
}


.password-toggle{

    position:absolute;

    top:50%;

    right:12px;

    transform:translateY(-50%);

    width:28px;

    height:28px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:0;

    background:transparent;

    color:#2854c5;

    font-size:14px;

    cursor:pointer;

    transition:.2s;
}


.password-toggle:hover{

    color:#ef3a9b;
}


/* =========================================================
   GENERAL AUTH POPUP
========================================================= */

.auth-popup-overlay{

    position:fixed;

    inset:0;

    z-index:99999;

    background:
        rgba(15,23,42,.58);

    backdrop-filter:blur(6px);

    display:flex;

    align-items:center;

    justify-content:center;

    padding:20px;

    opacity:1;

    visibility:visible;
}


.auth-popup{

    width:min(400px,100%);

    background:#fff;

    border-radius:22px;

    padding:30px 25px 24px;

    text-align:center;

    box-shadow:
        0 25px 70px rgba(15,23,42,.28);

    animation:
        authPopupIn .28s ease;
}


.auth-popup-icon{

    width:64px;

    height:64px;

    margin:0 auto 15px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#ecfdf5;

    color:#10b981;

    font-size:28px;
}


.auth-popup.warning .auth-popup-icon{

    background:#fffbeb;

    color:#f59e0b;
}


.auth-popup.error .auth-popup-icon{

    background:#fff1f2;

    color:#ef4444;
}


.auth-popup h3{

    margin:0 0 8px;

    color:#142a83;

    font-size:20px;

    font-weight:900;
}


.auth-popup p{

    margin:0 auto 20px;

    max-width:310px;

    color:#64748b;

    font-size:12px;

    line-height:1.65;
}


.auth-popup-btn{

    width:100%;

    height:44px;

    border:0;

    border-radius:11px;

    background:
        linear-gradient(
            135deg,
            #2854c5,
            #4b68d8
        );

    color:#fff;

    font-size:12px;

    font-weight:800;

    cursor:pointer;

    transition:.2s;
}


.auth-popup-btn:hover{

    transform:translateY(-2px);

    background:
        linear-gradient(
            135deg,
            #ef3a9b,
            #f45689
        );
}


/* =========================================================
   FORGOT PASSWORD POPUP
========================================================= */

.forgot-popup-overlay{

    position:fixed;

    inset:0;

    z-index:100000;

    background:
        rgba(15,23,42,.58);

    backdrop-filter:blur(6px);

    display:flex;

    align-items:center;

    justify-content:center;

    padding:20px;

    opacity:0;

    visibility:hidden;

    transition:.25s ease;
}


.forgot-popup-overlay.show{

    opacity:1;

    visibility:visible;
}


.forgot-popup{

    position:relative;

    width:min(400px,100%);

    background:#fff;

    border-radius:22px;

    padding:30px 25px 25px;

    text-align:center;

    box-shadow:
        0 25px 70px rgba(15,23,42,.28);

    transform:
        translateY(15px)
        scale(.96);

    transition:.28s ease;
}


.forgot-popup-overlay.show
.forgot-popup{

    transform:
        translateY(0)
        scale(1);
}


.forgot-popup-icon{

    width:64px;

    height:64px;

    margin:0 auto 15px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#eef3ff;

    color:#2854c5;

    font-size:27px;
}


.forgot-popup h3{

    margin:0 0 8px;

    color:#142a83;

    font-size:20px;

    font-weight:900;
}


.forgot-popup p{

    margin:0 auto 20px;

    max-width:310px;

    color:#64748b;

    font-size:12px;

    line-height:1.65;
}


.forgot-popup .input-group{

    text-align:left;

    margin-bottom:16px;
}


/* =========================================================
   FORGOT BUTTONS
========================================================= */

.forgot-popup-buttons{

    display:flex;

    gap:10px;
}


.forgot-cancel-btn,
.forgot-submit-btn{

    flex:1;

    height:44px;

    border-radius:11px;

    font-size:11px;

    font-weight:800;

    cursor:pointer;

    transition:.2s;
}


.forgot-cancel-btn{

    border:1px solid #dce2ef;

    background:#f8fafc;

    color:#64748b;
}


.forgot-cancel-btn:hover{

    background:#eef3ff;

    color:#2854c5;
}


.forgot-submit-btn{

    border:0;

    background:
        linear-gradient(
            135deg,
            #2854c5,
            #4b68d8
        );

    color:#fff;

    box-shadow:
        0 9px 20px rgba(40,84,197,.18);
}


.forgot-submit-btn:hover{

    transform:translateY(-2px);

    background:
        linear-gradient(
            135deg,
            #ef3a9b,
            #f45689
        );
}


/* =========================================================
   FORGOT CLOSE
========================================================= */

.forgot-close{

    position:absolute;

    top:15px;

    right:17px;

    width:30px;

    height:30px;

    border:0;

    background:transparent;

    color:#94a3b8;

    font-size:16px;

    cursor:pointer;
}


.forgot-close:hover{

    color:#ef3a9b;
}


/* =========================================================
   RESET PASSWORD POPUP
========================================================= */

.reset-page-overlay{

    position:fixed;

    inset:0;

    z-index:100001;

    background:
        rgba(15,23,42,.58);

    backdrop-filter:blur(6px);

    display:flex;

    align-items:center;

    justify-content:center;

    padding:20px;
}


.reset-popup{

    width:min(430px,100%);

    background:#fff;

    border-radius:22px;

    padding:30px 25px 25px;

    text-align:center;

    box-shadow:
        0 25px 70px rgba(15,23,42,.28);

    animation:
        authPopupIn .28s ease;
}


.reset-popup h3{

    margin:0 0 8px;

    color:#142a83;

    font-size:20px;

    font-weight:900;
}


.reset-popup p{

    margin:0 auto 20px;

    max-width:330px;

    color:#64748b;

    font-size:12px;

    line-height:1.65;
}


.reset-popup p strong{

    color:#2854c5;

    word-break:break-word;
}


/* =========================================================
   ANIMATION
========================================================= */

@keyframes authPopupIn{

    from{

        opacity:0;

        transform:
            translateY(15px)
            scale(.96);
    }

    to{

        opacity:1;

        transform:
            translateY(0)
            scale(1);
    }
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:800px){

    body{

        padding:16px;
    }


    .auth-wrapper{

        grid-template-columns:1fr;

        max-width:520px;
    }


    .brand-panel{

        min-height:290px;

        padding:30px;
    }


    .brand-content h1{

        font-size:32px;
    }


    .features{

        display:none;
    }


    .form-panel{

        padding:30px 24px;
    }
}


@media(max-width:480px){

    .brand-panel{

        min-height:255px;

        padding:24px;
    }


    .brand-content h1{

        font-size:29px;
    }


    .form-panel{

        padding:25px 18px;
    }


    .forgot-popup-buttons{

        flex-direction:column;
    }
}

/* =========================================================
   ADMIN LOGIN LINK
========================================================= */

.admin-login-link{
    display:block;
    margin-top:13px;
    text-align:center;
    color:#2854c5;
    font-size:10px;
    font-weight:800;
    text-decoration:none;
    transition:.2s;
}

.admin-login-link:hover{
    color:#ef3a9b;
}
</style>

<link rel="stylesheet" href="ui_polish.css">
</head>


<body>


<!-- =========================================================
     GENERAL MESSAGE POPUP
========================================================= -->

<?php if (!empty($message)): ?>

<div
    class="auth-popup-overlay"
    id="authPopupOverlay"
>

    <div
        class="auth-popup <?= htmlspecialchars($message_type) ?>"
    >

        <div class="auth-popup-icon">

            <?php if ($message_type === 'success'): ?>

                <i class="fa-solid fa-circle-check"></i>

            <?php elseif ($message_type === 'warning'): ?>

                <i class="fa-solid fa-triangle-exclamation"></i>

            <?php else: ?>

                <i class="fa-solid fa-circle-xmark"></i>

            <?php endif; ?>

        </div>


        <h3>

            <?php

            echo $message_type === 'success'
                ? 'Success!'
                : (
                    $message_type === 'warning'
                    ? 'Please Note'
                    : 'Login Failed'
                );

            ?>

        </h3>


        <p>

            <?= htmlspecialchars($message) ?>

        </p>


        <button type="button" class="auth-popup-btn" onclick="continueAfterPopup()">
            Continue
        </button>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     FORGOT PASSWORD POPUP
========================================================= -->

<div
    class="forgot-popup-overlay"
    id="forgotPasswordOverlay"
>

    <div class="forgot-popup">


        <!-- CLOSE BUTTON -->

        <button
            type="button"
            class="forgot-close"
            onclick="closeForgotPassword()"
            aria-label="Close"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <!-- ICON -->

        <div class="forgot-popup-icon">

            <i class="fa-solid fa-lock"></i>

        </div>


        <!-- TITLE -->

        <h3>
            Forgot Password?
        </h3>


        <!-- DESCRIPTION -->

        <p>

            Enter your registered email address and
            we'll help you reset your password.

        </p>


        <!-- FORGOT PASSWORD FORM -->

        <form
            action="login.php"
            method="POST"
        >

            <input
                type="hidden"
                name="action"
                value="forgot_password"
            >

            <input
                type="hidden"
                name="role"
                id="forgot-role"
                value="<?= htmlspecialchars($selected_role) ?>"
            >


            <div class="input-group">

                <label for="forgot-email">

                    EMAIL ADDRESS

                </label>


                <input
                    type="email"
                    id="forgot-email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>


            <div class="forgot-popup-buttons">


                <!-- CANCEL -->

                <button
                    type="button"
                    class="forgot-cancel-btn"
                    onclick="closeForgotPassword()"
                >

                    Cancel

                </button>


                <!-- SEND -->

                <button
                    type="submit"
                    class="forgot-submit-btn"
                >

                    <i class="fa-solid fa-paper-plane"></i>

                    Send Reset Link

                </button>


            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     RESET PASSWORD POPUP
========================================================= -->

<?php if ($reset_mode): ?>

<div class="reset-page-overlay">

    <div class="reset-popup">


        <div class="forgot-popup-icon">

            <i class="fa-solid fa-key"></i>

        </div>


        <h3>
            Reset Your Password
        </h3>


        <p>

            Create a new password for your
            SA Design account.

            <br>

            <strong>
                <?= htmlspecialchars($reset_email) ?>
            </strong>

        </p>


        <form
            action="login.php"
            method="POST"
        >

            <input
                type="hidden"
                name="action"
                value="reset_password"
            >


            <input
                type="hidden"
                name="reset_token"
                value="<?= htmlspecialchars($reset_token) ?>"
            >

            <input
                type="hidden"
                name="role"
                value="<?= htmlspecialchars($reset_role) ?>"
            >


            <!-- NEW PASSWORD -->

            <div
                class="input-group"
                style="text-align:left;"
            >

                <label for="new-password">

                    NEW PASSWORD

                </label>


                <div class="password-wrap">

                    <input
                        type="password"
                        id="new-password"
                        name="new_password"
                        placeholder="Enter new password"
                        minlength="6"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        data-target="new-password"
                    >

                        <i class="fa-regular fa-eye"></i>

                    </button>

                </div>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div
                class="input-group"
                style="text-align:left;"
            >

                <label for="confirm-password">

                    CONFIRM PASSWORD

                </label>


                <div class="password-wrap">

                    <input
                        type="password"
                        id="confirm-password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        minlength="6"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        data-target="confirm-password"
                    >

                        <i class="fa-regular fa-eye"></i>

                    </button>

                </div>

            </div>


            <button
                type="submit"
                class="submit"
            >

                <i class="fa-solid fa-lock"></i>

                RESET PASSWORD

            </button>

        </form>


        <p class="note">

            Password must contain at least 6 characters.

        </p>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     MAIN AUTH WRAPPER
========================================================= -->

<div class="auth-wrapper">


    <!-- =====================================================
         LEFT BRAND PANEL
    ===================================================== -->

    <section class="brand-panel">


        <div class="logo">

            <span>SA</span> DESIGN

        </div>


        <div class="brand-content">


            <span class="badge">

                <i class="fa-solid fa-sparkles"></i>

                PRINTING & ADVERTISING

            </span>


            <h1>

                Welcome to
                <span>SA Design</span>

            </h1>


            <p>

                Professional printing solutions for business,
                events, school and personal needs.
                Login or create an account to continue.

            </p>

        </div>


        <div class="features">


            <div class="feature">

                <i class="fa-solid fa-print"></i>

                Quality printing solutions

            </div>


            <div class="feature">

                <i class="fa-solid fa-pen-ruler"></i>

                Custom printing made for you

            </div>


            <div class="feature">

                <i class="fa-solid fa-bag-shopping"></i>

                Easy online ordering

            </div>


        </div>

    </section>


    <!-- =====================================================
         RIGHT FORM PANEL
    ===================================================== -->

    <section class="form-panel">


        <!-- BACK HOME -->

        <a
            class="top-link"
            href="index.php"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to Home

        </a>


        <!-- =================================================
             LOGIN / REGISTER SWITCH
        ================================================= -->

        <div class="switch">


            <button
                type="button"
                class="switch-btn
                <?= $active_tab === 'login'
                    ? 'active'
                    : '' ?>"
                id="btn-login-tab"
                onclick="switchTab('login')"
            >

                LOGIN

            </button>


            <button
                type="button"
                class="switch-btn
                <?= $active_tab === 'signup'
                    ? 'active'
                    : '' ?>"
                id="btn-signup-tab"
                onclick="switchTab('signup')"
            >

                REGISTER

            </button>

        </div>


        <!-- USER ICON -->

        <div
            class="user-icon"
            id="user-icon"
        >

            <i class="fa-regular fa-user"></i>

        </div>


        <!-- TITLE -->

        <h2
            class="title"
            id="form-title"
        >

            <?= $active_tab === 'signup'
                ? 'Create Your Account'
                : 'Welcome Back'
            ?>

        </h2>


        <!-- SUBTITLE -->

        <p
            class="subtitle"
            id="form-subtitle"
        >

            <?= $active_tab === 'signup'
                ? 'Register to start using SA Design.'
                : 'Login to continue to your SA Design account.'
            ?>

        </p>


        <!-- =================================================
             LOGIN FORM
        ================================================= -->

        <div
            class="form-box
            <?= $active_tab === 'signup'
                ? 'hidden'
                : '' ?>"
            id="login-box"
        >

            <form
                action="login.php"
                method="POST"
            >


                <input
                    type="hidden"
                    name="action"
                    value="login"
                >


                <input
                    type="hidden"
                    name="role"
                    id="login-role"
                    value="<?= htmlspecialchars($selected_role) ?>"
                >


                <!-- EMAIL -->

                <div class="input-group">

                    <label for="login-email">

                        EMAIL

                    </label>


                    <input
                        type="email"
                        id="login-email"
                        name="email"
                        placeholder="Enter email"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="input-group">

                    <label for="login-password">

                        PASSWORD

                    </label>


                    <div class="password-wrap">

                        <input
                            type="password"
                            id="login-password"
                            name="password"
                            placeholder="Enter password"
                            required
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            data-target="login-password"
                        >

                            <i class="fa-regular fa-eye"></i>

                        </button>

                    </div>

                </div>


                <!-- LOGIN OPTIONS -->

                <div class="login-options">


                    <!-- IMPORTANT:
                         THIS NOW OPENS POPUP
                    -->

                    <a
                        href="javascript:void(0);"
                        class="forgot-link"
                        onclick="openForgotPassword()"
                    >

                        Forgot Password?

                    </a>

                </div>


                <!-- LOGIN BUTTON -->

                <button type="submit" class="submit">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    LOGIN
                </button>

                <a href="<?= $selected_role === 'admin' ? 'login.php?role=customer' : 'login.php?role=admin' ?>" class="admin-login-link">
                    <?php if ($selected_role === 'admin'): ?>
                        <i class="fa-solid fa-user"></i>
                        Login as Customer
                    <?php else: ?>
                        <i class="fa-solid fa-user-shield"></i>
                        Login as Admin
                    <?php endif; ?>
                </a>

                <p class="note" id="login-note">
                    Secure login for SA Design customers.
                </p>

            </form>

        </div>


        <!-- =================================================
             REGISTER FORM
        ================================================= -->

        <div
            class="form-box
            <?= $active_tab === 'login'
                ? 'hidden'
                : '' ?>"
            id="signup-box"
        >

            <form
                action="login.php"
                method="POST"
            >


                <input
                    type="hidden"
                    name="action"
                    value="signup"
                >


                <input
                    type="hidden"
                    name="role"
                    id="signup-role"
                    value="<?= htmlspecialchars($selected_role) ?>"
                >


                <!-- FULL NAME -->

                <div class="input-group">

                    <label for="signup-name">

                        FULL NAME

                    </label>


                    <input
                        type="text"
                        id="signup-name"
                        name="fullname"
                        placeholder="Enter full name"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="input-group">

                    <label for="signup-email">

                        EMAIL

                    </label>


                    <input
                        type="email"
                        id="signup-email"
                        name="email"
                        placeholder="Enter email"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="input-group">

                    <label for="signup-password">

                        PASSWORD

                    </label>


                    <div class="password-wrap">

                        <input
                            type="password"
                            id="signup-password"
                            name="password"
                            placeholder="Create password"
                            required
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            data-target="signup-password"
                        >

                            <i class="fa-regular fa-eye"></i>

                        </button>

                    </div>

                </div>


                <!-- CUSTOMER ONLY -->

                <div id="customer-fields">


                    <!-- PHONE -->

                    <div class="input-group">

                        <label for="signup-phone">

                            PHONE NUMBER

                        </label>


                        <input
                            type="text"
                            id="signup-phone"
                            name="phone_number"
                            placeholder="Enter phone number"
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="input-group">

                        <label for="signup-address">

                            ADDRESS

                        </label>


                        <textarea
                            id="signup-address"
                            name="address"
                            placeholder="Enter address"
                            rows="3"
                        ></textarea>

                    </div>

                </div>


                <!-- ADMIN NOTE -->

                <div
                    id="admin-register-note"
                    class="<?= $selected_role === 'admin'
                        ? ''
                        : 'hidden' ?>"
                >

                    <p
                        class="note"
                        style="
                            margin-bottom:15px;
                            color:#f59e0b;
                            font-weight:700;
                        "
                    >

                        <i class="fa-solid fa-shield-halved"></i>

                        Only one admin account is allowed
                        in this system.

                    </p>

                </div>


                <!-- REGISTER BUTTON -->

                <button
                    type="submit"
                    class="submit"
                >

                    <i class="fa-solid fa-user-plus"></i>


                    <span id="register-button-text">

                        CREATE ACCOUNT

                    </span>

                </button>


                <p
                    class="note"
                    id="register-note"
                >

                    Create your customer account to order
                    and submit custom requests.

                </p>

            </form>

        </div>

    </section>

</div>

<script>

/* =========================================================
   CURRENT ROLE
========================================================= */

let currentRole = "<?= htmlspecialchars($selected_role) ?>";


/* =========================================================
   ELEMENTS
========================================================= */

const icon = document.getElementById('user-icon');
const customerFields = document.getElementById('customer-fields');
const adminNote = document.getElementById('admin-register-note');
const registerButtonText = document.getElementById('register-button-text');
const registerNote = document.getElementById('register-note');
const loginNote = document.getElementById('login-note');

const loginRole = document.getElementById('login-role');
const signupRole = document.getElementById('signup-role');
const forgotRole = document.getElementById('forgot-role');


/* =========================================================
   UPDATE ROLE UI
========================================================= */

function updateRole(role){

    currentRole = role;

    /* -----------------------------------------
       Update hidden role inputs
    ----------------------------------------- */

    if(loginRole){
        loginRole.value = role;
    }

    if(signupRole){
        signupRole.value = role;
    }

    if(forgotRole){
        forgotRole.value = role;
    }


    /* -----------------------------------------
       CUSTOMER
    ----------------------------------------- */

    if(role === 'customer'){

        if(icon){
            icon.innerHTML =
                '<i class="fa-regular fa-user"></i>';
        }

        if(customerFields){
            customerFields.classList.remove('hidden');
        }

        if(adminNote){
            adminNote.classList.add('hidden');
        }

        if(registerButtonText){
            registerButtonText.textContent =
                'CREATE ACCOUNT';
        }

        if(registerNote){
            registerNote.textContent =
                'Create your customer account to order and submit custom requests.';
        }

        if(loginNote){
            loginNote.textContent =
                'Secure login for SA Design customers.';
        }


        /* Customer fields required */

        const phone =
            document.getElementById('signup-phone');

        const address =
            document.getElementById('signup-address');

        if(phone){
            phone.required = true;
        }

        if(address){
            address.required = true;
        }

    }


    /* -----------------------------------------
       ADMIN
    ----------------------------------------- */

    else{

        if(icon){
            icon.innerHTML =
                '<i class="fa-solid fa-user-shield"></i>';
        }

        if(customerFields){
            customerFields.classList.add('hidden');
        }

        if(adminNote){
            adminNote.classList.remove('hidden');
        }

        if(registerButtonText){
            registerButtonText.textContent =
                'CREATE ADMIN ACCOUNT';
        }

        if(registerNote){
            registerNote.textContent =
                'Only one administrator account can be registered.';
        }

        if(loginNote){
            loginNote.textContent =
                'Secure login for SA Design administrator.';
        }


        /* Customer fields NOT required */

        const phone =
            document.getElementById('signup-phone');

        const address =
            document.getElementById('signup-address');

        if(phone){
            phone.required = false;
        }

        if(address){
            address.required = false;
        }
    }


    /* -----------------------------------------
       UPDATE TITLE BASED ON CURRENT TAB
    ----------------------------------------- */

    const signupBox =
        document.getElementById('signup-box');

    if(!signupBox){
        return;
    }

    const signupVisible =
        !signupBox.classList.contains('hidden');


    if(signupVisible){

        if(role === 'admin'){

            document.getElementById('form-title').textContent =
                'Create Admin Account';

            document.getElementById('form-subtitle').textContent =
                'Register the administrator account for SA Design.';

        }else{

            document.getElementById('form-title').textContent =
                'Create Your Account';

            document.getElementById('form-subtitle').textContent =
                'Register to start ordering with SA Design.';
        }

    }else{

        if(role === 'admin'){

            document.getElementById('form-title').textContent =
                'Admin Login';

            document.getElementById('form-subtitle').textContent =
                'Login to access the SA Design administration dashboard.';

        }else{

            document.getElementById('form-title').textContent =
                'Welcome Back';

            document.getElementById('form-subtitle').textContent =
                'Login to continue to your SA Design account.';
        }
    }
}


/* =========================================================
   SWITCH LOGIN / REGISTER
========================================================= */

function switchTab(mode){

    const loginBox =
        document.getElementById('login-box');

    const signupBox =
        document.getElementById('signup-box');

    const btnLogin =
        document.getElementById('btn-login-tab');

    const btnSignup =
        document.getElementById('btn-signup-tab');

    const title =
        document.getElementById('form-title');

    const subtitle =
        document.getElementById('form-subtitle');


    if(mode === 'signup'){

        /* Show Register */

        loginBox.classList.add('hidden');
        signupBox.classList.remove('hidden');

        btnSignup.classList.add('active');
        btnLogin.classList.remove('active');


        if(currentRole === 'admin'){

            title.textContent =
                'Create Admin Account';

            subtitle.textContent =
                'Register the administrator account for SA Design.';

        }else{

            title.textContent =
                'Create Your Account';

            subtitle.textContent =
                'Register to start ordering with SA Design.';
        }


    }else{

        /* Show Login */

        signupBox.classList.add('hidden');
        loginBox.classList.remove('hidden');

        btnLogin.classList.add('active');
        btnSignup.classList.remove('active');


        if(currentRole === 'admin'){

            title.textContent =
                'Admin Login';

            subtitle.textContent =
                'Login to access the SA Design administration dashboard.';

        }else{

            title.textContent =
                'Welcome Back';

            subtitle.textContent =
                'Login to continue to your SA Design account.';
        }
    }


    /* Update role UI */

    updateRole(currentRole);
}


/* =========================================================
   PASSWORD SHOW / HIDE
========================================================= */

document
    .querySelectorAll('.password-toggle')
    .forEach(function(button){

        button.addEventListener(
            'click',
            function(){

                const input =
                    document.getElementById(
                        this.dataset.target
                    );

                const icon =
                    this.querySelector('i');


                if(!input){
                    return;
                }


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
        );

    });


/* =========================================================
   OPEN FORGOT PASSWORD POPUP
========================================================= */

function openForgotPassword(){

    const popup =
        document.getElementById(
            'forgotPasswordOverlay'
        );


    if(!popup){
        return;
    }


    /* Make sure forgot password uses current role */

    if(forgotRole){
        forgotRole.value = currentRole;
    }


    popup.classList.add('show');


    /* Focus email */

    setTimeout(function(){

        const emailInput =
            document.getElementById(
                'forgot-email'
            );

        if(emailInput){
            emailInput.focus();
        }

    },200);
}


/* =========================================================
   CLOSE FORGOT PASSWORD POPUP
========================================================= */

function closeForgotPassword(){

    const popup =
        document.getElementById(
            'forgotPasswordOverlay'
        );


    if(!popup){
        return;
    }


    popup.classList.remove('show');
}


/* =========================================================
   CLICK OUTSIDE FORGOT POPUP
========================================================= */

const forgotOverlay =
    document.getElementById(
        'forgotPasswordOverlay'
    );


if(forgotOverlay){

    forgotOverlay.addEventListener(
        'click',
        function(event){

            if(event.target === this){

                closeForgotPassword();
            }

        }
    );
}


/* =========================================================
   ESC KEY
========================================================= */

document.addEventListener(
    'keydown',
    function(event){

        if(event.key === 'Escape'){

            closeForgotPassword();
            closeAuthPopup();
        }

    }
);


/* =========================================================
   CLOSE GENERAL AUTH POPUP
========================================================= */

function closeAuthPopup(){

    const popup =
        document.getElementById(
            'authPopupOverlay'
        );


    if(!popup){
        return;
    }


    popup.style.opacity = '0';
    popup.style.visibility = 'hidden';


    setTimeout(function(){

        if(popup){
            popup.remove();
        }

    },250);
}


/* =========================================================
   INITIALIZE ROLE
========================================================= */

updateRole(currentRole);

/* =========================================================
   CONTINUE AFTER SUCCESS POPUP
========================================================= */

const authRedirect = <?= json_encode($auth_redirect) ?>;

function continueAfterPopup(){

    const popup = document.getElementById('authPopupOverlay');

    if(popup){

        popup.style.opacity = '0';
        popup.style.visibility = 'hidden';

    }

    setTimeout(function(){

        if(authRedirect !== ''){

            window.location.href = authRedirect;

        }

    }, 250);
}
</script>

</body>

</html>
