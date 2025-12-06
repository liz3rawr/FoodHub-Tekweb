<?php 
session_start(); 
// Cek Login, jika tidak ada session user_id, lempar ke login
//if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; } 
$_SESSION['user_id'] = 1; //buat testing

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - FoodHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50 pb-20 font-sans">

    <nav class="bg-white shadow-sm sticky top-0 z-40 border-b border-gray-100 p-4 mb-6">
        <div class="container mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="text-orange-600 font-bold hover:underline flex items-center gap-2 transition">
                <span>←</span> Kembali ke Dashboard
            </a>
            <a href="logout.php" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-xs font-bold hover:bg-red-50 hover:text-red-600 transition border border-gray-200">
                Logout
            </a>
        </div>
    </nav>

    <div class="container mx-auto px-4 pb-12 max-w-5xl">
        
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 border border-gray-100">
            <div class="h-40 bg-gradient-to-r from-orange-500 to-orange-600"></div>
            
            <div class="px-6 pb-6">
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="-mt-16 flex-none mx-0 relative z-10">
                        <div id="profileAvatarContainer" class="w-32 h-32 rounded-full border-4 border-white shadow-lg bg-white flex items-center justify-center overflow-hidden">
                            <div class="animate-pulse bg-gray-200 w-full h-full"></div>
                        </div>
                    </div>
                    
                    <div class="flex-1 text-left pt-3">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                            <div class="mb-3 md:mb-0">
                                <h2 class="text-3xl font-bold text-gray-900 leading-tight" id="profileName">Loading...</h2>
                                <p class="text-gray-500 font-medium" id="profileEmail">...</p>
                            </div>
                            <button data-bs-toggle="modal" data-bs-target="#editProfileModal" 
                                class="text-sm bg-white border border-gray-300 hover:border-orange-500 hover:text-orange-600 text-gray-700 px-5 py-2 rounded-full font-bold shadow-sm transition flex items-center gap-2">
                                ✏️ Edit Profil
                            </button>
                        </div>
                        
                        <div class="mt-4 bg-gray-50 rounded-lg p-4 border border-gray-100 text-left w-full">
                            <p class="text-gray-600 italic text-sm" id="profileBio">...</p>
                        </div>
                        
                        <div class="mt-3 flex justify-start">
                             <span class="bg-orange-100 text-orange-700 text-xs px-3 py-1 rounded-full uppercase font-bold tracking-wide border border-orange-200" id="profileRole">USER</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex border-b border-gray-300 mb-6 relative z-0">
            <button id="btnMy" class="py-3 px-6 font-bold text-orange-600 border-b-2 border-orange-600 focus:outline-none transition hover:bg-orange-50">
                Resep Saya 📝
            </button>
            
            <button id="btnLiked" class="py-3 px-6 font-bold text-gray-500 border-b-2 border-transparent focus:outline-none transition hover:bg-orange-50 hover:text-orange-600">
                Disukai ❤️
            </button>
        </div>

        <div id="tabMyRecipes" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in">
            <div class="col-span-full text-center py-10">
                <div class="spinner-border text-orange-400" role="status"></div>
                <p class="text-gray-400 mt-2 text-sm">Memuat resep...</p>
            </div>
        </div>
        
        <div id="tabLikedRecipes" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in">
            <div class="col-span-full text-center py-10">
                <div class="spinner-border text-orange-400" role="status"></div>
                <p class="text-gray-400 mt-2 text-sm">Memuat resep...</p>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-xl border-0">
                <div class="modal-header border-b-0 bg-gray-50">
                    <h5 class="modal-title font-bold text-gray-800">Edit Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-6">
                    <form id="editProfileForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-4 text-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-orange-400 transition bg-white relative group">
                            <label class="block text-sm font-bold mb-2 z-10 relative cursor-pointer text-gray-600 group-hover:text-orange-600">
                                📷 Ganti Foto
                            </label>
                            <input type="file" name="photo" class="form-control absolute inset-0 opacity-0 w-full h-full cursor-pointer" 
                                   onchange="document.getElementById('fileNameDisplay').innerText = this.files[0] ? this.files[0].name : 'Klik untuk pilih...'; document.getElementById('fileNameDisplay').classList.add('text-orange-600', 'font-bold');">
                            
                            <p id="fileNameDisplay" class="text-xs text-gray-400 mt-2 pointer-events-none truncate px-4">
                                Klik area ini untuk memilih file...
                            </p>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold text-sm text-gray-700">Nama Lengkap</label>
                            <input type="text" name="name" id="inputName" class="form-control rounded-lg" required>
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold text-sm text-gray-700">Bio Singkat</label>
                            <textarea name="bio" id="inputBio" class="form-control rounded-lg" rows="3"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary w-100 bg-orange-600 border-0 hover:bg-orange-700 font-bold py-2 rounded-lg transition">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRecipeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-xl border-0 overflow-hidden">
                <div class="modal-header bg-orange-600 text-white border-0">
                    <h5 class="modal-title font-bold flex items-center gap-2">✏️ Edit Resep</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-6">
                    <form id="editRecipeForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editId">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold text-sm mb-1">Judul</label>
                                <input type="text" name="title" id="editTitle" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-sm mb-1">Kategori</label>
                                <select name="category" id="editCategory" class="form-select" required></select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold text-sm mb-1">Waktu Masak</label>
                                <input type="text" name="cooking_time" id="editTime" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-sm mb-1">Porsi</label>
                                <input type="text" name="servings" id="editServings" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-sm mb-1">Deskripsi</label>
                            <textarea name="description" id="editDesc" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-sm mb-1">Bahan-bahan</label>
                            <textarea name="ingredients" id="editIng" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-sm mb-1">Langkah Pembuatan</label>
                            <textarea name="steps" id="editStp" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold text-sm mb-1">Ganti Foto (Opsional)</label>
                            <input type="file" name="image" class="form-control">
                            <small class="text-danger text-xs italic mt-1 block">*Mengedit resep akan mengubah status kembali menjadi <b>PENDING</b> (Menunggu persetujuan Admin).</small>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary w-100 bg-orange-600 border-0 hover:bg-orange-700 font-bold py-2 rounded-lg transition">Simpan & Ajukan Ulang</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                        <div id="view_author_container" class="mt-2"></div>
                    </div>
                    
                    <div class="flex gap-6 mb-6 border-y border-gray-100 py-3">
                        <div class="flex items-center gap-2 text-sm text-gray-600">⏱️ <span id="view_time" class="font-bold">-</span></div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">🍽️ <span id="view_servings" class="font-bold">-</span></div>
                    </div>
                    
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <p id="view_desc" class="text-gray-600 italic leading-relaxed"></p>
                    </div>
                    
                    <div class="row g-5">
                        <div class="col-md-5">
                            <h4 class="font-bold border-b pb-2 mb-3 text-gray-800">🛒 Bahan-bahan</h4>
                            <div id="view_ing" class="text-sm text-gray-700 space-y-2 leading-relaxed"></div>
                        </div>
                        <div class="col-md-7">
                            <h4 class="font-bold border-b pb-2 mb-3 text-gray-800">👨‍🍳 Cara Membuat</h4>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>