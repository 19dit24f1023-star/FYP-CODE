<?php
session_start();

// Calculate total quantity of products in the session cart.
$total_cart_count = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_cart_count += isset($item['quantity'])
            ? (int)$item['quantity']
            : 1;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SA Design | About Us</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://cdnjs.cloudflare.com">

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    scroll-behavior:smooth;
}

body{
    font-family:'Plus Jakarta Sans',sans-serif;
    background:#f6e8f2;
    color:#172033;
    min-height:100vh;
}

button,
input,
select{
    font:inherit;
}

a{
    text-decoration:none;
}

.page-card{
    width:100%;
    min-height:100vh;
    background:#fff;
    overflow:hidden;
}

/* =========================================================
   NAVBAR
========================================================= */

.navbar{
    height:84px;
    padding:16px 48px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:25px;
    background:#fff;
    border-bottom:1px solid #e8ebf2;
    position:relative;
    z-index:20;
}

.nav-logo{
    display:flex;
    align-items:center;
    min-width:max-content;
}

.logo-text{
    display:flex;
    flex-direction:column;
    gap:2px;
}

.logo-title{
    font-size:36px;
    font-weight:900;
    line-height:.9;
    letter-spacing:-2.5px;
    white-space:nowrap;
}

.logo-sa{
    color:#f0208d;
}

.logo-design{
    color:#1557d6;
}

.logo-subtitle{
    color:#111827;
    font-size:12px;
    font-weight:900;
    letter-spacing:.7px;
    text-transform:uppercase;
}

.nav-links{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:32px;
    list-style:none;
}

.nav-links a{
    color:#111827;
    font-size:13px;
    font-weight:800;
    text-transform:uppercase;
    transition:.2s;
}

.nav-links a:hover,
.nav-links a.active{
    color:#f0208d;
}

.nav-icons{
    display:flex;
    align-items:center;
    gap:10px;
}

.login-pill{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:10px 15px;
    border-radius:11px;
    background:linear-gradient(135deg,#f0208d,#ff3b86);
    color:#fff;
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    box-shadow:0 8px 18px rgba(239,58,155,.20);
    transition:.2s;
}

.login-pill:hover{
    transform:translateY(-2px);
}

.logout-pill{
    border:0;
    cursor:pointer;
    font-family:'Plus Jakarta Sans',sans-serif;
}

.logout-pill:focus{
    outline:none;
}

.icon-btn{
    position:relative;
    width:42px;
    height:42px;
    border:1px solid #f0208d;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fff;
    color:#f0208d;
    transition:.2s;
}

.icon-btn:hover{
    transform:translateY(-2px);
    background:#ffe4f2;
}

.cart-badge{
    position:absolute;
    top:-4px;
    right:-4px;
    width:19px;
    height:19px;
    border:2px solid #fff;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#f0208d;
    color:#fff;
    font-size:9px;
    font-weight:900;
}

.menu-toggle{
    display:none;
    width:42px;
    height:42px;
    border:1px solid #dfe4ef;
    border-radius:11px;
    background:#fff;
    color:#172033;
    cursor:pointer;
}

/* =========================================================
   PAGE HERO
========================================================= */

.about-hero{
    position:relative;
    min-height:390px;
    display:flex;
    align-items:center;
    overflow:hidden;
    background:
        linear-gradient(
            120deg,
            #ffffff 0%,
            #d8e1ff 55%,
            #c7d5ff 100%
        );
}

.about-hero:before{
    content:"";
    position:absolute;
    inset:0;
    background:
        linear-gradient(
            135deg,
            transparent 0 55%,
            rgba(40,84,197,.20) 55% 61%,
            transparent 61%
        ),
        linear-gradient(
            140deg,
            transparent 0 67%,
            rgba(239,58,155,.20) 67% 75%,
            transparent 75%
        );
    pointer-events:none;
}

.about-hero-content{
    position:relative;
    z-index:2;
    max-width:720px;
    padding:65px 70px;
}

.hero-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 15px;
    border-radius:999px;
    background:#fff;
    border:1px solid #b7c8ff;
    color:#1747c7;
    font-size:10px;
    font-weight:800;
    letter-spacing:.7px;
}

.about-hero h1{
    margin-top:16px;
    color:#111827;
    font-size:clamp(38px,5vw,64px);
    line-height:.95;
    font-weight:900;
    letter-spacing:-3px;
    text-transform:uppercase;
}

.about-hero h1 span{
    color:#f0208d;
}

.about-hero p{
    max-width:600px;
    margin-top:17px;
    color:#525b6c;
    font-size:13px;
    line-height:1.7;
}

/* =========================================================
   INTRO SECTION
========================================================= */

.intro-section{
    padding:70px;
    background:#fff;
}

.intro-container{
    max-width:1120px;
    margin:auto;
    display:grid;
    grid-template-columns:1.05fr .95fr;
    gap:55px;
    align-items:center;
}

.intro-content .section-badge,
.story-content .section-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:7px 14px;
    border-radius:999px;
    background:#d5dfff;
    border:1px solid #b7c8ff;
    color:#1747c7;
    font-size:10px;
    font-weight:800;
    letter-spacing:.7px;
}

.intro-content h2,
.story-content h2{
    margin-top:13px;
    color:#102f91;
    font-size:35px;
    line-height:1.12;
    font-weight:900;
    letter-spacing:-1.3px;
}

.intro-content h2 span,
.story-content h2 span{
    color:#f0208d;
}

.intro-content p,
.story-content p{
    margin-top:14px;
    color:#64748b;
    font-size:13px;
    line-height:1.8;
}

.intro-content p + p{
    margin-top:10px;
}

.intro-image{
    position:relative;
    min-height:330px;
    border-radius:26px;
    overflow:hidden;
    background:linear-gradient(135deg,#d7e0ff,#f4d9ec);
    border:1px solid #c4d0ef;
    box-shadow:0 18px 38px rgba(28,53,118,.13);
}

.intro-image:before{
    content:"";
    position:absolute;
    width:190px;
    height:190px;
    top:-85px;
    right:-55px;
    border-radius:50%;
    background:rgba(239,58,155,.17);
}

.intro-image:after{
    content:"";
    position:absolute;
    width:170px;
    height:170px;
    bottom:-90px;
    left:-65px;
    border-radius:50%;
    background:rgba(40,84,197,.17);
}

.intro-image-content{
    position:absolute;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    z-index:2;
}

.intro-logo{
    font-size:48px;
    font-weight:900;
    letter-spacing:-3px;
}

.intro-logo .pink{
    color:#f0208d;
}

.intro-logo .blue{
    color:#1557d6;
}

.intro-image-content p{
    margin-top:7px;
    color:#172033;
    font-size:11px;
    font-weight:900;
    letter-spacing:1px;
    text-transform:uppercase;
}

/* =========================================================
   MISSION / VISION
========================================================= */

.mission-section{
    padding:68px 70px;
    background:linear-gradient(180deg,#f4edf6,#fff);
}

.section-header{
    text-align:center;
    margin-bottom:38px;
}

.section-header .section-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:7px 14px;
    border-radius:999px;
    background:#d5dfff;
    border:1px solid #b7c8ff;
    color:#1747c7;
    font-size:10px;
    font-weight:800;
    letter-spacing:.7px;
}

.section-header h2{
    margin-top:11px;
    color:#102f91;
    font-size:34px;
    font-weight:900;
    letter-spacing:-1.2px;
}

.section-header p{
    max-width:700px;
    margin:7px auto 0;
    color:#64748b;
    font-size:13px;
    line-height:1.6;
}

.mission-grid{
    max-width:1120px;
    margin:auto;
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:22px;
}

.mission-card{
    padding:30px;
    border-radius:21px;
    border:1px solid #d6dce9;
    background:#fff;
    box-shadow:0 10px 25px rgba(28,53,118,.07);
    transition:.25s;
}

.mission-card:hover{
    transform:translateY(-6px);
    border-color:#b7c8ff;
    box-shadow:0 18px 32px rgba(28,53,118,.12);
}

.mission-icon{
    width:54px;
    height:54px;
    border-radius:16px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#e6ebff;
    color:#1747c7;
    font-size:22px;
    margin-bottom:17px;
}

.mission-card:nth-child(2) .mission-icon{
    background:#ffe4f2;
    color:#f0208d;
}

.mission-card h3{
    color:#102f91;
    font-size:19px;
    font-weight:900;
    margin-bottom:9px;
}

.mission-card p{
    color:#64748b;
    font-size:12px;
    line-height:1.7;
}

/* =========================================================
   SERVICES
========================================================= */

.services-section{
    padding:68px 70px 75px;
    background:#0b237f;
}

.services-section .section-header .section-badge{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.16);
    color:#fff;
}

.services-section .section-header h2{
    color:#fff;
}

.services-section .section-header p{
    color:rgba(255,255,255,.82);
}

.services-grid{
    max-width:1120px;
    margin:auto;
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
}

.service-card{
    padding:27px 20px;
    text-align:center;
    border-radius:18px;
    border:1px solid rgba(255,255,255,.15);
    background:rgba(255,255,255,.08);
    backdrop-filter:blur(5px);
    transition:.25s;
}

.service-card:hover{
    transform:translateY(-6px);
    background:rgba(255,255,255,.13);
}

.service-card i{
    width:56px;
    height:56px;
    margin:0 auto 15px;
    border-radius:16px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.12);
    color:#fff;
    font-size:23px;
}

.service-card h3{
    color:#fff;
    font-size:15px;
    margin-bottom:8px;
}

.service-card p{
    color:rgba(255,255,255,.78);
    font-size:11px;
    line-height:1.6;
}

/* =========================================================
   WHY SA DESIGN
========================================================= */

.why-section{
    padding:70px;
    background:#fff;
}

.why-container{
    max-width:1120px;
    margin:auto;
}

.why-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
}

.why-card{
    padding:27px 22px;
    border-radius:18px;
    background:#faf8fb;
    border:1px solid #e4dce7;
    transition:.25s;
}

.why-card:hover{
    transform:translateY(-5px);
    border-color:#b7c8ff;
    box-shadow:0 14px 27px rgba(28,53,118,.09);
}

.why-number{
    color:#f0208d;
    font-size:12px;
    font-weight:900;
    letter-spacing:1px;
}

.why-card h3{
    margin:8px 0 8px;
    color:#102f91;
    font-size:16px;
    font-weight:900;
}

.why-card p{
    color:#64748b;
    font-size:11px;
    line-height:1.7;
}

/* =========================================================
   CTA
========================================================= */

.cta-section{
    padding:12px 70px 70px;
    background:#fff;
}

.cta-card{
    position:relative;
    overflow:hidden;
    max-width:1120px;
    min-height:210px;
    margin:auto;
    padding:38px 42px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:30px;
    border:1px solid #b7c8ff;
    border-radius:26px;
    background:linear-gradient(
        115deg,
        #e8eeff,
        #f8ebf4
    );
    box-shadow:0 15px 35px rgba(40,84,197,.18);
}

.cta-card:before{
    content:"";
    position:absolute;
    width:210px;
    height:210px;
    right:-70px;
    bottom:-115px;
    border-radius:50%;
    background:rgba(40,84,197,.12);
}

.cta-card:after{
    content:"";
    position:absolute;
    width:160px;
    height:160px;
    right:100px;
    top:-95px;
    border-radius:50%;
    background:rgba(239,58,155,.12);
}

.cta-content{
    position:relative;
    z-index:2;
    max-width:730px;
}

.cta-content .section-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:7px 14px;
    border-radius:999px;
    background:rgba(255,255,255,.8);
    border:1px solid #b7c8ff;
    color:#1747c7;
    font-size:10px;
    font-weight:800;
    letter-spacing:.7px;
    margin-bottom:12px;
}

.cta-content h2{
    color:#102f91;
    font-size:28px;
    line-height:1.2;
    font-weight:900;
    margin-bottom:8px;
}

.cta-content p{
    color:#64748b;
    font-size:13px;
    line-height:1.6;
}

.cta-button{
    position:relative;
    z-index:2;
    flex-shrink:0;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    min-width:205px;
    padding:14px 22px;
    border-radius:13px;
    background:linear-gradient(135deg,#1747c7,#3157d5);
    color:#fff;
    font-size:12px;
    font-weight:800;
    box-shadow:0 12px 25px rgba(40,84,197,.25);
    transition:.25s;
}

.cta-button:hover{
    transform:translateY(-4px);
    background:linear-gradient(135deg,#f0208d,#ff3b86);
}

/* =========================================================
   FLOATING ACTIONS
========================================================= */

.floating-actions{
    position:fixed;
    right:22px;
    bottom:22px;
    z-index:2500;
    display:flex;
    flex-direction:column;
    align-items:flex-end;
    gap:9px;
}

.float-whatsapp,
.float-top{
    font-family:'Plus Jakarta Sans',sans-serif;
    display:flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    cursor:pointer;
    transition:.25s;
    box-shadow:0 12px 28px rgba(15,23,42,.16);
}

.float-whatsapp{
    height:52px;
    padding:0 21px;
    border:0;
    border-radius:999px;
    background:#20d466;
    color:#fff;
    gap:10px;
    font-size:14px;
    font-weight:800;
}

.float-whatsapp i{
    font-size:22px;
}

.float-whatsapp:hover{
    transform:translateY(-3px);
}

.float-top{
    width:40px;
    height:40px;
    border:0;
    border-radius:50%;
    background:#1747c7;
    color:#fff;
    font-size:14px;
    opacity:0;
    visibility:hidden;
    transform:translateY(10px);
}

.float-top.show{
    opacity:1;
    visibility:visible;
    transform:translateY(0);
}

.float-top:hover{
    background:#f0208d;
}

/* =========================================================
   FOOTER
========================================================= */

.site-footer{
    padding:38px 70px;
    background:#0b1d69;
    color:#fff;
}

.footer-grid{
    display:grid;
    grid-template-columns:1.5fr 1fr 1fr;
    gap:35px;
    max-width:1120px;
    margin:auto;
}

.site-footer h4{
    font-size:12px;
    margin-bottom:10px;
}

.site-footer p,
.site-footer a{
    color:rgba(255,255,255,.72);
    font-size:11px;
    line-height:1.7;
}

.site-footer a{
    display:block;
    margin-bottom:4px;
}

.site-footer a:hover{
    color:#fff;
}

.footer-brand{
    font-size:24px;
    font-weight:900;
}

.footer-brand span{
    color:#f0208d;
}

.footer-line{
    max-width:1120px;
    margin:25px auto 0;
    padding-top:15px;
    border-top:1px solid rgba(255,255,255,.14);
    text-align:center;
    color:rgba(255,255,255,.6);
    font-size:10px;
}

/* =========================================================
   SWEETALERT
========================================================= */

.logout-popup{
    border-radius:18px !important;
    padding:28px !important;
}

.logout-title{
    color:#102f91 !important;
    font-family:'Plus Jakarta Sans',Arial,sans-serif !important;
    font-size:24px !important;
    font-weight:800 !important;
}

.logout-text{
    color:#64748b !important;
    font-family:'Plus Jakarta Sans',Arial,sans-serif !important;
    font-size:13px !important;
}

.logout-confirm,
.logout-cancel{
    border-radius:10px !important;
    padding:10px 18px !important;
    font-family:'Plus Jakarta Sans',Arial,sans-serif !important;
    font-size:12px !important;
    font-weight:800 !important;
}

/* =========================================================
   TABLET
========================================================= */

@media(max-width:1100px){

    .navbar{
        padding:16px 30px;
    }

    .nav-links{
        gap:20px;
    }

    .about-hero-content{
        padding-left:45px;
    }

    .intro-section,
    .mission-section,
    .why-section{
        padding-left:40px;
        padding-right:40px;
    }

    .services-section{
        padding-left:40px;
        padding-right:40px;
    }

    .cta-section{
        padding-left:40px;
        padding-right:40px;
    }

    .services-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .why-grid{
        grid-template-columns:repeat(2,1fr);
    }
}

/* =========================================================
   MOBILE
========================================================= */

@media(max-width:760px){

    .navbar{
        height:auto;
        min-height:76px;
        padding:15px 20px;
        flex-wrap:wrap;
    }

    .logo-title{
        font-size:29px;
    }

    .logo-subtitle{
        font-size:9px;
    }

    .menu-toggle{
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .nav-icons{
        margin-left:auto;
    }

    .nav-links{
        display:none;
        order:4;
        width:100%;
        padding:12px 0 3px;
        flex-direction:column;
        align-items:center;
        gap:14px;
        border-top:1px solid #eef1f6;
    }

    .navbar.menu-open .nav-links{
        display:flex;
    }

    .login-pill span{
        display:none;
    }

    .login-pill{
        width:40px;
        height:40px;
        padding:0;
        justify-content:center;
    }

    .icon-btn{
        width:40px;
        height:40px;
    }

    .about-hero{
        min-height:390px;
    }

    .about-hero-content{
        padding:50px 24px;
    }

    .about-hero h1{
        font-size:42px;
        letter-spacing:-2px;
    }

    .about-hero p{
        max-width:370px;
    }

    .intro-section{
        padding:55px 20px;
    }

    .intro-container{
        grid-template-columns:1fr;
        gap:35px;
    }

    .intro-content h2,
    .story-content h2{
        font-size:29px;
    }

    .intro-image{
        min-height:260px;
    }

    .mission-section{
        padding:55px 20px;
    }

    .section-header h2{
        font-size:29px;
    }

    .mission-grid{
        grid-template-columns:1fr;
    }

    .services-section{
        padding:55px 20px 60px;
    }

    .services-grid{
        grid-template-columns:1fr;
        gap:14px;
    }

    .why-section{
        padding:55px 20px;
    }

    .why-grid{
        grid-template-columns:1fr;
    }

    .cta-section{
        padding:10px 20px 55px;
    }

    .cta-card{
        flex-direction:column;
        align-items:flex-start;
        padding:30px 24px;
        min-height:auto;
    }

    .cta-content h2{
        font-size:24px;
    }

    .cta-button{
        width:100%;
        min-width:0;
    }

    .floating-actions{
        right:12px;
        bottom:12px;
    }

    .float-whatsapp{
        height:48px;
        padding:0 17px;
        font-size:12px;
    }

    .site-footer{
        padding:35px 20px;
    }

    .footer-grid{
        grid-template-columns:1fr 1fr;
        gap:24px;
    }

    .footer-brand-wrap{
        grid-column:1/-1;
    }
}

/* =========================================================
   SMALL MOBILE
========================================================= */

@media(max-width:430px){

    .about-hero h1{
        font-size:35px;
    }

    .intro-content h2,
    .story-content h2{
        font-size:27px;
    }

    .footer-grid{
        grid-template-columns:1fr;
    }

    .footer-brand-wrap{
        grid-column:auto;
    }
}
</style>
</head>

<body>

<div class="page-card">

<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar">

    <a class="nav-logo" href="home.php" title="Back to Home">

        <div class="logo-text">

            <span class="logo-title">
                <span class="logo-sa">SA</span>
                <span class="logo-design">DESIGN</span>
            </span>

            <span class="logo-subtitle">
                PRINTING &amp; ADVERTISING
            </span>

        </div>

    </a>


    <ul class="nav-links" id="mainNav">

        <li>
            <a href="home.php">
                Home
            </a>
        </li>

        <li>
            <a href="home.php#products-section">
                Product
            </a>
        </li>

        <li>
            <a href="about.php" class="active">
                About Us
            </a>
        </li>

        <li>
            <a href="custom_request.php">
                Custom Request
            </a>
        </li>

    </ul>


    <div class="nav-icons">

        <?php if (
            isset($_SESSION['is_logged_in']) &&
            $_SESSION['is_logged_in'] === true
        ): ?>

            <a
                href="cust_profile.php"
                class="login-pill"
                title="My profile"
            >
                <i class="fa-regular fa-user"></i>
                <span>Profile</span>
            </a>


            <button
                type="button"
                class="login-pill logout-pill"
                title="Log out"
                onclick="confirmLogout()"
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Log out</span>
            </button>

        <?php else: ?>

            <a
                href="index.php"
                class="login-pill"
                title="Login"
            >
                <i class="fa-regular fa-user"></i>
                <span>Login</span>
            </a>

        <?php endif; ?>


        <a
            href="cart.php"
            class="icon-btn"
            title="Cart"
            id="cartBtn"
            aria-label="Shopping cart"
        >

            <i class="fa-solid fa-bag-shopping"></i>

            <span
                class="cart-badge"
                id="cartBadgeCount"
            >
                <?= $total_cart_count ?>
            </span>

        </a>

    </div>


    <button
        type="button"
        class="menu-toggle"
        id="menuToggle"
        aria-label="Open menu"
        aria-expanded="false"
    >
        <i class="fa-solid fa-bars"></i>
    </button>

</nav>


<!-- =====================================================
     HERO
===================================================== -->

<section class="about-hero">

    <div class="about-hero-content">

        <span class="hero-badge">
            <i class="fa-solid fa-circle-info"></i>
            ABOUT SA DESIGN
        </span>

        <h1>
            Turning Ideas<br>
            Into <span>Print.</span>
        </h1>

        <p>
            SA Design is a printing and advertising service that helps
            businesses, organisations, schools and individuals bring
            their ideas to life through quality printing and creative
            visual solutions.
        </p>

    </div>

</section>


<!-- =====================================================
     INTRODUCTION
===================================================== -->

<section class="intro-section">

    <div class="intro-container">

        <div class="intro-content">

            <span class="section-badge">
                <i class="fa-solid fa-building"></i>
                WHO WE ARE
            </span>

            <h2>
                Your Ideas.<br>
                Our <span>Print.</span>
            </h2>

            <p>
                SA Design provides printing and advertising solutions
                for customers who need reliable, attractive and
                high-quality printed materials.
            </p>

            <p>
                From business stationery and promotional materials
                to event printing, apparel and custom requests,
                we aim to make the printing process simple and
                convenient for our customers.
            </p>

            <p>
                Our platform also allows customers to explore
                products, submit custom requests and manage their
                orders in a more organised way.
            </p>

        </div>


        <div class="intro-image">

            <div class="intro-image-content">

                <div class="intro-logo">
                    <span class="pink">SA</span>
                    <span class="blue">DESIGN</span>
                </div>

                <p>
                    Printing &amp; Advertising
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     MISSION & VISION
===================================================== -->

<section class="mission-section">

    <div class="section-header">

        <span class="section-badge">
            <i class="fa-solid fa-compass"></i>
            OUR DIRECTION
        </span>

        <h2>
            Mission &amp; Vision
        </h2>

        <p>
            We focus on quality, convenience and creative printing
            solutions that support our customers' needs.
        </p>

    </div>


    <div class="mission-grid">

        <div class="mission-card">

            <div class="mission-icon">
                <i class="fa-solid fa-bullseye"></i>
            </div>

            <h3>
                Our Mission
            </h3>

            <p>
                To provide quality, affordable and reliable printing
                and advertising solutions while making the ordering
                process easier and more convenient for every customer.
            </p>

        </div>


        <div class="mission-card">

            <div class="mission-icon">
                <i class="fa-solid fa-eye"></i>
            </div>

            <h3>
                Our Vision
            </h3>

            <p>
                To become a trusted printing and advertising service
                that combines creativity, technology and excellent
                customer service to deliver better printing experiences.
            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     OUR SERVICES
===================================================== -->

<section class="services-section">

    <div class="section-header">

        <span class="section-badge">
            <i class="fa-solid fa-print"></i>
            WHAT WE OFFER
        </span>

        <h2>
            Our Printing Solutions
        </h2>

        <p>
            Explore a range of printing and advertising products
            suitable for different purposes.
        </p>

    </div>


    <div class="services-grid">

        <div class="service-card">

            <i class="fa-solid fa-stamp"></i>

            <h3>
                Custom Stamps
            </h3>

            <p>
                Self-inking stamps suitable for business,
                stationery and personal use.
            </p>

        </div>


        <div class="service-card">

            <i class="fa-solid fa-shirt"></i>

            <h3>
                Custom Apparel
            </h3>

            <p>
                Sublimation shirts and apparel for teams,
                events, organisations and personal projects.
            </p>

        </div>


        <div class="service-card">

            <i class="fa-solid fa-panorama"></i>

            <h3>
                Banner &amp; Bunting
            </h3>

            <p>
                Large-format printing for promotions,
                events, businesses and special occasions.
            </p>

        </div>


        <div class="service-card">

            <i class="fa-solid fa-id-card"></i>

            <h3>
                Cards &amp; Stickers
            </h3>

            <p>
                Professional name cards, wedding cards,
                product stickers and promotional materials.
            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     WHY SA DESIGN
===================================================== -->

<section class="why-section">

    <div class="why-container">

        <div class="section-header">

            <span class="section-badge">
                <i class="fa-solid fa-sparkles"></i>
                WHY SA DESIGN
            </span>

            <h2>
                Designed Around You
            </h2>

            <p>
                We want every customer to have a simple,
                convenient and reliable printing experience.
            </p>

        </div>


        <div class="why-grid">

            <div class="why-card">

                <div class="why-number">
                    01
                </div>

                <h3>
                    Quality Materials
                </h3>

                <p>
                    We focus on using suitable materials and
                    reliable printing processes to produce
                    professional-looking results.
                </p>

            </div>


            <div class="why-card">

                <div class="why-number">
                    02
                </div>

                <h3>
                    Easy Ordering
                </h3>

                <p>
                    Customers can browse products, select their
                    preferred options and submit requests through
                    a more organised online platform.
                </p>

            </div>


            <div class="why-card">

                <div class="why-number">
                    03
                </div>

                <h3>
                    Customer Support
                </h3>

                <p>
                    We provide friendly assistance to help customers
                    choose suitable printing products and services.
                </p>

            </div>


            <div class="why-card">

                <div class="why-number">
                    04
                </div>

                <h3>
                    Flexible Solutions
                </h3>

                <p>
                    From standard products to custom requests,
                    we provide printing options for different
                    customer needs.
                </p>

            </div>


            <div class="why-card">

                <div class="why-number">
                    05
                </div>

                <h3>
                    Competitive Pricing
                </h3>

                <p>
                    Our products are offered at competitive prices
                    while maintaining a strong focus on quality.
                </p>

            </div>


            <div class="why-card">

                <div class="why-number">
                    06
                </div>

                <h3>
                    Creative Approach
                </h3>

                <p>
                    We help transform simple ideas into attractive
                    printed materials that can represent your
                    business, event or personal project.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     CALL TO ACTION
===================================================== -->

<section class="cta-section">

    <div class="cta-card">

        <div class="cta-content">

            <span class="section-badge">
                <i class="fa-solid fa-palette"></i>
                READY TO CREATE?
            </span>

            <h2>
                Have something special in mind?
            </h2>

            <p>
                Tell us what you need and submit a custom request.
                Let's turn your idea into something you can print,
                use and share.
            </p>

        </div>


        <a
            href="custom_request.php"
            class="cta-button"
        >
            <i class="fa-solid fa-pen-ruler"></i>
            Start Custom Request
        </a>

    </div>

</section>

</div>


<!-- =====================================================
     FLOATING ACTIONS
===================================================== -->

<div
    class="floating-actions"
    aria-label="Quick actions"
>

    <a
        href="https://wa.me/60194184147"
        target="_blank"
        rel="noopener"
        class="float-whatsapp"
        title="Chat with us"
    >
        <i class="fa-brands fa-whatsapp"></i>
        Chat with us
    </a>


    <button
        type="button"
        class="float-top"
        id="backToTop"
        title="Back to top"
        aria-label="Back to top"
    >
        <i class="fa-solid fa-arrow-up"></i>
    </button>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="site-footer">

    <div class="footer-grid">

        <div class="footer-brand-wrap">

            <div class="footer-brand">
                <span>SA</span> DESIGN
            </div>

            <p>
                Printing &amp; Advertising
            </p>

            <p>
                Quality printing solutions for business,
                events, school and personal needs.
            </p>

        </div>


        <div>

            <h4>
                QUICK LINKS
            </h4>

            <a href="home.php">
                Home
            </a>

            <a href="home.php#products-section">
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

            <a
                href="https://wa.me/60194184147"
                target="_blank"
                rel="noopener"
            >
                WhatsApp Us
            </a>

            <a href="custom_request.php">
                Request a Quote
            </a>

        </div>

    </div>


    <div class="footer-line">
        © 2026 SA Design. All rights reserved.
    </div>

</footer>


<script>

/* =========================================================
   LOGOUT CONFIRMATION
========================================================= */

function confirmLogout(){

    Swal.fire({

        title:'Logout',

        text:'Are you sure you want to logout?',

        icon:'warning',

        showCancelButton:true,

        confirmButtonText:'Logout',

        cancelButtonText:'Cancel',

        confirmButtonColor:'#f0208d',

        cancelButtonColor:'#cbd8ff',

        reverseButtons:true,

        background:'#ffffff',

        customClass:{
            popup:'logout-popup',
            title:'logout-title',
            htmlContainer:'logout-text',
            confirmButton:'logout-confirm',
            cancelButton:'logout-cancel'
        }

    }).then((result)=>{

        if(result.isConfirmed){

            window.location.href='logout.php';

        }

    });

}


/* =========================================================
   MOBILE MENU
========================================================= */

const menuToggle =
    document.getElementById('menuToggle');

const navbar =
    document.querySelector('.navbar');


menuToggle.addEventListener('click',()=>{

    const open =
        navbar.classList.toggle('menu-open');

    menuToggle.setAttribute(
        'aria-expanded',
        open ? 'true' : 'false'
    );

    menuToggle.innerHTML = open

        ? '<i class="fa-solid fa-xmark"></i>'

        : '<i class="fa-solid fa-bars"></i>';

});


document
    .querySelectorAll('.nav-links a')
    .forEach(link=>{

        link.addEventListener('click',()=>{

            navbar.classList.remove('menu-open');

            menuToggle.setAttribute(
                'aria-expanded',
                'false'
            );

            menuToggle.innerHTML =
                '<i class="fa-solid fa-bars"></i>';

        });

    });


/* =========================================================
   BACK TO TOP
========================================================= */

const backToTop =
    document.getElementById('backToTop');


window.addEventListener('scroll',()=>{

    if(window.scrollY > 450){

        backToTop.classList.add('show');

    }else{

        backToTop.classList.remove('show');

    }

});


backToTop.addEventListener('click',()=>{

    window.scrollTo({
        top:0,
        behavior:'smooth'
    });

});

</script>

</body>
</html>