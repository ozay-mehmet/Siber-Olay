<?php
declare(strict_types=1); // güvenlik için tip bildiriminin etkinleştirilmesi
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../config/db.php'; 
require_once '../includes/auth.php';
require_login();

$csrf_token_lifetime = 3600;

if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time'] > $csrf_token_lifetime / 2)) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// Kullanıcı giriş kontrolü
$userId = (int)$_SESSION['user_id'];
$user = null;
$stmtUser = mysqli_prepare($connect, "SELECT id, username, email, full_name, phone, department, bio, role FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmtUser, "i", $userId);
mysqli_stmt_execute($stmtUser);
$resultUser = mysqli_stmt_get_result($stmtUser);
$user = mysqli_fetch_assoc($resultUser);
mysqli_stmt_close($stmtUser);

if (!$user) {
    $_SESSION['message'] = "Kullanıcı bilgileri alınamadı.";
    header("Location: ../public/logout.php"); 
    exit;
}

// erişim kontrolü ==> eğer kullanıcı admin değilse ve düzenlemeye çalışıyorsa 
if (!in_array($user['role'], ['user', 'admin'])) {
    http_response_code(403);
    $_SESSION['message'] = "Bu sayfaya erişim yetkiniz bulunmamaktadır.";
    header("Location: ../public/dashboard.php");
    exit;
}

$events = [];
$stmtEvents = mysqli_prepare($connect, "SELECT id, title, description, created_at FROM incidents WHERE reporter_id = ? ORDER BY created_at DESC LIMIT 5");
mysqli_stmt_bind_param($stmtEvents, "i", $userId);
mysqli_stmt_execute($stmtEvents);
$resultEvents = mysqli_stmt_get_result($stmtEvents);
$events = mysqli_fetch_all($resultEvents, MYSQLI_ASSOC);
mysqli_stmt_close($stmtEvents);

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// CSRF koruması 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task']) && $_POST['task'] === 'info') {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token'], $_SESSION['csrf_token_time'])) {
        $_SESSION['message'] = "Güvenlik hatası: Token bilgileri eksik. Lütfen tekrar deneyin.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        header("Location: profile.php");
        exit;
    }

    $token_age = time() - $_SESSION['csrf_token_time'];

    //  CSRF token doğrulaması
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']) || $token_age > $csrf_token_lifetime) {
        $_SESSION['message'] = "Güvenlik hatası veya oturum zaman aşımı. Lütfen tekrar deneyin.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        header("Location: profile.php");
        exit;
    }

    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '');
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING) ?? '');
    $dept = trim(filter_input(INPUT_POST, 'dept', FILTER_SANITIZE_STRING) ?? '');
    $bio = trim(filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING) ?? '');
    $validation_message = ''; 

    // Giriş verilerinin doğrulanması ==> bazı önemli alanların ekstra kontrol edilmesi
    if (empty($name)) {
        $validation_message = "Ad Soyad boş olamaz.";
    } elseif (mb_strlen($name, 'UTF-8') > 100) {
        $validation_message = "Ad Soyad çok uzun (en fazla 100 karakter).";
    } elseif (mb_strlen($phone, 'UTF-8') > 20) {
        $validation_message = "Telefon numarası çok uzun (en fazla 20 karakter).";
    } elseif (mb_strlen($dept, 'UTF-8') > 50) {
        $validation_message = "Departman adı çok uzun (en fazla 50 karakter).";
    } elseif (mb_strlen($bio, 'UTF-8') > 500) {
        $validation_message = "Hakkımda yazısı çok uzun (en fazla 500 karakter).";
    } else {
        try{
            // Profil bilgilerini güncelleme
            $stmt = mysqli_prepare($connect, "UPDATE users SET full_name=?, phone=?, department=?, bio=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $phone, $dept, $bio, $userId);
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $_SESSION['message'] = "Profil başarıyla güncellendi.";
                    $user['full_name'] = $name; 
                    $user['phone'] = $phone;
                    $user['department'] = $dept;
                    $user['bio'] = $bio;
                } else {
                     $_SESSION['message'] = "Profil bilgileri güncellendi (veya değişiklik yapılmadı).";
                }
            } else {
                $_SESSION['message'] = "Profil bilgileri güncellenirken bir hata oluştu: " . mysqli_stmt_error($stmt);
                error_log("MySQLi error updating profile for user ID {$userId}: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } catch (mysqli_sql_exception $e) {
            $_SESSION['message'] = "Profil bilgileri güncellenirken bir veritabanı hatası oluştu. Lütfen sistem yöneticisine başvurun.";
            error_log("MySQLi Exception updating profile for user ID {$userId}: " . $e->getMessage());
        }
        // CSRF token ile zaman güncellemesi
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        header("Location: profile.php");
        exit;
    }
    if ($validation_message && !headers_sent()) { 
        $message = $validation_message;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
        $_SESSION['csrf_token_time'] = time();
    }
}

// Olay silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task']) && $_POST['task'] === 'delete_event') {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token'], $_SESSION['csrf_token_time'], $_POST['event_id'])) {
        $_SESSION['message'] = "Güvenlik hatası: İstek bilgileri eksik. Lütfen tekrar deneyin.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        header("Location: profile.php");
        exit;
    }

    $eventId = (int)($_POST['event_id'] ?? 0);
    $token_age = time() - $_SESSION['csrf_token_time'];

    // CSRF token doğrulaması
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']) || $token_age > $csrf_token_lifetime) {
        $_SESSION['message'] = "Güvenlik hatası veya oturum zaman aşımı. Lütfen tekrar deneyin.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        header("Location: profile.php");
        exit;
    }

    $stmtDel = mysqli_prepare($connect, "DELETE FROM incidents WHERE id = ? AND reporter_id = ?");
    mysqli_stmt_bind_param($stmtDel, "ii", $eventId, $userId);
    mysqli_stmt_execute($stmtDel);

    if (mysqli_stmt_affected_rows($stmtDel) > 0) {
        $_SESSION['message'] = "Olay başarıyla silindi.";
    } else {
        $_SESSION['message'] = "Olay silinemedi, bulunamadı veya silme yetkiniz yok.";
        if(mysqli_stmt_error($stmtDel)){
            error_log("MySQLi error deleting event ID {$eventId} for user ID {$userId}: " . mysqli_stmt_error($stmtDel));
        }
    }
    mysqli_stmt_close($stmtDel);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    header("Location: profile.php");
    exit;
}

// Olay güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task']) && $_POST['task'] === 'edit_event') {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token'], $_SESSION['csrf_token_time'], $_POST['event_id'], $_POST['edit_title'], $_POST['edit_desc'])) {
        $_SESSION['message'] = "Güvenlik hatası: İstek bilgileri eksik. Lütfen tekrar deneyin.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        header("Location: profile.php");
        exit;
    }

    $eventId = (int)($_POST['event_id'] ?? 0);
    $newTitle = trim($_POST['edit_title'] ?? ''); 
    $newDesc = trim($_POST['edit_desc'] ?? '');   
    $token_age = time() - $_SESSION['csrf_token_time'];

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']) || $token_age > $csrf_token_lifetime) {
        $_SESSION['message'] = "Güvenlik hatası veya oturum zaman aşımı. Lütfen tekrar deneyin.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        header("Location: profile.php");
        exit;
    }

    if ($newTitle && $newDesc) {
        $stmtUpd = mysqli_prepare($connect, "UPDATE incidents SET title = ?, description = ? WHERE id = ? AND reporter_id = ?");
        mysqli_stmt_bind_param($stmtUpd, "ssii", $newTitle, $newDesc, $eventId, $userId);
        mysqli_stmt_execute($stmtUpd);    
        if (mysqli_stmt_affected_rows($stmtUpd) > 0) {
            $_SESSION['message'] = "Olay başarıyla güncellendi.";
        } else {
            if(mysqli_stmt_error($stmtUpd)){
                 $_SESSION['message'] = "Olay güncellenirken bir hata oluştu: " . mysqli_stmt_error($stmtUpd);
                 error_log("MySQLi error updating event ID {$eventId} for user ID {$userId}: " . mysqli_stmt_error($stmtUpd));
            } else {
                 $_SESSION['message'] = "Olay güncellenemedi (veri aynı olabilir veya kayıt bulunamadı/yetkiniz yok).";
            }
        }
        mysqli_stmt_close($stmtUpd);
    } else {
        $_SESSION['message'] = "Başlık ve açıklama boş olamaz.";
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    header("Location: profile.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Profilim - <?= htmlspecialchars($user['full_name'] ?: $user['username'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnectect" href="https://fonts.googleapis.com">
    <link rel="preconnectect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body>
<div class="container">
    <?php if ($message): ?>
        <div class="message <?= (strpos($message, 'başarı') !== false || strpos($message, 'güncellendi') !== false || strpos($message, 'silindi') !== false) ? 'success' : 'error' ?>">
            <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <section class="box profile-main">
        <header class="profile-header">
            <div class="avatar-container">
                <div class="avatar">
                    <?php
                    $userAvatarFile = $user['avatar'] ?? null; 
                    $avatarToShow = null;
                    if ($userAvatarFile && $userAvatarFile !== 'default_avatar.png') {
                        $avatarPath = "../assets/images/avatars/" . htmlspecialchars($userAvatarFile, ENT_QUOTES, 'UTF-8');
                        if (file_exists($avatarPath)) {
                            $avatarToShow = $avatarPath . '?t=' . time(); 
                        }
                    }
                    ?>
                    <?php if ($avatarToShow): ?>
                        <img src="<?= htmlspecialchars($avatarToShow, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="avatar-img" id="avatarPreview">
                    <?php else: ?>
                        <!-- SVG profil silueti -->
                        <svg id="avatarPreview" class="avatar-img" viewBox="0 0 128 128" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="64" cy="64" r="64" fill="#e0e7ef"/>
                            <circle cx="64" cy="54" r="28" fill="#b6c3d1"/>
                            <ellipse cx="64" cy="102" rx="38" ry="22" fill="#b6c3d1"/>
                        </svg>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-info">
                <h2 id="profileName"><?= htmlspecialchars($user['full_name'] ?: $user['username'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p id="profileEmail"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($user['department']): ?>
                    <p id="profileDepartment"><strong>Departman:</strong> <?= htmlspecialchars($user['department'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                 <?php if ($user['bio']): ?>
                    <p class="profile-bio"><em><?= nl2br(htmlspecialchars($user['bio'], ENT_QUOTES, 'UTF-8')) ?></em></p>
                <?php endif; ?>
            </div>
        </header>
    </section>

    <section class="box profile-update">
        <h3>Profil Bilgilerini Güncelle</h3>
        <form method="post" class="profile-update-form" action="profile.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="task" value="info">
            <div class="form-group">
                <label for="nameInput">Ad Soyad</label>
                <input type="text" id="nameInput" name="name" value="<?= htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="100">
            </div>
            <div class="form-group">
                <label for="phoneInput">Telefon</label>
                <input type="tel" id="phoneInput" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="20" placeholder="Örn: 5551234567">
            </div>
            <div class="form-group">
                <label for="deptInput">Departman</label>
                <input type="text" id="deptInput" name="dept" value="<?= htmlspecialchars($user['department'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="50">
            </div>
            <div class="form-group full-width">
                <label for="bioInput">Hakkımda</label>
                <textarea id="bioInput" name="bio" rows="4" maxlength="500" placeholder="Kısaca kendinizden bahsedin..."><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="form-group full-width">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Bilgileri Kaydet
                </button>
            </div>
        </form>
    </section>

    <section class="box recent-events">
    <h3><i class="fas fa-history"></i> Son Bildirilen Olaylar</h3>
    <?php if (empty($events)): ?>
        <p class="empty-state-text">Henüz bir olay bildirimi yapılmamış.</p>
    <?php else: ?>
        <ul class="event-list">
            <?php foreach ($events as $event): ?>
                <li class="event-item" data-event-id="<?= htmlspecialchars((string)$event['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="event-item-header">
                        <strong class="event-title"><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><i class="far fa-clock"></i> <?= htmlspecialchars(date("d M Y, H:i", strtotime($event['created_at'])), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <p class="event-desc"><?= nl2br(htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <div class="event-actions">
                        <button class="btn btn-small btn-edit" type="button">
                            <i class="fas fa-edit"></i> Düzenle
                        </button>
                        <form method="post" class="inline-form" style="display:inline;" action="profile.php"> 
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="task" value="delete_event">
                            <input type="hidden" name="event_id" value="<?= htmlspecialchars((string)$event['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-small btn-danger btn-delete" onclick="return confirm('Bu olayı silmek istediğinize emin misiniz?');">
                                <i class="fas fa-trash"></i> Sil
                            </button>
                        </form>
                    </div>

                    <form method="post" class="edit-event-form" style="display:none;" action="profile.php"> 
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="task" value="edit_event">
                        <input type="hidden" name="event_id" value="<?= htmlspecialchars((string)$event['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="edit_title" value="<?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?>" required maxlength="100" class="edit-title-input">
                        <textarea name="edit_desc" rows="2" required maxlength="500" class="edit-desc-input"><?= htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="edit-actions">
                            <button type="submit" class="btn btn-small btn-primary"><i class="fas fa-save"></i> Kaydet</button>
                            <button type="button" class="btn btn-small btn-secondary btn-cancel-edit"><i class="fas fa-times"></i> Vazgeç</button>
                        </div>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

    <footer class="profile-footer">
        <a href="../public/dashboard.php" class="btn btn-primary" style="margin-right: 1rem;">
            <i class="fas fa-home"></i> Ana Sayfa
        </a>
        <a href="../public/logout.php" class="btn btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Çıkış Yap
        </a>
    </footer>
</div>
<script src="../assets/js/profile.js"></script>
</body>
</html>