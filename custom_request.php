<?php
session_start();
require_once 'db.php';

$requestMessage = '';
$requestStatus = '';

$total_cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_cart_count += isset($item['quantity']) ? (int) $item['quantity'] : 1;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_custom_request'])) {

    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }

    $fullname          = trim($_POST['fullname'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $phone             = trim($_POST['phone'] ?? '');
    $collectionMethod  = trim($_POST['collection_method'] ?? '');
    $deliveryAddress   = trim($_POST['delivery_address'] ?? '');
    $productName       = trim($_POST['product_name'] ?? '');
    $quantity          = max(1, (int)($_POST['quantity'] ?? 1));
    $additionalInfo    = trim($_POST['additional_info'] ?? '');

    // Semak medan wajib diisi
    if (
        $fullname !== '' &&
        filter_var($email, FILTER_VALIDATE_EMAIL) &&
        $phone !== '' &&
        $productName !== '' &&
        in_array($collectionMethod, ['Self Collection', 'Delivery'], true)
    ) {

        /*
         * ARTWORK UPLOAD
         * Files are stored in: fyp_code/uploads/
         */

        $artwork = null;

        if (
            !isset($_FILES['artwork']) ||
            $_FILES['artwork']['error'] === UPLOAD_ERR_NO_FILE
        ) {

            $requestStatus = 'error';
            $requestMessage = 'Please upload your artwork file before submitting the request.';

        } else {

            if ($_FILES['artwork']['error'] !== UPLOAD_ERR_OK) {

                $requestStatus = 'error';
                $requestMessage = 'The artwork file could not be uploaded. Please try again.';

            } else {

                $fileTmp  = $_FILES['artwork']['tmp_name'];
                $fileName = $_FILES['artwork']['name'];
                $fileSize = (int) $_FILES['artwork']['size'];

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'ai'];

                $extension = strtolower(
                    pathinfo($fileName, PATHINFO_EXTENSION)
                );

                /*
                 * Maximum artwork size: 10MB
                 */
                if ($fileSize > 10 * 1024 * 1024) {

                    $requestStatus = 'error';
                    $requestMessage = 'Artwork file is too large. Maximum size is 10MB.';

                } elseif (!in_array($extension, $allowedExtensions, true)) {

                    $requestStatus = 'error';
                    $requestMessage = 'Invalid artwork format. Please upload JPG, JPEG, PNG, PDF or AI.';

                } else {

                    $uploadDir = __DIR__ . '/uploads/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $safeBaseName = preg_replace(
                        '/[^A-Za-z0-9_-]/',
                        '_',
                        pathinfo($fileName, PATHINFO_FILENAME)
                    );

                    $safeBaseName = trim($safeBaseName, '_');

                    if ($safeBaseName === '') {
                        $safeBaseName = 'artwork';
                    }

                    $newFileName =
                        $safeBaseName .
                        '_' .
                        date('Ymd_His') .
                        '_' .
                        bin2hex(random_bytes(3)) .
                        '.' .
                        $extension;

                    $uploadPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmp, $uploadPath)) {
                        $artwork = $newFileName;
                    } else {
                        $requestStatus = 'error';
                        $requestMessage = 'Artwork upload failed. Please try again.';
                    }
                }
            }
        }

        /*
         * Only insert into database if there was no artwork upload error.
         */
        if ($requestStatus !== 'error') {

            $stmt = $conn->prepare(
                'INSERT INTO custom_request
                (fullname, email, phone, collection_method, delivery_address, product_name, quantity, additional_info, artwork)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            if ($stmt) {

                $stmt->bind_param(
                    'ssssssiss',
                    $fullname,
                    $email,
                    $phone,
                    $collectionMethod,
                    $deliveryAddress,
                    $productName,
                    $quantity,
                    $additionalInfo,
                    $artwork
                );

                if ($stmt->execute()) {

                    $requestStatus = 'success';
                    $requestMessage = 'Your request has been submitted successfully! Our team will contact you shortly.';

                } else {

                    if (
                        !empty($artwork) &&
                        isset($uploadPath) &&
                        file_exists($uploadPath)
                    ) {
                        unlink($uploadPath);
                    }

                    $requestStatus = 'error';
                    $requestMessage = 'We could not save your request. Please try again.';
                }

                $stmt->close();

            } else {

                if (
                    !empty($artwork) &&
                    isset($uploadPath) &&
                    file_exists($uploadPath)
                ) {
                    unlink($uploadPath);
                }

                $requestStatus = 'error';
                $requestMessage = 'Unable to process your request. Please try again.';
            }
        }

    } else {

        $requestStatus = 'error';
        $requestMessage = 'Please complete all required fields with a valid email address.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Custom Request | SA Design</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <style>

        /* =========================
           GLOBAL
        ========================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f6e8f2;
            color: #172033;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        button,
        input,
        textarea,
        select {
            font-family: inherit;
        }


        /* =========================
           NAVBAR
        ========================= */

        .navbar {
            min-height: 84px;
            padding: 16px 48px;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;

            background: #ffffff;
            border-bottom: 1px solid #e8ebf2;

            box-shadow: 0 4px 18px rgba(28, 53, 118, 0.05);

            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            flex-direction: column;
            line-height: .95;
        }

        .logo-main {
            display: flex;
            align-items: baseline;
            gap: 7px;

            font-size: 32px;
            font-weight: 900;

            letter-spacing: -2px;
        }

        .logo-sa {
            color: #f0208d;
        }

        .logo-design {
            color: #1557d6;
        }

        .logo-subtitle {
            margin-top: 4px;

            color: #111827;

            font-size: 9px;
            font-weight: 900;

            letter-spacing: .9px;
            text-transform: uppercase;
        }


        /* NAV LINKS */

        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-links a {
            color: #111827;

            font-size: 13px;
            font-weight: 800;

            text-transform: uppercase;

            transition: .2s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #f0208d;
        }


        /* NAV ICONS */

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            padding: 10px 18px;

            border-radius: 999px;

            background: linear-gradient(
                135deg,
                #f0208d,
                #ff3b86
            );

            color: white;

            font-size: 12px;
            font-weight: 800;

            text-transform: uppercase;

            box-shadow: 0 8px 18px rgba(239, 58, 155, .20);

            transition: .25s;
        }

        .login-pill:hover {
            transform: translateY(-2px);
        }

        .logout-pill {
            border: 0;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .logout-pill:focus {
            outline: none;
        }

        .logout-popup {
            border-radius: 18px !important;
        }

        .logout-title {
            color: #102f91 !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 24px !important;
            font-weight: 800 !important;
        }

        .logout-text {
            color: #64748b !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 13px !important;
        }

        .logout-confirm,
        .logout-cancel {
            border-radius: 10px !important;
            padding: 10px 18px !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 12px !important;
            font-weight: 800 !important;
        }

        .icon-btn {
            position: relative;

            width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 1px solid #f0208d;
            border-radius: 50%;

            background: white;

            color: #f0208d;

            transition: .2s;
        }

        .icon-btn:hover {
            background: #ffe4f2;
            transform: translateY(-2px);
        }


        /* MOBILE MENU */

        .menu-toggle {
            display: none;

            width: 42px;
            height: 42px;

            border: 1px solid #dfe4ef;
            border-radius: 11px;

            background: white;
            color: #172033;

            cursor: pointer;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 7px 14px;

            border-radius: 999px;

            background: white;

            border: 1px solid #dbe4ff;

            color: #1747c7;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: .7px;
        }

        /* =========================
        HERO (MATCHING ABOUT PAGE)
        ========================= */

        .hero {
            position: relative;
            overflow: hidden;
            padding: 78px 70px 72px; /* Matched padding from .about-hero */
            background: linear-gradient(120deg, #cbd8ff 0%, #d9b9eb 52%, #ffc0dc 100%);
            border-bottom: 1px solid #e0e7f8;
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            right: -90px;
            top: -150px;
            border-radius: 50%;
            background: rgba(40, 84, 197, .20);
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            left: -90px;
            bottom: -130px;
            border-radius: 50%;
            background: rgba(239, 58, 155, .20);
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            max-width: 1050px; /* Matched max-width from .about-hero-content */
            margin: auto;
            text-align: center;
        }

        .hero h1 {
            margin: 15px 0 10px;
            color: #102f91;
            font-size: clamp(35px, 5vw, 52px);
            font-weight: 900;
            letter-spacing: -2px;
        }

        .hero h1 span {
            color: #f0208d;
        }

        .hero p {
            max-width: 720px; /* Matched text container width from .about-hero p */
            margin: auto;
            color: #64748b;
            font-size: 14px; /* Increased font-size from 13px to 14px */
            line-height: 1.7;
        }
        
        /* =========================
           MAIN
        ========================= */

        .container {
            width: min(1120px, calc(100% - 140px));

            margin: 48px auto 70px;
        }


        /* SUCCESS MESSAGE */

        .message {
            margin-bottom: 20px;

            padding: 13px 15px;

            border: 1px solid #cfe8dc;
            border-radius: 11px;

            background: #effaf5;

            color: #087a52;

            font-size: 12px;
            font-weight: 700;
        }


        /* =========================
           REQUEST CARD
        ========================= */

        .request-card {
            display: grid;

            grid-template-columns: 1.05fr .95fr;

            overflow: hidden;

            border: 1px solid #e2e7f1;

            border-radius: 24px;

            background: white;

            box-shadow:
                0 14px 35px rgba(28, 53, 118, .07);
        }

        .request-side,
        .customer-side {
            padding: 34px;
        }

        .customer-side {
            background: #fff3fa;

            border-left: 1px solid #e9edf5;
        }


        /* SECTION TITLE */

        .section-title {
            margin: 0 0 22px;

            color: #102f91;

            font-size: 20px;
            font-weight: 900;
        }

        .section-title small {
            display: block;

            margin-top: 6px;

            color: #64748b;

            font-size: 11px;

            font-weight: 500;

            line-height: 1.6;
        }


        /* =========================
           FORM
        ========================= */

        .field {
            margin-bottom: 17px;
        }

        label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 11px;

            font-weight: 800;
        }

        input,
        textarea,
        select {
            width: 100%;

            border: 1px solid #dce2ef;

            border-radius: 11px;

            outline: none;

            background: white;

            color: #172033;

            padding: 12px 13px;

            font-size: 12px;

            transition: .2s;
        }

        textarea {
            min-height: 115px;

            resize: vertical;
        }

        input::placeholder,
        textarea::placeholder {
            color: #a0a8b8;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #1557d6;

            box-shadow:
                0 0 0 3px rgba(21, 87, 214, .08);
        }


        select {
            cursor: pointer;
        }

        .dynamic-options {
            display: none;
            margin: 4px 0 18px;
            padding: 15px;
            border: 1px solid #e1e7f2;
            border-radius: 14px;
            background: #f8faff;
        }

        .dynamic-options-title {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 13px;
            color: #1747c7;
            font-size: 11px;
            font-weight: 800;
        }

        .dynamic-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .dynamic-grid .field {
            margin-bottom: 0;
        }

        .dynamic-grid .full {
            grid-column: 1 / -1;
        }

        .required-mark {
            color: #f0208d;
            font-weight: 800;
        }

        .option-note {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 9px;
        }

        @media (max-width: 700px) {
            .dynamic-grid {
                grid-template-columns: 1fr;
            }

            .dynamic-grid .full {
                grid-column: auto;
            }
        }


        /* =========================
           QUANTITY
        ========================= */

        .quantity-wrap {
            display: flex;

            align-items: center;

            gap: 11px;
        }

        .qty-control {
            display: flex;

            align-items: center;

            overflow: hidden;

            border: 1px solid #dce2ef;

            border-radius: 11px;

            background: white;
        }

        .qty-control button {
            width: 40px;
            height: 40px;

            border: 0;

            background: white;

            color: #1747c7;

            font-size: 17px;

            font-weight: 800;

            cursor: pointer;
        }

        .qty-control button:hover {
            background: #c3d2ff;
        }

        .qty-control span {
            width: 42px;

            text-align: center;

            font-size: 12px;

            font-weight: 800;
        }

        .qty-note {
            color: #94a3b8;

            font-size: 10px;
        }


        /* =========================
           FILE UPLOAD
        ========================= */

        .file-box {
            position: relative;

            display: flex;
            align-items: center;

            min-height: 45px;

            overflow: hidden;

            border: 1px dashed #cbd5e1;

            border-radius: 11px;

            background: #fff3fa;
        }

        .file-box input {
            position: absolute;

            inset: 0;

            z-index: 2;

            opacity: 0;

            cursor: pointer;
        }

        .file-label {
            padding: 10px 13px;

            background: #cddaff;

            color: #1747c7;

            font-size: 10px;

            font-weight: 800;
        }

        .file-name {
            padding: 0 11px;

            color: #94a3b8;

            font-size: 10px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }

        .upload-guidance {
            display:flex;
            gap:10px;
            align-items:flex-start;
            margin-top:10px;
            padding:10px 12px;
            border:1px solid #dbe5ff;
            border-radius:11px;
            background:#f5f8ff;
            color:#64748b;
            font-size:10px;
            line-height:1.5;
        }
        .upload-guidance>i {color:#1747c7;margin-top:2px}
        .upload-guidance strong,.upload-guidance span {display:block}
        .upload-guidance strong {color:#102f91;margin-bottom:2px}

        /* =========================
           TIP CARD
        ========================= */

        .helper-card {
            margin-top: 18px;

            padding: 16px;

            border-radius: 15px;

            background:
                linear-gradient(
                    120deg,
                    #c7d5ff,
                    #ffc9df
                );

            border: 1px solid #b7c8ff;
        }

        .helper-card strong {
            display: block;

            margin-bottom: 4px;

            color: #102f91;

            font-size: 11px;
        }

        .helper-card span {
            color: #64748b;

            font-size: 10px;

            line-height: 1.5;
        }


        /* =========================
           SUBMIT
        ========================= */

        .submit {
            display: flex;

            align-items: center;
            justify-content: center;

            gap: 8px;

            width: 100%;

            margin-top: 9px;

            padding: 14px;

            border: 0;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    #f0208d,
                    #1557d6
                );

            color: white;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: .05em;

            cursor: pointer;

            box-shadow:
                0 10px 22px rgba(240, 32, 141, .20);

            transition: .25s;
        }

        .submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(21, 87, 214, .18);
        }

        .privacy {
            margin: 13px 0 0;

            color: #94a3b8;

            font-size: 9px;

            line-height: 1.5;

            text-align: center;
        }


        /* =========================
           FOOTER
        ========================= */

        .footer {
            padding: 38px 70px;

            background: #0f1f61;

            color: white;
        }

        .footer-grid {
            width: min(1120px, 100%);

            margin: auto;

            display: grid;

            grid-template-columns: 1.5fr 1fr 1fr;

            gap: 35px;
        }

        .footer-brand {
            font-size: 24px;

            font-weight: 900;
        }

        .footer-brand span {
            color: #f0208d;
        }

        .footer h4 {
            margin-bottom: 10px;

            font-size: 12px;
        }

        .footer p,
        .footer a {
            color: rgba(255,255,255,.72);

            font-size: 11px;

            line-height: 1.7;
        }

        .footer a {
            display: block;

            margin-bottom: 4px;
        }

        .footer a:hover {
            color: white;
        }

        .footer-line {
            width: min(1120px, 100%);

            margin: 24px auto 0;

            padding-top: 14px;

            border-top: 1px solid rgba(255,255,255,.14);

            text-align: center;

            color: rgba(255,255,255,.58);

            font-size: 10px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .navbar {
                padding: 15px 28px;
            }

            .request-card {
                grid-template-columns: 1fr;
            }

            .customer-side {
                border-top: 1px solid #e9edf5;

                border-left: 0;
            }

            .container {
                width: min(100% - 56px, 720px);
            }
        }


        @media (max-width: 760px) {

            .navbar {
                min-height: 76px;

                flex-wrap: wrap;

                padding: 15px 20px;
            }

            .logo-main {
                font-size: 28px;
            }

            .menu-toggle {
                display: flex;

                align-items: center;

                justify-content: center;
            }

            .nav-icons {
                margin-left: auto;
            }

            .login-pill span {
                display: none;
            }

            .login-pill {
                width: 40px;
                height: 40px;

                padding: 0;
            }

            .icon-btn {
                width: 40px;
                height: 40px;
            }

            .nav-links {
                display: none;

                order: 5;

                width: 100%;

                padding: 13px 0 2px;

                flex-direction: column;

                gap: 14px;

                border-top: 1px solid #eef1f6;
            }

            .navbar.menu-open .nav-links {
                display: flex;
            }

            .hero {
                padding: 55px 22px;
            }

            .hero h1 {
                font-size: 38px;
            }

            .container {
                width: calc(100% - 40px);

                margin: 38px auto 55px;
            }

            .request-side,
            .customer-side {
                padding: 24px 19px;
            }

            .footer {
                padding: 35px 20px;
            }

            .footer-grid {
                grid-template-columns: 1fr;

                gap: 24px;
            }
        }


        @media (max-width: 430px) {

            .hero h1 {
                font-size: 33px;
            }

            .quantity-wrap {
                align-items: flex-start;

                flex-direction: column;
            }
        }

    
        /* =========================
           SWEETALERT
        ========================= */

        .sa-success-popup {
            border-radius: 20px !important;
        }

        /* Login prompt: aligned with the SA Design blue and pink visual identity. */
        .sa-login-popup {
            border: 1px solid rgba(255, 255, 255, .76) !important;
            border-radius: 24px !important;
            background: linear-gradient(145deg, #ffffff 0%, #f8faff 100%) !important;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .28) !important;
            overflow: hidden !important;
        }

        .sa-login-popup::before {
            content: "";
            display: block;
            height: 7px;
            margin: -1.25em -1.25em 1.35em;
            background: linear-gradient(135deg, #f0208d, #1557d6);
        }

        .sa-login-title {
            color: #0f172a !important;
            font-size: 28px !important;
            font-weight: 900 !important;
            letter-spacing: -.7px !important;
        }

        .sa-login-text {
            max-width: 390px;
            margin: 0 auto !important;
            color: #64748b !important;
            font-size: 15px !important;
            line-height: 1.65 !important;
        }

        .sa-login-confirm,
        .sa-login-cancel {
            min-height: 46px;
            padding: 11px 21px !important;
            border: 0 !important;
            border-radius: 12px !important;
            font-family: inherit !important;
            font-size: 14px !important;
            font-weight: 800 !important;
            transition: transform .2s ease, box-shadow .2s ease !important;
        }

        .sa-login-confirm {
            background: linear-gradient(135deg, #f0208d, #1557d6) !important;
            box-shadow: 0 10px 20px rgba(240, 32, 141, .2) !important;
        }

        .sa-login-cancel {
            background: #edf3ff !important;
            color: #1557d6 !important;
        }

        .sa-login-confirm:hover,
        .sa-login-cancel:hover {
            transform: translateY(-2px);
        }

        .sa-login-confirm:hover { box-shadow: 0 14px 24px rgba(21, 87, 214, .25) !important; }

        @media (max-width: 480px) {
            .sa-login-popup { width: calc(100% - 28px) !important; }
            .sa-login-title { font-size: 24px !important; }
            .sa-login-confirm, .sa-login-cancel { min-width: 0; padding: 11px 16px !important; }
        }

</style>
<link rel="stylesheet" href="ui_polish.css">
<style>
    /* Match the shared Home navbar while leaving the request page content independent. */
    .navbar { height: 84px; min-height: 0; padding: 16px 48px; gap: 25px; box-shadow: none; position: relative; z-index: 20; }
    .nav-logo { display: flex; align-items: center; min-width: max-content; }
    .logo-text { display: flex; flex-direction: column; gap: 2px; }
    .logo-title { font-size: 36px; font-weight: 900; line-height: .9; letter-spacing: -2.5px; white-space: nowrap; }
    .logo-subtitle { margin-top: 0; font-size: 12px; letter-spacing: .7px; }
    .nav-links { justify-content: center; gap: 32px; list-style: none; }
    .login-pill { justify-content: flex-start; padding: 10px 15px; border-radius: 11px; }
    .cart-badge { position: absolute; top: -4px; right: -4px; width: 19px; height: 19px; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; border-radius: 50%; background: #f0208d; color: #fff; font-size: 9px; font-weight: 900; }
    @media (max-width: 1100px) { .navbar { padding: 16px 30px; } .nav-links { gap: 20px; } }
    @media (max-width: 760px) {
        .navbar { height: auto; min-height: 76px; padding: 15px 20px; }
        .logo-title { font-size: 29px; }
        .logo-subtitle { font-size: 9px; }
        .nav-links { order: 4; padding: 12px 0 3px; align-items: center; }
        .login-pill { justify-content: center; }
    }
    @media (max-width: 430px) {
        /* Match Home and Cart: keep controls on one row at standard phone widths. */
        .navbar { align-items: center; gap: 10px; padding-left: 14px; padding-right: 14px; }
        .navbar .nav-icons { order: 0; width: auto; margin-left: auto; justify-content: flex-end; gap: 6px; }
        .navbar .menu-toggle { order: 0; margin-left: 0; flex: 0 0 auto; }
        .navbar .nav-links { order: 4; }
        .navbar .logo-title { font-size: 26px; letter-spacing: -1.6px; }
        .navbar .logo-subtitle { font-size: 7px; letter-spacing: .45px; }
        .navbar .login-pill, .navbar .icon-btn { flex: 0 0 38px; width: 38px; height: 38px; }
    }
    @media (max-width: 360px) {
        .navbar .nav-icons { order: 3; width: 100%; margin-left: 0; gap: 8px; }
        .navbar .menu-toggle { order: 2; margin-left: auto; }
    }
</style>
</head>

<body>

<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">
    <a class="nav-logo" href="index.php" title="Back to Home">
        <div class="logo-text">
            <span class="logo-title"><span class="logo-sa">SA</span> <span class="logo-design">DESIGN</span></span>
            <span class="logo-subtitle">PRINTING &amp; ADVERTISING</span>
        </div>
    </a>

    <ul class="nav-links" id="mainNav">
        <li><a href="index.php">Home</a></li>
        <li><a href="index.php#products-section">Product</a></li>
        <li><a href="about.php">About Us</a></li>
        <li><a href="custom_request.php" class="active">Custom Request</a></li>
    </ul>

    <div class="nav-icons">
        <?php if(isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
            <a href="cust_profile.php" class="login-pill" title="My profile"><i class="fa-regular fa-user"></i><span>Profile</span></a>
            <button type="button" class="login-pill logout-pill" title="Log out" onclick="confirmLogout()"><i class="fa-solid fa-right-from-bracket"></i><span>Log out</span></button>
        <?php else: ?>
            <a href="login.php" class="login-pill" title="Login"><i class="fa-regular fa-user"></i><span>Login</span></a>
        <?php endif; ?>
        <a href="cart.php" class="icon-btn" title="Cart" aria-label="Shopping cart">
            <i class="fa-solid fa-bag-shopping"></i>
            <span class="cart-badge"><?= $total_cart_count ?></span>
        </a>
        <?php if(isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
            <a href="wishlist.php" class="icon-btn" title="Wishlist" aria-label="My wishlist"><i class="fa-regular fa-heart"></i></a>
        <?php endif; ?>
    </div>

    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
</nav>


<!-- =========================
     HERO
========================= -->

<section class="hero">

    <div class="hero-inner">

        <span class="badge">

            <i class="fa-solid fa-wand-magic-sparkles"></i>

            CUSTOM REQUEST

        </span>


        <h1>

            Bring Your
            <span>Ideas</span>
            to Print

        </h1>


        <p>

            Tell us what you need and submit your details.
            Our team will review your request and contact
            you with a quotation.

        </p>

    </div>

</section>


<!-- =========================
     MAIN FORM
========================= -->

<main class="container">


    <?php if($requestMessage !== ''): ?>

        <div
            id="requestResult"
            data-status="<?= htmlspecialchars($requestStatus) ?>"
            data-message="<?= htmlspecialchars($requestMessage, ENT_QUOTES) ?>"
            style="display:none;"
        ></div>

    <?php endif; ?>


    <form id="customRequestForm"
          method="post"
          enctype="multipart/form-data"
          class="request-card">


        <!-- PRODUCT DETAILS -->

        <section class="request-side">

            <h2 class="section-title">

                <i class="fa-solid fa-box-open"></i>

                Product Details

                <small>

                    Share the product, quantity and
                    specifications you require.

                </small>

            </h2>

            <!-- PRODUCT NAME (FREE TEXT INPUT) -->
            <div class="field">
                <label for="product_name">
                    Product / Item Name <span class="required-mark">*</span>
                </label>
                <input 
                    type="text" 
                    id="product_name" 
                    name="product_name" 
                    required 
                    placeholder="e.g. Acrylic Signage, Keychain, Lanyard, T-Shirt Cotton">
            </div>

            <!-- PRODUCT DESCRIPTION & SPECIFICATIONS -->
            <div class="field">
                <label for="additional_info">
                    Product Specifications & Details
                </label>
                <textarea 
                    id="additional_info" 
                    name="additional_info" 
                    rows="4" 
                    placeholder="e.g. Size (A3 / 2x3ft), Material type, Color preferences, Finishing, or special instructions..."></textarea>
            </div>

            <div class="field">

                <label>
                    Quantity <span class="required-mark">*</span>
                </label>

                <div class="quantity-wrap">

                    <div class="qty-control">

                        <button
                            type="button"
                            id="qty-minus"
                            aria-label="Decrease quantity">

                            −

                        </button>


                        <span id="qty-value">
                            1
                        </span>


                        <button
                            type="button"
                            id="qty-plus"
                            aria-label="Increase quantity">

                            +

                        </button>

                    </div>


                    <input
                        type="hidden"
                        id="quantity"
                        name="quantity"
                        value="1">


                    <span class="qty-note">
                        units required
                    </span>

                </div>

            </div>


            <div class="field">

                <label for="artwork">

                    Artwork File

                    <span class="required-mark">
                        * Required
                    </span>

                </label>


                <div class="file-box">

                    <input
                        id="artwork"
                        name="artwork"
                        type="file"
                        accept=".jpg,.jpeg,.png,.pdf,.ai"
                        required>


                    <span class="file-label">
                        Choose File
                    </span>


                    <span class="file-name"
                          id="file-name">

                        No file chosen

                    </span>

                </div>

            </div>

            <div class="upload-guidance">
                <i class="fa-solid fa-circle-info"></i>
                <div><strong>Artwork requirements</strong><span>JPG, JPEG, PNG, PDF or AI · Maximum 10MB</span><span>Use a clear, high-resolution file with the correct size and text.</span></div>
            </div>


            <!-- SMART TIP -->

            <div class="helper-card">

                <strong>

                    <i class="fa-solid fa-lightbulb"></i>

                    Tip for a faster quotation

                </strong>


                <span>

                    Include the size, material,
                    finishing, quantity and required
                    date in your request.

                </span>

            </div>

        </section>


        <!-- CUSTOMER DETAILS -->

        <section class="customer-side">

            <h2 class="section-title">

                <i class="fa-solid fa-user"></i>

                Your Details

                <small>

                    We use these details to prepare
                    and confirm your quotation.

                </small>

            </h2>


            <div class="field">

                <label for="name">
                    Full Name <span class="required-mark">*</span>
                </label>

                <input
                    id="name"
                    name="fullname"
                    required
                    placeholder="Enter your full name">

            </div>


            <div class="field">

                <label for="email">
                    Email Address <span class="required-mark">*</span>
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    placeholder="Enter your email">

            </div>


            <div class="field">

                <label for="phone">
                    Phone Number <span class="required-mark">*</span>
                </label>

                <input
                    id="phone"
                    name="phone"
                    type="tel"
                    required
                    placeholder="Enter your contact number">

            </div>


            <div class="field">

                <label for="collection_method">
                    Collection Method <span class="required-mark">*</span>
                </label>

                <select
                    id="collection_method"
                    name="collection_method"
                    required>

                    <option value="" selected disabled>
                        Select collection method
                    </option>

                    <option value="Self Collection">
                        Self Collection
                    </option>

                    <option value="Delivery">
                        Delivery
                    </option>

                </select>

            </div>


            <div
                class="field"
                id="deliveryAddressField"
                style="display:none;">

                <label for="delivery_address">
                    Delivery Address <span class="required-mark">*</span>
                </label>

                <textarea
                    id="delivery_address"
                    name="delivery_address"
                    rows="3"
                    placeholder="Enter your complete delivery address"></textarea>

            </div>


            <button
                class="submit"
                name="submit_custom_request"
                type="submit"
                id="submitCustomRequest">

                <i class="fa-solid fa-paper-plane"></i>

                <?php if(isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
                    SEND REQUEST
                <?php else: ?>
                    LOGIN TO SUBMIT
                <?php endif; ?>

            </button>


            <p class="privacy">

                <i class="fa-solid fa-lock"></i>

                Your request will be saved securely
                and our team will contact you shortly.

            </p>

        </section>

    </form>

</main>


<!-- =========================
     FOOTER
========================= -->

<footer class="footer">

    <div class="footer-grid">


        <div>

            <div class="footer-brand">

                <span>SA</span> DESIGN

            </div>

            <p>
                Printing &amp; Advertising
            </p>

            <p>
                Quality printing solutions for
                business, events, school and
                personal needs.
            </p>

        </div>


        <div>

            <h4>
                QUICK LINKS
            </h4>

            <a href="index.php">
                Home
            </a>

            <a href="index.php#products-section">
                Products
            </a>

            <a href="about.php">
                About Us
            </a>

            <a href="custom_request.php">
                Custom Request
            </a>

        </div>


        <div>

            <h4>
                CONTACT
            </h4>

            <a href="https://wa.me/60194184147"
               target="_blank"
               rel="noopener">

                WhatsApp Us

            </a>

            <a href="mailto:salamakal@gmail.com">

                Email Us

            </a>

        </div>

    </div>


    <div class="footer-line">

        © 2026 Politeknik Muadzam Shah. All rights reserved.

    </div>

</footer>



<script>

/* =========================
   LOGIN REQUIRED
========================= */

const customerLoggedIn = <?= (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) ? 'true' : 'false' ?>;

const customRequestForm = document.getElementById('customRequestForm');
const submitCustomRequest = document.getElementById('submitCustomRequest');

if (customRequestForm && submitCustomRequest && !customerLoggedIn) {

    customRequestForm.addEventListener('submit', function(event) {

        event.preventDefault();

        Swal.fire({
            title: 'Login Required',
            text: 'Please login to your customer account before submitting a custom request.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Login Now',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            buttonsStyling: false,
            iconColor: '#1557d6',
            customClass: {
                popup: 'sa-login-popup',
                title: 'sa-login-title',
                htmlContainer: 'sa-login-text',
                confirmButton: 'sa-login-confirm',
                cancelButton: 'sa-login-cancel'
            }
        }).then(function(result) {

            if (result.isConfirmed) {
                window.location.href = 'login.php';
            }

        });

    });

}


/* =========================
   LOGOUT
========================= */

function confirmLogout() {

    Swal.fire({
        title: 'Logout',
        text: 'Are you sure you want to logout?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Logout',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f0208d',
        reverseButtons: true
    }).then(function(result) {

        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }

    });

}


/* =========================
   PRODUCT OPTIONS DATA
========================= */

const productData = {

    "Custom Self-Inking Stamp": {
        fields: [
            {
                type: "select",
                name: "stamp_model",
                label: "Select Stamp Model / Code",
                options: [
                    "CODE 4912 (47x18mm) - RM 28.00/PCS",
                    "CODE 4913 (58x22mm) - RM 35.00/PCS",
                    "CODE 9511 (Pocket Stamp) - RM 28.00/PCS",
                    "CODE 4630 (Round 30mm) - RM 35.00/PCS",
                    "CODE 4916 (70x10mm) - RM 25.00/PCS"
                ],
                required: true
            },
            {
                type: "textarea",
                name: "stamp_text",
                label: "Stamp Text",
                placeholder: "e.g. JOHN SMITH / Senior Manager",
                required: false,
                full: true
            },
            {
                type: "textarea",
                name: "request_notes",
                label: "Additional Notes",
                placeholder: "e.g. ink colour or special requirement",
                required: false,
                full: true
            }
        ]
    },

    "Custom Sublimation Apparel": {
        fields: [
            {
                type: "select",
                name: "apparel_type",
                label: "Apparel Style",
                options: [
                    "ROUNDNECK SHORT SLEEVE - RM 29.00",
                    "ROUNDNECK LONG SLEEVE - RM 35.00",
                    "MUSLIMAH CUT - RM 36.00",
                    "SHORT SLEEVE POLO COLLAR - RM 34.00",
                    "SHORT SLEEVE RETRO COLLAR - RM 36.00"
                ],
                required: true
            },
            {
                type: "select",
                name: "apparel_size",
                label: "Size Choice",
                options: [
                    "XS", "S", "M", "L", "XL",
                    "2XL", "3XL", "4XL", "5XL"
                ],
                required: true
            },
            {
                type: "textarea",
                name: "request_notes",
                label: "Additional Notes",
                placeholder: "e.g. name, number, sponsor logo placement",
                required: false,
                full: true
            }
        ],
        minimumQuantity: 15
    },

    "Banner & Bunting Printing": {
        fields: [
            {
                type: "select",
                name: "banner_size",
                label: "Banner / Bunting Dimension",
                options: [
                    "2 X 2 FT - RM 15.00",
                    "2 X 3 FT - RM 21.00",
                    "2 X 4 FT - RM 27.00",
                    "2 X 5 FT - RM 34.00",
                    "2 X 6 FT - RM 40.00",
                    "4 X 2 FT - RM 25.00",
                    "4 X 3 FT - RM 38.00",
                    "4 X 4 FT - RM 51.00",
                    "4 X 5 FT - RM 64.00",
                    "4 X 6 FT - RM 76.00",
                    "4 X 7 FT - RM 89.00",
                    "5 X 2 FT - RM 32.00",
                    "5 X 3 FT - RM 48.00",
                    "5 X 4 FT - RM 64.00",
                    "5 X 5 FT - RM 80.00",
                    "5 X 6 FT - RM 96.00",
                    "5 X 7 FT - RM 112.00",
                    "6 X 2 FT - RM 38.00",
                    "6 X 3 FT - RM 55.00",
                    "6 X 4 FT - RM 75.00",
                    "6 X 5 FT - RM 96.00",
                    "6 X 6 FT - RM 115.00",
                    "6 X 7 FT - RM 134.00",
                    "7 X 2 FT - RM 41.00",
                    "7 X 3 FT - RM 62.00",
                    "7 X 4 FT - RM 83.00",
                    "7 X 5 FT - RM 104.00",
                    "7 X 6 FT - RM 125.00",
                    "7 X 7 FT - RM 146.00",
                    "8 X 2 FT - RM 51.00",
                    "8 X 3 FT - RM 76.00",
                    "8 X 4 FT - RM 99.00",
                    "8 X 5 FT - RM 128.00",
                    "8 X 6 FT - RM 153.00",
                    "8 X 7 FT - RM 179.00",
                    "9 X 2 FT - RM 57.00",
                    "9 X 3 FT - RM 86.00",
                    "9 X 4 FT - RM 115.00",
                    "9 X 5 FT - RM 144.00",
                    "9 X 6 FT - RM 172.00",
                    "9 X 7 FT - RM 201.00",
                    "10 X 2 FT - RM 64.00",
                    "10 X 3 FT - RM 96.00",
                    "10 X 4 FT - RM 120.00",
                    "10 X 5 FT - RM 160.00",
                    "10 X 6 FT - RM 192.00",
                    "10 X 7 FT - RM 224.00"
                ],
                required: true
            },
            {
                type: "textarea",
                name: "request_notes",
                label: "Additional Notes",
                placeholder: "e.g. eyelets, wooden rods, pocket or special finishing",
                required: false,
                full: true
            }
        ]
    },

    "Premium Wedding & Business Cards": {
        fields: [
            {
                type: "select",
                name: "card_type",
                label: "Card Type & Finishing",
                options: [
                    "SOFT TOUCH LAMINATION (300 GSM)",
                    "GLOSSY LAMINATION (300 GSM)"
                ],
                required: true
            },
            {
                type: "select",
                name: "card_qty",
                label: "Select Quantity Package",
                options: [],
                required: true,
                dependsOn: "card_type"
            },
            {
                type: "textarea",
                name: "card_details",
                label: "Card Text & Details",
                placeholder: "e.g. Name, Designation, Company, Contact",
                required: false,
                full: true
            },
            {
                type: "textarea",
                name: "request_notes",
                label: "Additional Notes",
                placeholder: "Any special card requirements",
                required: false,
                full: true
            }
        ]
    },

    "Outdoor Promotional Windflag": {
        fields: [
            {
                type: "select",
                name: "windflag_size",
                label: "Select Windflag Height",
                options: [
                    "3.4 METERS — RM 190.00",
                    "5.0 METERS — RM 280.00"
                ],
                required: true
            },
            {
                type: "select",
                name: "windflag_design",
                label: "Graphic Design Service",
                options: [
                    "Provide Ready Artwork (RM 0.00)",
                    "Request Graphic Design Service (+RM 30.00)"
                ],
                required: true
            },
            {
                type: "textarea",
                name: "windflag_details",
                label: "Text Details & Design Brief",
                placeholder: "e.g. brand name, offer or preferred colours",
                required: false,
                full: true
            }
        ]
    },

    "Mirrokote Product Stickers (Round & Square)": {
        fields: [
            {
                type: "select",
                name: "sticker_shape",
                label: "Sticker Shape",
                options: [
                    "ROUND / CIRCLE",
                    "SQUARE"
                ],
                required: true
            },
            {
                type: "select",
                name: "sticker_size",
                label: "Sticker Size",
                options: [
                    "SIZE: 3 CM",
                    "SIZE: 4 CM",
                    "SIZE: 5 CM",
                    "SIZE: 6 CM"
                ],
                required: true
            },
            {
                type: "select",
                name: "sticker_qty",
                label: "Select Quantity Package",
                options: [],
                required: true,
                dependsOn: "sticker_size"
            },
            {
                type: "textarea",
                name: "sticker_details",
                label: "Sticker Details",
                placeholder: "e.g. product name or label text",
                required: false,
                full: true
            },
            {
                type: "textarea",
                name: "request_notes",
                label: "Additional Notes",
                placeholder: "Any special sticker requirements",
                required: false,
                full: true
            }
        ]
    },

    "Others (Custom Request)": {
        fields: [
            {
                type: "textarea",
                name: "request_notes",
                label: "Describe Your Product / Requirements",
                placeholder: "e.g. Specify dimensions, materials, colours, or special details...",
                required: true,
                full: true
            }
        ]
    }
};


/* Exact package choices from product detail code */

const cardPackages = {
    "SOFT TOUCH LAMINATION (300 GSM)": [
        "100 PCS - RM 28.00",
        "200 PCS - RM 42.00",
        "300 PCS - RM 51.00",
        "500 PCS - RM 71.00",
        "1000 PCS - RM 88.00"
    ],
    "GLOSSY LAMINATION (300 GSM)": [
        "100 PCS - RM 37.00",
        "200 PCS - RM 53.00",
        "300 PCS - RM 57.00",
        "500 PCS - RM 79.00",
        "1000 PCS - RM 103.00"
    ]
};

const stickerPackages = {
    "SIZE: 3 CM": [
        "100 PCS - RM 62.00",
        "200 PCS - RM 65.00",
        "300 PCS - RM 67.00",
        "500 PCS - RM 80.00",
        "1000 PCS - RM 89.00"
    ],
    "SIZE: 4 CM": [
        "100 PCS - RM 74.00",
        "200 PCS - RM 77.00",
        "300 PCS - RM 81.00",
        "500 PCS - RM 87.00",
        "1000 PCS - RM 105.00"
    ],
    "SIZE: 5 CM": [
        "100 PCS - RM 75.00",
        "200 PCS - RM 82.00",
        "300 PCS - RM 87.00",
        "500 PCS - RM 97.00",
        "1000 PCS - RM 124.00"
    ],
    "SIZE: 6 CM": [
        "100 PCS - RM 77.00",
        "200 PCS - RM 85.00",
        "300 PCS - RM 93.00",
        "500 PCS - RM 108.00",
        "1000 PCS - RM 150.00"
    ]
};


/* =========================
   DELIVERY ADDRESS
========================= */

const collectionMethod = document.getElementById('collection_method');
const deliveryAddressField = document.getElementById('deliveryAddressField');
const deliveryAddress = document.getElementById('delivery_address');

if (collectionMethod) {
    collectionMethod.addEventListener('change', function() {
        const isDelivery = this.value === 'Delivery';
        deliveryAddressField.style.display = isDelivery ? 'block' : 'none';
        deliveryAddress.required = isDelivery;
        if (!isDelivery) {
            deliveryAddress.value = '';
        }
    });
}


/* =========================
   ARTWORK FILE NAME
========================= */

const artworkInput = document.getElementById('artwork');

if (artworkInput) {
    artworkInput.addEventListener('change', function() {
        const fileName = document.getElementById('file-name');
        fileName.textContent = this.files.length ? this.files[0].name : 'No file chosen';
    });
}


/* =========================
   QUANTITY CONTROL (FIXED)
========================= */

const qtyValue = document.getElementById('qty-value');
const qtyInput = document.getElementById('quantity');
const qtyMinus = document.getElementById('qty-minus');
const qtyPlus = document.getElementById('qty-plus');

if (qtyMinus && qtyPlus && qtyValue && qtyInput) {

    const newMinus = qtyMinus.cloneNode(true);
    const newPlus = qtyPlus.cloneNode(true);

    qtyMinus.parentNode.replaceChild(newMinus, qtyMinus);
    qtyPlus.parentNode.replaceChild(newPlus, qtyPlus);

    function getValidQty() {
        let val = parseInt(qtyInput.value, 10);
        return isNaN(val) || val < 1 ? 1 : val;
    }

    newMinus.addEventListener('click', function(e) {
        e.preventDefault();
        let currentQty = getValidQty();
        if (currentQty > 1) {
            currentQty--;
            qtyValue.textContent = currentQty;
            qtyInput.value = currentQty;
        }
    });

    newPlus.addEventListener('click', function(e) {
        e.preventDefault();
        let currentQty = getValidQty();
        currentQty++;
        qtyValue.textContent = currentQty;
        qtyInput.value = currentQty;
    });
}


/* =========================
   FORM VALIDATION
========================= */

const form = document.getElementById('customRequestForm');
const productNameInput = document.getElementById('product_name');

if (form) {
    form.addEventListener('submit', function(event) {
        if (!productNameInput.value.trim()) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Product Name Required',
                text: 'Please enter the name of the product you request.',
                confirmButtonColor: '#f0208d'
            });
            productNameInput.focus();
            return;
        }
    });
}


/* =========================
   SUCCESS / ERROR POPUP
========================= */

document.addEventListener('DOMContentLoaded', function() {

    const result = document.getElementById('requestResult');

    if (!result) return;

    const status = result.dataset.status;
    const message = result.dataset.message;

    if (status === 'success') {

        Swal.fire({
            icon: 'success',
            title: 'Request Submitted!',
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#f0208d',
            allowOutsideClick: false
        }).then(function() {

            if (form) form.reset();

            if (qtyValue && qtyInput) {
                qtyValue.textContent = '1';
                qtyInput.value = '1';
            }

            if (deliveryAddressField && deliveryAddress) {
                deliveryAddressField.style.display = 'none';
                deliveryAddress.required = false;
            }

            const fileNameElem = document.getElementById('file-name');
            if (fileNameElem) {
                fileNameElem.textContent = 'No file chosen';
            }

        });

    } else if (status === 'error') {

        Swal.fire({
            icon: 'error',
            title: 'Request Not Submitted',
            text: message,
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#f0208d'
        });

    }

});


/* =========================
   MOBILE MENU
========================= */

const menuToggle = document.getElementById('menuToggle');
const navbar = document.querySelector('.navbar');

if (menuToggle && navbar) {

    menuToggle.addEventListener('click', function() {

        const open = navbar.classList.toggle('menu-open');

        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        menuToggle.innerHTML = open
            ? '<i class="fa-solid fa-xmark"></i>'
            : '<i class="fa-solid fa-bars"></i>';

    });

}

</script>

</body>
</html>
