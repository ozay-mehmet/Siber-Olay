<?php
session_start();
require_once '../config/db.php'; 

$errors = [];
$username_or_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $errors[] = "Lütfen tüm alanları doldurun.";
    }

    if (empty($errors)) {
        try {
            if (!$connect) {
                throw new Exception("Veritabanı bağlantısı kurulamadı.");
            }

            $query = "SELECT id, username, password FROM users WHERE username = ? OR email = ? LIMIT 1";
            $stmt = mysqli_prepare($connect, $query);
            
            if (!$stmt) {
                throw new Exception("Sorgu hazırlama hatası: " . mysqli_error($connect));
            }

            $bind_result = mysqli_stmt_bind_param($stmt, "ss", $username_or_email, $username_or_email);
            if (!$bind_result) {
                throw new Exception("Parametre bağlama hatası: " . mysqli_stmt_error($stmt));
            }

            $execute_result = mysqli_stmt_execute($stmt);
            if (!$execute_result) {
                throw new Exception("Sorgu çalıştırma hatası: " . mysqli_stmt_error($stmt));
            }

            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                throw new Exception("Sonuç alma hatası: " . mysqli_error($connect));
            }

            $user = mysqli_fetch_assoc($result);
            
            if (!$user) {
                $errors[] = "Kullanıcı bulunamadı.";
            } else {
                if (!password_verify($password, $user['password'])) {
                    $errors[] = "Şifre hatalı.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    session_regenerate_id(true);
                    
                    header("Location: dashboard.php");
                    exit;
                }
            }

            mysqli_stmt_close($stmt);

        } catch (Exception $e) {
            error_log("Giriş hatası: " . $e->getMessage());
            $errors[] = "Giriş işlemi sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
            
            if (ini_get('display_errors')) {
                $errors[] = "Hata Detayı: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h2>Giriş Yap</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="username_or_email">Kullanıcı Adı veya E-posta</label>
                    <input type="text" id="username_or_email" name="username_or_email" 
                           value="<?= htmlspecialchars($username_or_email) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">Giriş Yap</button>
            </form>

            <div class="register-link">
                Hesabınız yok mu? <a href="register.php">Kayıt olun</a>
            </div>
        </div>
    </div>
</body>
</html>