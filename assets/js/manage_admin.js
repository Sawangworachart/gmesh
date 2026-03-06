/**
 * ไฟล์: assets/js/manage_admin.js
 * คำอธิบาย: ควบคุมการทำงานหน้า Edit User (Super Admin)
 * จัดการ Modal, API Request, และ SweetAlert
 */

const API_URL = 'manage_admin.php?api=true';

$(document).ready(function() {
    loadData();

    // Mouse Glow Effect
    const cards = document.querySelectorAll('.stat-card, .header-banner-custom');
    cards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--x', `${x}px`);
            card.style.setProperty('--y', `${y}px`);
        });
    });

    // Handle Form Submit
    $('#userForm').submit(function(e) {
        e.preventDefault();

        const pwd = $('#password').val();
        const userId = $('#userId').val();

        // Validation: รหัสผ่านต้องอย่างน้อย 6 ตัว (กรณีสร้างใหม่ หรือ แก้ไขแล้วกรอกรหัส)
        if ((userId == 0 || pwd !== '') && pwd.length < 6) {
            Swal.fire({
                icon: 'warning',
                title: 'รหัสผ่านไม่ถูกต้อง',
                text: 'รหัสผ่านต้องยาวอย่างน้อย 6 ตัวอักษร',
                confirmButtonColor: '#f59e0b'
            });
            return;
        }

        const formData = $(this).serialize();
        $.post(API_URL, formData, function(res) {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                closeModal();
                loadData();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('Connection Error', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
        });
    });

    // Close Modal Outside Click
    $(window).click(function(e) {
        if ($(e.target).hasClass('modal-overlay')) closeModal();
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

            let html = '';
            if (res.data.length === 0) {
                html = '<tr><td colspan="6" class="text-center" style="padding:40px; color:#94a3b8;"><i class="fas fa-folder-open fa-2x"></i><br>ไม่พบข้อมูลผู้ใช้งาน</td></tr>';
            } else {
                res.data.forEach((user, index) => {
                    // Badge Role
                    let roleBadge = '';
                    if (user.role === 'superadmin') {
                        roleBadge = '<span class="badge badge-super"><i class="fas fa-crown"></i> Super Admin</span>';
                    } else if (user.role === 'admin') {
                        roleBadge = '<span class="badge badge-admin"><i class="fas fa-shield-alt"></i> Admin</span>';
                    } else {
                        roleBadge = '<span class="badge badge-user"><i class="fas fa-user"></i> User</span>';
                    }

                    // Avatar
                    const avatarLetter = user.username ? user.username.charAt(0).toUpperCase() : '?';

                    // Badge Status
                    let statusBadge = user.status == 1 
                        ? '<span class="badge badge-active"><i class="fas fa-check-circle"></i> Active</span>' 
                        : '<span class="badge badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>';

                    html += `
                        <tr class="user-row" data-status="${user.status}">
                            <td><span style="color:#94a3b8; font-weight:600;">#${index + 1}</span></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <div class="user-avatar">${avatarLetter}</div>
                                    <div style="font-weight:600; color:#334155">${user.username}</div>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn-icon btn-key reset-pwd" data-id="${user.id}" title="รีเซ็ตรหัสผ่าน">
                                    <i class="fas fa-key"></i>
                                </button>
                            </td>
                            <td>${roleBadge}</td>
                            <td>${statusBadge}</td>
                            <td class="text-center">
                                <button class="btn-icon btn-edit" onclick="editUser(${user.id})" title="แก้ไข">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-icon btn-delete" onclick="deleteUser(${user.id})" title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            $('#tableBody').html(html);
            $('#noData').toggle(res.data.length === 0);
            
            // Re-apply filter if active
            const activeStatus = $('.stat-card.active').attr('onclick').match(/'([^']+)'/)[1];
            if(activeStatus) filterByStatus(activeStatus, $('.stat-card.active')[0]);
        }
    }, 'json');
}

// กรองตามสถานะ (Active/Inactive)
function filterByStatus(status, el) {
    $('.stat-card').removeClass('active');
    $(el).addClass('active');

    const rows = $("#tableBody tr");
    if (status === 'all') {
        rows.show();
    } else {
        rows.hide().filter(function() {
            return $(this).attr('data-status') == status;
        }).fadeIn(200);
    }
}

// ค้นหาในตาราง
function filterTable() {
    var value = $('#searchInput').val().toLowerCase();
    $("#tableBody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
}

// เปิด Modal
function openModal(mode) {
    $('#userForm')[0].reset();
    $('#userId').val('0');
    $('#password').attr('required', true);
    $('#passwordHint').hide();

    if (mode === 'create') {
        $('#modalTitle').text('เพิ่มผู้ใช้งานใหม่');
        $('.header-subtitle').text('กำหนดชื่อผู้ใช้และสิทธิ์การเข้าถึง');
        $('#saveBtn').html('<i class="fas fa-check-circle"></i> บันทึกข้อมูล');
        $('#role').val('user');
    }
    $('.modal-overlay').css('display', 'flex');
    setTimeout(() => $('.modal-overlay').addClass('show'), 10);
}

// ปิด Modal
function closeModal() {
    $('.modal-overlay').removeClass('show');
    setTimeout(() => $('.modal-overlay').css('display', 'none'), 300);
}

// Toggle Password Visibility
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

// แก้ไขผู้ใช้งาน
function editUser(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function(res) {
        if (res.success) {
            const d = res.data;
            $('#userId').val(d.id);
            $('#username').val(d.username);
            $('#role').val(d.role);
            $('#status').val(d.status);
            $('#password').val('').removeAttr('required');
            $('#passwordHint').show();

            $('#modalTitle').text('แก้ไขข้อมูลผู้ใช้งาน');
            $('.header-subtitle').text('รหัสผู้ใช้: ' + d.id);
            $('#saveBtn').html('<i class="fas fa-save"></i> อัปเดตข้อมูล');
            
            $('.modal-overlay').css('display', 'flex');
            setTimeout(() => $('.modal-overlay').addClass('show'), 10);
        }
    }, 'json');
}

// ลบผู้ใช้งาน
function deleteUser(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลผู้ใช้งานนี้จะถูกลบถาวรและไม่สามารถกู้คืนได้!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete', id: id }, function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบสำเร็จ',
                        text: res.message,
                        timer: 1000,
                        showConfirmButton: false
                    });
                    loadData();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
            });
        }
    });
}

// รีเซ็ตรหัสผ่าน
$(document).on('click', '.reset-pwd', function() {
    const userId = $(this).data('id');

    Swal.fire({
        title: 'รีเซ็ตรหัสผ่าน',
        icon: 'info',
        input: 'password',
        inputLabel: 'กำหนดรหัสผ่านใหม่',
        inputPlaceholder: 'อย่างน้อย 6 ตัวอักษร',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#8b5cf6',
        cancelButtonColor: '#94a3b8',
        inputValidator: (value) => {
            if (!value || value.length < 6) {
                return 'รหัสผ่านต้องยาวอย่างน้อย 6 ตัวอักษร';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, {
                action: 'reset_password',
                id: userId,
                password: result.value
            }, function(res) {
                if (res.success) {
                    Swal.fire('สำเร็จ', 'รีเซ็ตรหัสผ่านเรียบร้อย', 'success');
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }, 'json');
        }
    });
});
