$(document).ready(function() {
    let path = window.location.pathname;
    let rootIndex = path.indexOf('/views/');
    
    // Fallback jika tidak ketemu (misal di root)
    let root = (rootIndex > -1) ? path.substring(0, rootIndex) : '/FoodHub-Tekweb';
    
    const API_PATH = root + '/api/';
    const ASSETS_PATH = root + '/assets/uploads/';

    console.log("System Ready. API:", API_PATH);

    $('#tabLikedRecipes').addClass('hidden');
    if ($('#btnMy').length) {
        $('#btnMy').removeClass('text-gray-500 border-transparent').addClass('text-orange-600 border-orange-600');
    }

    $('#btnMy').on('click', function() {
        switchProfileTab('My');
    });

    $('#btnLiked').on('click', function() {
        switchProfileTab('Liked');
    });
    
    // buat buka detail kartu (User & Admin)
    $(document).on('click', '.js-open-detail', function(e) {
        // Jangan buka detail jika yang diklik adalah tombol (Edit/Hapus/Like)
        if ($(e.target).closest('button').length) return;
        
        let id = $(this).data('id');
        openDetailModal(id);
    });

    // kalau mau review yang lg pending (Admin)
    $(document).on('click', '.js-open-review', function(e) {
        let id = $(this).data('id');
        openReviewModal(id);
    });

    // tombol edit (User & Admin)
    $(document).on('click', '.js-edit-recipe', function(e) {
        e.stopPropagation(); // Cegah bubbling ke kartu
        let id = $(this).data('id');
        openEditModal(id);
    });

    // tombol hapus (User & Admin)
    $(document).on('click', '.js-delete-recipe', function(e) {
        e.stopPropagation();
        let id = $(this).data('id');
        deleteRecipe(id);
    });

    // tombol hapus kategori (Admin)
    $(document).on('click', '.js-delete-cat', function(e) {
        let id = $(this).data('id');
        deleteCategory(id);
    });

    // tombol like
    $(document).on('click', '.js-toggle-like', function(e) {
        e.stopPropagation();
        let id = $(this).data('id');
        let isLikedTab = $(this).data('tab') === 'liked';
        toggleLike(id, isLikedTab);
    });

    // admin actions
    $('#btnApproveAction').click(function() { processRecipe($(this).data('id'), 'approve'); });
    $('#btnRejectAction').click(function() { processRecipe($(this).data('id'), 'reject'); });

    // login
    $('#loginForm').submit(function(e){
        e.preventDefault();
        let btn = $(this).find('button'); let txt = btn.text();
        btn.text('Loading...').prop('disabled', true);

        $.ajax({
            url: API_PATH + 'auth.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(res){
                if(res.status === 'success') {
                    showToast('Login Berhasil!', 'success');
                    setTimeout(() => {
                        let role = res.role;
                        let inAdmin = path.includes('/admin/');
                        
                        if(role === 'admin') {
                            window.location.href = inAdmin ? 'dashboard.php' : root + '/views/admin/dashboard.php';
                        } else {
                            window.location.href = root + '/views/dashboard.php';
                        }
                    }, 1000);
                } else { showToast(res.message, 'error'); }
            },
            error: function(xhr) { console.error(xhr); showToast('Error Koneksi', 'error'); },
            complete: function() { btn.text(txt).prop('disabled', false); }
        });
    });

    // register
    $('#registerForm').submit(function(e){
        e.preventDefault();
        let btn = $(this).find('button'); let txt = btn.text(); btn.text('Proses...').prop('disabled',true);
        $.ajax({ url: API_PATH + 'auth.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(res){
                if(res.status === 'success') { alert(res.message); window.location.href = 'login.php'; }
                else showToast(res.message, 'error');
            },
            complete: function() { btn.text(txt).prop('disabled', false); }
        });
    });

    // add recipe (Global)
    $('#addRecipeForm').submit(function(e){
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]'); btn.prop('disabled', true).text('Mengirim...');
        $.ajax({
            url: API_PATH + 'recipe.php', type: 'POST', data: new FormData(this), contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    showToast(res.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('addRecipeModal')).hide();
                    $('#addRecipeForm')[0].reset();
                    reloadAllLists();
                } else showToast(res.message, 'error');
            },
            complete: function() { btn.prop('disabled', false).text('Terbitkan'); }
        });

    });

    // edit recipe (Global)
    $('#editRecipeForm').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: API_PATH + 'recipe.php', type: 'POST', data: new FormData(this), contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    showToast(res.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('editRecipeModal')).hide();
                    reloadAllLists();
                } else showToast(res.message, 'error');
            }
        });
    });

    // comment
    $('#commentForm').submit(function(e){
        e.preventDefault();
        $.post(API_PATH + 'comment.php', $(this).serialize(), function(res){
            if(res.status === 'success') {
                $('#commentForm input[name="comment"]').val('');
                loadComments($('#commentRecipeId').val());
            } else showToast(res.message, 'error');
        }, 'json');
    });

    // update profile
    $('#editProfileForm').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: API_PATH + 'profile.php', 
            type: 'POST', 
            data: new FormData(this), 
            contentType: false, 
            processData: false, 
            dataType: 'json',
            success: function(res){
                if(res.status === 'success'){
                    bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
                    loadProfileInfo(); showToast('Profil Update!', 'success');
                } else showToast(res.message, 'error');
            }
        });
    });

    // add category (Admin)
    $('#addCategoryForm').submit(function(e){
        e.preventDefault();
        $.post(API_PATH + 'recipe.php', $(this).serialize(), function(res){
            if(res.status === 'success'){ showToast('Kategori OK', 'success'); $('#addCategoryForm')[0].reset(); loadCategories(); }
            else showToast(res.message, 'error');
        }, 'json');
    });

    // listeners search
    $('#searchRecipe').on('keyup', function(){ loadRecipes($(this).val(), $('#filterCategory').val()); });
    $('#filterCategory').on('change', function(){ loadRecipes($('#searchRecipe').val(), $(this).val()); });


    // Jalankan fungsi load jika elemennya ada di halaman
    if ($('#recipeList').length) loadRecipes();
    if ($('#pendingList').length) loadPendingRecipes();
    if ($('#pendingListAdmin').length) { loadPendingRecipes(); loadAdminAllRecipes(); }
    if ($('#profileName').length) { loadProfileInfo(); loadMyRecipes(); loadLikedRecipes(); }
    loadCategories(); // Dropdown kategori



    function reloadAllLists() {
        if($('#recipeList').length) loadRecipes();
        if($('#pendingList').length) loadPendingRecipes();
        if($('#pendingListAdmin').length) loadPendingRecipes();
        if($('#adminAllRecipes').length) loadAdminAllRecipes();
        if($('#tabMyRecipes').length) loadMyRecipes();
    }

    // loaders
    function loadRecipes(k='', c='') {
        $.getJSON(API_PATH + 'recipe.php', {action:'read', search:k, category:c}, function(d){
            let h = (d.length == 0) ? '<div class="col-span-full text-center py-10 text-gray-400 border border-dashed rounded">Kosong</div>' : '';
            d.forEach(r => { h += renderCard(r); });
            $('#recipeList').html(h);
        });
    }
    function loadMyRecipes() {
        $.getJSON(API_PATH + 'profile.php?action=get_my_recipes', function(d){
            let h = (d.length == 0) ? '<div class="col-span-full text-center py-10 text-gray-400 border border-dashed rounded">Belum ada resep.</div>' : '';
            d.forEach(r => { h += renderCard(r, true); });
            $('#tabMyRecipes').html(h);
        });
    }
    function loadLikedRecipes() {
        $.getJSON(API_PATH + 'profile.php?action=get_liked_recipes', function(d){
            let h = (d.length == 0) ? '<div class="col-span-full text-center py-10 text-gray-400 border border-dashed rounded">Kosong.</div>' : '';
            d.forEach(r => { h += renderCard(r, false, true); });
            $('#tabLikedRecipes').html(h);
        });
    }
    function loadPendingRecipes() {
        $.getJSON(API_PATH + 'recipe.php?action=read_pending', function(d){
            let h = (d.length == 0) ? '<div class="text-center py-4 text-sm text-gray-400">Tidak ada data.</div>' : '';
            d.forEach(i => {
                let av = getUserAvatarHtml(i.user_photo, i.author, "w-8 h-8", "text-xs");
                // Class: js-open-review
                h += `<div class="js-open-review flex justify-between items-center border-b p-3 hover:bg-gray-50 cursor-pointer transition bg-white rounded mb-1" data-id="${i.id}">
                        <div class="flex items-center gap-3">${av}<div><div class="font-bold text-sm line-clamp-1">${i.title}</div><div class="text-xs text-gray-500">${i.author}</div></div></div>
                        <span class="text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded-full font-bold">Review ></span>
                      </div>`;
            });
            $('#pendingList, #pendingListAdmin').html(h);
        });
    }
    function loadAdminAllRecipes() {
        $.getJSON(API_PATH + 'recipe.php?action=read_all_admin', function(d){
            let h = (d.length == 0) ? '<div class="col-span-full text-center text-gray-400">Kosong</div>' : '';
            d.forEach(r => {
                let statusColor = r.status=='approved'?'green':(r.status=='rejected'?'red':'yellow');
                let badge = `<span class="bg-${statusColor}-100 text-${statusColor}-800 text-[10px] px-2 py-1 rounded uppercase font-bold absolute top-2 right-2 shadow-sm z-10">${r.status}</span>`;
                let img = getRecipeImgHtml(r.image, r.title, "h-40");
                // Class: js-open-detail, js-edit-recipe, js-delete-recipe
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
                        <button class="js-edit-recipe flex-1 bg-white border border-blue-200 text-blue-600 text-xs py-2 rounded-lg font-bold hover:bg-blue-50" data-id="${r.id}">Edit</button>
                        <button class="js-delete-recipe flex-1 bg-white border border-red-200 text-red-600 text-xs py-2 rounded-lg font-bold hover:bg-red-50" data-id="${r.id}">Hapus</button>
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
             let av = getUserAvatarHtml(d.photo, d.name, "w-full h-full", "text-5xl");
             $('#profileAvatarContainer').html(av);
             // Update Navbar
             if($('#navProfileLink').length) {
                 let navAv = getUserAvatarHtml(d.photo, d.name, "w-8 h-8", "text-sm");
                 $('#navProfileLink div').replaceWith(navAv); $('#navProfileLink img').replaceWith(navAv);
             }
        });
    }

    // modal openers
    
    function openDetailModal(id) {
        $('#view_title').text('Loading...'); new bootstrap.Modal(document.getElementById('detailModal')).show();
        $.getJSON(API_PATH + 'recipe.php', {action:'get_detail', id:id}, function(d){
            $('#view_title').text(d.title); $('#view_category').text(d.category);
            $('#view_desc').text(d.description); $('#view_time').text(d.cooking_time||"-"); $('#view_servings').text(d.servings||"-");
            $('#view_ing').html(d.ingredients); $('#view_stp').html(d.steps);
            $('#view_author_container').html(`<span class="text-gray-500 text-sm">Oleh: <span class="font-bold text-gray-800">${d.author}</span></span>`);
            if(d.image) { $('#view_image').attr('src', ASSETS_PATH + 'recipes/' + d.image).removeClass('hidden'); $('#view_placeholder').addClass('hidden'); }
            else { $('#view_image').addClass('hidden'); $('#view_placeholder').text(d.title.charAt(0)).removeClass('hidden'); }
            $('#commentRecipeId').val(id); loadComments(id);
        });
    }

    function openReviewModal(id) {
        $.getJSON(API_PATH + 'recipe.php', {action:'get_detail', id:id}, function(d){
            $('#r_title').text(d.title); 
            let av = getUserAvatarHtml(d.user_photo, d.author, "w-8 h-8", "text-xs");
            $('#r_author_container').html(`<div class="flex items-center gap-2 mb-3">${av} <span class="font-bold">${d.author}</span></div>`);
            $('#r_desc').text(d.description); $('#r_ing').html(d.ingredients); $('#r_stp').html(d.steps);
            if(d.image) $('#r_image').attr('src', ASSETS_PATH + 'recipes/' + d.image).removeClass('hidden'); else $('#r_image').addClass('hidden');
            
            // Set ID ke tombol di modal
            $('#btnApproveAction').data('id', d.id);
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
                $('#editIng').val(d.ingredients.replaceAll('<br />','\n')); $('#editStp').val(d.steps.replaceAll('<br />','\n'));
                new bootstrap.Modal(document.getElementById('editRecipeModal')).show();
            });
        });
    }

    // actions

    function processRecipe(id, act) {
        if(confirm(act=='approve'?'Setujui resep ini?':'Tolak resep ini?')) {
            $.post(API_PATH+'recipe.php', {action:act, id:id}, function(){
                bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
                reloadAllLists();
                showToast('Berhasil diproses', 'success');
            }, 'json');
        }
    }

    function deleteRecipe(id) {
        if(confirm("Hapus permanen?")) {
            $.post(API_PATH+'recipe.php', {action:'delete', id:id}, function(r){
                if(r.status=='success') $(`#card-${id}`).fadeOut(); else showToast(r.message,'error');
            }, 'json');
        }
    }

    function deleteCategory(id) {
        if(confirm("Hapus kategori?")) {
            $.post(API_PATH+'recipe.php', {action:'delete_category', id:id}, function(r){
                if(r.status=='success') loadCategories(); else showToast(r.message,'error');
            }, 'json');
        }
    }

    function toggleLike(id, isLikedTab) {
        let s=$(`#likeCount-${id}`), c=parseInt(s.text())||0, btn=s.parent();
        if(!isLikedTab) { if(btn.hasClass('text-red-500')){s.text(Math.max(0,c-1)); btn.removeClass('text-red-500');} else {s.text(c+1); btn.addClass('text-red-500');} }
        $.post(API_PATH+'recipe.php', {action:'toggle_like', recipe_id:id}, function(r){
            if(isLikedTab) $(`#card-${id}`).fadeOut();
        }, 'json');
    }

    function loadComments(id) {
        $.getJSON(API_PATH+'comment.php', {action:'list', recipe_id:id}, function(d){
            let h=''; if(d.length==0)h='<p class="text-gray-400 text-xs italic">Belum ada komentar.</p>';
            d.forEach(c=>{ let av=getUserAvatarHtml(c.photo,c.name,"w-8 h-8","text-xs"); h+=`<div class="flex gap-3 items-start mb-3 animate-fade-in">${av}<div class="bg-gray-50 p-3 rounded-lg w-full border border-gray-100"><div class="text-xs font-bold text-gray-700 mb-1">${c.name}</div><div class="text-sm text-gray-600">${c.comment}</div></div></div>`; });
            $('#commentList').html(h);
        });
    }

    function switchProfileTab(tab) {
        // reset button styles
        $('#btnMy').removeClass('text-orange-600 border-orange-600').addClass('text-gray-500 border-transparent');
        $('#btnLiked').removeClass('text-orange-600 border-orange-600').addClass('text-gray-500 border-transparent');

        // hide all tabs
        $('#tabMyRecipes').addClass('hidden');
        $('#tabLikedRecipes').addClass('hidden');

        // activate selected tab
        if (tab === 'My') {
            $('#btnMy').removeClass('text-gray-500 border-transparent').addClass('text-orange-600 border-orange-600');
            $('#tabMyRecipes').removeClass('hidden');
            loadMyRecipes();
        } else {
            $('#btnLiked').removeClass('text-gray-500 border-transparent').addClass('text-orange-600 border-orange-600');
            $('#tabLikedRecipes').removeClass('hidden');
            loadLikedRecipes();
        }
    }

    // ui helpers
    function getRecipeImgHtml(img, t, h="h-40") {
        if(img && img!=="") return `<img src="${ASSETS_PATH}recipes/${img}?t=${Date.now()}" class="w-full ${h} object-cover bg-gray-100 group-hover:scale-105 transition duration-500">`;
        return `<div class="${h} bg-orange-50 flex items-center justify-center text-orange-300 font-bold text-5xl group-hover:scale-105 transition duration-500 select-none">${t.charAt(0)}</div>`;
    }
    function getUserAvatarHtml(p, n, s="w-8 h-8", t="text-xs") {
        let nm = n || "U";
        if(p && p!=="") return `<img src="${ASSETS_PATH}${p}?t=${Date.now()}" class="${s} rounded-full object-cover border border-gray-200 shadow-sm">`;
        return `<div class="${s} rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold ${t} border border-white shadow-sm select-none">${nm.charAt(0).toUpperCase()}</div>`;
    }
    function showToast(m,t='info'){let c=t=='success'?'border-green-500 text-green-700 bg-green-50':'border-red-500 text-red-700 bg-red-50';let d=$(`<div class="toast-msg fixed top-5 right-5 z-50 p-4 rounded shadow-lg border-l-4 ${c} bg-white flex items-center gap-2 transition duration-300 transform translate-x-full"><span>${t=='success'?'✅':'⚠️'}</span> <b>${m}</b></div>`);$('body').append(d);setTimeout(()=>d.removeClass('translate-x-full'),10);setTimeout(()=>d.addClass('translate-x-full'),3000);setTimeout(()=>d.remove(),3300);}
    
    // render card
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
            let color = row.is_liked > 0 ? 'text-red-500' : 'text-gray-400';
            actionBtn = `
            <div class="px-4 pb-4 pt-3 mt-auto border-t border-gray-50 flex justify-between items-center bg-white">
                <div class="flex items-center gap-2">
                    ${userAvatar}
                    <span class="text-xs text-gray-600 font-medium truncate max-w-[120px]">${row.author}</span>
                </div>
                <button class="js-toggle-like ${color} hover:text-red-500 flex items-center gap-1 text-sm font-bold transition" data-id="${row.id}">❤️ <span id="likeCount-${row.id}">${row.like_count||0}</span></button>
            </div>`;
        }
        
        // Class: js-open-detail
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

});