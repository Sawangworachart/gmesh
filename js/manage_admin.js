// js/manage_admin.js
const API_URL = 'manage_admin.php?api=true';

$(document).ready(function() {
    loadData();

    $('#userForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const userId = $('#userId').val();
        const password = $('#password').val();

        if ((userId === '0' || password !== '') && password.length < 6) {
            Swal.fire('รหัสผ่านไม่ถูกต้อง', 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร', 'warning');
            return;
        }

        $.post(API_URL, formData, function(res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false });
                closeModal();
                loadData();
            } else {
                Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
        });
    });

    $('#searchInput').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $("#tableBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $(document).on('click', '.toggle-password', function() {
        const input = $(this).prev('input');
        const icon = $(this);
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});

function loadData() {
    $.post(API_URL, { action: 'fetch_all' }, function(res) {
        if (res.success) {
            $('#statTotal').text(res.stats.total);
            $('#statActive').text(res.stats.active);
            $('#statInactive').text(res.stats.inactive);

            let html = '';
            if (res.data.length === 0) {
                html = '<tr><td colspan="4" class="text-center">ไม่มีข้อมูลผู้ใช้งาน</td></tr>';
            } else {
                res.data.forEach(user => {
                    const avatarLetter = user.username.charAt(0).toUpperCase();
                    const roleBadge = getRoleBadge(user.role);
                    const statusBadge = getStatusBadge(user.status);

                    html += `
                        <tr data-status="${user.status}">
                            <td data-label="ผู้ใช้งาน">
                                <div class="user-info-cell">
                                    <div class="user-avatar">${avatarLetter}</div>
                                    <div class="user-name">${user.username}</div>
                                </div>
                            </td>
                            <td data-label="สิทธิ์" class="text-center">${roleBadge}</td>
                            <td data-label="สถานะ" class="text-center">${statusBadge}</td>
                            <td data-label="จัดการ" class="text-center">
                                <button class="btn btn-icon btn-edit" onclick="editUser(${user.id})"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-icon btn-delete" onclick="deleteUser(${user.id})"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>`;
                });
            }
            $('#tableBody').html(html);
        }
    }, 'json');
}

function getRoleBadge(role) {
    switch (role) {
        case 'superadmin': return '<span class="badge badge-super"><i class="fas fa-crown"></i> Super Admin</span>';
        case 'admin': return '<span class="badge badge-admin"><i class="fas fa-user-shield"></i> Admin</span>';
        default: return '<span class="badge badge-user"><i class="fas fa-user"></i> User</span>';
    }
}

function getStatusBadge(status) {
    return status == 1
        ? '<span class="badge badge-active"><i class="fas fa-check-circle"></i> Active</span>'
        : '<span class="badge badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>';
}

function filterByStatus(status, el) {
    $('.stat-card-item').removeClass('active');
    $(el).addClass('active');
    if (status === 'all') {
        $('#tableBody tr').show();
    } else {
        $('#tableBody tr').hide().filter(`[data-status="${status}"]`).show();
    }
}

function openModal(mode) {
    $('#userForm')[0].reset();
    $('#userId').val('0');
    $('#password').attr('required', true);
    $('#passwordHint').hide();

    if (mode === 'create') {
        $('#modalTitle').text('เพิ่มผู้ใช้งานใหม่');
        $('#saveBtn').html('<i class="fas fa-plus-circle"></i> สร้างผู้ใช้');
    } else {
        // This part is for editing, which will be handled by editUser function
    }
    $('#userModal').addClass('show');
}

function closeModal() {
    $('#userModal').removeClass('show');
}

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
            $('#modalTitle').text('แก้ไขข้อมูลผู้ใช้: ' + d.username);
            $('#saveBtn').html('<i class="fas fa-save"></i> อัปเดตข้อมูล');
            $('#userModal').addClass('show');
        }
    }, 'json');
}

function deleteUser(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลผู้ใช้งานนี้จะถูกลบอย่างถาวร!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete', id: id }, function(res) {
                if (res.success) {
                    Swal.fire('ลบแล้ว!', res.message, 'success');
                    loadData();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }, 'json');
        }
    });
}

// Close modal on outside click
$(window).on('click', function(event) {
    if ($(event.target).is('#userModal')) {
        closeModal();
    }
});
