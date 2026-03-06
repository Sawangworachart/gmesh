// js หน้า customers ของ admin
const API_URL = 'customers.php';

// -----------------------------------------------------
// 1. Core Functions
// -----------------------------------------------------

// แก้ไฟล์ customers.js ฟังก์ชัน loadTable
function loadTable() {
    // เปลี่ยนตรงนี้เป็น #tableBody
    $('#tableBody').html("<tr class='text-center'><td colspan='7' style='padding:50px; color:#ccc;'><i class='fas fa-spinner fa-spin fa-2x'></i><br>กำลังโหลดข้อมูล...</td></tr>");

    $.get(API_URL + '?action=fetch_all', function (data) {
        // เปลี่ยนตรงนี้เป็น #tableBody
        $('#tableBody').html(data);
        initSortable();
    });
}

function initSortable() {
    // เลือกทุก tbody ที่มี class sortable-list
    var containers = document.querySelectorAll('.sortable-list');

    containers.forEach(function (container) {
        new Sortable(container, {
            group: 'shared-customers', // ชื่อกลุ่มเดียวกันทำให้ลากข้ามหากันได้
            animation: 150, // ความนุ่มนวล
            ghostClass: 'sortable-ghost', // class ตอนกำลังลาก
            handle: 'tr', // ลากได้ทั้งแถว
            onEnd: function (evt) {
                // evt.item = element ที่ถูกลาก
                // evt.to = container ปลายทาง
                // evt.from = container ต้นทาง

                var itemEl = evt.item;
                var newGroupEl = evt.to;

                var customerId = itemEl.getAttribute('data-id');
                var newGroupId = newGroupEl.getAttribute('data-group-id');

                // ถ้าลากกลับมาที่เดิม ไม่ต้องทำอะไร
                if (evt.to === evt.from) return;

                // ส่ง AJAX ไปอัปเดตฐานข้อมูล
                updateCustomerGroup(customerId, newGroupId);
            }
        });
    });
}

function updateCustomerGroup(customerId, groupId) {
    $.post(API_URL, {
        action: 'move_customer',
        customer_id: customerId,
        group_id: groupId
    }, function (res) {
        if (res.status !== 'success') {
            Swal.fire('Error', 'ไม่สามารถย้ายกลุ่มได้: ' + res.message, 'error');
            loadTable(); // โหลดใหม่เพื่อคืนค่าเดิมถ้าพลาด
        } else {
            // สำเร็จ: อาจจะโชว์ Toast เล็กๆ หรือไม่ต้องทำอะไรก็ได้ (เพราะ UI เปลี่ยนไปแล้ว)
            console.log('Moved ID ' + customerId + ' to Group ' + groupId);

            // Optional: Reload ถ้าย้ายไปกลุ่มที่ว่างเปล่า เพื่อเคลียร์ข้อความ "ลากลูกค้ามาวางที่นี่"
            // loadTable(); 
        }
    }, 'json');
}

function createGroup() {
    Swal.fire({
        title: 'สร้างกลุ่มใหม่',
        input: 'text',
        inputLabel: 'ชื่อกลุ่ม / บริษัท',
        inputPlaceholder: 'ระบุชื่อกลุ่ม...',
        showCancelButton: true,
        confirmButtonText: 'สร้าง',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
            if (!value) {
                return 'กรุณาระบุชื่อกลุ่ม!'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, {
                action: 'create_group',
                group_name: result.value
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadTable();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}

function filterTable() {
    var value = $('#searchInput').val().toLowerCase();

    // ค้นหาในทุกแถว (customer-row) ของทุกตาราง
    $(".customer-row").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
}

// ใน customers.js (function openModal)

function openModal(action, id = null) {
    $('#customerForm')[0].reset();
    $('#customers_id').val('');
    
    // --- เพิ่มบรรทัดนี้เพื่อรีเซ็ตค่า action เป็น create ทุกครั้งที่เปิด ---
    $('#form_action').val('create'); 

    $('#customerForm input, #customerForm select, #customerForm textarea').prop('disabled', false);
    $('#saveBtn').show();

    if (action === 'create') {
        $('#modalTitle').text('เพิ่มลูกค้าใหม่');
        $('#customerModal').addClass('show'); 
    } else if (action === 'edit' || action === 'view') {
        
        if (action === 'edit') {
            $('#modalTitle').text('แก้ไขข้อมูลลูกค้า');
            // --- เพิ่มบรรทัดนี้: เปลี่ยน action เป็น update เมื่อกดปุ่มแก้ไข ---
            $('#form_action').val('update'); 
        } else if (action === 'view') {
            $('#modalTitle').text('รายละเอียดข้อมูลลูกค้า');
            $('#customerForm input, #customerForm select, #customerForm textarea').prop('disabled', true);
            $('#saveBtn').hide();
        }

        $.get(API_URL + '?action=fetch_single&id=' + id, function (data) {
            if (data) {
                $('#customers_id').val(data.customers_id);
                $('input[name="customers_name"]').val(data.customers_name);
                $('input[name="agency"]').val(data.agency);
                $('input[name="contact_name"]').val(data.contact_name);
                $('input[name="phone"]').val(data.phone);
                $('#address').val(data.address);
                $('input[name="province"]').val(data.province);
                $('#customerModal').addClass('show'); 
            }
        }, 'json');
    }
}

function closeModal() {
    $('#customerModal').removeClass('show');
}

function deleteCustomer(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลจะถูกลบถาวร!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete', id: id }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Deleted!', res.message, 'success');
                    loadTable();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    })
}

// -----------------------------------------------------
// 2. Document Ready
// -----------------------------------------------------

$(document).ready(function () {
    loadTable();

    // Form Submission
    $('#customerForm').submit(function (e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.post(API_URL, formData, function (res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                closeModal(); // ปิดหน้าต่างแก้
                loadTable();  // รีโหลดตารางเพื่อดึงชื่อกลุ่มที่เปลี่ยนใหม่มาแสดง
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

    // Close modal on outside click
    $(window).click(function (e) {
        if ($(e.target).hasClass('modal-overlay')) {
            closeModal();
        }
    });
});
// =========================================================
// จัดการแก้ไข/ลบกลุ่ม (แบบใหม่ แก้ปัญหากดไม่ติด)
// =========================================================
$(document).on('click', '.btn-edit-group', function(e) {
    e.stopPropagation(); // ป้องกันการพับหน้าจอ
    let groupId = $(this).data('id');
    let oldName = $(this).data('name');

    Swal.fire({
        title: 'แก้ไขชื่อกลุ่ม',
        input: 'text',
        inputValue: oldName,
        inputPlaceholder: 'ระบุชื่อกลุ่มใหม่...',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
            if (!value) return 'กรุณาระบุชื่อกลุ่ม!';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, {
                action: 'edit_group',
                group_id: groupId,
                group_name: result.value
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false });
                    loadTable();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
});

$(document).on('click', '.btn-delete-group', function(e) {
    e.stopPropagation(); // ป้องกันการพับหน้าจอ
    let groupId = $(this).data('id');

    Swal.fire({
        title: 'ยืนยันการลบกลุ่ม?',
        text: "ลูกค้าในกลุ่มนี้จะถูกย้ายไปที่ 'ไม่มีกลุ่ม' แทน",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete_group', group_id: groupId }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('ลบแล้ว!', res.message, 'success');
                    loadTable();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
});


// ฟังก์ชันสำหรับแก้ไขชื่อกลุ่ม
function editGroup(groupId, oldName) {
    Swal.fire({
        title: 'แก้ไขชื่อกลุ่ม',
        input: 'text',
        inputValue: oldName,
        inputPlaceholder: 'ระบุชื่อกลุ่มใหม่...',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
            if (!value) return 'กรุณาระบุชื่อกลุ่ม!';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, {
                action: 'edit_group',
                group_id: groupId,
                group_name: result.value
            }, function (res) {
                if (res.status === 'success') {
                    // 🌟 อัปเดตชื่อที่แสดงบนหน้าจอทันทีโดยไม่ต้อง Refresh ทั้งตาราง
                    // ค้นหาแถวที่มีฟังก์ชัน editGroup ของ id นี้ แล้วเปลี่ยน text ใน .company-name
                    const newName = result.value;
                    Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1000, showConfirmButton: false });
                    
                    // เรียก loadTable เพื่อความถูกต้องของข้อมูลทั้งหมด (หรือจะเขียน logic เปลี่ยน text เฉพาะจุดก็ได้)
                    loadTable(); 
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}

function deleteGroup(groupId) {
    Swal.fire({
        title: 'ยืนยันการลบกลุ่ม?',
        text: "คำเตือน: ข้อมูลลูกค้าทั้งหมดภายในกลุ่มนี้จะถูกลบออกถาวร!",
        icon: 'error', // ใช้ icon error เพื่อความชัดเจน
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยัน ลบทั้งหมด',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete_group', group_id: groupId }, function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบข้อมูลสำเร็จ',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadTable(); // โหลดตารางใหม่เพื่อให้ข้อมูลที่ลบหายไป
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }, 'json');
        }
    });
}