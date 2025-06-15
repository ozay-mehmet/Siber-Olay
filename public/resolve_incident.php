<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_login();

$incident_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$incident_id) {
    $_SESSION['error_message'] = "Geçersiz olay ID'si.";
    header("Location: incidents.php");
    exit();
}

$incident_title = "Bilinmeyen Olay";
try {
    $stmt = mysqli_prepare($connect, "SELECT title FROM incidents WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $incident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $incident_title = $row['title'];
    } else {
        $_SESSION['error_message'] = "Olay bulunamadı.";
        header("Location: incidents.php");
        exit();
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    error_log("Error fetching incident #{$incident_id}: " . $e->getMessage());
    $_SESSION['error_message'] = "Olay bilgileri alınırken hata oluştu.";
    header("Location: incidents.php");
    exit();
}

include_once '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olay Çözümü - #<?= htmlspecialchars($incident_id) ?></title>
    <link rel="stylesheet" href="../assets/css/resolve.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <h2>Olayı Çöz: #<?= htmlspecialchars($incident_id) ?> - <?= htmlspecialchars($incident_title) ?></h2>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Olay Çözüm Formu -->
        <form action="review_incident.php" method="POST" id="resolveForm">
            <input type="hidden" name="incident_id" value="<?= htmlspecialchars($incident_id) ?>">
            <input type="hidden" name="action" value="resolve_submit">

            <div class="form-group">
                <label for="resolution_details">Çözüm Detayları:</label>
                <textarea id="resolution_details" name="resolution_details" rows="5" required
                          placeholder="Olayın nasıl çözüldüğünü detaylı şekilde açıklayın..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Olayı Çöz
                </button>
                <a href="incidents.php?id=<?= htmlspecialchars($incident_id) ?>" class="btn btn-cancel">
                    <i class="fas fa-times-circle"></i> İptal
                </a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('resolveForm').addEventListener('submit', function(e) {
            const textarea = document.getElementById('resolution_details');
            if (textarea.value.trim().length < 10) {
                e.preventDefault();
                alert('Lütfen en az 10 karakterlik bir çözüm açıklaması girin.');
                textarea.focus();
            }
        });
    </script>
</body>
</html>
<?php
include_once '../includes/footer.php';
?>