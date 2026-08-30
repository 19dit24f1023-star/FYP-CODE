<?php
session_start();

// Calculate total quantity of products in the session cart
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
    justify-content:center;
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
   ABOUT HERO
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
            #fff 0%,
            #dce5ff 48%,
            #cbbce8 75%,
            #ffc7df 100%
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
            rgba(40,84,197,.18) 55% 61%,
            transparent 61%
        ),
        linear-gradient(
            140deg,
            transparent 0 67%,
            rgba(239,58,155,.18) 67% 76%,
            transparent 76%
        );

    pointer-events:none;
}

.about-hero:after{
    content:"";
    position:absolute;
    width:330px;
    height:330px;
    right:-120px;
    top:-160px;
    border-radius:50%;
    background:rgba(23,71,199,.12);
}

.about-hero-content{
    position:relative;
    z-index:2;
    width:min(1120px,100%);
    margin:auto;
    padding:65px 70px;
}

.about-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 15px;
    border-radius:999px;
    background:rgba(255,255,255,.78);
    border:1px solid #b7c8ff;
    color:#1747c7;
    font-size:10px;
    font-weight:800;
    letter-spacing:.8px;
}

.about-hero h1{
    max-width:650px;
    margin-top:15px;
    color:#111827;
    font-size:clamp(38px,5vw,62px);
    line-height:.98;
    font-weight:900;
    letter-spacing:-3px;
    text-transform:uppercase;
}

.about-hero h1 span{
    color:#f0208d;
}

.about-hero p{
    max-width:650px;
    margin-top:17px;
    color:#596579;
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

.intro-wrapper{
    max-width:1120px;
    margin:auto;

    display:grid;
    grid-template-columns:1.05fr .95fr;
    gap:55px;
    align-items:center;
}

.intro-image{
    position:relative;
    min-height:330px;
    border-radius:25px;
    overflow:hidden;

    background:
        linear-gradient(
            135deg,
            #1747c7,
            #102f91 55%,
            #f0208d
        );

    box-shadow:0 20px 40px rgba(40,84,197,.20);
}

.intro-image:before{
    content:"";
    position:absolute;
    width:240px;
    height:240px;
    right:-80px;
    top:-100px;
    border-radius:50%;
    background:rgba(255,255,255,.10);
}

.intro-image:after{
    content:"";
    position:absolute;
    width:160px;
    height:160px;
    left:-70px;
    bottom:-70px;
    border-radius:50%;
    background:rgba(240,32,141,.25);
}

.intro-image-content{
    position:absolute;
    inset:0;
    z-index:2;

    display:flex;
    align-items:center;
    justify-content:center;
    flex-direction:column;

    color:#fff;
    text-align:center;
    padding:30px;
}

.intro-logo{
    font-size:52px;
    font-weight:900;
    letter-spacing:-3px;
}

.intro-logo span{
    color:#ff7fba;
}

.intro-image-content small{
    margin-top:5px;
    font-size:11px;
    font-weight:800;
    letter-spacing:2px;
    text-transform:uppercase;
}

.intro-image-content i{
    margin-top:25px;
    font-size:42px;
    opacity:.9;
}

.intro-content .section-badge{
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

.intro-content h2{
    margin-top:14px;
    color:#102f91;
    font-size:34px;
    font-weight:900;
    line-height:1.1;
    letter-spacing:-1.2px;
}

.intro-content h2 span{
    color:#f0208d;
}

.intro-content p{
    margin-top:15px;
    color:#64748b;
    font-size:13px;
    line-height:1.8;
}

.intro-points{
    margin-top:20px;
    display:grid;
    gap:11px;
}

.intro-point{
    display:flex;
    align-items:flex-start;
    gap:11px;
}

.intro-point i{
    width:28px;
    height:28px;
    flex-shrink:0;
    display:grid;
    place-items:center;
    border-radius:9px;
    background:#eef2ff;
    color:#1747c7;
    font-size:12px;
}

.intro-point span{
    color:#475569;
    font-size:12px;
    line-height:1.6;
}

/* =========================================================
   MISSION & VISION
========================================================= */

.mission-section{
    padding:70px;
    background:linear-gradient(180deg,#f4eff7,#fff);
}

.section-header{
    text-align:center;
    margin-bottom:38px;
}

.section-badge{
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

.mv-grid{
    max-width:1120px;
    margin:auto;

    display:grid;
    grid-template-columns:1fr 1fr;
    gap:22px;
}

.mv-card{
    position:relative;
    overflow:hidden;

    padding:32px;

    border:1px solid #d9dff0;
    border-radius:22px;

    background:#fff;

    box-shadow:0 12px 30px rgba(28,53,118,.08);

    transition:.25s;
}

.mv-card:hover{
    transform:translateY(-6px);
    border-color:#b7c8ff;
    box-shadow:0 20px 38px rgba(28,53,118,.14);
}

.mv-card:after{
    content:"";
    position:absolute;
    width:150px;
    height:150px;
    right:-65px;
    top:-70px;
    border-radius:50%;
    background:rgba(240,32,141,.07);
}

.mv-icon{
    width:55px;
    height:55px;

    display:grid;
    place-items:center;

    border-radius:16px;

    background:linear-gradient(135deg,#1747c7,#3157d5);
    color:#fff;

    font-size:22px;

    margin-bottom:18px;
}

.mv-card:nth-child(2) .mv-icon{
    background:linear-gradient(135deg,#f0208d,#ff3b86);
}

.mv-card h3{
    color:#102f91;
    font-size:20px;
    font-weight:900;
    margin-bottom:10px;
}

.mv-card p{
    color:#64748b;
    font-size:12px;
    line-height:1.8;
}

/* =========================================================
   SERVICES
========================================================= */

.services-section{
    padding:70px;
    background:#fff;
}

.services-grid{
    max-width:1120px;
    margin:auto;

    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
}

.service-card{
    padding:25px 20px;

    text-align:center;

    border:1px solid #e1d8e5;
    border-radius:18px;

    background:#fff;

    box-shadow:0 8px 20px rgba(28,53,118,.06);

    transition:.25s;
}

.service-card:hover{
    transform:translateY(-6px);
    border-color:#b7c8ff;
    box-shadow:0 18px 32px rgba(28,53,118,.12);
}

.service-icon{
    width:55px;
    height:55px;
    margin:0 auto 15px;

    display:grid;
    place-items:center;

    border-radius:16px;

    background:#eef2ff;
    color:#1747c7;

    font-size:22px;

    transition:.25s;
}

.service-card:hover .service-icon{
    background:linear-gradient(135deg,#1747c7,#f0208d);
    color:#fff;
}

.service-card h3{
    color:#172033;
    font-size:15px;
    font-weight:800;
    margin-bottom:8px;
}

.service-card p{
    color:#64748b;
    font-size:11px;
    line-height:1.6;
}

/* =========================================================
   WHY SA DESIGN
========================================================= */

.why-section{
    padding:65px 70px 75px;
    background:linear-gradient(180deg,#0b237f,#16318f);
}

.why-section .section-header h2{
    color:#fff;
}

.why-section .section-header p{
    color:rgba(255,255,255,.82);
}

.why-section .section-badge{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.16);
    color:#fff;
}

.why-grid{
    max-width:1120px;
    margin:auto;

    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
}

.why-card{
    padding:28px 22px;

    text-align:center;

    border:1px solid rgba(255,255,255,.15);
    border-radius:20px;

    background:rgba(255,255,255,.08);

    backdrop-filter:blur(6px);

    transition:.25s;
}

.why-card:hover{
    transform:translateY(-6px);
    background:rgba(255,255,255,.13);
}

.why-card i{
    font-size:25px;
    color:#fff;
    margin-bottom:13px;
}

.why-card h3{
    color:#fff;
    font-size:15px;
    margin-bottom:8px;
}

.why-card p{
    color:rgba(255,255,255,.78);
    font-size:11px;
    line-height:1.7;
}

/* =========================================================
   CTA
========================================================= */

.cta-section{
    padding:65px 70px;
    background:#f1eaf5;
}

.cta-card{
    position:relative;
    overflow:hidden;

    max-width:1120px;
    margin:auto;

    padding:42px;

    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:30px;

    border:1px solid #b7c8ff;
    border-radius:26px;

    background:
        linear-gradient(
            115deg,
            #e8eeff,
            #f8ebf4
        );

    box-shadow:0 15px 35px rgba(40,84,197,.18);
}

.cta-card:after{
    content:"";
    position:absolute;

    width:220px;
    height:220px;

    right:-90px;
    top:-110px;

    border-radius:50%;

    background:rgba(240,32,141,.10);
}

.cta-content{
    position:relative;
    z-index:2;
}

.cta-content h2{
    margin-top:12px;
    color:#102f91;
    font-size:29px;
    font-weight:900;
}

.cta-content p{
    margin-top:8px;
    color:#64748b;
    font-size:12px;
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

    min-width:200px;

    padding:14px 22px;

    border-radius:13px;

    background:linear-gradient(135deg,#1747c7,#3157d5);

    color:#fff;

    font-size:12px;
    font-weight:800;

    box-shadow:0 12px 25px rgba(40,84,197,.22);

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
   RESPONSIVE
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
        padding-right:45px;
    }

    .intro-section,
    .mission-section,
    .services-section,
    .cta-section{
        padding-left:40px;
        padding-right:40px;
    }

    .services-grid{
        grid-template-columns:repeat(2,1fr);
    }
}

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
    }

    .icon-btn{
        width:40px;
        height:40px;
    }

    /* Hero */

    .about-hero{
        min-height:430px;
    }

    .about-hero-content{
        padding:55px 24px;
    }

    .about-hero h1{
        font-size:42px;
        letter-spacing:-2px;
    }

    .about-hero p{
        max-width:380px;
    }

    /* Intro */

    .intro-section{
        padding:55px 20px;
    }

    .intro-wrapper{
        grid-template-columns:1fr;
        gap:35px;
    }

    .intro-image{
        min-height:280px;
    }

    .intro-content h2{
        font-size:28px;
    }

    /* Mission */

    .mission-section{
        padding:55px 20px;
    }

    .mv-grid{
        grid-template-columns:1fr;
    }

    .section-header h2{
        font-size:28px;
    }

    /* Services */

    .services-section{
        padding:55px 20px;
    }

    .services-grid{
        grid-template-columns:1fr 1fr;
        gap:12px;
    }

    .service-card{
        padding:22px 14px;
    }

    /* Why */

    .why-section{
        padding:55px 20px 65px;
    }

    .why-grid{
        grid-template-columns:1fr;
    }

    /* CTA */

    .cta-section{
        padding:15px 20px 50px;
    }

    .cta-card{
        flex-direction:column;
        align-items:flex-start;
        padding:30px 24px;
    }

    .cta-content h2{
        font-size:24px;
    }

    .cta-button{
        width:100%;
    }

    /* Floating */

    .floating-actions{
        right:12px;
        bottom:12px;
    }

    .float-whatsapp{
        height:48px;
        padding:0 17px;
        font-size:12px;
    }

    /* Footer */

    .footer-grid{
        grid-template-columns:1fr 1fr;
        gap:24px;
    }

    .footer-brand-wrap{
        grid-column:1/-1;
    }

    .site-footer{
        padding:35px 20px;
    }
}

@media(max-width:430px){

    .about-hero h1{
        font-size:35px;
    }

    .intro-logo{
        font-size:42px;
    }

    .services-grid{
        grid-template-columns:1fr;
    }

    .footer-grid{
        grid-template-columns:1fr;
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
            <a href="home.php">Home</a>
        </li>

        <li>
            <a href="home.php#products-section">Product</a>
        </li>

        <li>
            <a href="about.php" class="active">About Us</a>
        </li>

        <li>
            <a href="custom_request.php">Custom Request</a>
        </li>
    </ul>

    <div class="nav-icons">

        <?php if (
            isset($_SESSION['is_logged_in']) &&
            $_SESSION['is_logged_in'] === true
        ): ?>

            <a href="cust_profile.php"
               class="login-pill"
               title="My profile">

                <i class="fa-regular fa-user"></i>
                <span>Profile</span>

            </a>

            <button
                type="button"
                class="login-pill logout-pill"
                title="Log out"
                onclick="confirmLogout()">

                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Log out</span>

            </button>

        <?php else: ?>

            <a href="index.php"
               class="login-pill"
               title="Login">

                <i class="fa-regular fa-user"></i>
                <span>Login</span>

            </a>

        <?php endif; ?>

        <a href="cart.php"
           class="icon-btn"
           title="Cart"
           aria-label="Shopping cart">

            <i class="fa-solid fa-bag-shopping"></i>

            <span class="cart-badge">
                <?= $total_cart_count ?>
            </span>

        </a>

    </div>

    <button
        type="button"
        class="menu-toggle"
        id="menuToggle"
        aria-label="Open menu"
        aria-expanded="false">

        <i class="fa-solid fa-bars"></i>

    </button>

</nav>


<!-- =====================================================
     ABOUT HERO
===================================================== -->

<section class="about-hero">

    <div class="about-hero-content">

        <span class="about-badge">
            <i class="fa-solid fa-building"></i>
            ABOUT SA DESIGN
        </span>

        <h1>
            We Turn Ideas<br>
            Into <span>Reality.</span>
        </h1>

        <p>
            SA Design is a printing and advertising service provider
            dedicated to transforming creative ideas into high-quality
            printed products for businesses, events, schools and
            personal projects.
        </p>

    </div>

</section>


<!-- =====================================================
     INTRODUCTION
===================================================== -->

<section class="intro-section">

    <div class="intro-wrapper">

        <div class="intro-image">

            <div class="intro-image-content">

                <div class="intro-logo">
                    <span>SA</span> DESIGN
                </div>

                <small>
                    Printing &amp; Advertising
                </small>

                <i class="fa-solid fa-print"></i>

            </div>

        </div>


        <div class="intro-content">

            <span class="section-badge">
                <i class="fa-solid fa-sparkles"></i>
                WHO WE ARE
            </span>

            <h2>
                Your Ideas.<br>
                Our <span>Printing.</span>
            </h2>

            <p>
                SA Design provides printing and advertising solutions
                designed to help customers bring their ideas to life.
                From everyday stationery and promotional materials
                to event printing and customised products, we aim to
                make the printing process easier and more convenient.
            </p>

            <p>
                Our platform allows customers to explore products,
                understand available options and submit custom requests
                through a more organised digital experience.
            </p>

            <div class="intro-points">

                <div class="intro-point">
                    <i class="fa-solid fa-check"></i>
                    <span>
                        Quality printing materials and professional
                        finishing.
                    </span>
                </div>

                <div class="intro-point">
                    <i class="fa-solid fa-check"></i>
                    <span>
                        Printing solutions for business, events,
                        school and personal needs.
                    </span>
                </div>

                <div class="intro-point">
                    <i class="fa-solid fa-check"></i>
                    <span>
                        Simple and convenient ordering experience.
                    </span>
                </div>

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
            We focus on delivering reliable printing services while
            continuously improving the customer experience.
        </p>

    </div>


    <div class="mv-grid">

        <div class="mv-card">

            <div class="mv-icon">
                <i class="fa-solid fa-bullseye"></i>
            </div>

            <h3>
                Our Mission
            </h3>

            <p>
                Establishing SA DESIGN as the leading printing center in Muadzam Shah and creating job opportunities for the youth of Muadzam Shah.
            </p>

        </div>


        <div class="mv-card">

            <div class="mv-icon">
                <i class="fa-solid fa-eye"></i>
            </div>

            <h3>
                Our Vision
            </h3>

            <p>
                To become the leading provider of printing materials in Rompin by 2030.
            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     SERVICES
===================================================== -->

<section class="services-section">

    <div class="section-header">

        <span class="section-badge">
            <i class="fa-solid fa-layer-group"></i>
            WHAT WE OFFER
        </span>

        <h2>
            Our Printing Solutions
        </h2>

        <p>
            A range of products and services to support different
            printing and advertising needs.
        </p>

    </div>


    <div class="services-grid">

        <div class="service-card">

            <div class="service-icon">
                <i class="fa-solid fa-stamp"></i>
            </div>

            <h3>
                Stationery
            </h3>

            <p>
                Custom stamps and essential stationery
                for everyday business use.
            </p>

        </div>


        <div class="service-card">

            <div class="service-icon">
                <i class="fa-solid fa-shirt"></i>
            </div>

            <h3>
                Custom Apparel
            </h3>

            <p>
                Custom sublimation shirts suitable for
                teams, events and organisations.
            </p>

        </div>


        <div class="service-card">

            <div class="service-icon">
                <i class="fa-solid fa-panorama"></i>
            </div>

            <h3>
                Large Format
            </h3>

            <p>
                Banners and bunting for promotions,
                events and outdoor advertising.
            </p>

        </div>


        <div class="service-card">

            <div class="service-icon">
                <i class="fa-solid fa-id-card"></i>
            </div>

            <h3>
                Cards
            </h3>

            <p>
                Professional business cards and
                elegant wedding cards.
            </p>

        </div>


        <div class="service-card">

            <div class="service-icon">
                <i class="fa-solid fa-flag"></i>
            </div>

            <h3>
                Windflag
            </h3>

            <p>
                Eye-catching outdoor promotional
                flags for businesses and events.
            </p>

        </div>


        <div class="service-card">

            <div class="service-icon">
                <i class="fa-solid fa-tags"></i>
            </div>

            <h3>
                Stickers
            </h3>

            <p>
                Custom product stickers and labels
                for branding and packaging.
            </p>

        </div>


        <div class="service-card">

            <div class="service-icon">
                <i class="fa-solid fa-palette"></i>
            </div>

            <h3>
                Custom Design
            </h3>

            <p>
                Custom printing requests based on
                your own ideas and requirements.
            </p>

        </div>


        <div class="service-card">

            <div class="service-icon">
                <i class="fa-solid fa-bullhorn"></i>
            </div>

            <h3>
                Advertising
            </h3>

            <p>
                Promotional materials that help
                businesses stand out and reach customers.
            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     WHY CHOOSE SA DESIGN
===================================================== -->

<section class="why-section">

    <div class="section-header">

        <span class="section-badge">
            <i class="fa-solid fa-star"></i>
            WHY SA DESIGN
        </span>

        <h2>
            Why Customers Choose Us
        </h2>

        <p>
            We combine quality, convenience and customer-focused
            service to deliver a better printing experience.
        </p>

    </div>


    <div class="why-grid">

        <div class="why-card">

            <i class="fa-solid fa-gem"></i>

            <h3>
                Premium Quality
            </h3>

            <p>
                We focus on quality materials and reliable
                printing results for every order.
            </p>

        </div>


        <div class="why-card">

            <i class="fa-solid fa-bolt"></i>

            <h3>
                Fast &amp; Efficient
            </h3>

            <p>
                Our organised process helps make ordering
                and managing print requests easier.
            </p>

        </div>


        <div class="why-card">

            <i class="fa-solid fa-comments"></i>

            <h3>
                Friendly Support
            </h3>

            <p>
                Customers can communicate their requirements
                and receive assistance when needed.
            </p>

        </div>


        <div class="why-card">

            <i class="fa-solid fa-wallet"></i>

            <h3>
                Affordable
            </h3>

            <p>
                Competitive pricing makes our printing
                solutions suitable for different budgets.
            </p>

        </div>


        <div class="why-card">

            <i class="fa-solid fa-laptop"></i>

            <h3>
                Digital Convenience
            </h3>

            <p>
                Customers can explore products and requests
                through a convenient online platform.
            </p>

        </div>


        <div class="why-card">

            <i class="fa-solid fa-lightbulb"></i>

            <h3>
                Creative Solutions
            </h3>

            <p>
                We support different ideas and requirements
                through customised printing solutions.
            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     CTA
===================================================== -->

<section class="cta-section">

    <div class="cta-card">

        <div class="cta-content">

            <span class="section-badge">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                HAVE AN IDEA?
            </span>

            <h2>
                Let's Turn Your Idea Into Print.
            </h2>

            <p>
                Have a special design, bulk order or unique
                printing requirement? Send us your request
                and let us help bring your idea to life.
            </p>

        </div>

        <a href="custom_request.php"
           class="cta-button">

            <i class="fa-solid fa-pen-ruler"></i>
            Start Custom Request

        </a>

    </div>

</section>

</div>


<!-- =====================================================
     FLOATING WHATSAPP / BACK TO TOP
===================================================== -->

<div class="floating-actions">

    <a href="https://wa.me/60194184147"
       target="_blank"
       rel="noopener"
       class="float-whatsapp"
       title="Chat with us">

        <i class="fa-brands fa-whatsapp"></i>
        Chat with us

    </a>


    <button
        type="button"
        class="float-top"
        id="backToTop"
        title="Back to top"
        aria-label="Back to top">

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

            <a href="https://wa.me/60194184147"
               target="_blank"
               rel="noopener">

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

/* =====================================================
   MOBILE MENU
===================================================== */

const menuToggle = document.getElementById('menuToggle');
const navbar = document.querySelector('.navbar');

menuToggle.addEventListener('click', () => {

    const open = navbar.classList.toggle('menu-open');

    menuToggle.setAttribute(
        'aria-expanded',
        open ? 'true' : 'false'
    );

    menuToggle.innerHTML = open
        ? '<i class="fa-solid fa-xmark"></i>'
        : '<i class="fa-solid fa-bars"></i>';

});


document.querySelectorAll('.nav-links a').forEach(link => {

    link.addEventListener('click', () => {

        navbar.classList.remove('menu-open');

        menuToggle.setAttribute(
            'aria-expanded',
            'false'
        );

        menuToggle.innerHTML =
            '<i class="fa-solid fa-bars"></i>';

    });

});


/* =====================================================
   LOGOUT CONFIRMATION
===================================================== */

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

    }).then((result) => {

        if(result.isConfirmed){

            window.location.href='logout.php';

        }

    });

}


/* =====================================================
   BACK TO TOP
===================================================== */

const backToTop =
    document.getElementById('backToTop');

window.addEventListener('scroll', () => {

    backToTop.classList.toggle(
        'show',
        window.scrollY > 400
    );

});


backToTop.addEventListener('click', () => {

    window.scrollTo({
        top:0,
        behavior:'smooth'
    });

});

</script>


<style>

/* SweetAlert Logout Style */

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

</style>

</body>
</html>