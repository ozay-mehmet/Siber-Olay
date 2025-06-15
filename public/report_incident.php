<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once '../config/db.php'; 
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = '';
$user_id = $_SESSION['user_id'];
$form_data = [
    'title' => '',
    'description' => '',
    'incident_type' => ''
];

// eğer GET isteği ile silme işlemi yapılıyorsa 
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $incident_id = intval($_GET['id']);
    
    try {
        $stmt = mysqli_prepare($connect, "DELETE FROM incidents WHERE id = ? AND reporter_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $incident_id, $user_id);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $success = "Olay başarıyla silindi.";
        } else {
            $errors[] = "Olay bulunamadı veya silme yetkiniz yok.";
        }
        mysqli_stmt_close($stmt);
    } catch (Exception $e) { 
        error_log("Silme hatası: " . $e->getMessage());
        $errors[] = "Olay silinirken bir hata oluştu.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data['title'] = trim($_POST['title'] ?? '');
    $form_data['description'] = trim($_POST['description'] ?? '');
    $form_data['incident_type'] = trim($_POST['incident_type'] ?? '');
    
    if (empty($form_data['title'])) {
        $errors[] = "Başlık boş bırakılamaz.";
    }
    
    if (empty($form_data['description'])) {
        $errors[] = "Açıklama boş bırakılamaz.";
    }
    
    if (empty($form_data['incident_type'])) {
        $errors[] = "Olay türü seçmelisiniz.";
    }
    
    if (empty($errors)) {
        try {

            // olayın veritabanına eklenmesi
            $stmt = mysqli_prepare($connect, "INSERT INTO incidents 
                (reporter_id, title, description, incident_type, status) 
                VALUES (?, ?, ?, ?, 'Açık')");

            $title_clean = $form_data['title'];
            $description_clean = $form_data['description'];
            $incident_type_clean = $form_data['incident_type'];

            mysqli_stmt_bind_param($stmt, "isss", 
                $user_id, 
                $title_clean,
                $description_clean,
                $incident_type_clean
            );
            mysqli_stmt_execute($stmt);
            
            $success = "Olay başarıyla bildirildi!";
            
            $form_data = [
                'title' => '',
                'description' => '',
                'incident_type' => ''
            ];
            mysqli_stmt_close($stmt);
            
        } catch (Exception $e) { 
            error_log("Olay bildirme hatası: " . $e->getMessage());
            $errors[] = "Olay bildirilirken bir hata oluştu. Lütfen tekrar deneyin.";
        }
    }
}

$incidents = [];
try {
    // kullanıcının bildirdiği olayların alınması
    $stmt = mysqli_prepare($connect, "SELECT id, title, description, incident_type, status, created_at 
                          FROM incidents 
                          WHERE reporter_id = ? 
                          ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $incidents = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    error_log("Olayları getirme hatası: " . $e->getMessage());
    $errors[] = "Olaylar yüklenirken bir hata oluştu.";
}

include_once '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/report.css">

<div class="page-container">
    <div class="form-card">
        <h2>Olay Bildir</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert success">
                <p><?= htmlspecialchars($success) ?></p>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="report_incident.php">
            <div class="form-group">
                <label for="title">Başlık:</label>
                <input type="text" id="title" name="title" 
                       value="<?= htmlspecialchars($form_data['title']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Açıklama:</label>
                <textarea id="description" name="description" rows="5" required><?= htmlspecialchars($form_data['description']) ?></textarea>
            </div>
            
            <!-- Olay Türü Seçimi -->
            <div class="form-group">
                <label for="incident_type">Olay Türü:</label>
                <select id="incident_type" name="incident_type" required>
                    <option value="">Seçiniz...</option>
                    <option value="Phishing" <?= ($form_data['incident_type'] ?? '') == 'Phishing' ? 'selected' : '' ?>>Phishing</option>
                    <option value="Malware" <?= ($form_data['incident_type'] ?? '') == 'Malware' ? 'selected' : '' ?>>Malware</option>
                    <option value="DDoS" <?= ($form_data['incident_type'] ?? '') == 'DDoS' ? 'selected' : '' ?>>DDoS</option>
                    <option value="Veri Sızıntısı" <?= ($form_data['incident_type'] ?? '') == 'Veri Sızıntısı' ? 'selected' : '' ?>>Veri Sızıntısı</option>
                    <option value="Yetkisiz Erişim" <?= ($form_data['incident_type'] ?? '') == 'Yetkisiz Erişim' ? 'selected' : '' ?>>Yetkisiz Erişim</option>
                    <option value="Diğer" <?= ($form_data['incident_type'] ?? '') == 'Diğer' ? 'selected' : '' ?>>Diğer</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Bildir</button>
        </form>
    </div>
    
    <div class="incidents-list-card">
        <h2>Bildirdiğim Olaylar</h2>
        
        <?php if (empty($incidents)): ?>
            <p>Henüz bildirdiğiniz bir olay bulunmamaktadır.</p>
        <?php else: ?>
            <table class="incident-table">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Tür</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incidents as $incident): ?>
                        <tr>
                            <td><?= htmlspecialchars($incident['title']) ?></td>
                            <td><?= htmlspecialchars($incident['incident_type']) ?></td>
                            <td><?= htmlspecialchars($incident['status']) ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($incident['created_at']))) ?></td>
                            <td>
                                <form method="GET" action="report_incident.php" onsubmit="return confirm('Bu olayı silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($incident['id']) ?>">
                                    <button type="submit" class="btn btn-delete">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/js/report.js"></script>

<?php 
include_once '../includes/footer.php';
?>