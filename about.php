<?php
session_start();
include('db.php'); // Sambungan ke pangkalan data

// Ambil jumlah item dalam cart dari session
$total_cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_cart_count += isset($item['quantity']) ? (int)$item['quantity'] : 1;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | SA Design</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f6e8f2;
            color: #172033;
            min-height: 100vh;
            overflow-x: hidden;
        }

        button, input, select { font: inherit; }
        a { text-decoration: none; }

        /* ==================== NAVBAR (SAMA DENGAN HOME) ==================== */
        .navbar {
            height: 84px;
            padding: 16px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            background: #fff;
            border-bottom: 1px solid #e8ebf2;
            position: relative;
            z-index: 20;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            min-width: max-content;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .logo-title {
            font-size: 36px;
            font-weight: 900;
            line-height: .9;
            letter-spacing: -2.5px;
            white-space: nowrap;
        }

        .logo-sa { color: #f0208d; }
        .logo-design { color: #1557d6; }

        .logo-subtitle {
            color: #111827;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 32px;
            list-style: none;
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

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 15px;
            border-radius: 11px;
            background: linear-gradient(135deg, #f0208d, #ff3b86);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            box-shadow: 0 8px 18px rgba(239, 58, 155, .20);
            transition: .2s;
        }

        .login-pill:hover { transform: translateY(-2px); }

        .logout-pill {
            border: 0;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .logout-pill:focus { outline: none; }

        .icon-btn {
            position: relative;
            width: 42px;
            height: 42px;
            border: 1px solid #f0208d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #f0208d;
            transition: .2s;
        }

        .icon-btn:hover {
            transform: translateY(-2px);
            background: #ffe4f2;
        }

        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 19px;
            height: 19px;
            border: 2px solid #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0208d;
            color: #fff;
            font-size: 9px;
            font-weight: 900;
        }

        .menu-toggle {
            display: none;
            width: 42px;
            height: 42px;
            border: 1px solid #dfe4ef;
            border-radius: 11px;
            background: #fff;
            color: #172033;
            cursor: pointer;
        }

        /* ==================== ABOUT HERO ==================== */
        .about-hero {
            position: relative;
            overflow: hidden;
            padding: 78px 70px 72px;
            background: linear-gradient(120deg, #b7c8ff 0%, #d9b9eb 52%, #ffc0dc 100%);
            border-bottom: 1px solid #e0e7f8;
        }

        .about-hero::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            right: -90px;
            top: -150px;
            border-radius: 50%;
            background: rgba(40, 84, 197, .20);
        }

        .about-hero::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            left: -90px;
            bottom: -130px;
            border-radius: 50%;
            background: rgba(239, 58, 155, .20);
        }

        .about-hero-content {
            position: relative;
            z-index: 2;
            max-width: 1050px;
            margin: auto;
            text-align: center;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 14px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #dbe4ff;
            color: #1747c7;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .7px;
        }

        .about-hero h1 {
            margin: 15px 0 10px;
            color: #102f91;
            font-size: clamp(35px, 5vw, 52px);
            font-weight: 900;
            letter-spacing: -2px;
        }

        .about-hero h1 span { color: #f0208d; }

        .about-hero p {
            max-width: 720px;
            margin: auto;
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        /* ==================== MAIN INFO ==================== */
        main {
            width: min(1120px, calc(100% - 140px));
            margin: 55px auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 28px;
        }

        .section-title h2 {
            margin-top: 11px;
            color: #102f91;
            font-size: 30px;
            font-weight: 900;
            letter-spacing: -1px;
        }

        .section-title p {
            margin-top: 6px;
            color: #64748b;
            font-size: 13px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .info-card {
            position: relative;
            overflow: hidden;
            min-height: 270px;
            padding: 28px 26px 30px;
            background: #eff4ff;
            border: 1px solid #c9d8fb;
            border-radius: 8px;
            box-shadow: none;
            transition: .25s;
        }

        .info-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: #1747c7;
        }

        .info-card:nth-child(2)::before { background: #f0208d; }
        .info-card:nth-child(3)::before { background: #07966c; }

        .info-card:nth-child(2) {
            background: #fff0f7;
            border-color: #f6c4dc;
        }

        .info-card:nth-child(3) {
            background: #effaf6;
            border-color: #c2e9da;
        }

        .info-card::after {
            content: "";
            position: absolute;
            width: 78px;
            height: 78px;
            right: -20px;
            bottom: -20px;
            background: #dbe7ff;
            transform: rotate(45deg);
        }

        .info-card:nth-child(2)::after { background: #ffd9e9; }
        .info-card:nth-child(3)::after { background: #d8f3e8; }

        .info-card:hover {
            transform: translateY(-5px);
            border-color: #9bb4f6;
            box-shadow: 0 14px 26px rgba(28, 53, 118, .10);
        }

        .info-label {
            display: block;
            margin-bottom: 22px;
            color: #1747c7;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 1.1px;
        }

        .info-card:nth-child(2) .info-label { color: #f0208d; }
        .info-card:nth-child(3) .info-label { color: #07966c; }

        .info-card .info-icon {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 38px;
            height: 38px;
            margin: 0;
            border-radius: 50%;
            font-size: 15px;
        }

        .info-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            margin-bottom: 17px;
            border-radius: 13px;
            background: #cddaff;
            color: #1747c7;
            font-size: 19px;
        }

        .info-card:nth-child(2) .info-icon {
            background: #ffc9df;
            color: #f0208d;
        }

        .info-card:nth-child(3) .info-icon {
            background: #dff5ed;
            color: #07966c;
        }

        .info-card h2 {
            margin-bottom: 14px;
            color: #102f91;
            font-size: 22px;
            font-weight: 900;
        }

        .info-card p {
            position: relative;
            z-index: 1;
            color: #64748b;
            font-size: 12px;
            line-height: 1.7;
            margin-bottom: 13px;
        }

        /* ==================== LOCATION + CONTACT ==================== */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 20px;
            margin-top: 22px;
        }

        .content-card {
            padding: 27px;
            background: #fff;
            border: 1px solid #e4e8f2;
            border-radius: 20px;
            box-shadow: 0 9px 23px rgba(28, 53, 118, .06);
        }

        .content-card h2 {
            margin-bottom: 17px;
            color: #102f91;
            font-size: 19px;
            font-weight: 900;
        }

        .content-card iframe {
            width: 100%;
            height: 330px;
            border: 0;
            border-radius: 14px;
            display: block;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid #e1dce7;
        }

        .contact-item > div:last-child { min-width: 0; }

        .contact-item:last-child { border-bottom: 0; }

        .contact-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: #cddaff;
            color: #1747c7;
        }

        .contact-item:nth-child(3) .contact-icon {
            background: #ffc9df;
            color: #f0208d;
        }

        .contact-item:nth-child(4) .contact-icon {
            background: #dff5ed;
            color: #07966c;
        }

        .contact-item strong {
            display: block;
            margin-bottom: 3px;
            color: #172033;
            font-size: 11px;
        }

        .contact-item span,
        .contact-link {
            color: #64748b;
            font-size: 11px;
            line-height: 1.6;
        }

        .contact-link:hover { color: #f0208d; }

        /* ==================== CTA ==================== */
        .about-cta {
            position: relative;
            overflow: hidden;
            margin-top: 22px;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            border-radius: 20px;
            background: linear-gradient(120deg, #cddaff, #ffc9df);
            border: 1px solid #b7c8ff;
        }

        .about-cta h3 {
            color: #102f91;
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .about-cta p {
            color: #64748b;
            font-size: 11px;
        }

        .cta-button {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 12px;
            background: #1747c7;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            transition: .25s;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            background: #f0208d;
        }

        /* ==================== MODAL ==================== */
        .login-required-modal {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(17, 24, 39, .55);
        }

        .login-required-modal.is-open { display: flex; }

        .login-required-dialog {
            position: relative;
            width: min(420px, 100%);
            padding: 36px 30px 30px;
            border-radius: 20px;
            background: #fff;
            text-align: center;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .2);
        }

        .login-required-dialog > i {
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: #efeeff;
            color: #5f58c8;
            font-size: 22px;
        }

        .login-required-dialog h2 {
            margin-bottom: 9px;
            color: #111827;
            font-size: 22px;
        }

        .login-required-dialog p {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
        }

        .login-required-close {
            position: absolute;
            top: 10px;
            right: 13px;
            width: 44px;
            height: 44px;
            border: 0;
            background: transparent;
            color: #6b7280;
            font-size: 25px;
            cursor: pointer;
        }

        .login-required-button {
            display: inline-block;
            margin-top: 22px;
            padding: 12px 19px;
            border-radius: 10px;
            background: #5f58c8;
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        /* ==================== FOOTER ==================== */
        footer {
            margin-top: 0;
            padding: 38px 70px;
            background: #0f1f61;
            color: #fff;
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

        .footer-brand span { color: #f0208d; }

        footer h4 {
            margin-bottom: 10px;
            font-size: 12px;
        }

        footer p,
        footer a {
            color: rgba(255, 255, 255, .72);
            font-size: 11px;
            line-height: 1.7;
        }

        footer a {
            display: block;
            margin-bottom: 4px;
        }

        footer a:hover { color: #fff; }

        .footer-line {
            width: min(1120px, 100%);
            margin: 24px auto 0;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, .14);
            text-align: center;
            color: rgba(255, 255, 255, .58);
            font-size: 10px;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1100px) {
            .navbar { padding: 16px 30px; }
            .nav-links { gap: 20px; }
        }

        @media (max-width: 900px) {
            .about-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .bottom-grid { grid-template-columns: 1fr; }
            main { width: min(720px, calc(100% - 56px)); }
        }

        @media (max-width: 760px) {
            .navbar {
                height: auto;
                min-height: 76px;
                padding: 15px 20px;
                flex-wrap: wrap;
            }

            .logo-title { font-size: 29px; }
            .logo-subtitle { font-size: 9px; }

            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .nav-icons { margin-left: auto; }

            .login-pill span { display: none; }
            .login-pill {
                width: 40px;
                height: 40px;
                padding: 0;
                justify-content: center;
            }

            .icon-btn { width: 40px; height: 40px; }

            .nav-links {
                display: none;
                order: 4;
                width: 100%;
                padding: 12px 0 3px;
                flex-direction: column;
                align-items: center;
                gap: 14px;
                border-top: 1px solid #eef1f6;
            }

            .navbar.menu-open .nav-links { display: flex; }

            .about-hero { padding: 55px 22px; }
            .about-hero h1 { font-size: 38px; }

            main {
                width: calc(100% - 40px);
                margin: 40px auto;
            }

            .about-cta {
                flex-direction: column;
                align-items: flex-start;
                padding: 25px 22px;
            }

            .cta-button {
                width: 100%;
                justify-content: center;
            }

            footer { padding: 35px 20px; }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
        }

        /* Mobile-only layout safeguards: preserve the desktop design while allowing
           dense content and touch controls to fit fluidly on every phone width. */
        @media (max-width: 600px) {
            .about-hero { padding: 46px 20px; }
            .about-hero h1 { font-size: clamp(2.1rem, 10vw, 2.45rem); letter-spacing: -1.5px; }
            .about-hero p { font-size: 13px; }

            main { width: calc(100% - 32px); margin: 32px auto; }
            .about-grid { grid-template-columns: minmax(0, 1fr); }
            .info-card { min-height: 0; padding: 24px 20px; }
            .section-title { margin-bottom: 22px; }
            .section-title h2 { font-size: clamp(1.5rem, 7vw, 1.8rem); }

            .content-card { padding: 20px; border-radius: 16px; }
            .content-card iframe { height: clamp(230px, 70vw, 300px); }
            .about-cta { padding: 22px 20px; }

            .login-required-modal { padding: 14px; }
            .login-required-dialog { width: 100%; max-height: calc(100vh - 28px); overflow-y: auto; padding: 32px 20px 24px; }

            .nav-links a { display: inline-flex; align-items: center; min-height: 44px; }
            .cta-button, .login-required-button { min-height: 44px; justify-content: center; }
            .contact-link { display: inline-flex; align-items: center; min-height: 44px; overflow-wrap: anywhere; }
            footer a { display: flex; align-items: center; min-height: 44px; }
        }

        @media (max-width: 380px) {
            .navbar { padding: 12px 14px; gap: 10px; }
            .logo-title { font-size: 26px; letter-spacing: -1.6px; }
            .logo-subtitle { font-size: 7px; letter-spacing: .45px; }
            .menu-toggle, .icon-btn, .login-pill { width: 44px; height: 44px; }
            .nav-icons { order: 3; width: 100%; margin-left: 0; justify-content: flex-end; }
            .nav-links { order: 4; }
            .about-hero { padding: 40px 16px; }
            main { width: calc(100% - 24px); }
            .content-card { padding: 18px 16px; }
            .section-badge { max-width: 100%; text-align: left; }
        }

        /* STYLES UNTUK SWEETALERT LOGOUT */
        .logout-popup { border-radius: 18px !important; padding: 28px !important; }
        .logout-title { color: #102f91 !important; font-family: 'Plus Jakarta Sans', Arial, sans-serif !important; font-size: 24px !important; font-weight: 800 !important; }
        .logout-text { color: #64748b !important; font-family: 'Plus Jakarta Sans', Arial, sans-serif !important; font-size: 13px !important; }
        .logout-confirm, .logout-cancel { border-radius: 10px !important; padding: 10px 18px !important; font-family: 'Plus Jakarta Sans', Arial, sans-serif !important; font-size: 12px !important; font-weight: 800 !important; }
    </style>
    <link rel="stylesheet" href="ui_polish.css">
</head>

<body>

<!-- NAVBAR (IDENTIK DENGAN HOME) -->
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
        <li><a href="about.php" class="active">About Us</a></li>
        <li><a href="custom_request.php">Custom Request</a></li>
    </ul>

    <div class="nav-icons">
        <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
            <a href="cust_profile.php" class="login-pill" title="My profile">
                <i class="fa-regular fa-user"></i><span>Profile</span>
            </a>
            <button type="button" class="login-pill logout-pill" title="Log out" onclick="confirmLogout()">
                <i class="fa-solid fa-right-from-bracket"></i><span>Log out</span>
            </button>
        <?php else: ?>
            <a href="login.php" class="login-pill" title="Login">
                <i class="fa-regular fa-user"></i><span>Login</span>
            </a>
        <?php endif; ?>
        <a href="cart.php" class="icon-btn" title="Cart" id="cartBtn" aria-label="Shopping cart">
            <i class="fa-solid fa-bag-shopping"></i>
            <span class="cart-badge" id="cartBadgeCount"><?= $total_cart_count ?></span>
        </a>
        <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
            <a href="wishlist.php" class="icon-btn" title="Wishlist" aria-label="My wishlist"><i class="fa-regular fa-heart"></i></a>
        <?php endif; ?>
    </div>
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
</nav>

<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge">
            <i class="fa-solid fa-building"></i>
            ABOUT SA DESIGN
        </span>
        <h1>Printing That Brings <span>Ideas</span> to Life</h1>
        <p>
            SA Design provides quality printing and advertising solutions
            for businesses, events, schools and personal needs.
        </p>
    </div>
</section>

<main>
    <div class="section-title">
        <span class="section-badge">
            <i class="fa-solid fa-compass"></i>
            WHO WE ARE
        </span>
        <h2>Our Mission, Vision &amp; Availability</h2>
        <p>Learn more about what drives SA Design and how we serve our customers.</p>
    </div>

    <div class="about-grid">
        <section class="info-card">
            <span class="info-label">01 / PURPOSE</span>
            <div class="info-icon"><i class="fa-solid fa-bullseye"></i></div>
            <h2>Mission</h2>
            <p>To make SA DESIGN the leading printing center in Muadzam Shah.</p>
            <p>To open up job opportunities for young people in Muadzam Shah.</p>
        </section>

        <section class="info-card">
            <span class="info-label">02 / DIRECTION</span>
            <div class="info-icon"><i class="fa-solid fa-eye"></i></div>
            <h2>Vision</h2>
            <p>To become the leading provider of printing materials in Muadzam Shah by the year 2030.</p>
        </section>

        <section class="info-card">
            <span class="info-label">03 / OPENING HOURS</span>
            <div class="info-icon"><i class="fa-solid fa-clock"></i></div>
            <h2>Availability</h2>
            <p><strong>Monday – Friday</strong><br>9:00 AM – 6:00 PM</p>
            <p><strong>Saturday &amp; Sunday</strong><br>Closed. Custom request messages can be sent at any time.</p>
        </section>
    </div>

    <div class="section-title" style="margin-top:55px;">
        <span class="section-badge">
            <i class="fa-solid fa-location-dot"></i>
            FIND US
        </span>
        <h2>Visit &amp; Contact Us</h2>
        <p>Have a question or need a custom printing solution? We’re ready to help.</p>
    </div>

    <div class="bottom-grid">
        <section class="content-card">
            <h2><i class="fa-solid fa-map-location-dot"></i> Location</h2>
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3787.0147704358624!2d103.08029067718353!3d3.0552928889801323!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cf0bd4bf3939b%3A0x5c12ede11de0b6a3!2sPUSAT%20PERCETAKAN%20MUADZAM%20SHAH%20%7C%20SA%20DESIGN!5e1!3m2!1sen!2smy!4v1785986672640!5m2!1sen!2smy"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                title="SA Design location map">
            </iframe>
        </section>

        <section class="content-card">
            <h2><i class="fa-solid fa-address-card"></i> Contact Us</h2>

            <div class="contact-item">
                <div class="contact-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                    <strong>ADDRESS</strong>
                    <span>MM29, Medan Mewah, 26700 Muadzam Shah, Pahang</span>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-icon"><i class="fa-solid fa-phone"></i></div>
                <div>
                    <strong>PHONE</strong>
                    <a href="tel:+60194184147" class="contact-link">+60194184147</a>
                </div>
            </div>

            <div class="contact-item">
                <div class="contact-icon"><i class="fa-solid fa-envelope"></i></div>
                <div>
                    <strong>EMAIL</strong>
                    <a href="mailto:sadesignartwork@gmail.com" class="contact-link">sadesignartwork@gmail.com</a>
                </div>
            </div>
        </section>
    </div>

    <div class="about-cta">
        <div>
            <h3>Ready to bring your idea to print?</h3>
            <p>Start a custom request and tell us what you need.</p>
        </div>
        <a href="custom_request.php" class="cta-button">
            Start Custom Request
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</main>

<div id="loginRequiredModal" class="login-required-modal" aria-hidden="true">
    <div class="login-required-dialog" role="dialog" aria-modal="true" aria-labelledby="loginRequiredTitle">
        <button type="button" class="login-required-close" aria-label="Close">&times;</button>
        <i class="fa-solid fa-lock"></i>
        <h2 id="loginRequiredTitle">Login required</h2>
        <p>Please log in or create an account before accessing your shopping cart.</p>
        <a href="login.php" class="login-required-button">Login or Register</a>
    </div>
</div>

<footer>
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
            <a href="mailto:sadesignartwork@gmail.com">Email Us</a>
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

// Semakan status login & fungsi Cart modal
const isLoggedIn = <?= isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true ? 'true' : 'false' ?>;
const loginRequiredModal = document.getElementById('loginRequiredModal');

const showLoginRequiredModal = () => {
    loginRequiredModal.classList.add('is-open');
    loginRequiredModal.setAttribute('aria-hidden','false');
};
const closeLoginRequiredModal = () => {
    loginRequiredModal.classList.remove('is-open');
    loginRequiredModal.setAttribute('aria-hidden','true');
};

if (!isLoggedIn) {
    const cartBtn = document.getElementById('cartBtn');
    if (cartBtn) {
        cartBtn.addEventListener('click', event => {
            event.preventDefault();
            showLoginRequiredModal();
        });
    }
}

document.querySelector('.login-required-close').addEventListener('click', closeLoginRequiredModal);
loginRequiredModal.addEventListener('click', event => {
    if(event.target === loginRequiredModal) closeLoginRequiredModal();
});

// Responsive menu toggle
const menuToggle = document.getElementById('menuToggle');
const navbar = document.querySelector('.navbar');

menuToggle.addEventListener('click', () => {
    const isOpen = navbar.classList.toggle('menu-open');
    menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    menuToggle.innerHTML = isOpen
        ? '<i class="fa-solid fa-xmark"></i>'
        : '<i class="fa-solid fa-bars"></i>';
});

document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        navbar.classList.remove('menu-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
    });
});
</script>

</body>
</html>
