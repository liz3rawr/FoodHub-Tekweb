<?php 
session_start(); 
// Jika session 'user_id' kosong (belum login), tendang ke login.php
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit; 
}

// ambil data session (untuk navbar)
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FoodHub</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/style.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50 font-sans pb-20">

    <nav class="bg-white shadow-sm sticky top-0 z-40 border-b border-gray-100">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="text-2xl">🍳</span>
                <h1 class="text-xl font-bold text-gray-800 tracking-tight">FoodHub</h1>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden md:block text-right">
                    <a href="profile.php" id="navProfileLink" class="flex items-center gap-2 text-sm font-bold text-gray-700 hover:text-orange-600 bg-gray-50 py-1 px-2 rounded-full transition border border-transparent hover:border-orange-200">
                        <div class="w-8 h-8 rounded-full bg-gray-200 animate-pulse"></div>
                        <span id="navProfileName" class="truncate max-w-[100px]"><?php echo htmlspecialchars($name); ?></span>
                    </a>
                </div>
                <a href="logout.php" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-sm font-bold hover:bg-gray-200 transition">
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-8 px-4">

        <?php if($role === 'admin'): ?>
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span class="bg-red-100 text-red-600 p-1 rounded">👑</span> Panel Admin
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border rounded-lg p-4">
                    <h3 class="font-bold text-gray-700 text-sm uppercase mb-3 border-b pb-2">Menunggu Persetujuan</h3>
                    <div id="pendingList" class="space-y-2 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                        <p class="text-center text-gray-400 text-sm py-4">Memuat data...</p>
                    </div>
                </div>

                <div class="border rounded-lg p-4 bg-gray-50">
                    <h3 class="font-bold text-gray-700 text-sm uppercase mb-3 border-b pb-2">Tambah Kategori</h3>
                    <form id="addCategoryForm" class="flex flex-col gap-3">
                        <input type="hidden" name="action" value="add_category">
                        <input type="text" name="name" class="form-control" placeholder="Nama Kategori..." required>
                        <button type="submit" class="bg-red-600 text-white py-2 rounded font-bold text-sm hover:bg-red-700">
                            + Simpan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>


        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div class="flex flex-col md:flex-row gap-2 w-full md:w-2/3">
                <select id="filterCategory" class="pl-4 pr-8 py-3 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500 shadow-sm md:w-1/3 text-gray-700 cursor-pointer">
                    <option value="">Semua Kategori</option>
                    <option value="">Loading...</option>
                </select>

                <div class="relative w-full">
                    <input type="text" id="searchRecipe" placeholder="Cari resep (cth: Nasi Goreng)..." 
                        class="w-full pl-10 pr-4 py-3 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500 shadow-sm">
                    <span class="absolute left-3 top-3.5 text-gray-400">🔍</span>
                </div>
            </div>
            
            <button data-bs-toggle="modal" data-bs-target="#addRecipeModal" 
                class="w-full md:w-auto bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-6 rounded-full shadow-md transition flex items-center justify-center gap-2">
                <span>📝</span> Tulis Resep Baru
            </button>
        </div>

        <div id="recipeList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="col-span-full text-center py-20">
                <div class="spinner-border text-orange-500" role="status"></div>
                <p class="text-gray-500 mt-2">Sedang mengambil resep...</p>
            </div>
        </div>

    </div>

    <div class="modal fade" id="addRecipeModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-orange-600 text-white">
                    <h5 class="modal-title font-bold">Tulis Resep Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-5">
                    <form id="addRecipeForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 text-sm">Judul Resep</label>
                                <input type="text" name="title" class="form-control" placeholder="Cth: Nasi Goreng Spesial" required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 text-sm">Kategori</label>
                                <select name="category" id="categorySelect" class="form-select" required>
                                    <option value="">Loading...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 text-sm">Waktu Masak</label>
                                <input type="text" name="cooking_time" class="form-control" placeholder="Cth: 30 Menit">
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold mb-1 text-sm">Porsi</label>
                                <input type="text" name="servings" class="form-control" placeholder="Cth: 2 Porsi">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold mb-1 text-sm">Deskripsi Singkat</label>
                            <textarea name="description" rows="2" class="form-control" placeholder="Ceritakan sedikit tentang masakan ini..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold mb-1 text-sm">Bahan-bahan (Baris baru per bahan)</label>
                            <textarea name="ingredients" rows="4" class="form-control" placeholder="500 gram Nasi&#10;2 butir Telur" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold mb-1 text-sm">Langkah Pembuatan (Baris baru per langkah)</label>
                            <textarea name="steps" rows="4" class="form-control" placeholder="1. Panaskan minyak&#10;2. Masukkan bumbu" required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold mb-1 text-sm">Foto Masakan</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold bg-orange-600 border-0 hover:bg-orange-700">Terbitkan Resep</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content rounded-xl shadow-2xl border-0">
                <div class="relative">
                    <img id="view_image" src="" class="w-full h-64 object-cover hidden bg-gray-200">
                    <div id="view_placeholder" class="w-full h-64 bg-orange-100 flex items-center justify-center text-orange-300 font-bold text-6xl hidden">?</div>
                    
                    <button type="button" class="btn-close absolute top-3 right-3 bg-white p-2 rounded-full opacity-75 hover:opacity-100 shadow" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-6 md:p-8">
                    <div class="mb-5">
                        <span id="view_category" class="bg-orange-100 text-orange-700 text-xs px-2 py-1 rounded-full font-bold uppercase tracking-wide"></span>
                        <h2 id="view_title" class="text-3xl font-bold text-gray-800 mt-2 leading-tight"></h2>
                        <div id="view_author_container" class="mt-2"></div>
                    </div>

                    <div class="flex gap-6 mb-6 border-y border-gray-100 py-3">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            ⏱️ <span id="view_time" class="font-bold">-</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            🍽️ <span id="view_servings" class="font-bold">-</span>
                        </div>
                    </div>

                    <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <p id="view_desc" class="text-gray-600 italic leading-relaxed"></p>
                    </div>

                    <div class="row g-5">
                        <div class="col-md-5">
                            <h4 class="font-bold border-b pb-2 mb-3 text-gray-800 flex items-center gap-2">🛒 Bahan-bahan</h4>
                            <div id="view_ing" class="text-sm text-gray-700 space-y-2 leading-relaxed"></div>
                        </div>
                        <div class="col-md-7">
                            <h4 class="font-bold border-b pb-2 mb-3 text-gray-800 flex items-center gap-2">👨‍🍳 Cara Membuat</h4>
                            <div id="view_stp" class="text-sm text-gray-700 space-y-3 leading-relaxed"></div>
                        </div>
                    </div>
                    
                    <hr class="my-6">
                    <h4 class="font-bold mb-4 text-gray-800">Komentar</h4>
                    <div id="commentList" class="space-y-4 mb-4 max-h-60 overflow-y-auto pr-2 custom-scrollbar"></div>
                    
                    <form id="commentForm" class="flex gap-2">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="recipe_id" id="commentRecipeId">
                        <input type="text" name="comment" class="border rounded-lg w-full p-2 text-sm focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Tulis komentar..." required>
                        <button type="submit" class="bg-orange-600 text-white px-4 rounded-lg text-sm font-bold hover:bg-orange-700 transition">Kirim</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reviewModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Review Resep</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-5"><img id="r_image" src="" class="w-full h-48 object-cover rounded mb-4 hidden"><h2 id="r_title" class="text-xl font-bold"></h2><div id="r_author_container" class="mb-3"></div><div id="r_desc" class="bg-gray-50 p-3 rounded mb-3 text-sm italic"></div><div class="grid grid-cols-2 gap-4 text-sm"><div><strong>Bahan:</strong><div id="r_ing"></div></div><div><strong>Langkah:</strong><div id="r_stp"></div></div></div></div><div class="modal-footer"><button id="btnRejectAction" class="btn btn-danger btn-sm text-white">Tolak</button><button id="btnApproveAction" class="btn btn-success btn-sm text-white">Setujui</button></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>

</body>
</html>