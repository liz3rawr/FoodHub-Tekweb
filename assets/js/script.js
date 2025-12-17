// ============================================================
// 1. KONFIGURASI GLOBAL
// ============================================================
const pathSegments = window.location.pathname.split('/');
const projectFolder = pathSegments[1] ? pathSegments[1] : 'FoodHub-Tekweb'; 

const BASE_URL = window.location.origin + '/' + projectFolder;
const API_PATH = BASE_URL + '/api/';
const ASSETS_PATH = BASE_URL + '/assets/uploads/';

console.log("System Ready.");
console.log("API Target:", API_PATH);

// ============================================================
// 2. DOCUMENT READY (EVENT LISTENERS)
// ============================================================
$(document).ready(function() {

    // --- TAB PROFILE ---
    $('#tabLikedRecipes').addClass('hidden');
    if ($('#btnMy').length) {
        $('#btnMy').removeClass('text-gray-500 border-transparent').addClass('text-orange-600 border-orange-600');
    }
    $('#btnMy').on('click', function() { switchProfileTab('My'); });
    $('#btnLiked').on('click', function() { switchProfileTab('Liked'); });
    
    // --- EVENT DELEGATION ---
    
    // 1. Buka Detail
    $(document).on('click', '.js-open-detail', function(e) {
        if ($(e.target).closest('button').length) return;
        let id = $(this).data('id');
        openDetailModal(id);
    });

    // 2. Review Pending
    $(document).on('click', '.js-open-review', function(e) {
        let id = $(this).data('id');
        openReviewModal(id);
    });

    // 3. Tombol Edit
    $(document).on('click', '.js-edit-recipe', function(e) {
        e.stopPropagation();
        let id = $(this).data('id');
        openEditModal(id);
    });

    // 4. Tombol Hapus
    $(document).on('click', '.js-delete-recipe', function(e) {
        e.stopPropagation();
        let id = $(this).data('id');
        deleteRecipe(id);
    });

    // 5. Hapus Kategori
    $(document).on('click', '.js-delete-cat', function(e) {
        let id = $(this).data('id');
        deleteCategory(id);
    });

    // 6. Tombol Like (SVG Updated)
    $(document).on('click', '.js-toggle-like', function(e) {
        e.stopPropagation();
        let id = $(this).data('id');
        let isLikedTab = ($(this).data('tab') === 'liked') || ($('#tabLikedRecipes').is(':visible'));
        toggleLike(id, isLikedTab);
    });

    // 7. Admin Actions
    $('#btnRejectAction').off('click').click(function() { processRecipe($(this).data('id'), 'reject'); });
    $('#btnApproveAction').off('click').click(function() { 
        let id = $(this).data('id');
        let category = $(this).data('category'); 

        // Cek apakah kategori kosong/null
        if (!category || category === 'null' || category.trim() === '') {
            // Kita cuma butuh tombol OK (untuk tutup), jadi callback 'onCancel' dikosongkan
            showConfirm(
                "Kategori resep ini <b>KOSONG</b> (mungkin telah dihapus).<br><br>Admin tidak dapat menyetujui resep tanpa kategori.<br>Silakan minta <b>User untuk Edit resep</b> ini terlebih dahulu.", 
                function() {
                    $('#reviewModal').modal('hide'); 
                },
                function() {
                },
                "PERINGATAN!"
            );
            return;
        }

        processRecipe(id, 'approve'); 
    });

    // --- FORM SUBMISSIONS ---

    // LOGIN
    $('#loginForm').submit(function(e){
        e.preventDefault(); 
        let btn = $(this).find('button'); 
        let txt = btn.text();
        btn.text('Loading...').prop('disabled', true);

        $.ajax({
            url: API_PATH + 'auth.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                if(res.status === 'success') {
                    showToast('Login Berhasil!', 'success');
                    setTimeout(() => {
                        let role = res.role;
                        let inAdmin = window.location.pathname.includes('/admin/');
                        if(role === 'admin') {
                            window.location.href = inAdmin ? 'dashboard.php' : BASE_URL + '/views/admin/dashboard.php';
                        } else {
                            window.location.href = BASE_URL + '/views/dashboard.php';
                        }
                    }, 1000);
                } else { 
                    showToast(res.message, 'error'); 
                    btn.text(txt).prop('disabled', false);
                }
            },
            error: function(xhr) { 
                console.error("Login Error:", xhr.responseText); 
                showToast('Gagal koneksi', 'error'); 
                btn.text(txt).prop('disabled', false);
            }
        });
    });

    // register
    $('#registerForm').submit(function(e){
        e.preventDefault();
        let btn = $(this).find('button');
        let txt = btn.text();
        btn.text('Proses...').prop('disabled',true);
        $.ajax({
            url: API_PATH + 'auth.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                if(res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(() => {
                        window.location.href = BASE_URL + '/views/login.php';
                    }, 2000);
                }
                else showToast(res.message, 'error');
            },
            complete: function() {
                btn.text(txt).prop('disabled', false);
            }
        });
    });

    // ADD RECIPE
    $('#addRecipeForm').submit(function(e){
        e.preventDefault();
        
        let form = $(this); 
        let btn = form.find('button[type="submit"]'); 
        let originalText = btn.text(); 
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mengirim...');
        
        $.ajax({
            url: API_PATH + 'recipe.php', 
            type: 'POST', 
            data: new FormData(this), 
            contentType: false, 
            processData: false, 
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    showToast(res.message, 'success');
                    
                    let modalEl = document.getElementById('addRecipeModal');
                    if(modalEl) {
                        let modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if(modalInstance) modalInstance.hide();
                    }
                    
                    form[0].reset();
                    reloadAllLists();
                    
                } else {
                    showToast(res.message, 'error');
                }
            },
            error: function(xhr) {
                console.error("Add Recipe Error:", xhr.responseText);
                showToast('Gagal terhubung ke server', 'error');
            },
            complete: function() { 
                btn.prop('disabled', false).text(originalText); 
            }
        });
    });

    // EDIT RECIPE
    $('#editRecipeForm').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: API_PATH + 'recipe.php', type: 'POST', data: new FormData(this), contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    showToast(res.message, 'success');
                    let modalEl = document.getElementById('editRecipeModal');
                    if(modalEl) bootstrap.Modal.getInstance(modalEl).hide();
                    reloadAllLists();
                } else showToast(res.message, 'error');
            }
        });
    });

    // COMMENT
    $('#commentForm').submit(function(e){
        e.preventDefault();
        $.post(API_PATH + 'comment.php', $(this).serialize(), function(res){
            if(res.status === 'success') {
                $('#commentForm input[name="comment"]').val('');
                loadComments($('#commentRecipeId').val());
            } else showToast(res.message, 'error');
        }, 'json');
    });

    // 8. HAPUS KOMENTAR (BARU)
    $(document).on('click', '.js-delete-comment', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let recipeId = $('#commentRecipeId').val(); // Ambil ID resep buat reload

        // Gunakan showConfirm punya kamu biar tampilannya konsisten
        showConfirm("Hapus komentar ini?", function() {
            $.post(API_PATH + 'comment.php', {
                action: 'delete', 
                comment_id: id
            }, function(res) {
                if(res.status === 'success') {
                    showToast('Komentar dihapus', 'success');
                    loadComments(recipeId); // Reload otomatis
                } else {
                    showToast(res.message, 'error');
                }
            }, 'json').fail(function() {
                showToast('Gagal koneksi server', 'error');
            });
        });
    });

    // UPDATE PROFILE
    $('#editProfileForm').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: API_PATH + 'profile.php', type: 'POST', data: new FormData(this), contentType: false, processData: false, dataType: 'json',
            success: function(res){
                if(res.status === 'success'){
                    let modalEl = document.getElementById('editProfileModal');
                    if(modalEl) bootstrap.Modal.getInstance(modalEl).hide();
                    loadProfileInfo(); 
                    showToast('Profil Update!', 'success');
                } else showToast(res.message, 'error');
            }
        });
    });

    // ADD CATEGORY
    $('#addCategoryForm').submit(function(e){
        e.preventDefault();
        $.post(API_PATH + 'recipe.php', $(this).serialize(), function(res){
            if(res.status === 'success'){ showToast(res.message, 'success'); $('#addCategoryForm')[0].reset(); loadCategories(); }
            else showToast(res.message, 'error');
        }, 'json');
    });

    // SEARCH
    $('#searchRecipe').on('keyup', function(){ loadRecipes($(this).val(), $('#filterCategory').val()); });
    $('#filterCategory').on('change', function(){ loadRecipes($('#searchRecipe').val(), $(this).val()); });

    // --- INITIAL LOAD ---
    reloadAllLists();
    loadCategories();
    if ($('#profileName').length) loadProfileInfo();

}); 

function showToast(m,t='info'){let c=t=='success'?'border-green-500 text-green-700 bg-green-50':'border-red-500 text-red-700 bg-red-50';let d=$(`<div class="toast-msg fixed top-5 right-5 z-50 p-4 rounded shadow-lg border-l-4 ${c} bg-white flex items-center gap-2 transition duration-300 transform translate-x-full"><span>${t=='success'?'✅':'⚠️'}</span> <b>${m}</b></div>`);$('body').append(d);setTimeout(()=>d.removeClass('translate-x-full'),10);setTimeout(()=>d.addClass('translate-x-full'),3000);setTimeout(()=>d.remove(),3300);}


function showConfirm(message, onOk, onCancel, title = 'Konfirmasi'){
    // buat elemen backdrop dan dialog
    const id = 'custom-confirm-' + Date.now();
    const backdrop = $(`<div id="${id}-backdrop" class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center"></div>`);
    
    // Gunakan parameter title di header (default: 'Konfirmasi')
    const dialog = $(`
        <div id="${id}" class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative">
            <div class="text-gray-800 font-bold text-lg mb-3">${title}</div>
            <div class="text-sm text-gray-600 mb-6">${message}</div>
            <div class="flex justify-end gap-3">
                <button class="cf-cancel bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded hover:!bg-red-600 hover:text-white transition-colors duration-200">Batal</button>
                <button class="cf-ok bg-orange-600 text-white px-4 py-2 rounded hover:!bg-orange-700 hover:text-white transition-colors duration-200">OK</button>
            </div>
        </div>
    `);

    // mencegah klik menutup backdrop
    backdrop.append(dialog);
    $('body').append(backdrop);

    // fokus ke tombol OK
    dialog.find('.cf-ok').focus();

    function cleanup(){
        backdrop.remove();
    }

    dialog.find('.cf-cancel').on('click', function(e){
        e.preventDefault();
        cleanup();
        if(typeof onCancel === 'function') onCancel();
    });

    dialog.find('.cf-ok').on('click', function(e){
        e.preventDefault();
        cleanup();
        if(typeof onOk === 'function') onOk();
    });
}

// ============================================================
// 3. FUNGSI GLOBAL & LOGIC
// ============================================================

function reloadAllLists() {
    if($('#recipeList').length) loadRecipes();
    if($('#pendingList').length) loadPendingRecipes();
    if($('#pendingListAdmin').length) loadPendingRecipes();
    if($('#adminAllRecipes').length) loadAdminAllRecipes();
    if($('#tabMyRecipes').length) loadMyRecipes();
    if($('#tabLikedRecipes').length) loadLikedRecipes();
}

// LOADERS
function loadRecipes(k='', c='') {
    $.getJSON(API_PATH + 'recipe.php', {action:'read', search:k, category:c}, function(d){
        let h = ''; 
        if(d.length == 0) h = '<div class="col-span-full text-center py-10 text-gray-400 border border-dashed rounded">Kosong</div>';
        d.forEach(r => { h += renderCard(r); });
        $('#recipeList').html(h);
    });
}
function loadMyRecipes() {
    $.getJSON(API_PATH + 'profile.php?action=get_my_recipes', function(d){
        let h = ''; if(d.length == 0) h = '<div class="col-span-full text-center py-10 text-gray-400 border border-dashed rounded">Belum ada resep.</div>';
        d.forEach(r => { h += renderCard(r, true); });
        $('#tabMyRecipes').html(h);
    });
}
function loadLikedRecipes() {
    $.getJSON(API_PATH + 'profile.php?action=get_liked_recipes', function(d){
        let h = ''; if(d.length == 0) h = '<div class="col-span-full text-center py-10 text-gray-400 border border-dashed rounded">Kosong.</div>';
        d.forEach(r => { h += renderCard(r, false, true); });
        $('#tabLikedRecipes').html(h);
    });
}
function loadPendingRecipes() {
    $.getJSON(API_PATH + 'recipe.php?action=read_pending', function(d){
        let h = (d.length == 0) ? '<div class="text-center py-4 text-sm text-gray-400 border-2 border-dashed border-gray-100 rounded-xl bg-gray-50">Tidak ada antrian resep.</div>' : '';
            
        d.forEach(i => {
            let av = getUserAvatarHtml(i.user_photo, i.author, "w-8 h-8", "text-sm");
                
            h += `
            <div class="js-open-review group flex justify-between items-center p-4 bg-white border border-gray-100 rounded-xl shadow-sm hover:shadow-md hover:border-orange-200 transition-all duration-300 cursor-pointer mb-3" data-id="${i.id}">
                <div class="flex items-center gap-4">
                    ${av}
                    <div>
                        <div class="font-bold text-gray-800 group-hover:text-orange-600 transition line-clamp-1">${i.title}</div>
                        <div class="text-xs text-gray-500 flex items-center gap-1">
                            Oleh: ${i.author}
                        </div>
                    </div>
                </div>
                    
                <button class="text-xs font-bold bg-orange-50 text-orange-600 px-4 py-2 rounded-lg group-hover:bg-orange-600 group-hover:text-white transition-all duration-300 shadow-sm flex items-center gap-2">
                    Review <i class="fas fa-arrow-right"></i>
                </button>
            </div>`;
        });
        $('#pendingList, #pendingListAdmin').html(h);
    });
}
function loadAdminAllRecipes() {
    $.getJSON(API_PATH + 'recipe.php?action=read_all_admin', function(d){
        let h = ''; if(d.length == 0) h = '<div class="col-span-full text-center text-gray-400">Kosong</div>';
        d.forEach(r => {
            let statusColor = r.status=='approved'?'green':(r.status=='rejected'?'red':'yellow');
            let badge = `<span class="bg-${statusColor}-100 text-${statusColor}-800 text-[10px] px-2 py-1 rounded uppercase font-bold absolute top-2 right-2 shadow-sm z-10">${r.status}</span>`;
            let img = getRecipeImgHtml(r.image, r.title, "h-40");
            h += `
            <div class="bg-white border rounded-xl overflow-hidden shadow-sm flex flex-col group hover:shadow-md transition relative">
                ${badge}
                <div class="js-open-detail cursor-pointer" data-id="${r.id}">
                    ${img}
                    <div class="p-4">
                        <div class="mb-1"><span class="text-[10px] font-bold uppercase bg-gray-100 px-2 py-0.5 rounded">${r.category}</span></div>
                        <h4 class="font-bold text-base mb-1 line-clamp-1 group-hover:text-orange-600">${r.title}</h4>
                        <p class="text-xs text-gray-500">Oleh: ${r.author}</p>
                    </div>
                </div>
                <div class="px-4 pb-4 pt-0 mt-auto flex gap-2">
                    <button class="js-edit-recipe flex-1 bg-white border border-blue-200 text-blue-600 text-xs py-2 rounded-lg font-bold hover:!bg-blue-600 hover:text-white transition-colors duration-200" data-id="${r.id}">Edit</button>
                    <button class="js-delete-recipe flex-1 bg-white border border-red-200 text-red-600 text-xs py-2 rounded-lg font-bold hover:!bg-red-600 hover:text-white transition-colors duration-200" data-id="${r.id}">Hapus</button>
                </div>
            </div>`;
        });
        $('#adminAllRecipes').html(h);
    });
}
function loadCategories() {
    if(!$('#filterCategory').length && !$('#categorySelect').length && !$('#categoryListAdmin').length) return;
    $.getJSON(API_PATH + 'recipe.php?action=get_categories', function(d){
        let o='<option value="">Semua Kategori</option>', fo='<option value="">Pilih Kategori...</option>', li='';
        d.forEach(c => {
            o+=`<option value="${c.name}">${c.name}</option>`;
            fo+=`<option value="${c.name}">${c.name}</option>`;
            li+=`<li class="flex justify-between items-center border-b py-2 text-sm"><span>${c.name}</span><button class="js-delete-cat text-red-400 font-bold px-2" data-id="${c.id}">✕</button></li>`;
        });
        $('#filterCategory').html(o); $('#categorySelect').html(fo); if($('#editCategory').length)$('#editCategory').html(fo); if($('#categoryListAdmin').length)$('#categoryListAdmin').html(li);
    });
}
function loadProfileInfo() {
    $.getJSON(API_PATH+'profile.php?action=get_info', function(d){
         $('#profileName').text(d.name); $('#profileEmail').text(d.email); $('#profileRole').text(d.role); 
         $('#profileBio').text(d.bio || "Belum ada bio."); $('#inputName').val(d.name); $('#inputBio').val(d.bio);
         
         // Fix: Mengirim data ke helper avatar
         let av = getUserAvatarHtml(d.photo, d.name, "w-full h-full", "text-5xl");
         $('#profileAvatarContainer').html(av);
    });
}

function formatTextToLines(text) {
    if(!text) return '-';
    return text.split(/<br\s*\/?>/gi)
                .map(t => t.trim())
                .filter(t => t !== '')
                .map(t => `<div>${t}</div>`) 
                .join('');
}

// MODAL OPENERS
function openDetailModal(id) {
    $('#view_title').text('Loading...'); 
    let modalEl = document.getElementById('detailModal');
    if(modalEl) new bootstrap.Modal(modalEl).show();

    $('#commentForm').show(); 
    $('#commentList').show().html('<div class="text-center py-2 text-gray-400 text-xs">Memuat...</div>');
    
    $.getJSON(API_PATH + 'recipe.php', {action:'get_detail', id:id}, function(d){
        $('#view_title').text(d.title); $('#view_category').text(d.category);
        $('#view_desc').text(d.description); $('#view_time').text(d.cooking_time||"-");
        $('#view_servings').text(d.servings||"-");
        $('#view_ing').html(formatTextToLines(d.ingredients)); 
        $('#view_stp').html(formatTextToLines(d.steps));
        $('#view_author_container').html(`<span class="text-gray-500 text-sm">Oleh: <span class="font-bold text-gray-800">${d.author}</span></span>`);

        if(d.image) {
            $('#view_image').attr('src', ASSETS_PATH + 'recipes/' + d.image).removeClass('hidden');
            $('#view_placeholder').addClass('hidden');
        }
        else {
            $('#view_image').addClass('hidden');
            $('#view_placeholder').text(d.title.charAt(0)).removeClass('hidden');
        }

        if(d.status === 'pending') {
            // Jika Pending: Sembunyikan form input & kasih pesan
            $('#commentForm').hide();
            $('#commentList').html(`
                <div class="flex flex-col items-center justify-center py-6 text-center bg-gray-50 rounded-lg border border-dashed border-gray-200">
                    <span class="text-2xl mb-2">🔒</span>
                    <p class="text-gray-500 text-sm font-medium">Komentar Dinonaktifkan</p>
                    <p class="text-gray-400 text-xs">Resep ini masih dalam peninjauan (Pending).</p>
                </div>
            `);
        } else {
            // Jika Approved/Rejected: Tampilkan form & load komentar
            $('#commentForm').show();
            $('#commentRecipeId').val(id); 
            loadComments(id);
        }
    });
}

function openReviewModal(id) {
    $.getJSON(API_PATH + 'recipe.php', {action:'get_detail', id:id}, function(d){
        $('#r_title').text(d.title); 
        $('#r_author_container').html(`<div class="flex items-center gap-2 mb-3"><span class="font-bold">${d.author}</span></div>`);
        $('#r_desc').text(d.description);
        $('#r_ing').html(formatTextToLines(d.ingredients)); 
        $('#r_stp').html(formatTextToLines(d.steps));
        if(d.image) $('#r_image').attr('src', ASSETS_PATH + 'recipes/' + d.image).removeClass('hidden'); else $('#r_image').addClass('hidden');
        
        $('#btnApproveAction').data('id', d.id);
        $('#btnApproveAction').data('category', d.category); 
        $('#btnRejectAction').data('id', d.id);
        new bootstrap.Modal(document.getElementById('reviewModal')).show();
    });
}

function openEditModal(id) {
    $.getJSON(API_PATH+'recipe.php?action=get_categories', function(c){
        let o=''; c.forEach(i=>o+=`<option value="${i.name}">${i.name}</option>`); $('#editCategory').html(o);
        $.getJSON(API_PATH+'recipe.php', {action:'get_detail', id:id}, function(d){
            $('#editId').val(d.id); $('#editTitle').val(d.title); $('#editDesc').val(d.description); $('#editCategory').val(d.category);
            $('#editTime').val(d.cooking_time); $('#editServings').val(d.servings);
            let cleanIng = d.ingredients.replace(/<br\s*\/?>/gi, '\n');
            let cleanStp = d.steps.replace(/<br\s*\/?>/gi, '\n');
                
            // Hapus enter berlebih
            cleanIng = cleanIng.replace(/\n\s*\n/g, '\n');
            cleanStp = cleanStp.replace(/\n\s*\n/g, '\n');

            $('#editIng').val(cleanIng);
            $('#editStp').val(cleanStp);

            new bootstrap.Modal(document.getElementById('editRecipeModal')).show();
        });
    });
}

// ACTIONS
function processRecipe(id, act) {
    const msg = act=='approve' ? 'Setujui resep ini?' : 'Tolak resep ini?';
    showConfirm(msg, function(){
        $.post(API_PATH+'recipe.php', {action:act, id:id}, function(){
            let modalEl = document.getElementById('reviewModal');
            if(modalEl) bootstrap.Modal.getInstance(modalEl).hide();
            reloadAllLists();
            showToast('Berhasil diproses', 'success');
        }, 'json');
    }, function(){ /* cancelled */ });
}

function deleteRecipe(id) {
    showConfirm('Hapus permanen?', function(){
        $.post(API_PATH+'recipe.php', {action:'delete', id:id}, function(r){
            if(r.status=='success') {
                showToast('Resep berhasil dihapus', 'success');
                    
                $(`#card-${id}`).remove(); 
                    
                setTimeout(() => {
                    reloadAllLists();
                }, 100);
                    
            } else {
                showToast(r.message || 'Gagal menghapus', 'error');
            }
        }, 'json').fail(function() {
            showToast('Gagal koneksi ke server', 'error');
        });
    }, function(){ /* cancelled */ });
}

function deleteCategory(id) {
    showConfirm('Hapus kategori?', function(){
        $.post(API_PATH+'recipe.php', {action:'delete_category', id:id}, function(r){
            if(r.status=='success') { showToast('Kategori berhasil dihapus', 'success'); loadCategories(); reloadAllLists();} 
            else showToast(r.message,'error');
        }, 'json');
    }, function(){ /* cancelled */ });
}

// --- TOGGLE LIKE (DENGAN SVG) ---
// --- TOGGLE LIKE (FIXED: Tidak hilang di Dashboard) ---
function toggleLike(id, fp=false) { 
    // SVG DEFINITIONS
    const iconUnliked = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>`;
    const iconLiked = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" /></svg>`;

    let countSpan = $(`#likeCount-${id}`);
    let iconSpan = $(`#likeIcon-${id}`);
    let btn = countSpan.length ? countSpan.closest('button') : $(`.js-toggle-like[data-id="${id}"]`);
    let currentCount = parseInt(countSpan.text()) || 0;

    // 1. Ubah tampilan ikon & warna SECARA LANGSUNG (Optimistic UI)
    //    Ini supaya user merasa responsif tanpa nunggu server
    if(!fp) {
        if(btn.hasClass('text-red-500')){
            // User melakukan UNLIKE
            if(countSpan.length) countSpan.text(Math.max(0, currentCount - 1));
            btn.removeClass('text-red-500').addClass('text-gray-400');
            if(iconSpan.length) iconSpan.html(iconUnliked);
        } else {
            // User melakukan LIKE
            if(countSpan.length) countSpan.text(currentCount + 1);
            btn.removeClass('text-gray-400').addClass('text-red-500');
            if(iconSpan.length) iconSpan.html(iconLiked);
        }
    }

    // 2. Kirim request ke Server
    $.post(API_PATH+'recipe.php', {action:'toggle_like', recipe_id:id}, function(r){
        if(r && r.status === 'success') {
            
            // PERBAIKAN BUG DISINI:
            // Kita hanya menghapus kartu JIKA user sedang ada di halaman 'Liked Recipes' (fp == true)
            if(r.action === 'unliked' && fp === true) {
                if($(`#card-${id}`).length) {
                    $(`#card-${id}`).fadeOut(180, function(){ $(this).remove(); });
                }
                
                // Cek jika halaman jadi kosong setelah dihapus
                setTimeout(() => {
                    if($('#tabLikedRecipes').children().length === 0) loadLikedRecipes();
                }, 200);
            }

            // Sinkronisasi jumlah like dari server (opsional, untuk akurasi)
            if(typeof r.like_count !== 'undefined' && countSpan.length) countSpan.text(r.like_count);

        } else {
            // Jika gagal, kembalikan tampilan ke semula (Revert)
            let msg = (r && r.message) ? r.message : 'Gagal mengubah like';
            showToast(msg, 'error');
            if(fp) loadLikedRecipes(); // Reload jika error terjadi di tab liked
        }
    }, 'json').fail(function(){
        showToast('Gagal koneksi ke server', 'error');
    });
}

function loadComments(id) {
    // Tampilkan loading text sederhana
    $('#commentList').html('<div class="text-center py-2 text-gray-400 text-xs">Memuat...</div>');

    $.getJSON(API_PATH + 'comment.php', { action: 'list', recipe_id: id }, function(d) {
        
        console.log("DEBUG KOMENTAR:", d); // Cek console browser nanti

        let h = '';
        if (d.length == 0) {
            h = '<p class="text-gray-400 text-xs italic text-center py-2">Belum ada komentar.</p>';
        } else {
            d.forEach(c => {
                let av = getUserAvatarHtml(c.photo, c.name, "w-8 h-8", "text-xs");
                
                // LOGIC TOMBOL HAPUS
                let deleteBtn = '';
                if (c.can_delete === true) { 
                    deleteBtn = `
                    <button class="js-delete-comment text-gray-300 hover:text-red-500 transition ml-auto p-1 rounded hover:bg-red-50" data-id="${c.id}" title="Hapus Komentar">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                          <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-3.53 6.19a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-1.5 0v-6a.75.75 0 0 1 .75-.75Zm4 0a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-1.5 0v-6a.75.75 0 0 1 .75-.75Zm4 0a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-1.5 0v-6a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                        </svg>
                    </button>`;
                }

                h += `
                <div class="flex gap-3 items-start mb-3 animate-fade-in group">
                    ${av}
                    <div class="bg-gray-50 p-3 rounded-lg w-full border border-gray-100 relative hover:border-orange-100 transition">
                        <div class="flex justify-between items-start">
                            <div class="text-xs font-bold text-gray-700 mb-1">${c.name}</div>
                            ${deleteBtn}
                        </div>
                        <div class="text-sm text-gray-600 break-words pr-4 leading-snug">${c.comment}</div>
                        <div class="text-[10px] text-gray-400 mt-2 text-right border-t border-gray-100 pt-1">${c.created_at || ''}</div>
                    </div>
                </div>`;
            });
        }
        $('#commentList').html(h);
    }).fail(function() {
        $('#commentList').html('<p class="text-red-400 text-xs text-center">Gagal memuat komentar.</p>');
    });
}

function switchProfileTab(tab) {
    $('#btnMy').removeClass('text-orange-600 border-orange-600').addClass('text-gray-500 border-transparent');
    $('#btnLiked').removeClass('text-orange-600 border-orange-600').addClass('text-gray-500 border-transparent');
    $('#tabMyRecipes').addClass('hidden'); $('#tabLikedRecipes').addClass('hidden');
    if (tab === 'My') { $('#btnMy').removeClass('text-gray-500 border-transparent').addClass('text-orange-600 border-orange-600'); $('#tabMyRecipes').removeClass('hidden'); loadMyRecipes(); } 
    else { $('#btnLiked').removeClass('text-gray-500 border-transparent').addClass('text-orange-600 border-orange-600'); $('#tabLikedRecipes').removeClass('hidden'); loadLikedRecipes(); }
}

// HELPERS
function getRecipeImgHtml(img, t, h="h-40") {
    if(img && img!=="") {
        return `<img src="${ASSETS_PATH}recipes/${img}?t=${Date.now()}" class="w-full ${h} object-cover bg-gray-100 group-hover:scale-105 transition duration-500">`;
    }
    return `
    <div class="w-full ${h} bg-gray-50 flex items-center justify-center text-gray-300 group-hover:scale-105 transition duration-500">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
        </svg>
    </div>`;
}

// --- FUNGSI AVATAR ---
function getUserAvatarHtml(p, n, s="w-8 h-8", t="text-xs") {
    let nm = n || "U";
    
    let profileFolder = 'users/'; 

    if(p && p!=="") {
        return `<img src="${ASSETS_PATH}${profileFolder}${p}?t=${Date.now()}" class="${s} rounded-full object-cover border border-gray-200 shadow-sm">`;
    }
    return `
    <div class="${s} rounded-full bg-gray-100 flex items-center justify-center text-gray-400 border border-gray-200 shadow-sm overflow-hidden">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-full h-full p-1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
        </svg>
    </div>`;
}

// --- RENDER CARD (DENGAN SVG) ---
function renderCard(row, my=false, liked=false) {
    let badge = my ? `<span class="bg-gray-100 text-gray-600 text-[10px] px-2 py-1 rounded-full font-bold uppercase mb-2 inline-block border">${row.status}</span>` : '';
    let imgHtml = getRecipeImgHtml(row.image, row.title, "h-48"); 
    let userAvatar = getUserAvatarHtml(row.user_photo, row.author, "w-6 h-6", "text-[10px]");
    let actionBtn = '';
    
    if(my) {
        actionBtn = `
        <div class="px-4 pb-4 mt-auto pt-3 border-t border-gray-50 flex gap-2">
            <button class="js-edit-recipe flex-1 text-xs bg-blue-50 text-blue-600 py-2 rounded font-bold hover:bg-blue-100 border border-blue-200 transition" data-id="${row.id}">✎ Edit</button>
            <button class="js-delete-recipe flex-1 text-xs bg-red-50 text-red-600 py-2 rounded font-bold hover:bg-red-100 border border-red-200 transition" data-id="${row.id}">🗑️ Hapus</button>
        </div>`;
    } else if(liked) {
        actionBtn = `
        <div class="px-4 pb-4 mt-auto pt-3 border-t border-gray-50">
            <button class="js-toggle-like w-full border border-red-200 text-red-500 text-sm py-2 rounded hover:bg-red-50 font-bold transition" data-id="${row.id}" data-tab="liked">💔 Hapus Like</button>
        </div>`;
    } else {
        // --- ICON DEFINITIONS (SVG) ---
        const iconUnliked = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>`;
        const iconLiked = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" /></svg>`;

        let isLiked = row.is_liked > 0;
        let currentIcon = isLiked ? iconLiked : iconUnliked;
        let colorClass = isLiked ? 'text-red-500' : 'text-gray-400 hover:text-red-500';

        actionBtn = `
        <div class="px-4 pb-4 pt-3 mt-auto border-t border-gray-50 flex justify-between items-center bg-white">
            <div class="flex items-center gap-2">
                ${userAvatar}
                <span class="text-xs text-gray-600 font-medium truncate max-w-[120px]">${row.author}</span>
            </div>
            <button class="js-toggle-like ${colorClass} flex items-center gap-1 transition duration-200" data-id="${row.id}">
                <span id="likeIcon-${row.id}">${currentIcon}</span>
                <span id="likeCount-${row.id}" class="text-sm font-bold">${row.like_count||0}</span>
            </button>
        </div>`;
    }
    
    return `
    <div id="card-${row.id}" class="bg-white rounded-xl shadow-sm hover:shadow-lg transition duration-300 border border-gray-100 overflow-hidden flex flex-col h-full group relative">
        <div class="js-open-detail cursor-pointer flex-grow" data-id="${row.id}">
            <div class="overflow-hidden">${imgHtml}</div>
            <div class="px-4 pb-3 pt-3">
                ${badge}
                <div class="mb-1"><span class="text-[10px] font-bold uppercase tracking-wider text-orange-600 bg-orange-100 px-2 rounded">${row.category}</span></div>
                <h3 class="text-lg font-bold text-gray-800 leading-tight mb-2 group-hover:text-orange-600 transition line-clamp-2">${row.title}</h3>
                <p class="text-sm text-gray-500 line-clamp-2">${row.description}</p>
            </div>
        </div>
        ${actionBtn}
    </div>`;
}
