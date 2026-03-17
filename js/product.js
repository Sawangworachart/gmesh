
// js/product.js

$(document).ready(function() {
    fetchStats();
    fetchAllData();
    fetchCustomersForDropdown();

    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        saveData();
    });
});

function fetchStats() {
    $.get("product.php?api=true&action=get_stats", function(res) {
        if (res.success) {
            $('#stat_all').text(res.stats.all);
            $('#stat_s1').text(res.stats.s1);
            $('#stat_s2').text(res.stats.s2);
            $('#stat_s3').text(res.stats.s3);
            $('#stat_s4').text(res.stats.s4);
        }
    });
}

function fetchAllData() {
    $.get("product.php?api=true&action=fetch_all", function(res) {
        if (res.success) {
            window.allData = res.data;
            renderTable(res.data);
        }
    });
}

function fetchCustomersForDropdown() {
    $.get("customers.php?api=true&action=fetch_for_dropdown", function(res) {
        if (res.success) {
            const select = $('#customers_id');
            select.empty().append('<option value="">-- เลือกลูกค้า --</option>');
            res.data.forEach(c => {
                select.append(`<option value="${c.id}">${c.name}</option>`);
            });
        }
    });
}

function renderTable(data) {
    const tableBody = $('#tableBody');
    tableBody.empty();
    if (data.length === 0) {
        tableBody.html('<tr><td colspan="6" class="text-center p-5 text-muted">ไม่พบข้อมูล</td></tr>');
        return;
    }

    data.forEach((row, index) => {
        const statusMap = {
            1: { text: 'รอสินค้า', class: 'status-1' },
            2: { text: 'ตรวจสอบ', class: 'status-2' },
            3: { text: 'รออะไหล่', class: 'status-3' },
            4: { text: 'ส่งคืน', class: 'status-4' }
        };
        const statusInfo = statusMap[row.status] || { text: 'N/A', class: '' };

        const tr = `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td>
                    <strong>${row.customers_name || 'N/A'}</strong><br>
                    <small class="text-muted">${row.agency || ''}</small>
                </td>
                <td>
                    <strong>${row.device_name}</strong><br>
                    <small class="text-muted">S/N: ${row.serial_number || '-'}</small>
                </td>
                <td class="text-center">
                    <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                </td>
                <td>
                    <div><i class="fas fa-play-circle text-primary"></i> ${row.start_date || '-'}</div>
                    <div><i class="fas fa-flag-checkered text-danger"></i> ${row.end_date || '-'}</div>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info" onclick="openModal('view', ${row.product_id})"><i class="far fa-eye"></i></button>
                    <button class="btn btn-sm btn-warning" onclick="openModal('edit', ${row.product_id})"><i class="far fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="deleteData(${row.product_id})"><i class="far fa-trash-alt"></i></button>
                </td>
            </tr>
        `;
        tableBody.append(tr);
    });
}

function openModal(mode, id = 0) {
    if (mode === 'create' || mode === 'edit') {
        $('#productForm')[0].reset();
        $('#product_id').val(0);
        $('#existing_file_container').empty();
        $('#modalTitle').text('เพิ่มข้อมูล Product Claim');

        if (mode === 'edit') {
            $('#modalTitle').text('แก้ไขข้อมูล Product Claim');
            $.get(`product.php?api=true&action=fetch_single&id=${id}`, function(res) {
                if (res.success) {
                    const d = res.data;
                    $('#product_id').val(d.product_id);
                    $('#customers_id').val(d.customers_id);
                    $('#device_name').val(d.device_name);
                    $('#serial_number').val(d.serial_number);
                    $('#repair_details').val(d.repair_details);
                    $('#status').val(d.status);
                    $('#status_remark').val(d.status_remark);
                    $('#start_date').val(d.start_date);
                    $('#end_date').val(d.end_date);
                    $('#existing_file_path').val(d.file_path);
                    if (d.file_path) {
                        $('#existing_file_container').html(`<a href="${d.file_path}" target="_blank" class="file-link"><i class="fas fa-file-alt"></i> ดูไฟล์เดิม</a>`);
                    }
                }
            });
        }
        $('#productModal').addClass('show');
    } else if (mode === 'view') {
        $.get(`product.php?api=true&action=fetch_single&id=${id}`, function(res) {
            if (res.success) {
                renderViewModal(res.data);
                $('#viewModal').addClass('show');
            }
        });
    }
}

function closeModal() {
    $('.modal-overlay').removeClass('show');
}

function saveData() {
    const formData = new FormData($('#productForm')[0]);
    $.ajax({
        url: 'product.php?api=true&action=save',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.success) {
                Swal.fire('สำเร็จ', 'บันทึกข้อมูลเรียบร้อย', 'success');
                closeModal();
                fetchStats();
                fetchAllData();
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        }
    });
}

function deleteData(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลจะถูกลบอย่างถาวร!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('product.php?api=true', { action: 'delete', id: id }, function(res) {
                if (res.success) {
                    Swal.fire('สำเร็จ', 'ข้อมูลถูกลบแล้ว', 'success');
                    fetchStats();
                    fetchAllData();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            });
        }
    });
}

function filterByStatus(status, element) {
    $('.stat-card').removeClass('active');
    $(element).addClass('active');
    if (status === 'all') {
        renderTable(window.allData);
    } else {
        const filteredData = window.allData.filter(row => row.status == status);
        renderTable(filteredData);
    }
}

function filterTable() {
    const searchTerm = $('#searchInput').val().toLowerCase();
    const filteredData = window.allData.filter(row => {
        return (
            row.customers_name?.toLowerCase().includes(searchTerm) ||
            row.device_name?.toLowerCase().includes(searchTerm) ||
            row.serial_number?.toLowerCase().includes(searchTerm)
        );
    });
    renderTable(filteredData);
}

function renderViewModal(data) {
    const statusMap = {
        1: { text: 'รอสินค้า', class: 'status-1' },
        2: { text: 'ตรวจสอบ', class: 'status-2' },
        3: { text: 'รออะไหล่', class: 'status-3' },
        4: { text: 'ส่งคืน', class: 'status-4' }
    };
    const statusInfo = statusMap[data.status] || { text: 'N/A', class: '' };

    let fileHtml = '';
    if (data.file_path) {
        fileHtml = `<a href="${data.file_path}" target="_blank" class="file-link"><i class="fas fa-paperclip"></i> ดูไฟล์แนบ</a>`;
    }

    const content = `
        <div class="view-grid">
            <div class="detail-box">
                <span class="detail-label">ลูกค้า</span>
                <p class="detail-value">${data.customers_name || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">แผนก</span>
                <p class="detail-value">${data.agency || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">อุปกรณ์</span>
                <p class="detail-value">${data.device_name || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">Serial Number</span>
                <p class="detail-value">${data.serial_number || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">วันที่เริ่ม</span>
                <p class="detail-value">${data.start_date || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">วันที่สิ้นสุด</span>
                <p class="detail-value">${data.end_date || '-'}</p>
            </div>
            <div class="detail-box col-span-2">
                <span class="detail-label">สถานะ</span>
                <p class="detail-value"><span class="status-badge ${statusInfo.class}">${statusInfo.text}</span> ${data.status_remark || ''}</p>
            </div>
            <div class="detail-box col-span-2">
                <span class="detail-label">อาการเสีย / สิ่งที่พบ</span>
                <p class="detail-value">${data.repair_details || '-'}</p>
            </div>
            <div class="detail-box col-span-2">
                <span class="detail-label">ไฟล์แนบ</span>
                ${fileHtml || '<p class="detail-value text-muted">ไม่มีไฟล์แนบ</p>'}
            </div>
        </div>
    `;
    $('#viewModalBody').html(content);
}

function exportExcel() {
    window.open('product_export.php', '_blank');
}
