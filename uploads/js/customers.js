// customers.js
const API_URL = 'customers.php';

// -----------------------------------------------------
// 1. Core Functions
// -----------------------------------------------------

function loadTable() {
    $('#tableBody').html("<tr><td colspan='8' class='text-center' style='padding:30px; color:#ccc;'><i class='fas fa-spinner fa-spin'></i> กำลังโหลดข้อมูล...</td></tr>");
    $.get(API_URL + '?action=fetch_all', function(data) {
        $('#tableBody').html(data);
    });
}

function filterTable() {
    var value = $('#searchInput').val().toLowerCase();
    $("#tableBody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
}

function openModal(mode, id = null) {
    const modal = $('#customerModal');
    $('#customerForm')[0].reset();
    $('#customers_id').val('');
    
    if (mode === 'create') {
        // [UPDATED] แก้ไขให้ใช้ไอคอนเริ่มต้นจาก customers.php (fas fa-user-plus)
        $('#modalTitle').html('<i class="fas fa-user-plus"></i> เพิ่มลูกค้าใหม่'); 
        $('#form_action').val('create');
        $('#saveBtn').html('<i class="fas fa-save"></i> บันทึก');
        modal.addClass('show');
    } 
    else if (mode === 'edit') {
        $.get(API_URL + '?action=fetch_single&id=' + id, function(data) {
            if (data) {
                // [UPDATED] แก้ไขให้ใช้ไอคอนเริ่มต้นจาก customers.php (fas fa-user-plus) และเปลี่ยนข้อความ
                $('#modalTitle').html('<i class="fas fa-user-plus"></i> แก้ไขข้อมูลลูกค้า');
                $('#form_action').val('update');
                $('#customers_id').val(data.customers_id);
                $('#customers_name').val(data.customers_name);
                $('#agency').val(data.agency);
                $('#contact_name').val(data.contact_name);
                $('#phone').val(data.phone);
                $('#address').val(data.address);
                $('#province').val(data.province);
                
                $('#saveBtn').html('<i class="fas fa-save"></i> อัปเดต');
                modal.addClass('show');
            }
        }, 'json');
    }
}

function closeModal() {
    $('#customerModal').removeClass('show');
}

// Delete customer
function deleteCustomer(id) {
    confirmDelete(id, API_URL, function() {
        loadTable();
    });
}

// -----------------------------------------------------
// 2. Document Ready
// -----------------------------------------------------
$(document).ready(function() {
    loadTable();

    $('#customerForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.post(API_URL, formData, function(res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                closeModal();
                loadTable();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

    $(window).click(function(e) {
        if (e.target.id === 'customerModal') {
            closeModal();
        }
    });
});