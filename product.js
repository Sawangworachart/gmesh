// product.js
const API_URL = 'product.php?api=true';

function openModal(mode) { 
    $('#productForm')[0].reset();
    $('#product_id').val('0');
    $('#existing_file_container').html('');
    if (mode === 'create') {
        $('#modalTitle').html('<i class="fas fa-box-open"></i> เพิ่มข้อมูลอุปกรณ์ใหม่');
        $('#saveBtn').html('<i class="fas fa-save"></i> บันทึกข้อมูล');
    }
    $('#productModal').addClass('show');
}

function closeModal() { $('#productModal').removeClass('show'); }

function loadTable() {
    $('#tableBody').html('<tr><td colspan="7" class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...</td></tr>');
    
    $.post(API_URL, { action: 'fetch_all' }, function(res) {
        if (res.success) {
            let html = '';
            let total = res.data.length;
            let hasFile = 0;
            let noFile = 0;

            if (total === 0) {
                html = '<tr><td colspan="7" class="loading-spinner">ไม่พบข้อมูล</td></tr>';
            } else {
                res.data.forEach((item, index) => {
                    // Logic สำหรับสถิติ
                    if (item.file_path && item.file_path !== "") { hasFile++; } else { noFile++; }

                    let fileIcon = '';
                    if(item.file_path) {
                        fileIcon = ` <a href="${item.file_path}" target="_blank" title="ดูไฟล์แนบ" style="color:var(--primary); margin-left:5px;"><i class="fas fa-paperclip"></i></a>`;
                    }

                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>
                                <div style="font-weight:600; color:var(--dark)">${item.customers_name || '-'}</div>
                                <small style="color:#888">${item.agency || ''}</small>
                            </td>
                            <td><span class="device-badge">${item.device_name}</span>${fileIcon}</td>
                            <td><span style="font-family:monospace; color:#555">${item.serial_number || '-'}</span></td>
                            <td>
                                <div><i class="fas fa-phone-alt" style="font-size:0.8rem; color:var(--primary)"></i> ${item.phone || '-'}</div>
                                <div style="font-size:0.85rem; margin-top:3px; color:#777">${item.address || ''}</div>
                            </td>
                            <td>${item.repair_details || '-'}</td>
                            <td class="text-center">
                                <button class="action-btn btn-edit" onclick="editItem(${item.product_id})"><i class="fas fa-pencil-alt"></i></button>
                                <button class="action-btn btn-delete" onclick="deleteItem(${item.product_id})"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    `;
                });
            }
            // อัปเดตตัวเลขใน Dashboard
            $('#total_products').text(total);
            $('#has_file_count').text(hasFile);
            $('#no_file_count').text(noFile);
            
            $('#tableBody').html(html);
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

            let fileHtml = d.file_path ? `<div style="color: #28a745;"><i class="fas fa-check-circle"></i> มีไฟล์แนบแล้ว</div>` : '';
            $('#existing_file_container').html(fileHtml);

            $('#modalTitle').html('<i class="fas fa-edit"></i> แก้ไขข้อมูลอุปกรณ์');
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
            $.post(API_URL, { action: 'delete', id: id }, function(res){
                if(res.success) { loadTable(); }
            }, 'json');
        }
    });
}

function filterTable() { 
    var value = $('#searchInput').val().toLowerCase();
    $("#tableBody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
}

$(document).ready(function() {
    loadTable(); 
    $('#productForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'save');
        $.ajax({
            url: API_URL, type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if (res.success) { closeModal(); loadTable(); }
            }
        });
    });
});