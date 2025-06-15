<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../config/db.php'; 
require_once '../includes/auth.php';
require_login();

include_once '../includes/header.php'; 
?>
<link rel="stylesheet" href="../assets/css/incidents.css">
<?php

// parametre kontrolü
$allowed_pages = ['dashboard', 'report_incident', 'incidents'];
$page = $_GET['page'] ?? 'incidents';

if (!in_array($page, $allowed_pages)) {
    $page = 'incidents';
}

// Kullanıcı giriş kontrolü
$protected_pages = ['dashboard', 'report_incident', 'incidents'];
if (in_array($page, $protected_pages) && !isset($_SESSION['user_id'])) { 
    require_login();
}

function renderIncidentsList($connect) { 
    ?>
    <div class="incidents-list-container">
        <div class="incidents-header">
            <h2 class="section-title">Olay Listesi</h2>
            <a href="report_incident.php" class="btn btn-primary">
                <i class="fa fa-plus-circle"></i> Yeni Olay Bildir
            </a>
        </div>
        
        <?php
        try {
           $sql = "SELECT id, title, incident_type, status, created_at FROM incidents ORDER BY created_at DESC";
           $result = mysqli_query($connect, $sql);
            
           // Olayların listelenmesi
            if ($result && mysqli_num_rows($result) > 0) {
                $incidents = mysqli_fetch_all($result, MYSQLI_ASSOC);
                echo '<div class="table-container">';
                echo '<table class="incidents-table">';
                echo '<thead><tr>
                        <th>ID</th>
                        <th>Başlık</th>
                        <th>Tür</th>
                        <th>Durum</th>
                        <th>Zaman</th>
                        <th>İşlemler</th>
                      </tr></thead>';
                echo '<tbody>';
                
                foreach ($incidents as $incident) {
                    $statusClass = strtolower(str_replace([' ', 'İ', 'ı', 'Ö', 'ö', 'Ü', 'ü', 'Ş', 'ş', 'Ç', 'ç', 'Ğ', 'ğ'], ['-', 'i', 'i', 'o', 'o', 'u', 'u', 's', 's', 'c', 'c', 'g', 'g'], $incident['status']));
                    echo '<tr class="incident-row">';
                    echo '<td>' . htmlspecialchars($incident['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($incident['title']) . '</td>';
                    echo '<td>' . htmlspecialchars($incident['incident_type']) . '</td>';
                    echo '<td><span class="status-badge ' . htmlspecialchars($statusClass) . '">' . htmlspecialchars($incident['status']) . '</span></td>';
                    echo '<td>' . htmlspecialchars(date('d.m.Y H:i', strtotime($incident['created_at']))) . '</td>';
                    echo '<td><a href="incidents.php?id=' . $incident['id'] . '" class="btn btn-view"><i class="fa fa-eye"></i> Görüntüle</a></td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table></div>';
            } 
            // herhangi bir olay yoksa
            else if ($result && mysqli_num_rows($result) == 0) {
                echo '<div class="empty-state">
                    <div class="empty-icon"><i class="fa fa-clipboard-list"></i></div>
                    <h3>Henüz raporlanmış bir olay yok</h3>
                    <p>Yeni bir olay bildirmek için butonu kullanabilirsiniz.</p>
                </div>';
            } else {
                error_log("Error fetching incidents list: " . mysqli_error($connect)); 
                echo '<div class="alert alert-error">Olayları çekerken bir hata oluştu. Lütfen daha sonra tekrar deneyin.</div>';
            }
        } catch (Exception $e) { 
            error_log("Error fetching incidents list: " . $e->getMessage()); 
            echo '<div class="alert alert-error">Olayları çekerken bir hata oluştu. Lütfen daha sonra tekrar deneyin.</div>';
        }
        ?>
    </div>
    <?php
}

function renderIncidentDetails($connect) { 
    $incident_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $error = null; 
    $incident = null; 
    
    if (!$incident_id) {
        $error = "Geçersiz olay ID'si";
    } else {
        try {
            // Olay detaylarını çekme
            $sql = "
                SELECT i.*, u.username as reporter_username, a.username as assigned_username
                FROM incidents i
                JOIN users u ON i.reporter_id = u.id 
                LEFT JOIN users a ON i.assigned_to = a.id
                WHERE i.id = ?
            ";
            $stmt = mysqli_prepare($connect, $sql);
            mysqli_stmt_bind_param($stmt, "i", $incident_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $incident = mysqli_fetch_assoc($result);
            
            if (!$incident) {
                $error = "Olay bulunamadı";
            }
            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            error_log("Error fetching incident details for ID {$incident_id}: " . $e->getMessage());
            $error = "Olay detayları alınırken bir veritabanı hatası oluştu. Lütfen sistem yöneticisine başvurun.";
        }
    }
    ?>
    <div class="incident-detail-container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <a href="incidents.php" class="btn btn-back">Listeye Dön</a>
        <?php elseif ($incident):?>
            <div class="incident-header">
                <h2><?= htmlspecialchars($incident['title']) ?></h2>
                <?php
                    $statusClassDetail = strtolower(str_replace([' ', 'İ', 'ı', 'Ö', 'ö', 'Ü', 'ü', 'Ş', 'ş', 'Ç', 'ç', 'Ğ', 'ğ'], ['-', 'i', 'i', 'o', 'o', 'u', 'u', 's', 's', 'c', 'c', 'g', 'g'], $incident['status']));
                ?>
                <span class="status-badge <?= htmlspecialchars($statusClassDetail) ?>">
                    <?= htmlspecialchars($incident['status']) ?>
                </span>
            </div>
            
            <!-- Olay Bilgileri -->
            <div class="incident-meta">
                <div class="meta-item">
                    <span class="meta-label">ID:</span>
                    <span class="meta-value">#<?= htmlspecialchars($incident['id']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Raporlayan:</span>
                    <span class="meta-value"><?= htmlspecialchars($incident['reporter_username']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Tür:</span>
                    <span class="meta-value"><?= htmlspecialchars($incident['incident_type']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Tarih:</span>
                    <span class="meta-value"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($incident['created_at']))) ?></span>
                </div>
                
                <?php if (!empty($incident['assigned_username'])): ?>
                <div class="meta-item">
                    <span class="meta-label">Atanan:</span>
                    <span class="meta-value"><?= htmlspecialchars($incident['assigned_username']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="incident-content">
                <div class="content-section">
                    <h3>Açıklama</h3>
                    <p><?= nl2br(htmlspecialchars($incident['description'])) ?></p>
                </div>
                
                <?php if (!empty($incident['resolution_details'])): ?>
                <div class="content-section">
                    <h3>Çözüm Detayları</h3>
                    <p><?= nl2br(htmlspecialchars($incident['resolution_details'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="incident-actions">
                <?php if ($incident['status'] !== 'Çözüldü' && $incident['status'] !== 'Kapatıldı'):?>
                    <a href="review_incident.php?id=<?= $incident['id'] ?>" class="btn btn-info">
                        <i class="fa fa-search"></i> İncelemeye Al
                    </a>
                    <a href="resolve_incident.php?id=<?= $incident['id'] ?>" class="btn btn-success">
                        <i class="fa fa-check-circle"></i> Çöz
                    </a>
                <?php endif; ?>
                <a href="incidents.php" class="btn btn-back">
                    <i class="fa fa-arrow-left"></i> Listeye Dön
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-error">Olay bilgileri yüklenemedi.</div>
            <a href="incidents.php" class="btn btn-back">Listeye Dön</a>
        <?php endif; ?>
    </div>
    <?php
}

if (isset($_GET['id'])) {
    renderIncidentDetails($connect);
} else {
    renderIncidentsList($connect);
}

include_once '../includes/footer.php';
?>