// =========================================
// JS สำหรับหน้า Product (Admin)
// =========================================

const API_URL = 'product.php?api=true';

// --- 1. Initialization ---
$(document).ready(function () {
    // โหลดข้อมูลเริ่มต้น
    loadTable();
    loadStats();

    // Mouse Glow Effect
    const body = document.querySelector('body');
    document.addEventListener('mousemove', (e) => {
        body.style.setProperty('--x', e.clientX + 'px');
        body.style.setProperty('--y', e.clientY + 'px');
    });

    // Form Submit
    $('#productForm').on('submit', function (e) {
        e.preventDefault();
        saveData(this);
    });

    // Close Modal on Click Outside
    $(window).on('click', function (e) {
        if (e.target == document.getElementById('productModal')) closeModal();
        if (e.target == document.getElementById('viewModal')) closeModal();
    });
});

// --- 2. Data Loading ---

function loadStats() {
    $.post(API_URL, { action: 'get_stats' }, function (res) {
        if (res.success) {
            animateCount('#stat_all', res.stats.all);
            animateCount('#stat_s1', res.stats.s1);
            animateCount('#stat_s2', res.stats.s2);
            animateCount('#stat_s3', res.stats.s3);
            animateCount('#stat_s4', res.stats.s4);
        }
    }, 'json');
}

function loadTable() {
    $('#loading').show();
    $('#noData').hide();
    $('#tableBody').empty();

    $.get(API_URL, { action: 'fetch_all' }, function (res) {
        $('#loading').hide();
        if (res.success && res.data.length > 0) {
            let html = '';
            res.data.forEach((item, index) => {
                html += renderRow(item, res.data.length - index);
            });
            $('#tableBody').html(html);
        } else {
            $('#noData').show();
        }
    }, 'json').fail(function() {
        $('#loading').hide();
        $('#noData').show();
        Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลได้', 'error');
    });
}

function renderRow(item, rowNum) {
    const startDate = item.start_date ? formatDate(item.start_date) : '<span class="text-muted">-</span>';
    const endDate = item.end_date ? formatDate(item.end_date) : '<span class="text-muted">-</span>';
    const sn = item.serial_number || '-';
    
    return `
    <tr>
        <td class="text-center" style="font-weight:600; color:#94a3b8;">${rowNum}</td>
        <td>
            <div class="user-info">
                <h4>${item.customers_name}</h4>
                <span><i class="fas fa-building"></i> ${item.agency || '-'}</span>
            </div>
        </td>
        <td style="font-weight:600;">${item.device_name}</td>
        <td style="font-family:'Courier New', monospace; color:#ef4444; background:#fee2e2; padding:2px 6px; border-radius:4px; font-size:0.85rem;">${sn}</td>
        <td class="text-center">${getStatusBadge(item.status)}</td>
        <td>
            <div style="font-size:0.85rem; display:flex; flex-direction:column; gap:4px;">
                <div style="color:#10b981;"><i class="fas fa-play-circle"></i> ${startDate}</div>
                <div style="color:#ef4444;"><i class="fas fa-check-circle"></i> ${endDate}</div>
            </div>
        </td>
        <td>
            <div class="action-btn-group">
                <button class="btn-icon btn-view" onclick="viewDetails(${item.product_id})" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>
                <button class="btn-icon btn-edit" onclick="editItem(${item.product_id})" title="แก้ไข"><i class="fas fa-pen"></i></button>
                <button class="btn-icon btn-del" onclick="deleteItem(${item.product_id})" title="ลบ"><i class="fas fa-trash"></i></button>
            </div>
        </td>
    </tr>`;
}

// --- 3. CRUD Operations ---

function saveData(form) {
    let fd = new FormData(form);
    fd.append('action', 'save');

    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: API_URL, type: 'POST', data: fd,
        contentType: false, processData: false, dataType: 'json',
        success: function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false });
                closeModal();
                loadTable();
                loadStats();
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }
    });
}

function viewDetails(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const d = res.data;
            $('#view_customer').text(d.customers_name);
            $('#view_ticket_id').text(`#PROD-${d.product_id}`);
            $('#view_device_name').text(d.device_name);
            $('#view_sn').text(d.serial_number || '-');
            $('#view_status_text').html(getStatusBadge(d.status));
            $('#view_start_date').text(d.start_date ? formatDate(d.start_date) : '-');
            $('#view_end_date').text(d.end_date ? formatDate(d.end_date) : '-');
            $('#view_details').text(d.repair_details || '-');

            if (d.file_path) {
                $('#view_file_section').show();
                $('#btn_open_file').attr('href', d.file_path);
            } else {
                $('#view_file_section').hide();
            }

            $('#viewModal').addClass('show');
        }
    }, 'json');
}

function editItem(id) {
    openModal('edit');
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const d = res.data;
            $('#product_id').val(d.product_id);
            $('#customers_id').val(d.customers_id);
            $('#device_name').val(d.device_name);
            $('#serial_number').val(d.serial_number);
            $('#repair_details').val(d.repair_details);
            $('#status').val(d.status);
            $('#start_date').val(d.start_date);
            $('#end_date').val(d.end_date);
            $('#existing_file_path').val(d.file_path || '');
            
            if (d.file_path) {
                const fileName = d.file_path.split('/').pop();
                $('#existing_file_container').html(`<span class="text-success"><i class="fas fa-check"></i> ไฟล์เดิม: ${fileName}</span>`);
            }
        }
    }, 'json');
}

function deleteItem(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลจะถูกลบถาวร ไม่สามารถกู้คืนได้!",
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
                    Swal.fire('ลบสำเร็จ!', '', 'success');
                    loadTable();
                    loadStats();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }, 'json');
        }
    });
}

// --- 4. Helpers ---

function openModal(mode = 'create') {
    $('#productForm')[0].reset();
    $('#product_id').val(0);
    $('#existing_file_container').empty();
    $('#modalTitle').text(mode === 'create' ? 'เพิ่มงานบริการใหม่' : 'แก้ไขข้อมูล');
    
    if (mode === 'create') {
        $('#start_date').val(new Date().toISOString().split('T')[0]);
    }
    
    $('#productModal').addClass('show');
}

function closeModal() {
    $('.modal-overlay').removeClass('show');
}

function showFileName(input) {
    const display = document.getElementById('file-name-display');
    if (input.files && input.files[0]) {
        display.textContent = "เลือกไฟล์: " + input.files[0].name;
        display.style.color = "#10b981";
    } else {
        display.textContent = "";
    }
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
}

function getStatusBadge(status) {
    status = parseInt(status);
    switch (status) {
        case 1: return '<span class="badge" style="background:#dbeafe; color:#1e40af;"><i class="fas fa-clock"></i> รอสินค้าจากลูกค้า</span>';
        case 2: return '<span class="badge" style="background:#f3e8ff; color:#6b21a8;"><i class="fas fa-search"></i> ตรวจสอบ</span>';
        case 3: return '<span class="badge" style="background:#ffedd5; color:#9a3412;"><i class="fas fa-truck"></i> รอสินค้าจาก Supplier</span>';
        case 4: return '<span class="badge" style="background:#dcfce7; color:#166534;"><i class="fas fa-check-double"></i> ส่งคืนลูกค้า</span>';
        default: return '<span class="badge badge-secondary">Unknown</span>';
    }
}

function animateCount(id, end) {
    const obj = $(id);
    $({ count: 0 }).animate({ count: end }, {
        duration: 800,
        step: function () {
            obj.text(Math.floor(this.count));
        },
        complete: function () {
            obj.text(this.count);
        }
    });
}

function filterTable() {
    const val = $('#searchInput').val().toLowerCase();
    $('#tableBody tr').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(val) > -1);
    });
}

function filterByStatus(statusName, el) {
    $('.stat-card').removeClass('active');
    $(el).addClass('active');
    
    // Note: This logic depends on exact string matching with badge text
    // A better approach would be to filter by data-status attribute
    if (statusName === 'all') {
        $('#tableBody tr').show();
        return;
    }
    
    $('#tableBody tr').each(function() {
        const rowStatus = $(this).find('td:nth-child(5)').text().trim();
        $(this).toggle(rowStatus.includes(statusName));
    });
}

function exportExcel() {
    window.open('product_export.php', '_blank');
}
