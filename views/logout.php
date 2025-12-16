<?php
session_start();
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
session_destroy();
if ($role === 'admin')  {
    header("Location: admin/login.php");    
} else {
    header("Location: login.php");
}
exit;
?>