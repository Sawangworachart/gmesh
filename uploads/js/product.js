// product.js
const API_URL = 'product.php?api=true';

function openModal(mode) { 
    $('#productForm')[0].reset();
    $('#product_id').val('0');
    $('#existing_file_container').html('');
    if (mode === 'create') {
        $('#modalTitle').text('เพิ่มอุปกรณ์ใหม่');
        $('#saveBtn').text('บันทึกข้อมูล');
    }
    $('#productModal').addClass('show');
}

function closeModal() { 
    $('#productModal').removeClass('show'); 
    $('#viewModal').removeClass('show'); 
}

function loadTable() {
    $.post(API_URL, { action: 'fetch_all' }, function(res) {
        if (res.success) {
            let html = '';
            res.data.forEach((item, index) => {
                let fileBtn = item.file_path ? `<a href="${item.file_path}" target="_blank" style="color:#5599ff; margin-left:5px;"><i class="fas fa-paperclip"></i></a>` : '';
                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.customers_name}</td>
                        <td>${item.device_name} ${fileBtn}</td>
                        <td>${item.serial_number || '-'}</td>
                        <td>${item.phone || '-'}</td>
                        <td>${item.repair_details || '-'}</td>
                        <td class="action-col">
                            <div class="action-buttons">
                                <button class="action-btn btn-view" onclick="viewItem(${item.product_id})" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>
                                <button class="action-btn btn-edit" onclick="editItem(${item.product_id})" title="แก้ไข"><i class="fas fa-edit"></i></button>
                                <button class="action-btn btn-delete" onclick="deleteItem(${item.product_id})" title="ลบ"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>`;
            });
            $('#tableBody').html(html);
        }
    }, 'json');
}

function viewItem(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function(res) {
        if (res.success) {
            const d = res.data;
            $('#view_device').text(d.device_name);
            $('#view_sn').text(d.serial_number || '-');
            
            let custText = d.customers_name || d.customers_id;
            if(d.agency) custText += ` (${d.agency})`;
            $('#view_customer').text(custText);

            $('#view_phone').text(d.phone || '-');
            $('#view_address').text(d.address || '-');
            $('#view_details').text(d.repair_details || '-');
            
            if(d.file_path) {
                $('#view_file_area').html(`
                    <div class="view-file-box">
                        <i class="fas fa-file-alt" style="color:var(--primary); font-size:2rem;"></i>
                        <div>
                            <div style="font-weight:600; font-size:0.95rem;">เอกสารแนบ</div>
                            <a href="${d.file_path}" target="_blank" style="color:var(--primary); text-decoration:none; font-size:0.9rem;">
                                คลิกเพื่อเปิดดูไฟล์ <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                `);
            } else {
                $('#view_file_area').html('<span style="color:#aaa; font-style:italic;">- ไม่มีเอกสารแนบ -</span>');
            }
            $('#viewModal').addClass('show');
        }
    }, 'json');
}

function editItem(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function(res) {
        if (res.success) {
            const d = res.data;
            $('#product_id').val(d.product_id);
            $('#customers_id').val(d.customers_id);
            $('#device_name').val(d.device_name);
            $('#serial_number').val(d.serial_number);
            $('#phone').val(d.phone);
            $('#address').val(d.address);
            $('#repair_details').val(d.repair_details);
            
            if(d.file_path) {
                $('#existing_file_container').html(`<small style="color:green">มีไฟล์เดิมอยู่แล้ว: <a href="${d.file_path}" target="_blank">เปิดดู</a></small>`);
            }
            $('#modalTitle').text('แก้ไขข้อมูลอุปกรณ์');
            $('#saveBtn').text('อัปเดตข้อมูล');
            $('#productModal').addClass('show');
        }
    }, 'json');
}

function deleteItem(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบข้อมูล'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete', id: id }, function(res) {
                if(res.success) { loadTable(); Swal.fire('ลบแล้ว!', res.message, 'success'); }
            }, 'json');
        }
    });
}

function filterTable() {
    let val = $('#searchInput').val().toLowerCase();
    $("#tableBody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1)
    });
}

$(document).ready(function() {
    loadTable();

    $('#customers_id').change(function() {
        let opt = $(this).find('option:selected');
        $('#phone').val(opt.data('phone'));
        $('#address').val(opt.data('address'));
    });

    $('#productForm').submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this); 
        formData.append('action', 'save');

        $.ajax({
            url: API_URL,
            type: 'POST',
            data: formData,
            contentType: false, 
            processData: false, 
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire('สำเร็จ', res.message, 'success');
                    closeModal();
                    loadTable();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }
        });
    });
});