<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../config/db.php'; 
require_once '../includes/auth.php'; 
require_login(); 

// Olay ID'sini al ve doğrula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resolve_submit') {
    $incident_id = filter_input(INPUT_POST, 'incident_id', FILTER_VALIDATE_INT);
    $resolution_details = trim($_POST['resolution_details'] ?? '');
    $new_status = "Çözüldü"; 

    if (!$incident_id) {
        $_SESSION['error_message'] = "Geçersiz olay ID'si.";
        header("Location: incidents.php"); 
        exit();
    }

    // Çözüm detaylarını kontrol et
    if (empty($resolution_details) || strlen($resolution_details) < 10) {
        $_SESSION['error_message'] = "Çözüm detayları en az 10 karakter olmalıdır.";
        header("Location: resolve_incident_page.php?id=" . $incident_id);
        exit();
    }

    try {
        // Olayı güncelle
        $stmt = mysqli_prepare($connect, "UPDATE incidents SET status = ?, resolution_details = ?, resolved_at = NOW(), updated_at = NOW() WHERE id = ?");
        if (!$stmt) {
            throw new Exception("SQL hazırlama hatası: " . mysqli_error($connect));
        }
        mysqli_stmt_bind_param($stmt, "ssi", $new_status, $resolution_details, $incident_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Olay güncellenirken hata: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } catch (Exception $e) {
        error_log("Error resolving incident ID {$incident_id}: " . $e->getMessage());
        $_SESSION['error_message'] = "Olay çözülürken bir veritabanı hatası oluştu. Lütfen sistem yöneticisine başvurun.";
    }

    header("Location: incidents.php?id=" . $incident_id);
    exit();

}
//  Olayı incelemeye alma işlemi
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'review' && isset($_GET['id'])) {
    $incident_id_review = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $new_status_review = "İnceleniyor";

    if ($incident_id_review) {
        try {
            $stmt_review = mysqli_prepare($connect, "UPDATE incidents SET status = ?, updated_at = NOW() WHERE id = ?");
            if (!$stmt_review) {
                throw new Exception("SQL hazırlama hatası (review): " . mysqli_error($connect));
            }
            mysqli_stmt_bind_param($stmt_review, "si", $new_status_review, $incident_id_review);
            if (mysqli_stmt_execute($stmt_review)) {
                $_SESSION['success_message'] = "Olay #" . htmlspecialchars($incident_id_review) . " incelemeye alındı.";
            } else {
                throw new Exception("Olay durumu güncellenirken hata (review): " . mysqli_stmt_error($stmt_review));
            }
            mysqli_stmt_close($stmt_review);
        } catch (Exception $e) {
            error_log("Error updating incident status to review (ID {$incident_id_review}): " . $e->getMessage());
            $_SESSION['error_message'] = "Olay durumu güncellenirken bir hata oluştu.";
        }
        header("Location: incidents.php?id=" . $incident_id_review);
        exit();
    } else {
        $_SESSION['error_message'] = "Geçersiz olay ID'si (review).";
        header("Location: incidents.php");
        exit();
    }
} else {
    if (!isset($_SESSION['error_message']) && !isset($_SESSION['success_message'])) {
      $_SESSION['error_message'] = "Geçersiz istek yapıldı.";
    }
    header("Location: incidents.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olay Yönetim Sistemi</title>

    <!-- Bootstrap 5 CSS hazır kütüphanesi -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons hazır kütüphanesi -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="../assets/css/review.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>