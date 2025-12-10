<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - FoodHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="bg-gray-800 flex items-center justify-center h-screen font-sans">

    <div class="bg-white p-8 rounded-xl shadow-2xl w-96 border border-gray-700">
        <div class="text-center mb-6">
            <div class="text-4xl mb-2">🛡️</div>
            <h2 class="text-2xl font-bold text-gray-800">Admin Panel</h2>
            <p class="text-gray-500 text-sm">Login khusus administrator</p>
        </div>

        <form id="loginForm">
            <input type="hidden" name="action" value="login">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Email Admin</label>
                <input type="email" name="email" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 bg-gray-50" placeholder="admin@foodhub.com" required>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 bg-gray-50" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-2 px-4 rounded-lg focus:outline-none transition shadow-md">
                Masuk ke Panel
            </button>
        </form>

        <div id="message" class="mt-4 text-center text-sm"></div>
        
        <div class="mt-4 text-center">
            <a href="../../views/login.php" class="text-xs text-gray-400 hover:underline">← Kembali ke Login User</a>
        </div>

    </div>
    
    <script src="../../assets/js/script.js"></script>
</body>
</html>