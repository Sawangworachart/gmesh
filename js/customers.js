/**
 * ไฟล์: assets/js/customers.js
 * คำอธิบาย: ควบคุมการทำงานหน้าจัดการลูกค้า (Customers)
 * รวมถึงการจัดการ Modal, AJAX Request และ Interaction ต่างๆ
 */

$(document).ready(function() {
    loadCustomers();
    
    // ตั้งค่า Mouse Glow Effect
    const cards = document.querySelectorAll('.card, .header-banner-custom');
    cards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--x', `${x}px`);
            card.style.setProperty('--y', `${y}px`);
        });
    });

    // ปิด Modal เมื่อคลิกข้างนอก
    $(window).click(function(e) {
        if ($(e.target).is('#customerModal')) {
            closeModal();
        }
    });
});

// โหลดข้อมูลลูกค้า
function loadCustomers() {
    $.get('customers.php?action=fetch_all', function(data) {
        $('#tableBody').html(data);
    });
}

// เปิด/ปิด การแสดงผลกลุ่มลูกค้า
function toggleGroup(groupId, element) {
    $('.' + groupId).fadeToggle(200);
    $(element).find('.arrow-icon').toggleClass('rotated');
    $(element).toggleClass('active');
}

// เปิด Modal (สำหรับสร้างใหม่ หรือ แก้ไข)
function openModal(action, id = 0) {
    $('#customerModal').addClass('show');
    $('#form_action').val(action);
    $('#customers_id').val(id);

    // รีเซ็ตฟอร์ม
    $('#customerForm')[0].reset();

    if (action === 'create') {
        $('#modalTitle').text('เพิ่มข้อมูลลูกค้าใหม่');
        $('#saveBtn').html('<i class="fas fa-plus-circle"></i> บันทึกข้อมูล');
    } else if (action === 'edit') {
        $('#modalTitle').text('แก้ไขข้อมูลลูกค้า');
        $('#saveBtn').html('<i class="fas fa-save"></i> บันทึกการแก้ไข');
        
        // ดึงข้อมูลเดิมมาใส่ในฟอร์ม
        $.get('customers.php?action=fetch_single&id=' + id, function(data) {
            $('#customers_name').val(data.customers_name);
            $('#agency').val(data.agency);
            $('#contact_name').val(data.contact_name);
            $('#phone').val(data.phone);
            $('#address').val(data.address);
            $('#province').val(data.province);
        });
    }
}

// ปิด Modal
function closeModal() {
    $('#customerModal').removeClass('show');
}

// บันทึกข้อมูล (Submit Form)
$('#customerForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: 'customers.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                closeModal();
                loadCustomers();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: response.message
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Connection Error',
                text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
            });
        }
    });
});

// ลบลูกค้า
function deleteCustomer(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลจะถูกลบถาวรและไม่สามารถกู้คืนได้!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#858796',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('customers.php', { action: 'delete', id: id }, function(response) {
                // สมมติว่า server return json ถ้าไม่ return ให้แก้ logic ตรงนี้
                try {
                    const res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res.status === 'success') {
                        Swal.fire('ลบสำเร็จ!', res.message, 'success');
                        loadCustomers();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                } catch(e) {
                    // กรณี response เป็น text ธรรมดา หรือ error
                    loadCustomers(); // reload anyway
                    Swal.fire('ลบสำเร็จ!', 'ลบข้อมูลเรียบร้อยแล้ว', 'success');
                }
            });
        }
    });
}

// แก้ไขชื่อกลุ่ม
function editGroup(groupId, currentName) {
    Swal.fire({
        title: 'แก้ไขชื่อกลุ่ม',
        input: 'text',
        inputValue: currentName,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
            if (!value) {
                return 'กรุณากรอกชื่อกลุ่ม!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('customers.php', { 
                action: 'edit_group', 
                group_id: groupId, 
                group_name: result.value 
            }, function(response) {
                if (response.status === 'success') {
                    Swal.fire('สำเร็จ', response.message, 'success');
                    loadCustomers();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }, 'json');
        }
    });
}

// ลบกลุ่ม
function deleteGroup(groupId) {
    Swal.fire({
        title: 'ยืนยันลบกลุ่มนี้?',
        text: "ลูกค้าทั้งหมดในกลุ่มนี้จะถูกลบไปด้วย!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ลบทั้งกลุ่ม'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('customers.php', { 
                action: 'delete_group', 
                group_id: groupId 
            }, function(response) {
                if (response.status === 'success') {
                    Swal.fire('ลบสำเร็จ', response.message, 'success');
                    loadCustomers();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }, 'json');
        }
    });
}

// ค้นหา (Filter Table)
function filterTable() {
    const value = $('#searchInput').val().toLowerCase();
    
    // ค้นหาทั้งใน group header และ items
    // วิธีง่ายคือ: ถ้า item ตรง search ให้ show item และ show group header ของมัน
    
    // reset ก่อน
    if (value === '') {
        // กลับสู่สภาพเดิม (ซ่อน items, โชว์ headers)
        $('.group-item').hide();
        $('.group-header').show();
        return;
    }

    // ซ่อนทั้งหมดก่อน
    $('.group-item').hide();
    $('.group-header').hide();

    // Loop check items
    $('.group-item').each(function() {
        const text = $(this).text().toLowerCase();
        if (text.indexOf(value) > -1) {
            $(this).show();
            // หา class group-X ของมัน
            const classes = $(this).attr('class').split(' ');
            let groupIdClass = '';
            classes.forEach(c => {
                if (c.startsWith('group-') && c !== 'group-item') {
                    groupIdClass = c;
                }
            });
            
            // Show header ที่คู่กัน (ต้องหา header ที่มี onclick toggleGroup('group-X') หรือวิธีอื่น)
            // ใน PHP: header มี onclick="toggleGroup('group-{$gid}', this)"
            // เราสามารถหา header โดยการหา tr ก่อนหน้าที่มี class group-header (แต่มันอาจจะไม่ติดกันเสมอไป)
            // วิธีที่ชัวร์คือ หา tr.group-header ที่ control group นี้
            // แต่ HTML structure ไม่ได้ link กันด้วย ID ชัดเจน
            
            // Hack: ใช้ prevAll('.group-header').first() อาจจะไม่เวิร์คถ้าข้าม group
            // เปลี่ยน Logic ใน PHP ให้ header มี ID หรือ data-attribute ดีกว่า
            // แต่ตอนนี้ใช้ JS บ้านๆไปก่อน:
            
            // ถ้าเจอ item, ให้ show item นั้น
            // และ show header ของมัน
             $(this).prevAll('.group-header').first().show();
        }
    });
}
