<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar - FoodHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-xl shadow-lg w-96">
        <h2 class="text-2xl font-bold text-center mb-6">Daftar Akun</h2>
        <form id="registerForm", method="POST">
            <input type="hidden" name="action" value="register">
            <div class="mb-4"><label class="block text-sm font-bold mb-2">Nama</label><input type="text" name="name" class="w-full border p-2 rounded" required></div>
            <div class="mb-4"><label class="block text-sm font-bold mb-2">Email</label><input type="email" name="email" class="w-full border p-2 rounded" required></div>
            <div class="mb-4"><label class="block text-sm font-bold mb-2">Password</label><input type="password" name="password" class="w-full border p-2 rounded" required></div>
            <div class="mb-6"><label class="block text-xs text-gray-500 mb-2">Kode Admin (Opsional)</label><input type="text" name="secret_key" class="w-full border p-2 rounded text-xs" placeholder="Kosongkan jika user biasa"></div>
            <button type="submit" class="w-full bg-orange-600 text-white font-bold py-2 rounded hover:bg-orange-700">Daftar</button>
        </form>
        <div class="mt-4 text-center text-sm">Sudah punya akun? <a href="login.php" class="text-orange-600 font-bold">Login</a></div>
    </div>
    <?php 
        // Deteksi nama folder project secara otomatis
        $path = $_SERVER['REQUEST_URI']; 
        $parts = explode('/', trim($path, '/'));
        $projectFolder = $parts[0]; // Mengambil nama folder pertama setelah localhost
    ?>
    <script src="/<?php echo $projectFolder; ?>/assets/js/script.js"></script>
</body>
</html>