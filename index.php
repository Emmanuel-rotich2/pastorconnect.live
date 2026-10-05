
<?php
require_once __DIR__ . '/includes/bootstrap.php';

$homepageMission=setting($pdo,'homepage_mission','');
$homepageVision=setting($pdo,'homepage_vision','');
$homepageMotto=setting($pdo,'homepage_motto','');
$homepageTheme=setting($pdo,'homepage_theme_year','');
$homepageQuote=setting($pdo,'homepage_daily_quote','');
$homepageQuoteImage=setting($pdo,'homepage_daily_quote_image','');
$homepageQuoteDate=setting($pdo,'homepage_daily_quote_date','');
?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, maximum-scale=1"
    >

    <meta
        name="description"
        content="FGCK Joyland — a secure digital platform connecting members, pastors, church leaders and administrators across church life."
    >

    <meta
        name="theme-color"
        content="#071a33"
    >

    <!-- FGCK Joyland favicon -->
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/assets/images/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="64x64" href="/assets/images/favicon-64.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon-256.png">

    <title>FGCK Joyland | perfected to influence</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         MAIN APPLICATION CSS
    ====================================================== -->

    <link
        href="/assets/css/app.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         PAGE STYLES
    ====================================================== -->

    <style>

        /* =====================================================
           COLOR SYSTEM
        ====================================================== */

        :root {

            --primary: #8b5cf6;
            --primary-dark: #6d28d9;
            --primary-light: #c4b5fd;

            --blue-deep: #100b2e;
            --blue-dark: #1b114b;
            --blue-mid: #35206f;
            --blue-soft: #f4f0ff;

            --gold: #f6c453;
            --gold-dark: #d99d20;
            --gold-light: #ffe6a1;

            --white: #ffffff;
            --ivory: #f8fbff;

            --text-light: #e9f5ff;
            --text-muted: #b8cee0;

            --glass: rgba(255,255,255,.075);
            --glass-hover: rgba(255,255,255,.12);

            --border: rgba(255,255,255,.14);
        }


        /* =====================================================
           GLOBAL
        ====================================================== */

        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body.landing {

            margin: 0;

            min-height: 100vh;

            color: var(--white);

            background:

                radial-gradient(
                    circle at 15% 20%,
                    rgba(139,92,246,.34),
                    transparent 32%
                ),

                radial-gradient(
                    circle at 85% 25%,
                    rgba(246,196,83,.15),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 50% 100%,
                    rgba(53,32,111,.34),
                    transparent 40%
                ),

                linear-gradient(
                    135deg,
                    rgba(10,7,30,.98),
                    rgba(24,13,62,.95)
                ),

                url('/assets/images/church-background.jpg')
                center center / cover fixed no-repeat;

            overflow-x: hidden;
        }


        body.landing::before {

            content: "";

            position: fixed;

            inset: 0;

            pointer-events: none;

            background:
                linear-gradient(
                    180deg,
                    rgba(3,17,34,.15),
                    rgba(3,17,34,.38)
                );

            z-index: -1;
        }


        a {
            text-decoration: none;
        }


        /* =====================================================
           NAVIGATION
        ====================================================== */

        .landing-nav {

            position: sticky;

            top: 0;

            z-index: 1050;

            padding: 13px 0;

            background:

                linear-gradient(
                    90deg,
                    rgba(14,9,39,.98),
                    rgba(35,19,82,.96)
                );

            backdrop-filter: blur(20px);

            -webkit-backdrop-filter: blur(20px);

            border-bottom:
                1px solid rgba(196,181,253,.20);

            box-shadow:
                0 8px 35px rgba(0,0,0,.28);
        }


        .brand-wrapper {
            min-width: 0;
        }


        .brand-logo {

            width: 49px;

            height: 49px;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            background:
                linear-gradient(
                    145deg,
                    #ffffff,
                    #edf6ff
                );

            border:
                2px solid rgba(244,201,93,.65);

            border-radius: 14px;

            overflow: hidden;

            box-shadow:

                0 7px 25px rgba(0,0,0,.28),

                0 0 0 3px
                rgba(93,183,245,.07);
        }


        .brand-logo img {

            width: 100%;

            height: 100%;

            object-fit: contain;

            padding: 4px;
        }


        .brand-name {

            color: #ffffff !important;

            font-size: 15px;

            font-weight: 800;

            line-height: 1.2;
        }


        .brand-subtitle {

            margin-top: 3px;

            color: var(--gold-light);

            font-size: 11px;

            font-weight: 500;
        }


        /* =====================================================
           DESKTOP NAVIGATION BUTTONS
        ====================================================== */

        .nav-actions {

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .nav-actions .btn {

            min-height: 40px;

            padding: 7px 15px;

            border-radius: 10px;

            font-weight: 700;

            white-space: nowrap;

            transition:
                all .25s ease;
        }


        /* MEMBER LOGIN */

        .nav-actions .btn-outline-light {

            color: #eaf5ff;

            border-color:
                rgba(255,255,255,.30);

            background:
                rgba(255,255,255,.035);
        }


        .nav-actions .btn-outline-light:hover {

            color: #ffffff;

            background:
                rgba(22,131,216,.18);

            border-color:
                rgba(93,183,245,.65);

            transform:
                translateY(-2px);

            box-shadow:
                0 8px 22px rgba(22,131,216,.12);
        }


        /* REGISTER */

        .nav-actions .btn-success {

            background:
                linear-gradient(
                    135deg,
                    #1683d8,
                    #0d5fa8
                );

            border: none;

            box-shadow:
                0 7px 20px
                rgba(22,131,216,.25);
        }


        .nav-actions .btn-success:hover {

            background:
                linear-gradient(
                    135deg,
                    #2795e8,
                    #0870c7
                );

            transform:
                translateY(-2px);

            box-shadow:
                0 10px 28px
                rgba(22,131,216,.38);
        }


        /* PASTOR LOGIN */

        .nav-actions .btn-warning {

            color: #2c2107;

            background:
                linear-gradient(
                    135deg,
                    #ffe39a,
                    #f4c95d
                );

            border: none;

            box-shadow:
                0 7px 22px
                rgba(244,201,93,.20);
        }


        .nav-actions .btn-warning:hover {

            color: #1c1605;

            background:
                linear-gradient(
                    135deg,
                    #ffeab0,
                    #eab83f
                );

            transform:
                translateY(-2px);

            box-shadow:
                0 10px 28px
                rgba(244,201,93,.30);
        }


        /* =====================================================
           HERO
        ====================================================== */

        .hero {

            min-height:
                calc(100vh - 75px);

            display: flex;

            flex-direction: column;

            justify-content: center;

            padding-top: 75px;

            padding-bottom: 75px;

            position: relative;
        }


        /* LEFT BLUE GLOW */

        .hero::before {

            content: "";

            position: absolute;

            width: 500px;

            height: 500px;

            left: -280px;

            top: 80px;

            background:

                radial-gradient(
                    circle,
                    rgba(22,131,216,.28),
                    transparent 70%
                );

            pointer-events: none;
        }


        /* RIGHT GOLD/BLUE GLOW */

        .hero::after {

            content: "";

            position: absolute;

            width: 450px;

            height: 450px;

            right: -250px;

            bottom: 50px;

            background:

                radial-gradient(
                    circle,
                    rgba(93,183,245,.15),
                    transparent 70%
                );

            pointer-events: none;
        }


        .hero-content {

            position: relative;

            z-index: 2;
        }


        /* =====================================================
           HERO BADGE
        ====================================================== */

        .hero-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            margin:
                0 auto 24px;

            padding:
                10px 18px;

            color: #123252;

            background:

                linear-gradient(
                    135deg,
                    #eaf6ff,
                    #bfe5ff
                );

            border:
                1px solid
                rgba(93,183,245,.65);

            border-radius: 999px;

            font-size: 12px;

            font-weight: 800;

            box-shadow:

                0 10px 35px
                rgba(0,0,0,.20),

                0 0 30px
                rgba(22,131,216,.10);
        }


        .hero-badge i {

            color:
                #0d70bb;
        }


        /* =====================================================
           HERO HEADING
        ====================================================== */

        .hero h1 {

            max-width: 950px;

            margin:
                0 auto 22px;

            color: #ffffff;

            font-size:
                clamp(2.2rem, 6vw, 4.5rem);

            line-height: 1.05;

            letter-spacing: -2px;

            text-shadow:
                0 8px 35px
                rgba(0,0,0,.35);
        }


        .hero h1 .highlight {

            color:
                var(--primary-light);

            text-shadow:
                0 0 30px
                rgba(93,183,245,.20);
        }


        /* =====================================================
           HERO DESCRIPTION
        ====================================================== */

        .hero-description {

            max-width: 710px;

            margin:
                0 auto;

            color:
                var(--text-muted);

            font-size:
                clamp(15px, 2vw, 18px);

            line-height: 1.8;
        }


        /* =====================================================
           HERO BUTTONS
        ====================================================== */

        .hero-buttons {

            margin-top: 32px;

            display: flex;

            justify-content: center;

            align-items: center;

            gap: 13px;

            flex-wrap: wrap;
        }


        .hero-buttons .btn {

            min-height: 54px;

            padding:
                14px 25px;

            border-radius: 13px;

            font-weight: 800;

            transition:

                transform .25s ease,

                box-shadow .25s ease,

                background .25s ease;
        }


        .hero-buttons .btn:hover {

            transform:
                translateY(-3px);
        }


        /* MAIN CTA */

        .hero-buttons .btn-warning {

            color: #302207;

            background:

                linear-gradient(
                    135deg,
                    #ffe9a8 0%,
                    #f4c95d 50%,
                    #e9b83e 100%
                );

            border: none;

            box-shadow:
                0 12px 35px
                rgba(244,201,93,.22);
        }


        .hero-buttons .btn-warning:hover {

            color: #211804;

            background:

                linear-gradient(
                    135deg,
                    #fff0bd,
                    #f6cf67
                );

            box-shadow:
                0 16px 42px
                rgba(244,201,93,.32);
        }


        /* LOGIN BUTTON */

        .hero-buttons .btn-outline-light {

            color:
                #e9f5ff;

            background:
                rgba(255,255,255,.045);

            border:
                1px solid
                rgba(255,255,255,.28);

            backdrop-filter:
                blur(10px);
        }


        .hero-buttons .btn-outline-light:hover {

            color: #ffffff;

            background:
                rgba(22,131,216,.20);

            border-color:
                rgba(93,183,245,.70);

            box-shadow:
                0 10px 30px
                rgba(22,131,216,.18);
        }


        /* =====================================================
           FEATURE SECTION
        ====================================================== */

        .features {

            margin-top: 75px;
        }


        .feature {

            height: 100%;

            padding: 27px;

            background:

                linear-gradient(
                    145deg,
                    rgba(255,255,255,.09),
                    rgba(22,131,216,.035)
                );

            backdrop-filter:
                blur(18px);

            -webkit-backdrop-filter:
                blur(18px);

            border:
                1px solid
                rgba(255,255,255,.12);

            border-radius: 20px;

            box-shadow:
                0 18px 45px
                rgba(0,0,0,.20);

            transition:

                transform .3s ease,

                background .3s ease,

                border-color .3s ease,

                box-shadow .3s ease;
        }


        .feature:hover {

            transform:
                translateY(-8px);

            background:

                linear-gradient(
                    145deg,
                    rgba(255,255,255,.13),
                    rgba(22,131,216,.11)
                );

            border-color:
                rgba(93,183,245,.40);

            box-shadow:
                0 25px 55px
                rgba(0,0,0,.28);
        }


        /* FEATURE ICON */

        .feature-icon {

            width: 52px;

            height: 52px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 17px;

            color:
                #bfe5ff;

            background:

                linear-gradient(
                    145deg,
                    rgba(22,131,216,.28),
                    rgba(93,183,245,.08)
                );

            border:
                1px solid
                rgba(93,183,245,.30);

            border-radius: 15px;

            font-size: 23px;

            box-shadow:
                0 8px 22px
                rgba(22,131,216,.12);
        }


        .feature h3 {

            margin-bottom: 9px;

            color:
                #ffffff;

            font-size: 17px;
        }


        .feature p {

            margin: 0;

            color:
                var(--text-muted);

            font-size: 14px;

            line-height: 1.7;
        }


        /* =====================================================
           TRUST LINE
        ====================================================== */

        .trust-line {

            margin-top: 32px;

            color:
                #9eb8ca;

            font-size: 13px;
        }


        .trust-line i {

            color:
                var(--primary-light);
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .landing-footer {

            padding:
                28px 15px;

            color:
                #91a9ba;

            text-align:
                center;

            font-size:
                13px;

            background:

                linear-gradient(
                    180deg,
                    rgba(5,25,48,.78),
                    rgba(3,16,31,.96)
                );

            border-top:
                1px solid
                rgba(93,183,245,.15);
        }


        .landing-footer strong {

            color:
                var(--gold);

            font-weight:
                800;
        }


        /* =====================================================
           MOBILE MENU BUTTON
        ====================================================== */

        .mobile-menu-button {

            display: none;
        }


        .mobile-menu-button .btn {

            border-radius: 11px;

            border-color:
                rgba(93,183,245,.38);

            background:
                rgba(255,255,255,.04);

            color: #ffffff;
        }


        .mobile-menu-button .btn:hover {

            background:
                rgba(22,131,216,.14);

            border-color:
                rgba(93,183,245,.65);
        }


        /* =====================================================
           OFFCANVAS
        ====================================================== */

        .offcanvas {

            background:

                linear-gradient(
                    160deg,
                    #071a33,
                    #0a2747 55%,
                    #0f4778
                ) !important;
        }


        .offcanvas-header {

            border-color:
                rgba(93,183,245,.16) !important;
        }


        .offcanvas-title {

            color:
                #ffffff;
        }


        .offcanvas .btn {

            min-height:
                53px;

            border-radius:
                13px;

            font-weight:
                800;
        }


        /* =====================================================
           MOBILE INFO CARD
        ====================================================== */

        .mobile-info-card {

            padding:
                19px;

            background:
                rgba(255,255,255,.055);

            border:
                1px solid
                rgba(255,255,255,.10);

            border-radius:
                15px;
        }


        /* =====================================================
           TABLET
        ====================================================== */

        @media (max-width: 991.98px) {

            .desktop-nav {

                display:
                    none !important;
            }


            .mobile-menu-button {

                display:
                    block;
            }


            .hero {

                padding-top:
                    58px;

                padding-bottom:
                    60px;
            }


            .features {

                margin-top:
                    55px;
            }
        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 575.98px) {

            body.landing {

                background-attachment:
                    scroll;
            }


            .landing-nav {

                padding:
                    10px 0;
            }


            .brand-logo {

                width:
                    42px;

                height:
                    42px;

                border-radius:
                    11px;
            }


            .brand-name {

                max-width:
                    210px;

                overflow:
                    hidden;

                text-overflow:
                    ellipsis;

                font-size:
                    13px;
            }


            .brand-subtitle {

                font-size:
                    9px;
            }


            .hero {

                min-height:
                    auto;

                padding-top:
                    48px;

                padding-bottom:
                    48px;
            }


            .hero-badge {

                max-width:
                    100%;

                padding:
                    8px 12px;

                font-size:
                    10px;
            }


            .hero h1 {

                font-size:
                    2.15rem;

                line-height:
                    1.12;

                letter-spacing:
                    -.9px;
            }


            .hero-description {

                font-size:
                    14px;

                line-height:
                    1.7;
            }


            .hero-buttons {

                flex-direction:
                    column;

                width:
                    100%;
            }


            .hero-buttons .btn {

                width:
                    100%;

                max-width:
                    360px;
            }


            .features {

                margin-top:
                    38px;
            }


            .feature {

                padding:
                    21px;

                border-radius:
                    16px;
            }


            .trust-line {

                font-size:
                    11px;

                line-height:
                    1.6;
            }
        }


        /* =====================================================
           SMALL PHONES
        ====================================================== */

        @media (max-width: 380px) {

            .brand-name {

                max-width:
                    165px;

                font-size:
                    12px;
            }


            .brand-subtitle {

                display:
                    none;
            }


            .brand-logo {

                width:
                    38px;

                height:
                    38px;
            }


            .hero h1 {

                font-size:
                    1.9rem;
            }


            .hero-description {

                font-size:
                    13px;
            }


            .hero-badge {

                font-size:
                    9px;
            }
        }


        /* =====================================================
           REDUCED MOTION
        ====================================================== */

        @media (prefers-reduced-motion: reduce) {

            html {

                scroll-behavior:
                    auto;
            }


            *,
            *::before,
            *::after {

                transition:
                    none !important;

                animation:
                    none !important;
            }
        }


        /* =====================================================
           ENHANCED MOBILE RESPONSIVENESS
           Optimized for phones, tablets and touch screens
        ====================================================== */

        /* Prevent accidental horizontal overflow */
        html,
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .container {
            width: 100%;
        }

        /* Better touch targets */
        .btn,
        .mobile-menu-button button,
        .offcanvas .btn {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        /* Tablet and phones */
        @media (max-width: 991.98px) {

            .landing-nav {
                padding: 9px 0;
            }

            .landing-nav > .container {
                padding-left: 14px;
                padding-right: 14px;
            }

            .brand-wrapper {
                min-width: 0;
                max-width: calc(100% - 58px);
            }

            .brand-wrapper > div:last-child {
                min-width: 0;
            }

            .mobile-menu-button {
                flex-shrink: 0;
            }

            .mobile-menu-button .btn {
                width: 46px;
                height: 44px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .hero {
                width: 100%;
                min-height: auto;
            }

            .hero-content {
                width: 100%;
            }

            .hero h1,
            .hero-description,
            .hero-badge,
            .hero-buttons,
            .features,
            .trust-line {
                max-width: 100%;
            }

            .feature {
                overflow-wrap: anywhere;
            }
        }

        /* Phones */
        @media (max-width: 767.98px) {

            .landing-nav {
                position: sticky;
                top: 0;
            }

            .landing-nav > .container {
                padding-left: 12px;
                padding-right: 12px;
            }

            .brand-logo {
                width: 42px;
                height: 42px;
                min-width: 42px;
                border-radius: 10px;
            }

            .brand-logo img {
                padding: 3px;
            }

            .brand-name {
                max-width: calc(100vw - 105px);
                font-size: 12.5px;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .brand-subtitle {
                max-width: calc(100vw - 105px);
                font-size: 8.5px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .hero {
                padding-left: 14px;
                padding-right: 14px;
                padding-top: 42px;
                padding-bottom: 42px;
            }

            .hero::before {
                width: 260px;
                height: 260px;
                left: -170px;
                top: 30px;
            }

            .hero::after {
                width: 260px;
                height: 260px;
                right: -170px;
                bottom: 10px;
            }

            .hero-badge {
                display: flex;
                width: fit-content;
                max-width: 100%;
                margin: 0 auto 20px;
                padding: 8px 11px;
                font-size: 9.5px;
                line-height: 1.35;
                white-space: normal;
                text-align: center;
            }

            .hero h1 {
                width: 100%;
                margin-bottom: 17px;
                font-size: clamp(1.85rem, 9vw, 2.45rem);
                line-height: 1.12;
                letter-spacing: -0.7px;
                overflow-wrap: break-word;
            }

            .hero-description {
                width: 100%;
                padding: 0 2px;
                font-size: 14px;
                line-height: 1.65;
            }

            .hero-buttons {
                width: 100%;
                margin-top: 25px;
                gap: 10px;
            }

            .hero-buttons .btn {
                width: 100%;
                max-width: 100%;
                min-height: 52px;
                padding: 13px 16px;
                font-size: 14px;
                border-radius: 12px;
            }

            .features {
                width: 100%;
                margin-top: 34px;
            }

            .feature {
                width: 100%;
                min-height: auto;
                padding: 20px;
                border-radius: 15px;
            }

            .feature-icon {
                width: 48px;
                height: 48px;
                margin-bottom: 14px;
                border-radius: 13px;
                font-size: 21px;
            }

            .feature h3 {
                font-size: 16px;
                line-height: 1.35;
            }

            .feature p {
                font-size: 13.5px;
                line-height: 1.65;
            }

            .trust-line {
                margin-top: 25px;
                padding: 0 8px;
                font-size: 10.5px;
                line-height: 1.8;
            }

            .landing-footer {
                padding: 23px 12px;
                font-size: 11.5px;
                line-height: 1.7;
            }

            .offcanvas {
                width: min(88vw, 360px) !important;
            }

            .offcanvas-body {
                padding: 20px 16px;
            }

            .offcanvas .btn {
                min-height: 52px;
                font-size: 14px;
            }

            .mobile-info-card {
                padding: 16px;
            }
        }

        /* Very small phones: 320px–375px */
        @media (max-width: 380px) {

            .landing-nav > .container {
                padding-left: 9px;
                padding-right: 9px;
            }

            .brand-logo {
                width: 37px;
                height: 37px;
                min-width: 37px;
            }

            .brand-name {
                max-width: calc(100vw - 91px);
                font-size: 11.5px;
            }

            .brand-subtitle {
                display: none;
            }

            .mobile-menu-button .btn {
                width: 42px;
                height: 40px;
            }

            .hero {
                padding-left: 11px;
                padding-right: 11px;
                padding-top: 35px;
                padding-bottom: 35px;
            }

            .hero-badge {
                font-size: 8.5px;
                padding: 7px 9px;
            }

            .hero h1 {
                font-size: 1.75rem;
                line-height: 1.13;
            }

            .hero-description {
                font-size: 13px;
                line-height: 1.6;
            }

            .hero-buttons .btn {
                min-height: 50px;
                font-size: 13px;
            }

            .feature {
                padding: 17px;
            }

            .feature h3 {
                font-size: 15px;
            }

            .feature p {
                font-size: 12.5px;
            }

            .trust-line {
                font-size: 9.5px;
            }
        }

        /* Landscape phones */
        @media (max-width: 767.98px) and (orientation: landscape) {

            .hero {
                padding-top: 30px;
                padding-bottom: 35px;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero-buttons {
                flex-direction: row;
                justify-content: center;
            }

            .hero-buttons .btn {
                width: auto;
                min-width: 180px;
            }
        }

        /* Avoid fixed-background performance problems on mobile */
        @media (hover: none) and (pointer: coarse) {

            body.landing {
                background-attachment: scroll;
            }

            .feature:hover,
            .hero-buttons .btn:hover,
            .nav-actions .btn:hover {
                transform: none;
            }
        }

        /* Safe-area support for modern phones */
        @supports (padding: max(0px)) {

            .landing-nav > .container {
                padding-left: max(12px, env(safe-area-inset-left));
                padding-right: max(12px, env(safe-area-inset-right));
            }

            .landing-footer {
                padding-bottom: max(23px, env(safe-area-inset-bottom));
            }
        }

        /* =====================================================
           ROLE ACCESS CARDS
        ====================================================== */

        .role-access-heading{max-width:820px;margin:0 auto 18px}
        .role-access-kicker,.mission-kicker{display:inline-block;font-size:10px;letter-spacing:2.2px;font-weight:900;color:var(--gold-light);margin-bottom:7px}
        .role-access-heading h2{font-size:1.45rem;font-weight:900;margin:0 0 7px}
        .role-access-heading p{max-width:720px;margin:0 auto;color:var(--text-muted);font-size:13px;line-height:1.65}
        .role-card{height:100%;min-height:145px;display:flex;flex-direction:column;align-items:flex-start;justify-content:space-between;padding:20px;border:1px solid rgba(255,255,255,.15);border-radius:22px;background:linear-gradient(145deg,rgba(255,255,255,.13),rgba(255,255,255,.045));box-shadow:0 18px 42px rgba(0,0,0,.20),inset 0 1px 0 rgba(255,255,255,.08);transition:transform .25s ease,background .25s ease,border-color .25s ease,box-shadow .25s ease;color:#fff}
        .role-card:hover{transform:translateY(-7px);background:linear-gradient(145deg,rgba(139,92,246,.30),rgba(255,255,255,.08));border-color:rgba(246,196,83,.72);color:#fff;box-shadow:0 25px 55px rgba(0,0,0,.28),0 0 0 1px rgba(246,196,83,.08)}
        .role-card-icon{width:50px;height:50px;border-radius:16px;display:grid;place-items:center;background:linear-gradient(145deg,rgba(246,196,83,.20),rgba(139,92,246,.22));color:var(--gold-light);font-size:21px;margin-bottom:20px;border:1px solid rgba(246,196,83,.25)}
        .role-card-title{font-size:17px;font-weight:900}
        .role-card-action{margin-top:12px;font-size:11px;font-weight:900;color:var(--gold-light);display:flex;align-items:center;gap:7px}
        .role-card-action i{transition:transform .2s ease}
        .role-card:hover .role-card-action i{transform:translate(3px,-3px)}
        .church-mission{max-width:860px;margin-left:auto;margin-right:auto;padding:25px 28px;border:1px solid rgba(255,255,255,.12);border-radius:24px;background:linear-gradient(145deg,rgba(255,255,255,.08),rgba(255,255,255,.035));box-shadow:inset 0 1px 0 rgba(255,255,255,.06)}
        .church-mission h2{font-size:1.55rem;font-weight:900;margin:0 0 8px}
        .church-mission p{margin:0;color:var(--text-muted);font-size:13px;line-height:1.8}

        @media(max-width:767.98px){
            .role-card{min-height:130px;padding:16px}
            .role-card-icon{width:44px;height:44px;margin-bottom:12px}
            .role-card-title{font-size:15px}
            .role-access-heading h2{font-size:1.2rem}
            .church-mission{padding:21px 18px}
            .church-mission h2{font-size:1.3rem}
        }

    
/* Inspirational section refinement */
.fgck-inspiration-after-hero{
  margin: 0 auto 3rem;
  width: min(1180px, calc(100% - 2rem));
  border-radius: 30px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 22px 55px rgba(37,99,235,.14);
}
.fgck-inspiration-after-hero .fgck-me-inner{
  padding: clamp(2rem,5vw,4rem) clamp(1.25rem,5vw,4rem);
}
.fgck-inspiration-after-hero .fgck-me-title{
  max-width: 900px;
  margin-left: auto;
  margin-right: auto;
}
.fgck-inspiration-after-hero .fgck-me-sub{
  max-width: 760px;
  margin-left: auto;
  margin-right: auto;
}
.fgck-me-quotes{
  min-height: 82px;
  display:flex;
  align-items:center;
  justify-content:center;
  margin-top:1.4rem;
}
.fgck-inspiration-after-hero .fgck-me-quote{
  max-width: 880px;
  text-align:center;
  font-size:clamp(1.05rem,2vw,1.38rem);
  font-weight:700;
  line-height:1.65;
  letter-spacing:.01em;
}
.fgck-inspiration-after-hero .fgck-me-dots{
  margin-top:1.6rem;
}
.fgck-inspiration-after-hero .fgck-me-dot{
  width:8px;
  height:8px;
  margin:0 4px;
  opacity:.38;
  transition:all .25s ease;
}
.fgck-inspiration-after-hero .fgck-me-dot.active{
  width:24px;
  border-radius:999px;
  opacity:1;
}
@media (max-width:576px){
  .fgck-inspiration-after-hero{
    width:calc(100% - 1rem);
    border-radius:22px;
  }
  .fgck-me-quotes{min-height:135px}
}


/* Premium professional homepage */
body{
  background:
    radial-gradient(circle at 8% 12%, rgba(56,189,248,.16), transparent 28%),
    radial-gradient(circle at 92% 18%, rgba(99,102,241,.14), transparent 30%),
    radial-gradient(circle at 50% 100%, rgba(14,165,233,.10), transparent 34%),
    linear-gradient(135deg,#f8fbff 0%,#eef5ff 46%,#f7f5ff 100%);
}
.fgck-premium-hero{
  position:relative;
  overflow:hidden;
  margin:0 auto 2rem;
  width:min(1240px,calc(100% - 2rem));
  border-radius:34px;
  background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 52%,#4f46e5 100%);
  color:#fff;
  box-shadow:0 28px 70px rgba(30,64,175,.22);
}
.fgck-premium-hero:after{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(90deg,rgba(15,23,42,.26),rgba(37,99,235,.06));
  pointer-events:none;
}
.fgck-premium-hero-inner{
  position:relative;
  z-index:2;
  padding:clamp(3rem,7vw,6.5rem) clamp(1.5rem,7vw,6.5rem);
  max-width:980px;
}
.fgck-premium-eyebrow{
  display:inline-flex;
  align-items:center;
  gap:.45rem;
  padding:.55rem .85rem;
  border:1px solid rgba(255,255,255,.25);
  border-radius:999px;
  background:rgba(255,255,255,.10);
  backdrop-filter:blur(12px);
  font-size:.72rem;
  font-weight:800;
  letter-spacing:.14em;
}
.fgck-premium-hero h1{
  margin:1.2rem 0 1rem;
  max-width:900px;
  font-size:clamp(2.35rem,5vw,4.7rem);
  line-height:1.03;
  letter-spacing:-.045em;
}
.fgck-premium-hero p{
  max-width:760px;
  margin:0;
  font-size:clamp(1rem,1.6vw,1.22rem);
  line-height:1.75;
  color:rgba(255,255,255,.86);
}
.fgck-premium-actions{
  display:flex;
  align-items:center;
  gap:1rem;
  flex-wrap:wrap;
  margin-top:2rem;
}
.fgck-premium-primary{
  display:inline-flex;
  align-items:center;
  gap:.75rem;
  padding:.95rem 1.25rem;
  border-radius:14px;
  background:#fff;
  color:#1d4ed8 !important;
  text-decoration:none !important;
  font-weight:800;
  box-shadow:0 12px 30px rgba(0,0,0,.15);
  transition:transform .2s ease,box-shadow .2s ease;
}
.fgck-premium-primary:hover{transform:translateY(-2px);box-shadow:0 16px 34px rgba(0,0,0,.2)}
.fgck-premium-note{
  font-size:.84rem;
  color:rgba(255,255,255,.68);
}
.fgck-premium-orb{
  position:absolute;
  border-radius:50%;
  filter:blur(2px);
  opacity:.42;
}
.fgck-orb-one{
  width:360px;height:360px;right:-90px;top:-120px;
  background:radial-gradient(circle,rgba(34,211,238,.65),transparent 68%);
}
.fgck-orb-two{
  width:300px;height:300px;right:20%;bottom:-190px;
  background:radial-gradient(circle,rgba(167,139,250,.55),transparent 68%);
}
.fgck-three-inspiration{
  width:min(1240px,calc(100% - 2rem));
  margin:0 auto 3rem;
}
.fgck-three-heading{
  text-align:center;
  max-width:760px;
  margin:0 auto 1.5rem;
}
.fgck-three-heading span{
  font-size:.72rem;
  font-weight:900;
  letter-spacing:.16em;
  color:#4f46e5;
}
.fgck-three-heading h2{
  margin:.55rem 0 0;
  color:#172033;
  font-size:clamp(1.65rem,3vw,2.45rem);
  line-height:1.15;
}
.fgck-inspiration-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:1rem;
}
.fgck-inspiration-card{
  position:relative;
  min-height:280px;
  padding:1.7rem;
  border:1px solid rgba(148,163,184,.22);
  border-radius:24px;
  overflow:hidden;
  background:rgba(255,255,255,.78);
  backdrop-filter:blur(18px);
  box-shadow:0 16px 42px rgba(15,23,42,.08);
  transition:transform .25s ease,box-shadow .25s ease;
}
.fgck-inspiration-card:hover{
  transform:translateY(-6px);
  box-shadow:0 24px 52px rgba(15,23,42,.13);
}
.fgck-card-left{background:linear-gradient(145deg,rgba(239,246,255,.94),rgba(224,242,254,.75))}
.fgck-card-center{background:linear-gradient(145deg,rgba(245,243,255,.94),rgba(237,233,254,.75))}
.fgck-card-right{background:linear-gradient(145deg,rgba(236,253,245,.94),rgba(207,250,254,.72))}
.fgck-inspiration-number{
  position:absolute;right:1.35rem;top:1.1rem;
  font-size:.72rem;font-weight:900;letter-spacing:.12em;color:#94a3b8;
}
.fgck-inspiration-icon{
  display:flex;align-items:center;justify-content:center;
  width:48px;height:48px;border-radius:15px;
  background:#fff;box-shadow:0 8px 20px rgba(15,23,42,.08);
  color:#4f46e5;font-size:1.35rem;
}
.fgck-inspiration-card h3{
  margin:1.35rem 0 .7rem;
  color:#172033;font-size:1.35rem;
}
.fgck-inspiration-card p{
  margin:0;color:#526174;line-height:1.75;font-size:.98rem;
}
.fgck-more-inspiration{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:.75rem;
  margin-top:1rem;
}
.fgck-more-item{
  padding:1rem 1.1rem;
  border-radius:16px;
  background:rgba(255,255,255,.62);
  border:1px solid rgba(148,163,184,.16);
  color:#536176;
  font-size:.88rem;
  line-height:1.55;
}
@media(max-width:800px){
  .fgck-inspiration-grid,.fgck-more-inspiration{grid-template-columns:1fr}
  .fgck-inspiration-card{min-height:auto}
}
@media(max-width:576px){
  .fgck-premium-hero,.fgck-three-inspiration{width:calc(100% - 1rem)}
  .fgck-premium-hero{border-radius:24px}
  .fgck-premium-hero-inner{padding:2.5rem 1.25rem}
  .fgck-premium-actions{align-items:flex-start;flex-direction:column}
  .fgck-premium-note{max-width:300px}
}


/* Pastor-managed homepage content */
.fgck-pastor-content{width:min(1240px,calc(100% - 2rem));margin:0 auto 3rem}
.fgck-pastor-content-head{text-align:center;max-width:800px;margin:0 auto 1.5rem}
.fgck-pastor-content-head span{font-size:.72rem;font-weight:900;letter-spacing:.16em;color:#4f46e5}
.fgck-pastor-content-head h2{margin:.55rem 0 0;color:#172033;font-size:clamp(1.6rem,3vw,2.4rem);line-height:1.15}
.fgck-pastor-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}
.fgck-pastor-grid article{padding:1.5rem;border-radius:22px;background:rgba(255,255,255,.8);border:1px solid rgba(148,163,184,.2);box-shadow:0 14px 36px rgba(15,23,42,.07)}
.fgck-pastor-grid article p{margin:1rem 0 0;color:#526174;line-height:1.75;font-size:.96rem}
.fgck-pastor-label{font-size:.72rem;font-weight:900;letter-spacing:.12em;color:#4f46e5}
.fgck-pastor-label i{margin-right:.3rem}
.fgck-theme-card{background:linear-gradient(145deg,rgba(245,243,255,.95),rgba(224,242,254,.82)) !important}
.fgck-theme-card p{font-weight:800;color:#27344a !important;font-size:1.1rem !important}
.fgck-daily-quote{margin-top:1rem;border-radius:26px;overflow:hidden;background:linear-gradient(135deg,#0f172a,#1d4ed8 58%,#4f46e5);color:#fff;display:grid;grid-template-columns:1fr minmax(280px,420px);box-shadow:0 20px 50px rgba(30,64,175,.18)}
.fgck-daily-quote-copy{padding:2rem 2.25rem;display:flex;flex-direction:column;justify-content:center}
.fgck-quote-label{font-size:.72rem;font-weight:900;letter-spacing:.13em;color:rgba(255,255,255,.7)}
.fgck-daily-quote blockquote{margin:1rem 0 0;font-size:clamp(1.25rem,2.5vw,1.8rem);font-weight:750;line-height:1.5}
.fgck-daily-quote-image{min-height:240px;background:rgba(255,255,255,.08)}
.fgck-daily-quote-image img{width:100%;height:100%;min-height:240px;object-fit:cover;display:block}
@media(max-width:1000px){.fgck-pastor-grid{grid-template-columns:repeat(2,1fr)}.fgck-daily-quote{grid-template-columns:1fr}}
@media(max-width:576px){.fgck-pastor-content{width:calc(100% - 1rem)}.fgck-pastor-grid{grid-template-columns:1fr}.fgck-daily-quote-copy{padding:1.5rem}.fgck-daily-quote-image,.fgck-daily-quote-image img{min-height:200px}}


/* Dynamic motivation cards */
.fgck-random-motivation{display:none;opacity:0;transform:translateY(8px)}
.fgck-random-motivation.fgck-random-visible{
  display:block;
  animation:fgckMotivationIn .55s ease forwards;
}
.fgck-random-note{
  margin:.65rem auto 0;
  color:#64748b;
  font-size:.88rem;
}
@keyframes fgckMotivationIn{
  from{opacity:0;transform:translateY(10px)}
  to{opacity:1;transform:translateY(0)}
}


/* Bible verse styling for rotating encouragement cards */
.fgck-bible-verse{margin-top:1.1rem;padding-top:1rem;border-top:1px solid rgba(100,116,139,.16);display:flex;flex-direction:column;gap:.4rem}
.fgck-bible-verse strong{font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:#4f46e5}
.fgck-bible-verse span{color:#334155;font-size:.88rem;line-height:1.6;font-style:italic}
</style>




<style id="fgck-modern-home">
:root{
  --mh-ink:#10213b;
  --mh-muted:#64748b;
  --mh-primary:#2563eb;
  --mh-primary2:#4f46e5;
  --mh-cyan:#06b6d4;
  --mh-gold:#f59e0b;
  --mh-bg:#f6f9ff;
  --mh-card:rgba(255,255,255,.90);
  --mh-line:rgba(15,23,42,.08);
}
html{scroll-behavior:smooth}
body{
  background:
    radial-gradient(circle at 10% 5%,rgba(37,99,235,.10),transparent 26rem),
    radial-gradient(circle at 90% 18%,rgba(6,182,212,.09),transparent 25rem),
    linear-gradient(180deg,#f8fbff 0%,#fff 46%,#f8fafc 100%);
  color:var(--mh-ink);
}
.fgck-modern-encouragement{
  position:relative; overflow:hidden; width:100%;
  margin:0 0 34px; padding:28px clamp(20px,5vw,72px);
  background:linear-gradient(105deg,#173fbe 0%,#2563eb 38%,#4f46e5 72%,#7c3aed 100%);
  color:#fff; box-shadow:0 12px 34px rgba(37,99,235,.20);
}
.fgck-modern-encouragement:before{
  content:""; position:absolute; width:320px;height:320px;border-radius:50%;
  right:-100px;top:-190px;background:rgba(255,255,255,.12);
}
.fgck-modern-encouragement:after{
  content:""; position:absolute; width:190px;height:190px;border-radius:50%;
  left:-80px;bottom:-145px;background:rgba(6,182,212,.20);
}
.fgck-me-inner{position:relative;z-index:2;max-width:1200px;margin:auto}
.fgck-me-label{
  display:inline-flex;align-items:center;gap:8px;text-transform:uppercase;
  letter-spacing:.13em;font-size:.72rem;font-weight:800;opacity:.85;margin-bottom:8px
}
.fgck-me-title{margin:0;font-size:clamp(1.45rem,3vw,2.25rem);font-weight:850;letter-spacing:-.03em}
.fgck-me-sub{margin:7px 0 17px;max-width:760px;opacity:.88;line-height:1.55}
.fgck-me-quote{display:none;max-width:900px;font-size:clamp(1rem,2vw,1.22rem);font-weight:650;line-height:1.55}
.fgck-me-quote.active{display:block}
.fgck-me-dots{display:flex;gap:7px;margin-top:16px}
.fgck-me-dot{width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,.4);transition:.25s}
.fgck-me-dot.active{width:25px;border-radius:9px;background:#fff}

main, .main-content, .container{
  position:relative;
}
.fgck-modern-section{
  max-width:1200px;margin:0 auto 54px;padding:0 22px;
}
.fgck-modern-kicker{
  color:var(--mh-primary);font-size:.75rem;font-weight:850;
  text-transform:uppercase;letter-spacing:.14em;margin-bottom:8px
}
.fgck-modern-heading{
  font-size:clamp(1.8rem,4vw,3.15rem);line-height:1.05;
  letter-spacing:-.045em;margin:0 0 13px;font-weight:900
}
.fgck-modern-lead{color:var(--mh-muted);font-size:1.03rem;line-height:1.7;max-width:740px}
.fgck-modern-card{
  background:var(--mh-card);border:1px solid var(--mh-line);border-radius:24px;
  box-shadow:0 12px 36px rgba(15,23,42,.07);backdrop-filter:blur(12px);
  transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease
}
.fgck-modern-card:hover{transform:translateY(-4px);box-shadow:0 20px 44px rgba(15,23,42,.11);border-color:rgba(37,99,235,.18)}
.fgck-modern-accent{
  width:76px;height:5px;border-radius:99px;margin:18px 0 0;
  background:linear-gradient(90deg,var(--mh-primary),var(--mh-cyan),var(--mh-gold))
}
.fgck-modern-glow{
  position:absolute;pointer-events:none;width:220px;height:220px;border-radius:50%;
  background:rgba(37,99,235,.08);filter:blur(5px);right:5%;top:15%
}
@media(max-width:700px){
  .fgck-modern-encouragement{padding:24px 18px;margin-bottom:25px}
  .fgck-modern-section{padding:0 16px;margin-bottom:38px}
}
</style>
</head>


<body class="landing">




<!-- =========================================================
     NAVIGATION
========================================================= -->

<nav class="landing-nav">

    <div class="container">

        <div
            class="d-flex justify-content-between align-items-center"
        >


            <!-- BRAND -->

            <div
                class="d-flex align-items-center gap-2 brand-wrapper"
            >

                <div class="brand-logo">

                    <img
                        src="/assets/images/full_gospel_churches_logo.png"
                        alt="FGCK Makutano West Joyland Church Logo"
                    >

                </div>


                <div>

                    <div class="brand-name">

                        FGCK Makutano West Joyland

                    </div>


                    <div class="brand-subtitle">

                       perfected to influence

                    </div>

                </div>

            </div>


            <!-- DESKTOP ACTIONS -->

            <div
                class="nav-actions desktop-nav"
            >


                <a
                    href="/auth/login"
                    class="btn btn-outline-light btn-sm"
                >

                    <i
                        class="bi bi-box-arrow-in-right me-1"
                    ></i>

                    Sign In

                </a>


                <a
                    href="/auth/register"
                    class="btn btn-success btn-sm"
                >

                    <i
                        class="bi bi-person-plus me-1"
                    ></i>

                    Register

                </a>

</div>


            <!-- MOBILE MENU -->

            <div
                class="mobile-menu-button"
            >

                <button
                    class="btn btn-outline-light"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#mobileMenu"
                    aria-controls="mobileMenu"
                    aria-label="Open navigation menu"
                >

                    <i
                        class="bi bi-list fs-5"
                    ></i>

                </button>

            </div>

        </div>

    </div>

</nav>

<section class="fgck-premium-hero" aria-label="FGCK Joyland welcome">
  <div class="fgck-premium-orb fgck-orb-one"></div>
  <div class="fgck-premium-orb fgck-orb-two"></div>
  <div class="fgck-premium-hero-inner">
    <div class="fgck-premium-eyebrow">FGCK JOYLAND • CONNECT • SERVE • GROW</div>
    <h1>A connected church for a stronger community.</h1>
    <p>Welcome to the FGCK Joyland digital experience — a trusted space designed to bring people, ministry and church life together with clarity, care and purpose.</p>
    <div class="fgck-premium-actions">
      <a href="/auth/login" class="fgck-premium-primary">Sign In to the Church Portal <span>→</span></a>
      <span class="fgck-premium-note">Secure access • Role-based experience • Built for the whole church</span>
    </div>
  </div>
</section>

<?php if($homepageMission || $homepageVision || $homepageMotto || $homepageTheme || $homepageQuote || $homepageQuoteImage): ?>
<section class="fgck-pastor-content" aria-label="Church message">
  <div class="fgck-pastor-content-head">
    <span>FROM THE PASTOR</span>
    <h2>What guides us. What inspires us. What we are becoming.</h2>
  </div>
  <div class="fgck-pastor-grid">
    <?php if($homepageMission): ?><article><div class="fgck-pastor-label"><i class="bi bi-compass"></i> MISSION</div><p><?=nl2br(e($homepageMission))?></p></article><?php endif; ?>
    <?php if($homepageVision): ?><article><div class="fgck-pastor-label"><i class="bi bi-eye"></i> VISION</div><p><?=nl2br(e($homepageVision))?></p></article><?php endif; ?>
    <?php if($homepageMotto): ?><article><div class="fgck-pastor-label"><i class="bi bi-stars"></i> MOTTO</div><p><?=nl2br(e($homepageMotto))?></p></article><?php endif; ?>
    <?php if($homepageTheme): ?><article class="fgck-theme-card"><div class="fgck-pastor-label"><i class="bi bi-sun"></i> CHURCH THEME OF THE YEAR</div><p><?=nl2br(e($homepageTheme))?></p></article><?php endif; ?>
  </div>
  <?php if($homepageQuote || $homepageQuoteImage): ?>
  <div class="fgck-daily-quote">
    <div class="fgck-daily-quote-copy">
      <span class="fgck-quote-label"><i class="bi bi-quote"></i> DAILY QUOTE<?= $homepageQuoteDate ? ' • '.e(date('F j, Y',strtotime($homepageQuoteDate))) : '' ?></span>
      <?php if($homepageQuote): ?><blockquote>“<?=nl2br(e($homepageQuote))?>”</blockquote><?php endif; ?>
    </div>
    <?php if($homepageQuoteImage): ?><div class="fgck-daily-quote-image"><img src="<?=e($homepageQuoteImage)?>" alt="Daily inspirational quote"></div><?php endif; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="fgck-three-inspiration" aria-label="Inspirational messages">
  <div class="fgck-three-heading">
    <span>DAILY ENCOURAGEMENT</span>
    <h2>Words to strengthen your faith and inspire your journey.</h2>
    <p class="fgck-random-note">A fresh encouragement and Bible verse are selected every time you visit or refresh this page.</p>
  </div>

  <div class="fgck-inspiration-grid">
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="1">
      <div class="fgck-inspiration-number">01</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Keep Moving Forward</h3>
      <p>Keep going with faith. What feels like a small step today can become a testimony tomorrow.</p><div class="fgck-bible-verse"><strong>Joshua 1:9</strong><span>“Be strong and of a good courage; be not afraid, neither be thou dismayed: for the LORD thy God is with thee whithersoever thou goest.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="2">
      <div class="fgck-inspiration-number">02</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Your Presence Matters</h3>
      <p>Your presence matters. Your kindness, service and encouragement can change someone’s day.</p><div class="fgck-bible-verse"><strong>Philippians 4:13</strong><span>“I can do all things through Christ which strengtheneth me.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="3">
      <div class="fgck-inspiration-number">03</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Walk With Purpose</h3>
      <p>Serve with joy, love generously, and remember that your faith can inspire someone else.</p><div class="fgck-bible-verse"><strong>Psalm 23:1</strong><span>“The LORD is my shepherd; I shall not want.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="4">
      <div class="fgck-inspiration-number">04</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Choose Hope</h3>
      <p>There is always hope. Every new day brings another opportunity to grow, serve and begin again.</p><div class="fgck-bible-verse"><strong>Proverbs 3:5</strong><span>“Trust in the LORD with all thine heart; and lean not unto thine own understanding.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="5">
      <div class="fgck-inspiration-number">05</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Be a Light</h3>
      <p>Let your life be a light. A simple word of kindness can become a powerful encouragement to someone.</p><div class="fgck-bible-verse"><strong>Jeremiah 29:11</strong><span>“For I know the thoughts that I think toward you, saith the LORD, thoughts of peace, and not of evil, to give you an expected end.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="6">
      <div class="fgck-inspiration-number">06</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Trust the Journey</h3>
      <p>You may not see the whole path, but you can take the next step with faith and courage.</p><div class="fgck-bible-verse"><strong>1 Corinthians 16:14</strong><span>“Let all your things be done with charity.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="7">
      <div class="fgck-inspiration-number">07</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Keep Believing</h3>
      <p>Great things often begin quietly. Keep serving, keep believing and keep trusting God.</p><div class="fgck-bible-verse"><strong>Nehemiah 8:10</strong><span>“The joy of the LORD is your strength.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="8">
      <div class="fgck-inspiration-number">08</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Strength in Community</h3>
      <p>We are stronger together. Encourage someone, pray for someone and be a blessing today.</p><div class="fgck-bible-verse"><strong>2 Corinthians 5:7</strong><span>“For we walk by faith, not by sight.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="9">
      <div class="fgck-inspiration-number">09</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>New Mercies</h3>
      <p>Every new morning is an opportunity to receive fresh strength, renewed hope and a grateful heart.</p><div class="fgck-bible-verse"><strong>Isaiah 40:31</strong><span>“But they that wait upon the LORD shall renew their strength; they shall mount up with wings as eagles; they shall run, and not be weary; and they shall walk, and not faint.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="10">
      <div class="fgck-inspiration-number">10</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Courage to Continue</h3>
      <p>When the road feels difficult, remember that difficulty does not mean the journey has no purpose.</p><div class="fgck-bible-verse"><strong>Matthew 19:26</strong><span>“With men this is impossible; but with God all things are possible.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="11">
      <div class="fgck-inspiration-number">11</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Serve With Joy</h3>
      <p>Your service matters. What you do with love today can leave a lasting impact tomorrow.</p><div class="fgck-bible-verse"><strong>Psalm 46:1</strong><span>“God is our refuge and strength, a very present help in trouble.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="12">
      <div class="fgck-inspiration-number">12</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Faith Over Fear</h3>
      <p>Choose courage over fear, gratitude over worry and faith over doubt.</p><div class="fgck-bible-verse"><strong>Romans 8:28</strong><span>“And we know that all things work together for good to them that love God.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="13">
      <div class="fgck-inspiration-number">13</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Your Story Matters</h3>
      <p>Your story is still being written. Do not let yesterday limit what tomorrow can become.</p><div class="fgck-bible-verse"><strong>Psalm 118:24</strong><span>“This is the day which the LORD hath made; we will rejoice and be glad in it.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="14">
      <div class="fgck-inspiration-number">14</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Purpose in Every Step</h3>
      <p>Walk forward with purpose. Even small acts of faithfulness can make a meaningful difference.</p><div class="fgck-bible-verse"><strong>Galatians 6:9</strong><span>“And let us not be weary in well doing: for in due season we shall reap, if we faint not.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="15">
      <div class="fgck-inspiration-number">15</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Hope for Tomorrow</h3>
      <p>Believe that better days are ahead. Keep your heart hopeful and your hands ready to serve.</p><div class="fgck-bible-verse"><strong>Psalm 37:5</strong><span>“Commit thy way unto the LORD; trust also in him; and he shall bring it to pass.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="16">
      <div class="fgck-inspiration-number">16</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Make a Difference</h3>
      <p>Wherever you are today, look for one opportunity to encourage, help or uplift another person.</p><div class="fgck-bible-verse"><strong>Isaiah 41:10</strong><span>“Fear thou not; for I am with thee: be not dismayed; for I am thy God: I will strengthen thee; yea, I will help thee.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="17">
      <div class="fgck-inspiration-number">17</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Stay Faithful</h3>
      <p>Faithfulness in small things creates a foundation for greater things. Keep showing up with love.</p><div class="fgck-bible-verse"><strong>Psalm 34:8</strong><span>“O taste and see that the LORD is good: blessed is the man that trusteth in him.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="18">
      <div class="fgck-inspiration-number">18</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Peace for Today</h3>
      <p>Take a breath, release the worry and focus on what you can do today with wisdom and faith.</p><div class="fgck-bible-verse"><strong>Romans 15:13</strong><span>“Now the God of hope fill you with all joy and peace in believing, that ye may abound in hope.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="19">
      <div class="fgck-inspiration-number">19</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>You Are Not Alone</h3>
      <p>You are part of a church family. There is strength, encouragement and fellowship around you.</p><div class="fgck-bible-verse"><strong>Psalm 121:1-2</strong><span>“I will lift up mine eyes unto the hills, from whence cometh my help. My help cometh from the LORD.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="20">
      <div class="fgck-inspiration-number">20</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>A Fresh Beginning</h3>
      <p>You can begin again. Let today be a fresh opportunity to learn, grow and move forward.</p><div class="fgck-bible-verse"><strong>Matthew 11:28</strong><span>“Come unto me, all ye that labour and are heavy laden, and I will give you rest.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="21">
      <div class="fgck-inspiration-number">21</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Love Generously</h3>
      <p>Give encouragement freely. A caring heart can bring hope to someone who needs it most.</p><div class="fgck-bible-verse"><strong>Psalm 55:22</strong><span>“Cast thy burden upon the LORD, and he shall sustain thee: he shall never suffer the righteous to be moved.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="22">
      <div class="fgck-inspiration-number">22</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Grow Through Every Season</h3>
      <p>Every season can teach you something. Keep learning, keep growing and keep trusting.</p><div class="fgck-bible-verse"><strong>Proverbs 16:3</strong><span>“Commit thy works unto the LORD, and thy thoughts shall be established.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="23">
      <div class="fgck-inspiration-number">23</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Let Faith Lead</h3>
      <p>When you are uncertain about the next step, let faith, wisdom and prayer guide your direction.</p><div class="fgck-bible-verse"><strong>Psalm 27:1</strong><span>“The LORD is my light and my salvation; whom shall I fear? the LORD is the strength of my life.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="24">
      <div class="fgck-inspiration-number">24</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Celebrate Small Wins</h3>
      <p>Do not overlook small victories. Every positive step is worth gratitude and encouragement.</p><div class="fgck-bible-verse"><strong>Hebrews 11:1</strong><span>“Now faith is the substance of things hoped for, the evidence of things not seen.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="25">
      <div class="fgck-inspiration-number">25</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Be Encouraged</h3>
      <p>You have come farther than you think. Pause, appreciate the journey and continue with confidence.</p><div class="fgck-bible-verse"><strong>Psalm 30:5</strong><span>“Weeping may endure for a night, but joy cometh in the morning.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="26">
      <div class="fgck-inspiration-number">26</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Lead by Example</h3>
      <p>The way you live can inspire others. Let your words, choices and service reflect compassion.</p><div class="fgck-bible-verse"><strong>Ephesians 3:20</strong><span>“Now unto him that is able to do exceeding abundantly above all that we ask or think.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="27">
      <div class="fgck-inspiration-number">27</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Tomorrow Can Be Better</h3>
      <p>Do not give up because today is difficult. Tomorrow can bring a new opportunity.</p><div class="fgck-bible-verse"><strong>Psalm 19:14</strong><span>“Let the words of my mouth, and the meditation of my heart, be acceptable in thy sight, O LORD.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-left fgck-random-motivation" data-motivation="28">
      <div class="fgck-inspiration-number">28</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Give Thanks</h3>
      <p>Gratitude changes perspective. Notice the good, appreciate the people around you and keep moving.</p><div class="fgck-bible-verse"><strong>Colossians 3:23</strong><span>“And whatsoever ye do, do it heartily, as to the Lord, and not unto men.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-center fgck-random-motivation" data-motivation="29">
      <div class="fgck-inspiration-number">29</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Serve With Purpose</h3>
      <p>Every act of service has value. Give your best, even when nobody is watching.</p><div class="fgck-bible-verse"><strong>Psalm 90:17</strong><span>“And let the beauty of the LORD our God be upon us: and establish thou the work of our hands upon us.”</span></div></article>
    <article class="fgck-inspiration-card fgck-card-right fgck-random-motivation" data-motivation="30">
      <div class="fgck-inspiration-number">30</div>
      <div class="fgck-inspiration-icon">✦</div>
      <h3>Keep Your Heart Open</h3>
      <p>Stay ready to learn, forgive, encourage and receive the good that each new day can bring.</p><div class="fgck-bible-verse"><strong>1 Thessalonians 5:16-18</strong><span>“Rejoice evermore. Pray without ceasing. In every thing give thanks: for this is the will of God in Christ Jesus concerning you.”</span></div></article>
  </div>
</section>





<!-- =========================================================
     MOBILE OFFCANVAS MENU
========================================================= -->

<div
    class="offcanvas offcanvas-end text-bg-dark"
    tabindex="-1"
    id="mobileMenu"
    aria-labelledby="mobileMenuLabel"
>


    <div
        class="offcanvas-header border-bottom border-secondary"
    >

        <h5
            class="offcanvas-title fw-bold"
            id="mobileMenuLabel"
        >

            <i
                class="bi bi-calendar-check text-warning me-2"
            ></i>

            FGCK Joyland

        </h5>


        <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="offcanvas"
            aria-label="Close"
        ></button>

    </div>


    <div
        class="offcanvas-body"
    >


        <!-- MOBILE BUTTONS -->

        <div
            class="d-grid gap-3"
        >


            <a
                href="/auth/login"
                class="btn btn-outline-light btn-lg"
            >

                <i
                    class="bi bi-box-arrow-in-right me-2"
                ></i>

                Sign In

            </a>


            <a
                href="/auth/register"
                class="btn btn-success btn-lg"
            >

                <i
                    class="bi bi-person-plus me-2"
                ></i>

                Create Member Account

            </a>

</div>


        <hr
            class="border-secondary my-4"
        >


        <!-- INFORMATION CARD -->

        <div
            class="mobile-info-card"
        >

            <div
                class="d-flex gap-3"
            >

                <div>

                    <i
                        class="bi bi-calendar2-check text-warning fs-4"
                    ></i>

                </div>


                <div>

                    <div
                        class="fw-bold text-white mb-1"
                    >

                        Pastor Appointments

                    </div>


                    <div
                        class="text-secondary small"
                    >

                        Book a private 30-minute appointment
                        through the FGCK Joyland member portal.

                    </div>

                </div>

            </div>

        </div>


        <!-- SECOND INFORMATION CARD -->

        <div
            class="mobile-info-card mt-3"
        >

            <div
                class="d-flex gap-3"
            >

                <div>

                    <i
                        class="bi bi-shield-check text-info fs-4"
                    ></i>

                </div>


                <div>

                    <div
                        class="fw-bold text-white mb-1"
                    >

                        Private & Secure

                    </div>


                    <div
                        class="text-secondary small"
                    >

                        Your appointment details are protected
                        within your member account.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main>


    <!-- =====================================================
         HERO SECTION
    ====================================================== -->




</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer
    class="landing-footer"
>

    <div
        class="container"
    >

        © 2026 FGCK Joyland. All rights reserved.

        <br>

        Powered by

        <strong>
            @MylesHubTechnologies
        </strong>

    </div>

</footer>



<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  const params = new URLSearchParams(window.location.search);
  if(params.get('logged_out') === '1'){
    Swal.fire({icon:'success',title:'Signed Out Successfully',text:'You have been safely signed out of FGCK Joyland.',confirmButtonText:'Continue',confirmButtonColor:'#6d28d9',timer:2800,timerProgressBar:true});
    window.history.replaceState({},document.title,window.location.pathname);
  }
})();
</script>

<!-- =========================================================
     BOOTSTRAP JAVASCRIPT
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<script id="fgck-modern-home-js">
document.addEventListener('DOMContentLoaded',function(){
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const cards = Array.from(document.querySelectorAll('.fgck-random-motivation'));
  if (!cards.length) return;

  function shuffle(items) {
    const copy = items.slice();
    for (let i = copy.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [copy[i], copy[j]] = [copy[j], copy[i]];
    }
    return copy;
  }

  function showFreshMotivation() {
    const selected = shuffle(cards).slice(0, 3);
    cards.forEach(card => {
      card.classList.remove('fgck-random-visible');
      card.setAttribute('aria-hidden', 'true');
    });
    selected.forEach((card, index) => {
      card.classList.add('fgck-random-visible');
      card.setAttribute('aria-hidden', 'false');
      card.style.animationDelay = (index * 90) + 'ms';
    });
  }

  showFreshMotivation();

  // Keep the three motivation panels fresh while the visitor remains on the page.
  setInterval(showFreshMotivation, 12000);
});
</script>

</body>
</html>