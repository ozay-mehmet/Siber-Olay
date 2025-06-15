<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php'; 

// Güvenlik başlıkları
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

// CSRF Token oluşturma
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$successMessage = '';
$formData = [
    'username' => '',
    'email' => '',
    'full_name' => '',  
    'phone' => '',
    'department' => '',
    'bio' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Güvenlik hatası: CSRF doğrulaması başarısız.";
    } else {
        $formData['username'] = trim($_POST['username'] ?? '');
        $formData['email'] = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $formData['full_name'] = trim($_POST['full_name'] ?? '');
        $formData['phone'] = trim($_POST['phone'] ?? '');
        $formData['department'] = trim($_POST['department'] ?? '');
        $formData['bio'] = trim($_POST['bio'] ?? '');

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (empty($formData['username'])) {
            $errors[] = "Kullanıcı adı gerekli.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $formData['username'])) {
            $errors[] = "Kullanıcı adı yalnızca harf, rakam ve alt çizgi içerebilir (3-20 karakter).";
        }

        if (empty($formData['email'])) {
            $errors[] = "E-posta gerekli.";
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Geçersiz e-posta adresi.";
        }

        if (empty($password)) {
            $errors[] = "Şifre gerekli.";
        } elseif (strlen($password) <  8) {
            $errors[] = "Şifre en az 8 karakter olmalı.";
        } elseif ($password !== $confirmPassword) {
            $errors[] = "Şifreler uyuşmuyor.";
        }

        if (empty($errors)) {
            try {
                // şifrenin güvenli hale getirilmesi
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                if ($hashedPassword === false) {
                    throw new Exception("Şifreleme sırasında bir hata oluştu.");
                }
                

                mysqli_begin_transaction($connect);

                // kullanıcı adının ve e-posta adresinin benzersizliğinin kontrol edilmesi
                $stmt_check = mysqli_prepare($connect, "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
                if (!$stmt_check) {
                    throw new Exception("Veritabanı sorgusu hazırlanamadı (check): " . mysqli_error($connect));
                }
                
                mysqli_stmt_bind_param($stmt_check, "ss", $formData['username'], $formData['email']);
                if (!mysqli_stmt_execute($stmt_check)) {
                    throw new Exception("Veritabanı sorgusu çalıştırılamadı (check): " . mysqli_stmt_error($stmt_check));
                }
                
                $result_check = mysqli_stmt_get_result($stmt_check);

                if (mysqli_fetch_assoc($result_check)) {
                    $errors[] = "Bu kullanıcı adı veya e-posta zaten kayıtlı.";
                    mysqli_rollback($connect); 
                } else {
                    mysqli_stmt_close($stmt_check); 

                    // kullanıcının veritabanına eklenmesi
                    $stmt_insert = mysqli_prepare($connect, "
                        INSERT INTO users 
                            (username, email, password, full_name, phone, department, bio, role) 
                        VALUES 
                            (?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    if (!$stmt_insert) {
                        throw new Exception("Veritabanı sorgusu hazırlanamadı (insert): " . mysqli_error($connect));
                    }
                    
                    $role = 'user'; 
                    $fullName = $formData['full_name'] ?: null;
                    $phone = $formData['phone'] ?: null;
                    $department = $formData['department'] ?: null;
                    $bio = $formData['bio'] ?: null;

                    // parametrelerin bağlanması ve sorgunun çalıştırılması
                    mysqli_stmt_bind_param($stmt_insert, "ssssssss", 
                        $formData['username'], 
                        $formData['email'], 
                        $hashedPassword,
                        $fullName,
                        $phone,
                        $department,
                        $bio,
                        $role
                    );
                    
                    if (!mysqli_stmt_execute($stmt_insert)) {
                        throw new Exception("Kullanıcı veritabanına kaydedilemedi: " . mysqli_stmt_error($stmt_insert));
                    }
                    
                    mysqli_commit($connect);
                    $successMessage = "Kullanıcı başarıyla oluşturuldu. Giriş sayfasına yönlendiriliyorsunuz...";
                    $errorMessaage = "Kullanıcı kaydedilemedi. Lütfen tekrar deneyin.";
                    $_SESSION['registration_success'] = true;
                    
                    // Form verilerini temizle
                    $formData = ['username' => '', 'email' => '', 'full_name' => '', 'phone' => '', 'department' => '', 'bio' => ''];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    // Yönlendirme için 0.01 saniye bekleme
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 10);
                    </script>";
                }
                
                if (isset($stmt_insert)) {
                    mysqli_stmt_close($stmt_insert);
                }
                
            } catch (Exception $e) {
                if (isset($connect) && mysqli_ping($connect)) { 
                    mysqli_rollback($connect);
                }
                $errors[] = "Bir hata oluştu: " . $e->getMessage();
                error_log("Kayıt Sırasında Hata: " . $e->getMessage());
            }
        }
        else{
            echo "<script>alert('İstenilene uygun bir değer girin.');</script>";

            echo "<script>
                        setTimeout(function() {
                            window.location.href = 'register.php';
                        }, 10);
                    </script>";
        }
        // Bağlantıyı kapatma
        if (isset($connect) && mysqli_ping($connect)) {
            mysqli_close($connect);
    }
}
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self' fonts.googleapis.com; font-src fonts.gstatic.com; frame-ancestors 'none';">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Siber Güvenlik Olay Raporlama Sistemi Kayıt Sayfası">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="../assets/css/register.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="background-animation">
        <?php for ($i = 0; $i < 20; $i++): ?>
            <span></span>
        <?php endfor; ?>
    </div>

    <div class="form-wrapper">
        <div class="form-container">
            <h2>Hesap Oluştur</h2>

            <?php if (!empty($errors)): ?>
                <div class="message-box error-box"> 
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="message-box success-box">
                    <p><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm" autocomplete="off" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="input-group">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" name="username" id="username"
                           value="<?= htmlspecialchars($formData['username'], ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Kullanıcı adınızı girin"
                           required pattern="[a-zA-Z0-9_]{3,20}" minlength="3" maxlength="20">
                </div>

                <div class="input-group">
                    <label for="email">E-posta</label>
                    <input type="email" name="email" id="email"
                           value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="E-posta adresinizi girin"
                           required>
                </div>

                <div class="input-group">
                    <label for="password">Şifre</label>
                    <input type="password" name="password" id="password" required minlength="8"
                           placeholder="en az 8 karakter">
                </div>

                <div class="input-group">
                    <label for="confirm_password">Şifreyi Onayla</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                           placeholder="Şifrenizi onaylayın">
                </div>

                <button type="submit" class="register-button">Kayıt Ol</button>
                <p class="login-link">Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
            </form>
        </div>
    </div>

    <script src="../assets/js/register.js" defer></script>
</body>
</html>