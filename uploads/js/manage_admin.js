// manage_admin.js
const API_URL = 'manage_admin.php?api=true';

// -----------------------------------------------------
// Core Functions (Global Scope - Callable from HTML)
// -----------------------------------------------------

function loadData() {
    $.post(API_URL, { action: 'fetch_all' }, function(res) {
        if (res.success) {
            // Update Stats
            $('#statTotal').text(res.stats.total);
            $('#statActive').text(res.stats.active);
            $('#statInactive').text(res.stats.inactive);

            // Render Table
            let html = '';
            if (res.data.length === 0) {
                html = '<tr><td colspan="5" class="text-center" style="padding:30px; color:#999;">ไม่พบข้อมูลผู้ใช้งาน</td></tr>';
            } else {
                res.data.forEach(user => {
                    // Status Badge
                    let statusBadge = user.status == 1 
                        ? '<span class="badge status-active"><i class="fas fa-check-circle"></i> Active</span>' 
                        : '<span class="badge status-inactive"><i class="fas fa-times-circle"></i> Inactive</span>';
                    
                    // Role Badge
                    let roleBadge = user.role === 'admin'
                        ? '<span class="badge role-admin"><i class="fas fa-user-shield"></i> Admin</span>'
                        : '<span class="badge role-user"><i class="fas fa-user"></i> User</span>';

                    let avatarLetter = user.username.charAt(0).toUpperCase();

                    // การเรียกใช้ editUser และ deleteUser ยังคงทำงานได้
                    html += `
                        <tr>
                            <td>#${user.id}</td>
                            <td>
                                <div class="user-row-flex">
                                    <div class="user-avatar">${avatarLetter}</div>
                                    <div style="font-weight:600; color:#333">${user.username}</div>
                                </div>
                            </td>
                            <td>${roleBadge}</td>
                            <td>${statusBadge}</td>
                            <td class="text-center">
                                <button class="action-btn btn-edit" onclick="editUser(${user.id})"><i class="fas fa-pencil-alt"></i></button>
                                <button class="action-btn btn-delete" onclick="deleteUser(${user.id})"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    `;
                });
            }
            $('#tableBody').html(html);
        }
    }, 'json');
}

function openModal(mode) {
    $('#userForm')[0].reset();
    $('#userId').val('0');
    $('#password').attr('required', true); // Create needs password
    $('#passwordHint').hide();
    
    // Reset password field type
    $('#password').attr('type', 'password');
    $('.toggle-password').removeClass('fa-eye-slash').addClass('fa-eye');
    
    if (mode === 'create') {
        $('#modalTitle').html('<i class="fas fa-user-plus"></i> เพิ่มผู้ใช้งานใหม่');
        $('#saveBtn').html('<i class="fas fa-save"></i> บันทึก');
        $('#role').val('user'); // Default role
    }
    $('#userModal').addClass('show');
}

function closeModal() {
    $('#userModal').removeClass('show');
}

function togglePassword() {
    const input = $('#password');
    const icon = $('.toggle-password');
    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        input.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

function editUser(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function(res) {
        if (res.success) {
            const d = res.data;
            $('#userId').val(d.id);
            $('#username').val(d.username);
            $('#role').val(d.role); // Set role
            $('#status').val(d.status);
            
            // Edit mode config
            $('#password').val('').removeAttr('required');
            $('#passwordHint').show();
            
            // Reset password field type
            $('#password').attr('type', 'password');
            $('.toggle-password').removeClass('fa-eye-slash').addClass('fa-eye');
            
            $('#modalTitle').html('<i class="fas fa-user-edit"></i> แก้ไขผู้ใช้งาน (ID: '+d.id+')');
            $('#saveBtn').html('<i class="fas fa-save"></i> อัปเดต');
            $('#userModal').addClass('show');
        }
    }, 'json');
}

// [MODIFIED] ฟังก์ชันสำหรับเรียกใช้ global_delete.js
function deleteUser(id) {
    // เรียกฟังก์ชันกลาง: ส่ง ID, URL API ปัจจุบัน, และ Callback
    confirmDelete(id, API_URL, function() {
        // Callback: เมื่อลบสำเร็จ ให้โหลดตารางใหม่
        loadData();
    });
}

function filterTable() {
    var value = $('#searchInput').val().toLowerCase();
    $("#tableBody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
}

// -----------------------------------------------------
// Initialization and Event Handlers (Document Ready)
// -----------------------------------------------------

$(document).ready(function() {
    loadData();

    $('#userForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.post(API_URL, formData, function(res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false });
                closeModal();
                loadData();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        });
    });

    $(window).click(function(e) {
        if ($(e.target).is('#userModal')) closeModal();
    });
});