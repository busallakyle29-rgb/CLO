<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (auth_is_logged_in()) {
    redirect_to('dashboard.php');
}

// redirect_to('login.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="City Legal Office Document Tracking System"
    >

    <meta
        name="theme-color"
        content="#003049"
    >

    <!-- =====================================================
         PAGE TITLE
    ====================================================== -->

    <title>
        City Legal Office | Document Tracking System
    </title>

    <!-- =====================================================
         FAVICON / TAB LOGO
    ====================================================== -->
    <link
        rel="icon"
        type="image/png"
        href="assets/logo/CLO_LOGO.png"
    >

    <!-- =====================================================
         BOOTSTRAP 5
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
         EXTERNAL CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="assets/css/features/landing/opening.css?v=20260903b"
    >
    <link
        rel="stylesheet"
        href="assets/css/features/global/scrollbar.css?v=20261006b"
    >

</head>

<body>

    <!-- =====================================================
         LOADING SCREEN
    ====================================================== -->

    <div
        id="loadingScreen"
        aria-label="Loading"
    >

        <div class="loader-content">

            <div class="loader-icon"></div>

            <div class="loader-text">

                Loading Document Tracking System...

            </div>

        </div>

    </div>

    <!-- =====================================================
         OPENING PAGE
    ====================================================== -->

    <main class="opening-page">

        <!-- =================================================
             BACKGROUND DECORATIONS
        ================================================== -->

        <div
            class="circle circle-1"
            aria-hidden="true"
        ></div>

        <div
            class="circle circle-2"
            aria-hidden="true"
        ></div>

        <div
            class="circle circle-3"
            aria-hidden="true"
        ></div>

        <!-- =================================================
             MAIN CONTENT
        ================================================== -->

        <div class="content">

            <!-- =================================================
                 OFFICE LOGO
            ================================================== -->

            <div class="logo-container">

                <img
                    src="assets/logo/CLO_LOGO.png"
                    alt="City Legal Office Logo"
                    class="clo-logo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                >

                <!-- Fallback Icon -->

                <i
                    class="bi bi-bank2 logo-fallback"
                    aria-hidden="true"
                ></i>

            </div>

            <!-- =================================================
                 OFFICE NAME
            ================================================== -->

            <div class="office-name">

                City Legal Office - Panabo

            </div>

            <!-- =================================================
                 SYSTEM TITLE
            ================================================== -->

            <h1 class="system-title">

                Document

                <span>Tracking</span>

                <br>

                System

            </h1>

            <!-- =================================================
                 DESCRIPTION
            ================================================== -->

            <p class="subtitle">

                A centralized system for receiving, tracking,
                routing, monitoring, and managing legal office
                documents efficiently and securely.

            </p>

            <!-- =================================================
                 BUTTONS
            ================================================== -->

            <div class="button-container">

                <!-- =================================================
                     LOGIN / ENTRY
                ================================================== -->

                <a
                    href="login.php"
                    class="enter-btn signin-btn"
                    id="signInButton"
                >

                    <i
                        class="bi bi-box-arrow-in-right"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Login
                    </span>

                </a>

                <!-- =================================================
                     TRACK DOCUMENT
                ================================================== -->

                <!-- <a
                    href="document_tracking.php"
                    class="enter-btn secondary-btn"
                    id="trackDocumentButton"
                >

                    <i
                        class="bi bi-search"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Track Document
                    </span> -->

                </a>

            </div>

            <!-- =================================================
                 FEATURES
            ================================================== -->

            <div class="features">

                <!-- Document Management -->

                <div class="feature">

                    <i
                        class="bi bi-file-earmark-text"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Document Management
                    </span>

                </div>

                <!-- Document Routing -->

                <div class="feature">

                    <i
                        class="bi bi-signpost-2"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Document Routing
                    </span>

                </div>

                <!-- Tracking History -->

                <div class="feature">

                    <i
                        class="bi bi-clock-history"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Tracking History
                    </span>

                </div>

                <!-- Security -->

                <div class="feature">

                    <i
                        class="bi bi-shield-check"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Secure Records
                    </span>

                </div>

            </div>

            <!-- =================================================
                 ADDITIONAL INFORMATION
            ================================================== -->

            <div class="system-info">

                <div class="info-item">

                    <i class="bi bi-check-circle-fill"></i>

                    <span>
                        Fast
                    </span>

                </div>

                <div class="info-item">

                    <i class="bi bi-check-circle-fill"></i>

                    <span>
                        Secure
                    </span>

                </div>

                <div class="info-item">

                    <i class="bi bi-check-circle-fill"></i>

                    <span>
                        Organized
                    </span>

                </div>

                <div class="info-item">

                    <i class="bi bi-check-circle-fill"></i>

                    <span>
                        Efficient
                    </span>

                </div>

            </div>

        </div>

        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <footer class="footer">

            <div>

                © 2026 City Legal Office

                <span class="footer-divider">
                    •
                </span>

                Document Tracking System

            </div>

            <div class="footer-subtitle">

                Official Document Management and Tracking System

            </div>

        </footer>

    </main>

    <!-- =====================================================
         BOOTSTRAP JAVASCRIPT
    ====================================================== -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>

    <!-- =====================================================
         EXTERNAL JAVASCRIPT
    ====================================================== -->

    <script
        src="assets/js/features/landing/opening.js">
    </script>

</body>

</html>
