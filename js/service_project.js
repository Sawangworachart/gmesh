// js หน้า Service ของ admin
const API_URL = 'service_project.php?api=true';

$(document).ready(function () {
    // ---------------------------------------------------------
    // จุดที่แก้ไข: ปิดการเรียกใช้ loadCustomers() ชั่วคราว
    // สาเหตุ: เนื่องจากไม่มีฟังก์ชันนี้ในไฟล์ ทำให้ Script error และหยุดทำงาน
    // ถ้าตัวเลือก (Dropdown) ลูกค้าถูกสร้างจาก PHP แล้ว บรรทัดนี้ไม่จำเป็นครับ
    // ---------------------------------------------------------
    // loadCustomers(); 

    // โหลดข้อมูลสรุปและตาราง (จะทำงานได้แล้วหลังจากปิดบรรทัดบน)
    loadSummary();
    loadTable();

    // Handle Submit Form
    $('#serviceForm').on('submit', function (e) {
        e.preventDefault();

        // ตรวจสอบว่า API_URL ถูกกำหนดค่าไว้หรือยัง ถ้ายังให้กำหนดค่าตรงนี้
        // const API_URL = 'service_project.php?api=true'; 

        let formData = new FormData(this);
        formData.append('action', 'save_data');

        Swal.fire({
            title: 'กำลังบันทึก...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: (typeof API_URL !== 'undefined' ? API_URL : 'service_project.php?api=true'), // กันพลาดกรณีตัวแปรหาย
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // ปิด Modal (ต้องมั่นใจว่ามีฟังก์ชันนี้ หรือใช้ code ปิด modal โดยตรง)
                    if (typeof closeModal === 'function') {
                        closeModal();
                    } else {
                        // กรณีไม่มีฟังก์ชัน closeModal ให้ใช้ jQuery ปิดเอง
                        $('#serviceModal').fadeOut();
                        $('.modal-overlay').removeClass('active');
                    }

                    loadSummary();
                    loadTable();

                    // ล้างค่าในฟอร์มหลังจากบันทึกเสร็จ
                    $('#serviceForm')[0].reset();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire('เกิดข้อผิดพลาด', 'การเชื่อมต่อขัดข้อง: ' + error, 'error');
            }
        });
    });
});

// --- Fetch Customers ---
function loadCustomers() {
    $.post(API_URL, { action: 'fetch_customers' }, function (res) {
        if (res.success) {
            let opts = '<option value="">-- เลือกลูกค้า --</option>';
            res.data.forEach(c => {
                let contact = c.contact_name ? ` (${c.contact_name})` : '';
                opts += `<option value="${c.customers_id}">${c.customers_name}${contact}</option>`;
            });
            $('#customers_id').html(opts);
        }
    }, 'json');
}

// --- View Data (ดูรายละเอียด) ---
function viewData(id) {
    $('#viewModal').addClass('show');

    // แสดงข้อความ Loading ระหว่างรอข้อมูล
    $('#view_project_name_header').text('กำลังโหลดข้อมูล...');
    $('#view_customer_name_header').text('-');
    $('#view_ref_number_header').text('-');

    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const data = res.data;

            // 1. ส่งข้อมูลไปที่ Header
            $('#view_project_name_header').text(data.project_name || 'ไม่ระบุชื่อโครงการ');
            $('#view_customer_name_header').text(data.customers_name || '-');
            $('#view_ref_number_header').text(data.number || '-');

            // 2. จัดการรูปแบบบริการ (Status)
            let statusBadge = '';
            if (data.status_val === 'On-site') {
                statusBadge = '<span style="color:#4361ee;"><i class="fas fa-building"></i> On-site</span>';
            } else if (data.status_val === 'Remote') {
                statusBadge = '<span style="color:#0ea5e9;"><i class="fas fa-laptop-house"></i> Remote</span>';
            } else {
                statusBadge = '<span style="color:#f59e0b;"><i class="fas fa-user-friends"></i> Subcontractor</span>';
            }
            $('#view_status_badge').html(statusBadge);

            // 3. จัดการช่วงเวลา
            const start = formatDate(data.start_date);
            const end = data.end_date ? formatDate(data.end_date) : 'ปัจจุบัน';
            $('#view_date_range').text(`${start} - ${end}`);

            // 4. จัดการอุปกรณ์
            const sn = data.sn || data['s/n'] || '-';
            $('#view_equipment_sn').html(`${data.equipment || '-'}<br><small style="color:#64748b; font-weight:400;">SN: ${sn}</small>`);

            // 5. รายละเอียดงาน
            $('#view_symptom').text(data.symptom || '-');
            $('#view_action').text(data.action_taken || '-');

            if (data.file_path) {
                $('#view_file_container').html(`
        <div style="display: flex; justify-content: flex-start;"> 
            <a href="uploads/${data.file_path}" target="_blank" 
               style="text-decoration:none; 
                      display:inline-flex; 
                      align-items:center; 
                      gap:8px; 
                      background:#4361ee; 
                      padding:8px 16px; 
                      border-radius:8px; 
                      color:white; 
                      font-weight:600; 
                      font-size:0.9rem; 
                      transition:0.3s;
                      box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);"
               onmouseover="this.style.background='#3651d1'" 
               onmouseout="this.style.background='#4361ee'">
                <i class="fas fa-file-pdf"></i> ดูไฟล์แนบ
            </a>
        </div>
    `);
            } else {
                $('#view_file_container').html(`
                    <div style="text-align: center; padding: 20px; border: 2px dashed #e2e8f0; border-radius: 12px; color: #cbd5e1;">
                        <i class="fas fa-file-alt" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <div style="font-size: 0.85rem; font-style: italic;">ไม่มีไฟล์แนบ</div>
                    </div>
                `);
            }
        }
    }, 'json');
}


function closeViewModal() { $('#viewModal').removeClass('show'); }

// --- Open/Close Modal ---
function openModal() {
    $('#serviceForm')[0].reset();
    $('#service_id').val('0');
    $('#filePreview').html('');
    $('#modalTitle').text('เพิ่มงานบริการใหม่');
    // ตั้งค่าวันเริ่มต้นเป็นวันนี้
    $('#start_date').val(new Date().toISOString().split('T')[0]);
    $('#serviceModal').addClass('show');
}

function closeModal() { $('#serviceModal').removeClass('show'); }

// --- Edit Data ---
function editData(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const data = res.data;
            $('#service_id').val(data.service_id);
            $('#detail_id').val(data.detail_id);
            $('#customers_id').val(data.customers_id);
            $('#project_name').val(data.project_name);
            $('#equipment').val(data.equipment);
            $('#sn').val(data.sn || data['s/n']); // รับค่า S/N ให้ถูกต้อง
            $('#number').val(data.number);
            $('#symptom').val(data.symptom);
            $('#action_taken').val(data.action_taken);
            $('#status').val(data.status_val); // Map ค่าสถานะให้ตรง Dropdown
            $('#start_date').val(data.start_date);
            $('#end_date').val(data.end_date);

            if (data.file_path) {
                $('#filePreview').html(`<a href="uploads/${data.file_path}" target="_blank" class="text-primary"><i class="fas fa-file-alt"></i> ดูไฟล์เดิม</a>`);
            } else {
                $('#filePreview').html('');
            }

            $('#modalTitle').text('แก้ไขข้อมูล');
            $('#serviceModal').addClass('show');
        }
    }, 'json');
}

// --- Animation Counter ---
function animateCounter(id, start, end, duration) {
    let obj = document.getElementById(id);
    if (!obj) return;
    let target = obj.querySelector('.count');
    if (!target) target = obj;

    let range = end - start;
    let current = start;
    let increment = end > start ? 1 : -1;
    let stepTime = Math.abs(Math.floor(duration / range));

    if (range === 0) {
        target.innerText = end;
        return;
    }
    stepTime = Math.max(stepTime, 20);

    let timer = setInterval(function () {
        current += increment;
        target.innerText = current;
        if (current == end) {
            clearInterval(timer);
        }
    }, stepTime);
}

// --- Load Summary (Cards) ---
function loadSummary() {
    $.get(API_URL, { action: 'fetch_status_summary' }, function (res) {
        if (res.success) {
            // key ต้องตรงกับที่ PHP ส่งมา
            animateCounter('cardOnsite', 0, res.data['On-site'] || 0, 1000);
            animateCounter('cardRemote', 0, res.data['Remote'] || 0, 1000);
            animateCounter('cardSub', 0, res.data['แจ้ง Subcontractor'] || 0, 1000);
            animateCounter('cardTotal', 0, res.data['Total'] || 0, 1000);
        }
    }, 'json');
}

// --- Load Table (FIXED) ---
function loadTable() {
    $.get(API_URL, { action: 'fetch_all' }, function (res) {
        if (res.success) {
            let html = '';

            if (!res.data || res.data.length === 0) {
                html = '<tr><td colspan="7" class="text-center p-4 text-muted">ไม่พบข้อมูลงานบริการ</td></tr>';
            } else {
                res.data.forEach((item, index) => {

                    // กัน undefined ทุกตัว
                    const id = item.detail_id ?? 0;
                    const number = item.number ?? '-';
                    const startDate = item.start_date ?? null;
                    const endDate = item.end_date ?? null; // ดึงค่าวันจบมาใช้งาน
                    const project = item.project_name ?? '-';
                    const customer = item.customers_name ?? '-';
                    const agency = item.agency ?? '-';
                    const equipment = item.equipment ?? '-';
                    const sn = item.sn ?? '-';
                    const status = item.status ?? 'On-site';

                    // Status badge
                    let badgeClass = 'badge-onsite';
                    let statusIcon = 'fa-building';

                    if (status === 'Remote') {
                        badgeClass = 'badge-remote';
                        statusIcon = 'fa-laptop-house';
                    } else if (status === 'แจ้ง Subcontractor') {
                        badgeClass = 'badge-sub';
                        statusIcon = 'fa-user-friends';
                    }

                    html += `
<tr class="table-row-anim" style="animation-delay: ${index * 0.05}s">
    <td class="text-center text-muted" style="font-weight: 500; font-size: 0.85rem;">
        ${number}
    </td>

    <td style="min-width: 110px;">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div style="font-size: 0.82rem; color: #10b981; font-weight: 700; display: flex; align-items: center; gap: 5px;">
                <i class="fas fa-play-circle" style="font-size: 0.7rem;"></i>
                ${startDate ? formatDate(startDate) : '-'}
            </div>
            <div style="font-size: 0.82rem; color: ${endDate ? '#ef4444' : '#94a3b8'}; font-weight: 700; display: flex; align-items: center; gap: 5px; border-top: 1px dashed #f1f5f9; padding-top: 2px;">
                <i class="fas fa-check-circle" style="font-size: 0.7rem;"></i>
                ${endDate ? formatDate(endDate) : '<span style="font-weight:400; font-style: italic; font-size: 0.75rem;">ไม่ได้ระบุ</span>'}
            </div>
        </div>
    </td>

    <td>
        <div style="font-weight:700; color:#1e293b; font-size: 0.9rem; line-height: 1.3;">${project}</div>
    </td>

    <td>
        <div style="font-weight:600; color:#475569; font-size: 0.88rem;">${customer}</div>
        <div style="font-size:0.78rem; color:#94a3b8;">${agency}</div>
    </td>

   <td style="max-width: 150px; vertical-align: top; padding: 12px 8px;">
    <div style="font-size: 0.82rem; 
                color: #64748b; 
                display: -webkit-box; 
                -webkit-line-clamp: 2; 
                -webkit-box-orient: vertical; 
                overflow: hidden; 
                line-height: 1.4;
                margin-bottom: 4px;" 
         title="${equipment}">
        <i class="fas fa-microchip" style="font-size:0.7rem; margin-right:3px; color: #94a3b8;"></i> 
        ${equipment}
    </div>
    <div style="font-size: 0.72rem; color: #cbd5e1; font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
        SN: ${sn}
    </div>
</td>

    <td class="text-center">
        <span class="status-badge ${badgeClass}" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 8px;">
            <i class="fas ${statusIcon}"></i> ${status}
        </span>
    </td>

    <td class="text-center">
        <div class="action-buttons" style="display: flex; justify-content: center; gap: 5px;">
            <button class="btn-icon btn-att" onclick="viewData(${id})" title="ดูรายละเอียด" style="width: 30px; height: 30px; font-size: 0.8rem;">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn-icon btn-edit" onclick="editData(${id})" title="แก้ไข" style="width: 30px; height: 30px; font-size: 0.8rem;">
                <i class="fas fa-pencil-alt"></i>
            </button>
        </div>
    </td>
</tr>`;
                });
            }

            $('#tableBody').html(html);
        } else {
            $('#tableBody').html('<tr><td colspan="7" class="text-center text-danger">โหลดข้อมูลไม่สำเร็จ</td></tr>');
        }
    }, 'json')
        .fail(function (xhr) {
            console.error(xhr.responseText);
            $('#tableBody').html('<tr><td colspan="7" class="text-center text-danger">API Error</td></tr>');
        });
}


function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
}

// --- Filter Logic ---
let selectedStatusFilter = "";

function filterByStatus(status) {
    // ปรับ Logic ให้ตรงกับค่าในตาราง (Subcontractor)
    if (status === "Subcontractor") status = "Subcontractor";
    selectedStatusFilter = status.toUpperCase();

    // UI Active State
    document.querySelectorAll('.status-card').forEach(c => c.classList.remove('active-filter'));
    if (status === "") document.querySelector('.card-total').classList.add('active-filter');
    else if (status === "On-site") document.querySelector('.card-onsite').classList.add('active-filter');
    else if (status === "Remote") document.querySelector('.card-remote').classList.add('active-filter');
    else if (status === "Subcontractor") document.querySelector('.card-sub').classList.add('active-filter');

    filterTable();
}

function filterTable() {
    // ดึงค่าการค้นหาและแปลงเป็นตัวพิมพ์ใหญ่เพื่อความแม่นยำ
    const searchVal = $('#searchInput').val().toUpperCase();
    const projectVal = $('#projectFilter').val().toUpperCase();

    // วนลูปผ่านทุกแถวในตาราง (ยกเว้น Header)
    $('#tableBody tr').each(function () {
        // ข้ามแถวที่เป็นข้อความ "Loading" หรือ "ไม่พบข้อมูล"
        if ($(this).find('td').length < 2) return;

        const allText = $(this).text().toUpperCase();
        const rowProject = $(this).find('td:eq(2)').text().trim().toUpperCase();
        const rowStatus = $(this).find('td:eq(5)').text().trim().toUpperCase();

        // ตรวจสอบเงื่อนไขทั้ง 3 อย่าง (AND Logic)
        const matchSearch = allText.includes(searchVal);
        const matchProject = projectVal === "" || rowProject === projectVal;
        const matchStatus = selectedStatusFilter === "" || rowStatus.includes(selectedStatusFilter.toUpperCase());

        // แสดงแถวที่ผ่านทุกเงื่อนไข ซ่อนแถวที่ไม่ผ่าน
        if (matchSearch && matchProject && matchStatus) {
            $(this).fadeIn(200); // เพิ่ม Animation เล็กน้อยให้ดูนุ่มนวล
        } else {
            $(this).hide();
        }
    });
}

// Window Events
window.onclick = function (e) {
    if (e.target == document.getElementById('serviceModal')) closeModal();
    if (e.target == document.getElementById('viewModal')) closeViewModal();
}