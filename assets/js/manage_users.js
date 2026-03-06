/**
 * ไฟล์: assets/js/manage_users.js
 * คำอธิบาย: ควบคุมการทำงานหน้าจัดการผู้ใช้งาน (User Management)
 */

const API_URL = 'manage_users.php?api=true';

$(document).ready(function() {
    loadData();

    // Mouse Glow Effect สำหรับ Card
    const cards = document.querySelectorAll('.stat-card, .card');
    cards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--x', `${x}px`);
            card.style.setProperty('--y', `${y}px`);
        });
    });
});

// โหลดข้อมูลผู้ใช้งาน
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
                                <button class="action-btn btn-edit" onclick="editUser(${user.id})" title="แก้ไข"><i class="fas fa-pencil-alt"></i></button>
                                <button class="action-btn btn-delete" onclick="deleteUser(${user.id})" title="ลบ"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    `;
                });
            }
            $('#tableBody').html(html);
        }
    }, 'json');
}

// เปิด Modal
function openModal(mode) {
    $('#userForm')[0].reset();
    $('#userId').val('0');
    $('#password').attr('required', true); // Create needs password
    $('#passwordHint').hide();
    
    if (mode === 'create') {
        $('#modalTitle').html('<i class="fas fa-user-plus"></i> เพิ่มผู้ใช้งานใหม่');
        $('#saveBtn').html('<i class="fas fa-save"></i> บันทึก');
        $('#role').val('user'); // Default role
    }
    $('#userModal').addClass('show');
}

// ปิด Modal
function closeModal() {
    $('#userModal').removeClass('show');
}

// Toggle การแสดง Password
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

// ดึงข้อมูลเพื่อแก้ไข
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
            
            $('#modalTitle').html('<i class="fas fa-user-edit"></i> แก้ไขผู้ใช้งาน (ID: '+d.id+')');
            $('#saveBtn').html('<i class="fas fa-save"></i> อัปเดต');
            $('#userModal').addClass('show');
        }
    }, 'json');
}

// บันทึกข้อมูล
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

// ลบผู้ใช้งาน
function deleteUser(id) {
    // ใช้ Swal แทน global_delete.js เพื่อความชัวร์ หรือถ้ามี global_delete.js ให้ใช้ก็ได้
    // ในที่นี้เขียนแยกเพื่อความ self-contained ของไฟล์นี้
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลผู้ใช้งานนี้จะถูกลบถาวร!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ลบข้อมูล'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete', id: id }, function(res) {
                if (res.success) {
                    Swal.fire('ลบสำเร็จ!', res.message, 'success');
                    loadData();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}

// ค้นหาในตาราง
function filterTable() {
    var value = $('#searchInput').val().toLowerCase();
    $("#tableBody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
}

// ปิด Modal เมื่อคลิกข้างนอก
$(window).click(function(e) {
    if ($(e.target).is('#userModal')) closeModal();
});
