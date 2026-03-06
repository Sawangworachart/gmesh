// =========================================
// JS สำหรับหน้า Preventive Maintenance (Admin)
// =========================================

const API_URL = 'pm_project.php?api=true';
const FORM_ID = '#pmProjectForm';
const TABLE_BODY_ID = '#tableBody';

// --- 1. เริ่มทำงานเมื่อโหลดหน้าเว็บเสร็จ (Document Ready) ---
$(document).ready(function () {
    // โหลดข้อมูลโครงการทั้งหมด
    fetchAllData();

    // ตั้งค่า Flatpickr (ปฏิทินเลือกวันที่)
    initFlatpickr();

    // จัดการการส่งฟอร์มบันทึกโครงการ
    $(FORM_ID).on('submit', function (e) {
        e.preventDefault();
        saveProject();
    });

    // จัดการการส่งฟอร์มบันทึกแผน MA
    $('#maManageForm').on('submit', function (e) {
        e.preventDefault();
        saveMAOnly();
    });

    // ตรวจสอบการเปลี่ยนแปลงสถานะ (แสดงช่องหมายเหตุเมื่อเลือก "รอการตรวจสอบ")
    $('#status').on('change', function () {
        if ($(this).val() === 'รอการตรวจสอบ') {
            $('#statusRemarkWrapper').slideDown();
        } else {
            $('#statusRemarkWrapper').slideUp();
        }
    });

    // ระบบค้นหา (Search)
    $('#searchInput').on('keyup', function () {
        const value = $(this).val().toLowerCase().trim();
        const rows = $("#tableBody tr").not("#no-data-row");
        let foundAny = false;

        rows.each(function () {
            const text = $(this).text().toLowerCase();
            const isMatch = text.indexOf(value) > -1;
            $(this).toggle(isMatch);
            if (isMatch) foundAny = true;
        });

        // แสดงข้อความเมื่อไม่พบข้อมูล
        if (!foundAny && value !== "") {
            if ($("#no-data-row").length === 0) {
                $("#tableBody").append(`
                    <tr id="no-data-row">
                        <td colspan="8" class="text-center p-5 text-muted">
                            <i class="fas fa-search fa-2x mb-3" style="opacity:0.5;"></i><br>
                            ไม่พบข้อมูลสำหรับ: "<strong>${$(this).val()}</strong>"
                        </td>
                    </tr>
                `);
            }
        } else {
            $("#no-data-row").remove();
        }
    });

    // จัดการ Filter ตามสถานะ (Card Filter)
    $(document).on('click', '.stat-card', function () {
        const status = $(this).data('status'); // ต้องเพิ่ม data-status ใน HTML ด้วยถ้าจะใช้
        
        // ถ้าไม่มี data-status (เช่นคลิกที่ Total) ให้แสดงทั้งหมด
        if (!status) return; 

        $('.stat-card').removeClass('active');
        $(this).addClass('active');

        $('#tableBody tr').each(function () {
            if (status === 'all') {
                $(this).show();
            } else {
                const statusText = $(this).find('.status-pill').text().trim();
                if (statusText === status) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            }
        });
    });

    // ตรวจสอบการพิมพ์หมายเหตุใน MA Card เพื่อเปลี่ยนสี
    $(document).on('keyup', '.ma-remark-input', function () {
        const remark = $(this).val().trim();
        const card = $(this).closest('.ma-card');

        if (remark !== "") {
            card.css({ 'border-left': '4px solid #059669', 'background': '#f0fdf4' });
        } else {
            card.css({ 'border-left': '4px solid #10b981', 'background': '#fff' });
        }
    });
});

// --- 2. ฟังก์ชันหลัก (Core Functions) ---

/**
 * ตั้งค่า Flatpickr สำหรับช่องวันที่
 */
function initFlatpickr() {
    const config = {
        dateFormat: "Y-m-d",   // format ที่ส่งเข้า PHP
        altInput: true,
        altFormat: "d/m/Y",    // format ที่แสดงให้ user เห็น
        allowInput: true
    };
    flatpickr("#deliver_work_date", config);
    flatpickr("#end_date", config);
}

/**
 * โหลดข้อมูลทั้งหมดจาก Server
 */
function fetchAllData() {
    $(TABLE_BODY_ID).html('<tr><td colspan="8" class="text-center p-4">กำลังโหลด...</td></tr>');

    $.post(API_URL, { action: 'fetch_all' }, function (res) {
        if (res.success) {
            updateStats(res.data);
            let html = '';
            if (res.data.length === 0) {
                html = '<tr><td colspan="8" class="text-center p-4 text-muted">ไม่พบข้อมูล</td></tr>';
            } else {
                res.data.forEach((p) => {
                    const statusLabels = { 1: 'รอการตรวจสอบ', 2: 'กำลังดำเนินการ', 3: 'ดำเนินการเสร็จสิ้น' };
                    let statusText = statusLabels[p.status] || 'ไม่ระบุ';
                    
                    // สร้าง HTML ของแถวตาราง
                    html += `
                    <tr>
                        <td style="font-weight:bold;">${p.number}</td>
                        <td><span style="font-weight:600; color:#333;">${p.project_name}</span></td>
                        <td>${p.customers_name || '-'}</td>
                        <td>
                            <span class="status-pill status-${statusText} ${statusText === 'รอการตรวจสอบ' ? 'status-clickable' : ''}"
                                  data-id="${p.pmproject_id}"
                                  data-remark="${encodeURIComponent(p.status_remark || '')}"
                                  ${statusText === 'รอการตรวจสอบ' ? 'onclick="addCheckRemarkFromEl(this)"' : ''}>
                                ${statusText}
                            </span>
                        </td>
                        <td>${p.contract_period}</td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <div style="color: #10b981; font-weight: 600; font-size: 0.8rem;" title="วันส่งมอบงาน">
                                    <i class="fas fa-calendar-check"></i> ${formatDate(p.deliver_work_date)}
                                </div>
                                <div style="color: #ef4444; font-weight: 600; font-size: 0.8rem;" title="วันสิ้นสุดสัญญา">
                                    <i class="fas fa-calendar-times"></i> ${formatDate(p.end_date)}
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <button class="btn-icon" onclick="openMAModal(${p.pmproject_id})" title="จัดการแผน MA"><i class="fas fa-calendar-alt" style="color:#16a34a;"></i></button>
                            <button class="btn-icon" onclick="viewProject(${p.pmproject_id})" title="ดูรายละเอียด"><i class="fas fa-eye" style="color:#2563eb;"></i></button>
                            <button class="btn-icon" onclick="openModal(${p.pmproject_id})" title="แก้ไขข้อมูล"><i class="fas fa-edit" style="color:#f59e0b;"></i></button>
                        </td>
                    </tr>`;
                });
            }
            $(TABLE_BODY_ID).html(html);
        }
    }, 'json');
}

/**
 * บันทึกข้อมูลโครงการ (เพิ่ม/แก้ไข)
 */
function saveProject() {
    const formData = new FormData($(FORM_ID)[0]);
    formData.append('action', 'save');
    $.ajax({
        url: API_URL, type: 'POST', data: formData,
        contentType: false, processData: false, dataType: 'json',
        success: function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1000, showConfirmButton: false });
                closeModal();
                fetchAllData();
            } else { Swal.fire('Error', res.message, 'error'); }
        }
    });
}

/**
 * บันทึกเฉพาะข้อมูล MA
 */
window.saveMAOnly = function () {
    const form = document.getElementById('maManageForm');
    const formData = new FormData(form);
    formData.append('action', 'save_ma_only');

    $.ajax({
        url: API_URL, type: 'POST', data: formData,
        contentType: false, processData: false, dataType: 'json',
        success: function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1000, showConfirmButton: false });
                closeMAModal();
                fetchAllData();
            } else { Swal.fire('Error', res.message, 'error'); }
        },
        error: function () { Swal.fire('Error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'); }
    });
};

// --- 3. ฟังก์ชันจัดการ Modal (Open/Close) ---

window.openModal = function (id) {
    $(FORM_ID)[0].reset();
    $('#pmproject_id').val(0);
    $('#maScheduleContainer').empty();
    $('#statusRemarkWrapper').hide();

    if (id == 0) {
        $('#modalTitle').text('เพิ่มข้อมูลโครงการใหม่');
        $('#maSectionWrapper').show();
        $('#pmProjectModal').addClass('show');
    } else {
        $('#modalTitle').text('แก้ไขข้อมูลโครงการ');
        $('#maSectionWrapper').hide(); // ซ่อนส่วนสร้าง MA อัตโนมัติเวลาแก้ไข
        $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
            if (res.success) {
                const p = res.data;
                $('#pmproject_id').val(p.pmproject_id);
                $('#number').val(p.number);
                $('#project_name').val(p.project_name);
                $('#customers_id').val(p.customers_id);
                $('#responsible_person').val(p.responsible_person);
                $('#deliver_work_date').val(p.deliver_work_date);
                $('#end_date').val(p.end_date);
                $('#contract_period').val(p.contract_period);
                $('#going_ma').val(p.going_ma);

                const statusMap = { 1: 'รอการตรวจสอบ', 2: 'กำลังดำเนินการ', 3: 'ดำเนินการเสร็จสิ้น' };
                $('#status').val(statusMap[p.status]);

                $('#status_remark').val(p.status_remark || '');
                if (statusMap[p.status] === 'รอการตรวจสอบ') {
                    $('#statusRemarkWrapper').show();
                }
                
                // อัปเดต Flatpickr ให้แสดงค่าวันที่ที่ดึงมา
                if (document.querySelector("#deliver_work_date")._flatpickr) {
                    document.querySelector("#deliver_work_date")._flatpickr.setDate(p.deliver_work_date);
                }
                if (document.querySelector("#end_date")._flatpickr) {
                    document.querySelector("#end_date")._flatpickr.setDate(p.end_date);
                }

                $('#pmProjectModal').addClass('show');
            }
        }, 'json');
    }
};

window.closeModal = function () { $('#pmProjectModal').removeClass('show'); };

window.openMAModal = function (id) {
    $('#ma_pmproject_id').val(id);
    $('#maManageContainer').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
    
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const p = res.data;
            const statusLabels = { 1: 'รอการตรวจสอบ', 2: 'กำลังดำเนินการ', 3: 'ดำเนินการเสร็จสิ้น' };
            let statusText = statusLabels[p.status] || 'ไม่ระบุ';

            $('#ma_project_title').text(p.project_name);
            $('#ma_project_dates').text(`${formatDate(p.deliver_work_date)} - ${formatDate(p.end_date)}`);
            $('#ma_project_status').text(statusText).attr('class', `status-pill status-${statusText}`);
            
            $('#ma_ref_start_date').val(p.deliver_work_date);
            $('#ma_ref_end_date').val(p.end_date);
            
            renderMAModern(res.ma);
        }
    }, 'json');
    $('#maManageModal').addClass('show');
};

window.closeMAModal = function () { $('#maManageModal').removeClass('show'); };

window.viewProject = function (id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (!res.success) return;
        const p = res.data;
        const statusMap = { 1: 'รอการตรวจสอบ', 2: 'กำลังดำเนินการ', 3: 'ดำเนินการเสร็จสิ้น' };
        const statusText = statusMap[p.status] || 'ไม่ระบุ';

        $('#view_project_name').text(p.project_name);
        $('#view_number').text(p.number);
        $('#view_customer_name').text(p.customers_name || '-');
        $('#view_responsible').text(p.responsible_person || '-');
        $('#view_contract_period_display').text(p.contract_period || '-');
        $('#view_deliver_date').html(`<i class="far fa-calendar-check"></i> ${formatDate(p.deliver_work_date)}`);
        $('#view_end_date').html(`<i class="far fa-calendar-times"></i> ${formatDate(p.end_date)}`);
        $('#view_status_container').html(`<span class="status-pill status-${statusText}">${statusText}</span>`);
        $('#view_going_ma').html(p.going_ma ? p.going_ma : '<span style="color:#ccc;">ไม่มีข้อมูลรายละเอียด</span>');

        if (p.status_remark && p.status_remark.trim() !== '') {
            $('#view_status_remark').text(p.status_remark);
            $('#view_remark_wrapper').show();
        } else {
            $('#view_remark_wrapper').hide();
        }

        let maHtml = '';
        if (res.ma && res.ma.length > 0) {
            res.ma.forEach((m, i) => {
                const isDone = (m.is_done == 1);
                const rowBg = isDone ? 'background-color: #f0fdf4;' : '';
                const statusText = isDone ? 'เสร็จสิ้น' : 'รอดำเนินการ';
                const statusColor = isDone ? '#16a34a' : '#94a3b8';
                const statusIcon = isDone ? '<i class="fas fa-check-circle"></i> ' : '<i class="far fa-clock"></i> ';
                const fileLink = m.file_path ? `<a href="${m.file_path}" target="_blank" style="color: #64748b;"><i class="fas fa-file-pdf fa-lg"></i></a>` : '-';

                maHtml += `
                <tr style="border-bottom: 1px solid #f1f5f9; ${rowBg}">
                    <td style="padding: 12px; text-align: center; opacity: 0.6;">${i + 1}</td>
                    <td style="padding: 12px; font-weight: 500;">${formatDate(m.ma_date)}</td>
                    <td style="padding: 12px;">
                        <span style="font-weight: 600; color: ${statusColor}; font-size: 0.85rem;">${statusIcon}${statusText}</span>
                    </td>
                    <td style="padding: 12px; color: #475569; font-size: 0.85rem;">${m.remark || '-'}</td>
                    <td style="padding: 12px; text-align: center;">${fileLink}</td>
                </tr>`;
            });
        } else {
            maHtml = '<tr><td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8;">ยังไม่มีข้อมูลแผน MA</td></tr>';
        }
        $('#view_ma_table tbody').html(maHtml);
        $('#viewProjectModal').addClass('show');
    }, 'json');
};

window.closeViewModal = function () { $('#viewProjectModal').removeClass('show'); };

// --- 4. ฟังก์ชันคำนวณและ Helper (Utilities) ---

/**
 * คำนวณแผน MA อัตโนมัติ
 */
window.calculateMA = function (containerId, startId, endId, freqId) {
    const container = $(containerId);
    const existingRows = container.find('.ma-card').length;

    const proceedCalculation = () => {
        const start = $(startId).val();
        const end = $(endId).val();
        const freq = parseInt($(freqId).val());

        if (!start || !end || isNaN(freq)) {
            Swal.fire('แจ้งเตือน', 'กรุณาระบุวันเริ่ม-จบสัญญา และรอบ MA', 'warning');
            return;
        }

        const startDate = parseLocalDate(start);
        const stopDate = parseLocalDate(end);

        let html = '';
        let count = 1;

        while (true) {
            const nextDate = addMonthsReal(startDate, freq * count);
            if (nextDate > stopDate) break;
            html += generateMARowHTML(count, formatDateForInput(nextDate), '', '');
            count++;
        }
        container.html(html || '<p class="text-center p-3 text-muted">ระยะเวลาสัญญาไม่เพียงพอสำหรับรอบที่เลือก</p>');
    };

    if (existingRows > 0) {
        Swal.fire({
            title: 'ยืนยันการคำนวณใหม่?',
            text: "ข้อมูลที่กรอกไว้จะหายไปทั้งหมด!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ใช่, คำนวณใหม่',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) proceedCalculation();
        });
    } else {
        proceedCalculation();
    }
};

/**
 * สร้าง HTML สำหรับแถว MA
 */
function generateMARowHTML(index, date, note, remark, filePath = '') {
    const fileLink = filePath ? `<div class="mt-1"><a href="${filePath}" target="_blank" style="font-size:0.8rem;"><i class="fas fa-file-pdf"></i> ดูไฟล์เดิม</a></div>` : '';
    const hasRemark = remark && remark.trim() !== "";
    const cardStyle = hasRemark ? "border-left: 4px solid #059669; background: #f0fdf4;" : "border-left: 4px solid #10b981; background: #fff;";

    return `
    <div class="ma-card fade-in" style="${cardStyle} margin-bottom: 15px; padding: 15px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; gap: 15px; align-items: flex-start;">
        <div class="ma-card-index" style="width: 30px; height: 30px; background: #ecfdf5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.85rem; flex-shrink: 0;">${index}</div>
        <div style="flex: 1; display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px;">
            <div>
                <label style="font-size: 0.75rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px;">วันที่บำรุงรักษา</label>
                <input type="date" name="ma_dates[]" class="form-control-custom" value="${date}" required>
            </div>
            <div>
                <label style="font-size: 0.75rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px;">รายละเอียดงวดงาน</label>
                <input type="text" name="ma_notes[]" class="form-control-custom" value="${note || 'MA ครั้งที่ ' + index}">
            </div>
            <div style="grid-column: span 2;">
                <label style="font-size: 0.75rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px;">หมายเหตุ / ผลการดำเนินงาน</label>
                <input type="text" name="ma_remarks[]" class="form-control-custom ma-remark-input" value="${remark || ''}" placeholder="กรอกหมายเหตุเพื่อยืนยันว่าเสร็จสิ้น" style="width: 100%;">
            </div>
            <div style="grid-column: span 2;">
                <label style="font-size: 0.75rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px;"><i class="fas fa-paperclip"></i> แนบไฟล์หลักฐาน</label>
                <input type="file" name="ma_files[]" class="form-control-custom">
                <input type="hidden" name="ma_existing_files[]" value="${filePath}">
                ${fileLink}
            </div>
        </div>
        <button type="button" class="btn-icon" onclick="$(this).closest('.ma-card').remove()" style="color: #ef4444; background: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; width: 35px; height: 35px;"><i class="fas fa-trash-alt"></i></button>
    </div>`;
}

// ฟังก์ชันเพิ่มแถวเอง
window.addMARow = function (containerSelector) {
    const container = $(containerSelector);
    if (container.find('.text-center').length > 0) container.empty();
    const idx = container.children('.ma-card').length + 1;
    container.append(generateMARowHTML(idx, '', `งวดพิเศษ / เพิ่มเติม`, ''));
};

function renderMAModern(data) {
    const container = $('#maManageContainer');
    container.empty();
    if (!data || data.length === 0) {
        container.html('<div class="text-center p-5 text-muted"><p>ยังไม่มีแผนการบำรุงรักษา (MA)</p></div>');
    } else {
        data.forEach((item, index) => {
            container.append(generateMARowHTML(index + 1, item.ma_date, item.note, item.remark, item.file_path));
        });
    }
}

// ฟังก์ชันอื่นๆ
function exportExcel() {
    window.open('pm_project_export.php', '_blank');
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '-' || dateStr === '0000-00-00' || dateStr === null) return 'ไม่ได้ระบุ';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return 'ไม่ได้ระบุ';
    return d.toLocaleDateString('th-TH', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateForInput(date) {
    const y = date.getFullYear();
    const m = ("0" + (date.getMonth() + 1)).slice(-2);
    const d = ("0" + date.getDate()).slice(-2);
    return `${y}-${m}-${d}`;
}

function parseLocalDate(dateStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function addMonthsReal(date, months) {
    const d = new Date(date);
    const day = d.getDate();
    d.setDate(1);
    d.setMonth(d.getMonth() + months);
    const lastDay = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    d.setDate(Math.min(day, lastDay));
    return d;
}

function updateStats(data) {
    const total = data.length;
    const pending = data.filter(x => parseInt(x.status) === 1).length;
    const processing = data.filter(x => parseInt(x.status) === 2).length;
    const completed = data.filter(x => parseInt(x.status) === 3).length;

    animateCount('#stat_total', total);
    animateCount('#stat_pending', pending);
    animateCount('#stat_processing', processing);
    animateCount('#stat_completed', completed);
}

function animateCount(elementId, targetValue) {
    const $el = $(elementId);
    let startValue = parseInt($el.text().replace(/,/g, '')) || 0;

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

function addCheckRemark(id, oldRemark = '') {
    Swal.fire({
        title: 'หมายเหตุรอการตรวจสอบ',
        input: 'textarea',
        inputValue: oldRemark,
        inputPlaceholder: 'กรอกหมายเหตุ...',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'save_check_remark', id: id, remark: result.value }, function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', timer: 1000, showConfirmButton: false });
                    fetchAllData();
                } else { Swal.fire('Error', res.message, 'error'); }
            }, 'json');
        }
    });
}

function addCheckRemarkFromEl(el) {
    const id = el.getAttribute('data-id');
    const oldRemark = decodeURIComponent(el.getAttribute('data-remark') || '');
    addCheckRemark(id, oldRemark);
}
