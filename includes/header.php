<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siber Güvenlik Olay Yönetimi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <nav class="navbar">
                <div class="logo">
                    <a href="dashboard.php">
                        <i class="fas fa-shield-alt logo-icon"></i>
                        <span>SiberOlay</span>
                    </a>
                </div>

                <button class="hamburger" aria-label="Menüyü Aç/Kapat">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </button>

                <ul class="nav-links">
                    <?php
                    // sayfa link ögeleri
                    $navItems = [
                        'dashboard' => ['icon' => 'fa-tachometer-alt', 'label' => 'Kontrol Paneli'],
                        'report_incident' => ['icon' => 'fa-bell', 'label' => 'Olay Bildir'],
                        'incidents' => ['icon' => 'fa-list', 'label' => 'Olay Listesi'],
                    ];

                    foreach ($navItems as $page => $item):
                        $active = ($currentPage === $page) ? 'class="active"' : '';
                    ?>
                        <li><a href="../public/<?= $page ?>.php" <?= $active ?>>
                            <i class="fas <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
                        </a></li>
                    <?php endforeach; ?>

                    <!-- Mobil uyum için menü -->
                    <li class="mobile-profile">
                        <button class="mobile-dropdown-toggle">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                            <i class="fas fa-chevron-down mobile-dropdown-arrow"></i>
                        </button>
                        <div class="mobile-dropdown-menu">
                            <a href="../profile/profile.php"><i class="fas fa-user"></i> Profilim</a>
                            <div class="dropdown-divider"></div>
                            <a href="../public/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                        </div>
                    </li>
                </ul>

                <!-- Masaüstü için profil menüsü -->
                <div class="nav-user">
                    <div class="dropdown desktop-dropdown">
                        <button class="dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="../profile/profile.php"><i class="fas fa-user"></i> Profilim</a>
                            <div class="dropdown-divider"></div>
                            <a href="../public/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <main class="content">
