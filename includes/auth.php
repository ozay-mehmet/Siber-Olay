<?php
// erişim kontrolü ve oturum yönetimi 
function require_login($required_role = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // rol kontrolü ==> admin değilse erişemez
    if ($required_role !== null && (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role)) {
        http_response_code(403); 
        echo "Bu sayfaya erişim yetkiniz yok.";
        exit;
    }
    else{
        
    }
}

?>
