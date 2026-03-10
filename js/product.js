// js หน้า Product ของ admin
const API_URL = 'product.php?api=true';

function closeModal() {
    $('.modal-overlay').removeClass('show');
}

function openModal(mode) {
    const modal = $('#productModal');
    $('#productForm')[0].reset();
    $('#product_id').val(0);
    $('#existing_file_container').empty();
    $('#file-name-display').text(''); // ล้างชื่อไฟล์ที่เลือกในกล่อง upload ใหม่

    if (mode === 'create') {
        $('#modalTitle').text('เพิ่มงานบริการใหม่');
    }
    modal.addClass('show');
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === 'null') return '-';

    // แยกวันเดือนปีโดยไม่ผ่าน Timezone (ป้องกันวันที่เพี้ยน)
    const parts = dateStr.split('-');
    if (parts.length < 3) return '-';

    const y = parseInt(parts[0]);
    const m = parseInt(parts[1]);
    const d = parseInt(parts[2]);

    const thaiMonths = [
        "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.",
        "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."
    ];

    // คำนวณปีพ.ศ. แล้วตัดเอาแค่ 2 ตัวท้าย (slice -2)
    const thaiYear = (y + 543).toString().slice(-2);
    const monthName = thaiMonths[m - 1];

    return `${d} ${monthName} ${thaiYear}`;
}

// Helper: แปลง Status ID เป็นข้อความ (สำหรับแสดงในการ์ดสีฟ้า)
function getStatusName(statusId) {
    let s = parseInt(statusId);
    switch (s) {
        case 1: return 'รอสินค้าจากลูกค้า';
        case 2: return 'ตรวจสอบ';
        case 3: return 'รอสินค้าจาก Supplier';
        case 4: return 'ส่งคืนลูกค้า';
        default: return '-';
    }
}

// 1. ฟังก์ชันแสดง Badge ในตาราง (ต้องตรงกับข้อความที่ใช้กรอง)
function getStatusBadge(statusId) {
    let s = parseInt(statusId);
    switch (s) {
        case 1: return '<span class="badge" style="background:#dbeafe; color:#1e40af;"><i class="fas fa-clock"></i> รอสินค้าจากลูกค้า</span>';
        case 2: return '<span class="badge" style="background:#f3e8ff; color:#6b21a8;"><i class="fas fa-search"></i> ตรวจสอบ</span>';
        case 3: return '<span class="badge" style="background:#ffedd5; color:#9a3412;"><i class="fas fa-users"></i> รอสินค้าจากsupplier</span>';
        case 4: return '<span class="badge" style="background:#dcfce7; color:#166534;"><i class="fas fa-check-double"></i> ส่งคืนลูกค้า</span>';
        default: return `<span class="badge">${statusId}</span>`;
    }
}

// 2. โหลดสถิติ
// 2. โหลดสถิติ
function loadStats() {
    $.post(API_URL, { action: 'get_stats' }, function (res) {
        if (res.success) {
            // เปลี่ยนจาก .text() เป็น animateCount()
            animateCount('#stat_all', res.stats.all);
            animateCount('#stat_s1', res.stats.s1);
            animateCount('#stat_s2', res.stats.s2);
            animateCount('#stat_s3', res.stats.s3);
            animateCount('#stat_s4', res.stats.s4);
        }
    }, 'json');
}
// ฟังก์ชันสำหรับทำให้ตัวเลขวิ่ง (Count Up Animation)
function animateCount(elementId, targetValue) {
    const $el = $(elementId);
    let startValue = parseInt($el.text().replace(/,/g, ''));
    if (isNaN(startValue)) startValue = 0;

    $({ countNum: startValue }).animate({ countNum: targetValue }, {
        duration: 800,
        easing: 'swing',
        step: function () {
            $el.text(Math.floor(this.countNum).toLocaleString());
        },
        complete: function () {
            $el.text(targetValue.toLocaleString());
        }
    });
}

// 3. โหลดตาราง
// 3. โหลดตาราง
// แก้ไขฟังก์ชัน loadTable ใน js/product.js
function loadTable() {
    $('#loading').show();
    $('#tableBody').empty();
    $('#noData').hide();

    $.get(API_URL, { action: 'fetch_all' }, function (res) {
        $('#loading').hide();
        if (res.success && res.data.length > 0) {
            let html = '';
            let totalItems = res.data.length;

            res.data.forEach((item, index) => {
                let rowNumber = totalItems - index;

                // --- ส่วนที่แก้ไข: เช็ควันที่มีข้อมูลไหม ---
                let startDate = item.start_date ? formatDate(item.start_date) : '-';
                let endDate = item.end_date ? formatDate(item.end_date) : '-';

                html += `
                <tr>
                    <td style="text-align:center; color:#94a3b8; font-weight:600;">${rowNumber}</td>
                    
                    <td>
                        <div class="user-info">
                            <h4>${item.customers_name}</h4>
                            <span><i class="fas fa-building"></i> ${item.agency || '-'}</span>
                        </div>
                    </td>

                        <td>${item.device_name}</td>
                        <td>${item.serial_number || '-'}</td>

                    <td style="text-align:center;">${getStatusBadge(item.status)}</td>

<td>
    <div style="display: flex; flex-direction: column; gap: 4px;">
        <div style="color: #16a34a; font-weight: 600; font-size: 0.9rem;">
            <i class="fas fa-calendar-check" style="margin-right: 5px;"></i> 
            ${(!startDate || startDate === '-') ? 'ไม่ได้ระบุ' : startDate}
        </div>
        
        <div style="color: #dc2626; font-weight: 600; font-size: 0.9rem;">
            <i class="fas fa-calendar-times" style="margin-right: 5px;"></i> 
            ${(!endDate || endDate === '-') ? 'ไม่ได้ระบุ' : endDate}
        </div>
    </div>
</td>

                    <td>
                        <div class="action-btn-group" style="display:flex; gap:5px;">
                            <button class="btn-icon" onclick="viewDetails(${item.product_id})" title="ดูรายละเอียด" 
                                    style="background: #eff6ff; color: #2563eb; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </button>

                            <button class="btn-icon" onclick="editItem(${item.product_id})" title="แก้ไข" 
                                    style="background: #fffbeb; color: #d97706; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer;">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            });
            $('#tableBody').html(html);
        } else {
            $('#noData').show();
        }
    }, 'json');
}
// js/product.js
window.viewDetails = function (id) {
    console.log("Opening ID:", id);

    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const d = res.data;

            // 1. ใส่ข้อมูล Text ทั่วไป
            $('#view_customer').text(d.customers_name + (d.agency ? ` (${d.agency})` : ''));
            $('#view_ticket_id').text('#PROD-' + d.product_id);
            $('#view_status_text').text(getStatusName(d.status));
            $('#view_start_date').text(formatDateTh(d.start_date));
            $('#view_end_date').text(formatDateTh(d.end_date));
            $('#view_device_name').text(d.device_name);
            $('#view_sn').text(d.serial_number || '-');
            $('#view_details').text(d.repair_details || '-');

            if (d.file_path && d.file_path.trim() !== "") {
                $('#btn_open_file').attr('href', d.file_path);
                $('#view_file_section').css('display', 'flex'); // ใช้ flex แทน show() เพื่อให้จัดซ้ายขวาได้
            } else {
                $('#view_file_section').hide();
            }

            // 3. เปิด Modal
            $('#viewModal').addClass('show');

        } else {
            Swal.fire('Error', 'ไม่พบข้อมูล', 'error');
        }
    }, 'json');
};
// Helper: แปลงวันที่ (ตรวจสอบว่ามีฟังก์ชันนี้หรือยัง ถ้ามีแล้วไม่ต้องใส่ซ้ำ)
function formatDateTh(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === 'null') return '-';
    const date = new Date(dateStr);
    if (isNaN(date)) return '-';
    return date.toLocaleDateString('th-TH', {
        year: '2-digit', month: 'short', day: 'numeric'
    });
}

// 5. ฟังก์ชัน Edit Item
function editItem(id) {
    openModal('edit');
    $('#modalTitle').text('แก้ไขข้อมูลอุปกรณ์');
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

            // แสดงชื่อไฟล์เดิมในส่วนใต้กล่อง Upload
            if (d.file_path) {
                // ดึงเฉพาะชื่อไฟล์ออกมาแสดง
                let fileName = d.file_path.split('/').pop();
                $('#existing_file_container').html(`
                    <div style="margin-top:5px; color:#3b82f6; font-size:0.9rem;">
                        <i class="fas fa-check-circle"></i> มีไฟล์เดิม: <b>${fileName}</b>
                    </div>
                `);
            } else {
                $('#existing_file_container').empty();
            }
        }
    }, 'json');
}

// ฟังก์ชัน Filter
function filterTable() {
    let val = $('#searchInput').val().toLowerCase();
    let visibleCount = 1;

    $('#tableBody tr').each(function () {
        let rowText = $(this).text().toLowerCase();
        if (rowText.indexOf(val) > -1) {
            $(this).show();
            $(this).find('td:first-child span').text('(' + visibleCount + ')');
            visibleCount++;
        } else {
            $(this).hide();
        }
    });
}

function filterByStatus(statusName, el) {
    $('.stat-card').removeClass('active');
    $(el).addClass('active');

    let visibleCount = 1;
    $('#tableBody tr').each(function () {
        // ดึง Text จาก Badge (ต้อง trim เพราะมี icon และ space)
        let statusText = $(this).find('td:nth-child(5)').text().trim();

        if (statusName === 'all' || statusText.includes(statusName)) {
            $(this).show();
            $(this).find('td:first-child span').text('(' + visibleCount + ')');
            visibleCount++;
        } else {
            $(this).hide();
        }
    });
}

// Document Ready
$(document).ready(function () {
    loadTable();
    loadStats();

    // จัดการ Form Submit
    $('#productForm').on('submit', function (e) {
        e.preventDefault();
        let fd = new FormData(this);
        fd.append('action', 'save');
        $.ajax({
            url: API_URL, type: 'POST', data: fd,
            contentType: false, processData: false, dataType: 'json',
            success(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    closeModal();
                    loadTable();
                    loadStats();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }
        });
    });
});