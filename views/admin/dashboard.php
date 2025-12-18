<?php 
session_start(); 
// Cek sesi login & role admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
} 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-50 pb-20 font-sans">

    <nav class="bg-gray-900 text-white p-4 mb-8 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-user-shield text-blue-300"></i> Admin FoodHub
            </h1>
            <a href="../logout.php" class="bg-red-600 px-4 py-2 rounded text-xs font-bold hover:bg-red-900 transition">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
        </div>
    </nav>

    <div class="container mx-auto px-4">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <!-- Kelola Kategori -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex flex-col h-[25rem]">
                <h3 class="font-bold text-lg mb-4 border-b pb-2 text-gray-800 flex items-center gap-2">
                    <i class="fas fa-tags text-orange-600"></i> Kelola Kategori</h3>
                <form id="addCategoryForm" class="flex gap-2 mb-4">
                    <input type="hidden" name="action" value="add_category">
                    <input type="text" name="name" class="border border-gray-300 p-2 rounded w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 hover:!border-blue-500 hover:shadow-md hover:bg-blue-50 transition-all duration-300" 
                        placeholder="Kategori Baru..." required>
                    <button type="submit" class="bg-blue-600 text-white px-4 rounded text-sm font-bold hover:bg-blue-900 transition flex items-center gap-2 shrink-0">
                        <i class="fas fa-plus"></i> Tambah</button>
                </form>
                <ul id="categoryListAdmin" class="text-sm space-y-2 text-gray-600 max-h-60 overflow-y-auto custom-scrollbar flex-grow pr-2">
                    </ul>
            </div>
            
            <!-- Menunggu Persetujuan -->
            <div class="md:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex flex-col h-[25rem]">

                <div class="flex justify-between items-center border-b pb-2 mb-4">
                    <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                        <i class="fas fa-clock text-yellow-500"></i> Menunggu Persetujuan
                    </h3>
                    
                    <span id="pendingCountBadge" class="bg-red-100 text-red-800 text-xs font-bold px-3 py-1 rounded-full hidden">
                        0 resep
                    </span>
                </div>
                    
                <div id="pendingListAdmin" class="space-y-3 overflow-y-auto custom-scrollbar flex-grow pr-4">
                    <p class="text-center text-gray-400 py-4">Loading...</p>
                </div>
            </div>
        </div>

        <!-- Manajemen Resep -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 border-b pb-4 gap-4">
                <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                    <i class="fas fa-book-open text-orange-600"></i> Manajemen Semua Resep</h3>
                
                <button data-bs-toggle="modal" data-bs-target="#addRecipeModal" class="bg-orange-600 text-white px-5 py-2 rounded-full text-sm font-bold hover:bg-orange-700 transition flex items-center gap-2 shadow-sm">
                    <i class="fas fa-pen"></i> Tulis Resep Baru
                </button>
            </div>

            <div id="adminAllRecipes" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="col-span-full text-center py-10">
                    <div class="spinner-border text-gray-400" role="status"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Review -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content rounded-xl border-0">
                <div class="modal-header bg-gray-100">
                    <h5 class="modal-title font-bold text-gray-800"><i class="fas fa-search"></i> Review Resep</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-6">
                    <img id="r_image" src="" class="w-full h-48 object-cover rounded-lg mb-4 hidden bg-gray-200 border border-gray-300">
                    
                    <h2 id="r_title" class="text-2xl font-bold text-gray-900 mb-2"></h2>
                    <div id="r_author_container" class="mb-4"></div>
                    
                    <div class="bg-orange-50 p-4 rounded-lg mb-4 border border-orange-100">
                        <p id="r_desc" class="text-gray-700 italic text-sm"></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <h4 class="font-bold border-b pb-1 mb-2">Bahan-bahan</h4>
                            <div id="r_ing" class="text-gray-600 leading-relaxed"></div>
                        </div>
                        <div>
                            <h4 class="font-bold border-b pb-1 mb-2">Langkah Pembuatan</h4>
                            <div id="r_stp" class="text-gray-600 leading-relaxed"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50">
                    <button id="btnRejectAction" class="btn btn-danger btn-sm fw-bold text-white px-4">
                        <i class="fas fa-times"></i> Tolak</button>
                    <button id="btnApproveAction" class="btn btn-success btn-sm fw-bold text-white px-4">
                        <i class="fas fa-check"></i> Setujui</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div class="modal fade" id="editRecipeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-xl border-0">
                <div class="modal-header bg-orange-600 text-white">
                    <h5 class="modal-title font-bold"><i class="fas fa-edit"></i> Edit Resep (Admin Mode)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-6">
                    <form id="editRecipeForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editId">
                        
                        <div class="row mb-3">
                            <div class="col-md-6"><label class="fw-bold text-sm">Judul</label><input type="text" name="title" id="editTitle" class="form-control" required></div>
                            <div class="col-md-6"><label class="fw-bold text-sm">Kategori</label><select name="category" id="editCategory" class="form-select" required></select></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><label class="fw-bold text-sm">Waktu</label><input type="text" name="cooking_time" id="editTime" class="form-control"></div>
                            <div class="col-md-6"><label class="fw-bold text-sm">Porsi</label><input type="text" name="servings" id="editServings" class="form-control"></div>
                        </div>
                        <div class="mb-3"><label class="fw-bold text-sm">Deskripsi</label><textarea name="description" id="editDesc" class="form-control" rows="2" required></textarea></div>
                        <div class="mb-3"><label class="fw-bold text-sm">Bahan</label><textarea name="ingredients" id="editIng" class="form-control" rows="4" required></textarea></div>
                        <div class="mb-3"><label class="fw-bold text-sm">Langkah</label><textarea name="steps" id="editStp" class="form-control" rows="4" required></textarea></div>
                        <div class="mb-3"><label class="fw-bold text-sm">Ganti Foto</label><input type="file" name="image" class="form-control"></div>
                        
                        <button type="submit" class="btn btn-primary w-100 bg-orange-600 border-0 hover:bg-orange-700 py-2 font-bold">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal add resep -->
    <div class="modal fade" id="addRecipeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-xl border-0">
                <div class="modal-header bg-orange-600 text-white">
                    <h5 class="modal-title font-bold"><i class="fas fa-plus-circle"></i> Buat Resep Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-6">
                    <form id="addRecipeForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row mb-3">
                            <div class="col-md-6"><label class="fw-bold text-sm">Judul</label><input type="text" name="title" class="form-control" placeholder="Cth: Nasi Goreng" required></div>
                            <div class="col-md-6"><label class="fw-bold text-sm">Kategori</label><select name="category" id="categorySelect" class="form-select" required></select></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><label class="fw-bold text-sm">Waktu</label><input type="text" name="cooking_time" class="form-control" placeholder="Cth: 15 Menit"></div>
                            <div class="col-md-6"><label class="fw-bold text-sm">Porsi</label><input type="text" name="servings" class="form-control" placeholder="Cth: 1 Piring"></div>
                        </div>
                        <div class="mb-3"><label class="fw-bold text-sm">Deskripsi</label><textarea name="description" class="form-control" rows="2" placeholder="Ceritakan sedikit..." required></textarea></div>
                        <div class="mb-3"><label class="fw-bold text-sm">Bahan (Baris baru per item)</label>
                            <textarea name="ingredients" class="form-control" rows="4" placeholder="500 gram Nasi&#10;2 butir Telur" required></textarea></div>
                        <div class="mb-3"><label class="fw-bold text-sm">Langkah (Baris baru per item)</label>
                            <textarea name="steps" class="form-control" rows="4" placeholder="1. Panaskan minyak&#10;2. Masukkan bumbu" required></textarea></div>
                        <div class="mb-3"><label class="fw-bold text-sm">Foto</label><input type="file" name="image" class="form-control"></div>
                        
                        <button type="submit" class="btn btn-primary w-100 bg-orange-600 border-0 hover:bg-orange-700 py-2 font-bold">Terbitkan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal detail resep -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content rounded-xl border-0">
                <div class="relative">
                    <img id="view_image" src="" class="w-full h-64 object-cover hidden bg-gray-200">
                    <div id="view_placeholder" class="w-full h-64 bg-orange-100 flex items-center justify-center text-orange-300 font-bold text-6xl hidden">?</div>
                    <button type="button" class="btn-close absolute top-3 right-3 bg-white p-2 rounded-full opacity-75 hover:opacity-100 shadow" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-6 md:p-8">
                    <div class="mb-5">
                        <span id="view_category" class="bg-orange-100 text-orange-700 text-xs px-2 py-1 rounded-full font-bold uppercase tracking-wide"></span>
                        <h2 id="view_title" class="text-3xl font-bold text-gray-800 mt-2 leading-tight"></h2>
                        <div id="view_author_container" class="mt-2 text-sm text-gray-600"></div>
                    </div>
                    
                    <div class="flex gap-6 mb-6 border-y border-gray-100 py-3">
                        <div class="flex items-center gap-2 text-sm text-gray-600"><i class="far fa-clock"></i> <span id="view_time" class="font-bold">-</span></div>
                        <div class="flex items-center gap-2 text-sm text-gray-600"><i class="fas fa-utensils"></i> <span id="view_servings" class="font-bold">-</span></div>
                    </div>
                    
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <p id="view_desc" class="text-gray-600 italic leading-relaxed"></p>
                    </div>
                    
                    <div class="row g-5">
                        <div class="col-md-5"><h4 class="font-bold border-b pb-2 mb-3 text-gray-800">🛒 Bahan</h4><div id="view_ing" class="text-sm text-gray-700 space-y-2"></div></div>
                        <div class="col-md-7"><h4 class="font-bold border-b pb-2 mb-3 text-gray-800">👨‍🍳 Cara</h4><div id="view_stp" class="text-sm text-gray-700 space-y-3"></div></div>
                    </div>
                    
                    <hr class="my-6">
                    <h4 class="font-bold mb-4 text-gray-800">Komentar</h4>
                    <div id="commentList" class="space-y-4 mb-4 max-h-60 overflow-y-auto pr-2"></div>
                    <form id="commentForm" class="flex gap-2">
                        <input type="hidden" name="action" value="add"><input type="hidden" name="recipe_id" id="commentRecipeId">
                        <input type="text" name="comment" class="border rounded-lg w-full p-2 text-sm focus:ring-2 focus:ring-orange-500" placeholder="Tulis komentar..." required>
                        <button type="submit" class="bg-orange-600 text-white px-4 rounded-lg text-sm font-bold hover:bg-orange-700">Kirim</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>