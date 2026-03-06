// =========================================
// JS สำหรับหน้า Service Project (Admin)
// =========================================

const API_URL = 'service_project.php?api=true';

// --- 1. เริ่มทำงานเมื่อโหลดหน้าเว็บเสร็จ ---
$(document).ready(function () {
    // โหลดข้อมูลสรุปและตารางข้อมูล
    loadSummary();
    loadTable();

    // จัดการการส่งฟอร์ม (บันทึก/แก้ไข)
    $('#serviceForm').on('submit', function (e) {
        e.preventDefault();
        saveServiceData(this);
    });

    // ปิด Modal เมื่อคลิกพื้นหลัง
    $(window).on('click', function (e) {
        if (e.target == document.getElementById('serviceModal')) closeModal();
        if (e.target == document.getElementById('viewModal')) closeViewModal();
    });
});

// --- 2. ฟังก์ชันโหลดข้อมูล (Data Loading) ---

/**
 * โหลดข้อมูลสรุปตัวเลข (Stats Cards)
 */
function loadSummary() {
    $.get(API_URL, { action: 'fetch_status_summary' }, function (res) {
        if (res.success) {
            animateCounter('cardOnsite', 0, res.data['On-site'] || 0, 1000);
            animateCounter('cardRemote', 0, res.data['Remote'] || 0, 1000);
            animateCounter('cardSub', 0, res.data['แจ้ง Subcontractor'] || 0, 1000);
            animateCounter('cardTotal', 0, res.data['Total'] || 0, 1000);
        }
    }, 'json');
}

/**
 * โหลดตารางข้อมูลทั้งหมด
 */
function loadTable() {
    $.get(API_URL, { action: 'fetch_all' }, function (res) {
        if (res.success) {
            let html = '';
            if (!res.data || res.data.length === 0) {
                html = '<tr><td colspan="7" class="text-center p-4 text-muted">ไม่พบข้อมูลงานบริการ</td></tr>';
            } else {
                res.data.forEach((item, index) => {
                    html += generateTableRow(item, index);
                });
            }
            $('#tableBody').html(html);
        } else {
            $('#tableBody').html('<tr><td colspan="7" class="text-center text-danger">โหลดข้อมูลไม่สำเร็จ</td></tr>');
        }
    }, 'json').fail(function () {
        $('#tableBody').html('<tr><td colspan="7" class="text-center text-danger">เกิดข้อผิดพลาดในการเชื่อมต่อ</td></tr>');
    });
}

/**
 * สร้าง HTML สำหรับแถวในตาราง
 */
function generateTableRow(item, index) {
    const id = item.detail_id ?? 0;
    const number = item.number ?? '-';
    const startDate = item.start_date ? formatDate(item.start_date) : '-';
    const endDate = item.end_date ? formatDate(item.end_date) : '<span style="font-style:italic; font-weight:400; font-size:0.75rem;">ไม่ได้ระบุ</span>';
    const project = item.project_name ?? '-';
    const customer = item.customers_name ?? '-';
    const agency = item.agency ?? '-';
    const equipment = item.equipment ?? '-';
    const sn = item.sn ?? '-';
    const status = item.status ?? 'On-site';

    // กำหนดสีและไอคอนตามสถานะ
    let badgeClass = 'badge-onsite';
    let statusIcon = 'fa-building';

    if (status === 'Remote') {
        badgeClass = 'badge-remote';
        statusIcon = 'fa-laptop-house';
    } else if (status === 'แจ้ง Subcontractor') {
        badgeClass = 'badge-sub';
        statusIcon = 'fa-user-friends';
    }

    return `
    <tr class="table-row-anim" style="animation-delay: ${index * 0.05}s">
        <td class="text-center text-muted" style="font-weight: 500;">${number}</td>
        <td style="min-width: 120px;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <div style="color: #10b981; font-weight: 700; font-size: 0.85rem;"><i class="fas fa-play-circle"></i> ${startDate}</div>
                <div style="color: ${item.end_date ? '#ef4444' : '#94a3b8'}; font-weight: 700; font-size: 0.85rem; border-top: 1px dashed #f1f5f9; padding-top: 2px;">
                    <i class="fas fa-check-circle"></i> ${endDate}
                </div>
            </div>
        </td>
        <td><div style="font-weight:700; color:#1e293b;">${project}</div></td>
        <td>
            <div style="font-weight:600; color:#475569;">${customer}</div>
            <div style="font-size:0.8rem; color:#94a3b8;">${agency}</div>
        </td>
        <td style="max-width: 180px;">
            <div style="font-size: 0.85rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${equipment}">
                <i class="fas fa-microchip"></i> ${equipment}
            </div>
            <div style="font-size: 0.75rem; color: #cbd5e1;">SN: ${sn}</div>
        </td>
        <td class="text-center">
            <span class="status-badge ${badgeClass}"><i class="fas ${statusIcon}"></i> ${status}</span>
        </td>
        <td class="text-center">
            <button class="btn-icon btn-att" onclick="viewData(${id})" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>
            <button class="btn-icon btn-edit" onclick="editData(${id})" title="แก้ไข"><i class="fas fa-pencil-alt"></i></button>
        </td>
    </tr>`;
}

// --- 3. ฟังก์ชันจัดการข้อมูล (CRUD Operations) ---

/**
 * บันทึกข้อมูล (Save)
 */
function saveServiceData(form) {
    let formData = new FormData(form);
    formData.append('action', 'save_data');

    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    $.ajax({
        url: API_URL, type: 'POST', data: formData,
        contentType: false, processData: false, dataType: 'json',
        success: function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false });
                closeModal();
                loadSummary();
                loadTable();
                $('#serviceForm')[0].reset();
            } else {
                Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
            }
        },
        error: function () { Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'); }
    });
}

/**
 * ดูรายละเอียด (View)
 */
function viewData(id) {
    $('#viewModal').addClass('show');
    $('#view_project_name_header').text('กำลังโหลดข้อมูล...');

    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const data = res.data;
            $('#view_project_name_header').text(data.project_name || 'ไม่ระบุชื่อโครงการ');
            $('#view_customer_name_header').text(data.customers_name || '-');
            $('#view_ref_number_header').text(data.number || '-');

            // Status Badge
            let statusBadge = '';
            if (data.status_val === 'On-site') statusBadge = '<span class="status-pill status-onsite"><i class="fas fa-building"></i> On-site</span>';
            else if (data.status_val === 'Remote') statusBadge = '<span class="status-pill status-remote"><i class="fas fa-laptop-house"></i> Remote</span>';
            else statusBadge = '<span class="status-pill status-sub"><i class="fas fa-user-friends"></i> Subcontractor</span>';
            $('#view_status_badge').html(statusBadge);

            // Date Range
            const start = formatDate(data.start_date);
            const end = data.end_date ? formatDate(data.end_date) : 'ปัจจุบัน';
            $('#view_date_range').text(`${start} - ${end}`);

            // Equipment & Details
            const sn = data.sn || data['s/n'] || '-';
            $('#view_equipment_sn').html(`${data.equipment || '-'}<br><small style="color:#94a3b8;">SN: ${sn}</small>`);
            $('#view_symptom').text(data.symptom || '-');
            $('#view_action').text(data.action_taken || '-');

            // File Attachment
            if (data.file_path) {
                $('#view_file_container').html(`
                    <a href="uploads/${data.file_path}" target="_blank" class="btn-file-download">
                        <i class="fas fa-file-pdf"></i> ดูไฟล์แนบ
                    </a>`);
            } else {
                $('#view_file_container').html('<div style="text-align:center; color:#cbd5e1; font-style:italic;">ไม่มีไฟล์แนบ</div>');
            }
        }
    }, 'json');
}

/**
 * แก้ไขข้อมูล (Edit)
 */
function editData(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const data = res.data;
            $('#service_id').val(data.service_id);
            $('#detail_id').val(data.detail_id);
            $('#customers_id').val(data.customers_id);
            $('#project_name').val(data.project_name);
            $('#equipment').val(data.equipment);
            $('#sn').val(data.sn || data['s/n']);
            $('#number').val(data.number);
            $('#symptom').val(data.symptom);
            $('#action_taken').val(data.action_taken);
            $('#status').val(data.status_val);
            $('#start_date').val(data.start_date);
            $('#end_date').val(data.end_date);

            if (data.file_path) {
                $('#filePreview').html(`<span class="text-success"><i class="fas fa-check"></i> มีไฟล์เดิมอยู่แล้ว</span>`);
            } else {
                $('#filePreview').html('');
            }

            $('#modalTitle').text('แก้ไขข้อมูลบริการ');
            $('#serviceModal').addClass('show');
        }
    }, 'json');
}

// --- 4. ฟังก์ชัน Helper และ UI ---

function openModal() {
    $('#serviceForm')[0].reset();
    $('#service_id').val('0');
    $('#detail_id').val('0');
    $('#filePreview').html('');
    $('#modalTitle').text('เพิ่มงานบริการใหม่');
    $('#start_date').val(new Date().toISOString().split('T')[0]);
    $('#serviceModal').addClass('show');
}

function closeModal() { $('#serviceModal').removeClass('show'); }
function closeViewModal() { $('#viewModal').removeClass('show'); }

function previewFile(input) {
    if (input.files && input.files[0]) {
        $('#filePreview').html(`<span style="color:#059669"><i class="fas fa-check"></i> เลือกไฟล์: ${input.files[0].name}</span>`);
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', { day: '2-digit', month: 'short', year: 'numeric' });
}

function animateCounter(id, start, end, duration) {
    let obj = document.getElementById(id);
    if (!obj) return;
    let target = obj.querySelector('.count');
    if (!target) target = obj;

    let range = end - start;
    let current = start;
    let increment = end > start ? 1 : -1;
    let stepTime = Math.abs(Math.floor(duration / range));
    stepTime = Math.max(stepTime, 20);

    let timer = setInterval(function () {
        current += increment;
        target.innerText = current;
        if (current == end) clearInterval(timer);
    }, stepTime);
}

// --- Filter Logic ---
let selectedStatusFilter = "";

function filterByStatus(status) {
    selectedStatusFilter = status.toUpperCase();
    $('.status-card').removeClass('active-filter');

    if (status === "") $('#cardTotal').addClass('active-filter');
    else if (status === "On-site") $('#cardOnsite').addClass('active-filter');
    else if (status === "Remote") $('#cardRemote').addClass('active-filter');
    else if (status === "Subcontractor") $('#cardSub').addClass('active-filter');

    filterTable();
}

function filterTable() {
    const searchVal = $('#searchInput').val().toUpperCase();
    const projectVal = $('#projectFilter').val().toUpperCase();

    $('#tableBody tr').each(function () {
        if ($(this).find('td').length < 2) return; // ข้ามแถว Loading

        const allText = $(this).text().toUpperCase();
        const rowProject = $(this).find('td:eq(2)').text().trim().toUpperCase();
        const rowStatus = $(this).find('td:eq(5)').text().trim().toUpperCase();

        const matchSearch = allText.includes(searchVal);
        const matchProject = projectVal === "" || rowProject === projectVal;
        const matchStatus = selectedStatusFilter === "" || rowStatus.includes(selectedStatusFilter);

        if (matchSearch && matchProject && matchStatus) $(this).show();
        else $(this).hide();
    });
}
