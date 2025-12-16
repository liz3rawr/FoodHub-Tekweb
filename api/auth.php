<?php
session_start();
include_once '../config/Database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$action = isset($_POST['action']) ? $_POST['action'] : '';

// REGISTER
if($action == 'register') {
    // sanitize input
    $name = htmlspecialchars(strip_tags($_POST['name']));
    $email = htmlspecialchars(strip_tags($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // passsword encryption
    
    // bisa pakai secret key untuk jadi admin
    $role = 'user'; // Default role
    if(isset($_POST['secret_key']) && $_POST['secret_key'] === 'MAHASISWA_KEREN') {
        $role = 'admin';
    }

    // Cek apakah email sudah ada?
    $check_query = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$email]);
    
    if($check_stmt->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Email sudah terdaftar! Gunakan email lain."]);
        exit;
    }

    // Insert Data Baru
    $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if($stmt->execute([$name, $email, $password, $role])) {
        $msg = ($role === 'admin') ? "Registrasi Admin Berhasil!" : "Registrasi Berhasil! Silakan Login.";
        echo json_encode(["status" => "success", "message" => $msg]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal mendaftar. Cek database."]);
    }
}

// LOGIN
elseif($action == 'login') {
    $email = htmlspecialchars(strip_tags($_POST['email']));
    $password = $_POST['password'];

    // Cari user berdasarkan email
    $query = "SELECT id, name, password, role FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);

    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verifikasi Password Hash
        if(password_verify($password, $row['password'])) {
            // Set Session PHP
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['role'] = $row['role'];
            
            // PENTING: Kirim 'role' kembali ke JavaScript untuk redirect
            echo json_encode([
                "status" => "success", 
                "message" => "Login berhasil!",
                "role" => $row['role'], // Ini dipakai script.js untuk redirect pintar
                "user_name" => $row['name']
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Password salah!"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Email tidak ditemukan!"]);
    }
}

// DEFAULT UNKNOWN ACTION
else {
    echo json_encode(["status" => "error", "message" => "Action tidak valid."]);
}
?>