<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once '../config/db.php';
require_once '../includes/auth.php';

require_login();

$currentPage = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING) ?? 'dashboard';

include_once '../includes/header.php';

function get_incident_icon($type) {
    $icons = [
        'Phishing' => 'fish',
        'Malware' => 'bug',
        'DDoS' => 'network-wired',
        'Veri İhlali' => 'database',
        'Yetki Aşımı' => 'user-shield',
        'Diğer' => 'exclamation-circle'
    ];
    return $icons[$type] ?? 'exclamation-circle';
}

$open_incidents = 0;
$in_progress = 0;
$resolved = 0;
$latest_incidents = [];
$db_error = null;

try {
    if (!$connect) {
        throw new Exception("Database connection failed");
    }
    
    $connect->set_charset("utf8mb4");
    
    $status_open = 'Açık';
    $status_in_progress = 'İnceleniyor';
    $status_resolved = 'Çözüldü';
    
    // Açık olay sayısı
    $query = "SELECT COUNT(*) as count FROM incidents WHERE status = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("s", $status_open);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $open_incidents = $row['count'] ?? 0;
    $stmt->close();
    
    // İnceleniyor durumundaki olaylar
    $query = "SELECT COUNT(*) as count FROM incidents WHERE status = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("s", $status_in_progress);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $in_progress = $row['count'] ?? 0;
    $stmt->close();
    
    // Son 30 günde çözülen olaylar
    $query = "SELECT COUNT(*) as count FROM incidents WHERE status = ? AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("s", $status_resolved);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $resolved = $row['count'] ?? 0;
    $stmt->close();
    
    $query = "SELECT id, title, incident_type, status, created_at FROM incidents ORDER BY created_at DESC LIMIT 5";
    $stmt = $connect->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest_incidents = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
} catch (Exception $e) {
    $db_error = $e->getMessage();
    error_log("Dashboard error: " . $db_error);
    $_SESSION['error_message'] = "Veri alınırken bir hata oluştu. Lütfen sistem yöneticinizle iletişime geçin.";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com; style-src 'self' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:;">
    <title>Siber Güvenlik Olay Yönetimi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1 class="welcome-title">
                Merhaba, <span class="username">
                <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : 'Kullanıcı' ?>
                </span>!
            </h1>
        </div>
        <div class="quick-actions">
            <a href="report_incident.php" class="quick-action-btn">
                <i class="fas fa-plus-circle"></i> Yeni Olay Bildir
            </a>
            <a href="incidents.php" class="quick-action-btn">
                <i class="fas fa-list-ul"></i> Tüm Olaylar
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?>
            <?php if ($db_error): ?>
                <div class="error-details">Hata Detayı: <?= htmlspecialchars($db_error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="dashboard-content">
        <section class="summary-section">
            <h2 class="section-title"><i class="fas fa-chart-pie"></i> Sistem Özeti</h2>
            <div class="summary-cards">
                <div class="summary-card card-accent-1">
                    <div class="card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-content">
                        <h3>Açık Olaylar</h3>
                        <p class="count"><?= htmlspecialchars($open_incidents, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="card-description">Çözüm bekleyen olaylar</p>
                    </div>
                </div>
                <div class="summary-card card-accent-2">
                    <div class="card-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="card-content">
                        <h3>İnceleniyor</h3> 
                        <p class="count"><?= htmlspecialchars($in_progress, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="card-description">Aktif inceleme altındaki olaylar</p>
                    </div>
                </div>
                <div class="summary-card card-accent-3">
                    <div class="card-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3>Çözülenler</h3>
                        <p class="count"><?= htmlspecialchars($resolved, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="card-description">Son 30 günde çözülen olaylar</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="recent-incidents-section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-history"></i> Son Olaylar</h2>
                <a href="incidents.php" class="view-all-link">Tümünü Görüntüle <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <?php if (!empty($latest_incidents)): ?>
                <div class="incidents-table">
                    <div class="table-header">
                        <div class="header-item">Olay Başlığı</div>
                        <div class="header-item">Tür</div>
                        <div class="header-item">Durum</div>
                        <div class="header-item">Tarih</div>
                        <div class="header-item">İşlem</div>
                    </div>
                    <?php foreach ($latest_incidents as $incident): ?>
                        <?php
                        $statusClass = mb_strtolower($incident['status'], 'UTF-8');
                        $statusClass = str_replace(
                            ['ı', 'ö', 'ü', 'ş', 'ç', 'ğ', ' '],
                            ['i', 'o', 'u', 's', 'c', 'g', '-'],
                            $statusClass
                        );
                        ?>
                        <div class="table-row">
                            <div class="table-cell incident-title">
                                <i class="fas fa-<?= get_incident_icon($incident['incident_type']) ?>"></i>
                                <?= htmlspecialchars($incident['title'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="table-cell incident-type">
                                <?= htmlspecialchars($incident['incident_type'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="table-cell incident-status">
                                <span class="status-badge status-<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($incident['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="table-cell incident-date">
                                <?= htmlspecialchars(date('d.m.Y H:i', strtotime($incident['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="table-cell incident-action">
                                <a href="incidents.php?id=<?= htmlspecialchars($incident['id'], ENT_QUOTES, 'UTF-8') ?>" class="view-btn">
                                    <i class="fas fa-eye"></i> Detay
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list empty-icon"></i>
                    <h3>Henüz raporlanmış bir olay bulunmamaktadır</h3>
                    <p>Yeni bir olay bildirmek için "Olay Bildir" butonunu kullanabilirsiniz</p>
                    <a href="report_incident.php" class="primary-btn">
                        <i class="fas fa-plus"></i> Olay Bildir
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<script src="../assets/js/dashboard.js" defer></script>

<?php
include_once '../includes/footer.php';
?>