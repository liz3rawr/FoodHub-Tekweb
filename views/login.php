<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - FoodHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-xl shadow-lg w-96">
        <h2 class="text-2xl font-bold text-center mb-6">FoodHub Login</h2>
        <form id="loginForm">
            <input type="hidden" name="action" value="login">
            <div class="mb-4"><label class="block text-sm font-bold mb-2">Email</label><input type="email" name="email" class="w-full border p-2 rounded" required></div>
            <div class="mb-6"><label class="block text-sm font-bold mb-2">Password</label><input type="password" name="password" class="w-full border p-2 rounded" required></div>
            <button type="submit" class="w-full bg-orange-600 text-white font-bold py-2 rounded hover:bg-orange-700">Masuk</button>
        </form>
        <div class="mt-4 text-center text-sm">Belum punya akun? <a href="register.php" class="text-orange-600 font-bold">Daftar</a></div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>