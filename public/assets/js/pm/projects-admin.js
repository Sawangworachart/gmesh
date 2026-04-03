// js หน้า Preventive Maintenance ของ admin
const API_URL = 'pm_project.php?api=true';
const FORM_ID = '#pmProjectForm';
const TABLE_BODY_ID = '#tableBody';

$(document).ready(function () {
    // 1. โหลดข้อมูลครั้งแรก
    fetchAllData();

    // 2. จัดการการส่งฟอร์ม (บันทึกข้อมูล)
    $(FORM_ID).on('submit', function (e) {
        e.preventDefault();
        saveProject();
    });

    $('#maManageForm').on('submit', function (e) {
        e.preventDefault();
        saveMAOnly();
    });

    // [แก้ไข] เปลี่ยน selector ให้ตรงกับ HTML id="statusRemarkWrapper"
    $('#status').on('change', function () {
        if ($(this).val() === 'รอการตรวจสอบ') {
            $('#statusRemarkWrapper').slideDown();
        } else {
            $('#statusRemarkWrapper').slideUp();
            // ถ้าไม่อยากให้เคลียร์ค่าออกเมื่อซ่อน คอมเมนต์บรรทัดล่างได้
            // $('#status_remark').val(''); 
        }
    });

    // 3. ระบบค้นหา (Search)
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

        if (!foundAny && value !== "") {
            if ($("#no-data-row").length === 0) {
                $("#tableBody").append(`
                    <tr id="no-data-row">
                        <td colspan="7" class="text-center p-5 text-muted">
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
});

function fetchAllData() {
    // เปลี่ยนตัวเลข colspan เป็น 8 ให้พอดีกับจำนวนคอลัมน์ใหม่
    $(TABLE_BODY_ID).html('<tr><td colspan="8" class="text-center p-4">กำลังโหลด...</td></tr>');

    $.post(API_URL, { action: 'fetch_all' }, function (res) {
        if (res.success) {
            updateStats(res.data); // เรียกใช้นักนับยอดตัวเลข
            let html = '';
            if (res.data.length === 0) {
                html = '<tr><td colspan="8" class="text-center p-4 text-muted">ไม่พบข้อมูล</td></tr>';
            } else {
                res.data.forEach((p) => {
                    // แปลงเลขสถานะ (1,2,3) เป็นข้อความ
                    const statusLabels = { 1: 'รอการตรวจสอบ', 2: 'กำลังดำเนินการ', 3: 'ดำเนินการเสร็จสิ้น' };
                    let statusText = statusLabels[p.status] || 'ไม่ระบุ';

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
                            <button class="btn-icon" onclick="openMAModal(${p.pmproject_id})"><i class="fas fa-calendar-alt" style="color:#16a34a;"></i></button>
                            <button class="btn-icon" onclick="viewProject(${p.pmproject_id})"><i class="fas fa-eye" style="color:#2563eb;"></i></button>
                            <button class="btn-icon" onclick="openModal(${p.pmproject_id})"><i class="fas fa-edit" style="color:#f59e0b;"></i></button>
                        </td>
                    </tr>`;
                });
            }
            $(TABLE_BODY_ID).html(html); // เมื่อใส่ข้อมูลครบ ตารางจะแสดงผลแทนที่คำว่ากำลังโหลด
        }
    }, 'json');
}

// --- ฟังก์ชันบันทึก ---
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

function formatDateForInput(date) {
    const y = date.getFullYear();
    const m = ("0" + (date.getMonth() + 1)).slice(-2);
    const d = ("0" + date.getDate()).slice(-2);
    return `${y}-${m}-${d}`;
}

function addMonthsReal(date, months) {
    const d = new Date(date);
    const day = d.getDate();

    // ไปวันที่ 1 ก่อน
    d.setDate(1);

    // ขยับเดือน
    d.setMonth(d.getMonth() + months);

    // หาวันสุดท้ายของเดือนใหม่
    const lastDay = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();

    // คืนวันที่ที่ถูกต้อง
    d.setDate(Math.min(day, lastDay));

    return d;
}

window.calculateMA = function (containerId, startId, endId, freqId) {
    const container = $(containerId);

    // ตรวจสอบว่าใน Container มีการ์ดข้อมูลอยู่แล้วหรือไม่
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

            const dStr = formatDateForInput(nextDate);
            // สร้าง Template แถวใหม่
            html += generateMARowHTML(count, dStr, '', '');
            count++;
        }
        container.html(html || '<p class="text-center p-3 text-muted">ระยะเวลาสัญญาไม่เพียงพอสำหรับรอบที่เลือก</p>');
    };

    // ถ้ามีข้อมูลอยู่แล้ว ให้ถามก่อน
    if (existingRows > 0) {
        Swal.fire({
            title: 'ยืนยันการคำนวณใหม่?',
            text: "ข้อมูลหมายเหตุและไฟล์ที่กรอกไว้ในรายการข้างล่างจะหายไปทั้งหมด!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'ใช่, คำนวณใหม่',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) proceedCalculation();
        });
    } else {
        proceedCalculation();
    }
};

// แยกฟังก์ชันสร้าง HTML ออกมาเพื่อความสะอาด (Helper)
function generateMARowHTML(index, date, note, remark, filePath = '') {
    const fileLink = filePath ? `<div class="mt-1"><a href="${filePath}" target="_blank" style="font-size:0.8rem;"><i class="fas fa-file-pdf"></i> ดูไฟล์เดิม</a></div>` : '';

    // --- [เพิ่มตรงนี้] เช็คสถานะหมายเหตุเพื่อกำหนดสีเริ่มต้น ---
    const hasRemark = remark && remark.trim() !== "";
    const cardStyle = hasRemark
        ? "border-left: 4px solid #059669; background: #f0fdf4;"
        : "border-left: 4px solid #10b981; background: #fff;";

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
// ค้นหาฟังก์ชัน window.openModal ใน pm_project.js
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

                // [แก้ไข] แมปตัวเลขกลับเป็นข้อความเพื่อให้ Select ทำงาน
                const statusMap = { 1: 'รอการตรวจสอบ', 2: 'กำลังดำเนินการ', 3: 'ดำเนินการเสร็จสิ้น' };
                $('#status').val(statusMap[p.status]);

                // จัดการ status_remark
                $('#status_remark').val(p.status_remark || '');
                if (statusMap[p.status] === 'รอการตรวจสอบ') {
                    $('#statusRemarkWrapper').show();
                }

                $('#pmProjectModal').addClass('show');
            }
        }, 'json');
    }
};

window.closeModal = function () { $('#pmProjectModal').removeClass('show'); };

// --- จัดการ MA Modal ---
// --- จัดการ MA Modal ---
window.openMAModal = function (id) {
    $('#ma_pmproject_id').val(id);
    $('#maManageContainer').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const p = res.data;
            
            // [แก้ไขตรงนี้] แปลงเลขสถานะให้เป็นข้อความภาษาไทยก่อนแสดงผล
            const statusLabels = { 1: 'รอการตรวจสอบ', 2: 'กำลังดำเนินการ', 3: 'ดำเนินการเสร็จสิ้น' };
            let statusText = statusLabels[p.status] || 'ไม่ระบุ';

            $('#ma_project_title').text(p.project_name);
            $('#ma_project_dates').text(`${formatDate(p.deliver_work_date)} - ${formatDate(p.end_date)}`);
            
            // [แก้ไขตรงนี้] ใส่ statusText แทน p.status และเปลี่ยน class ตามข้อความ
            $('#ma_project_status').text(statusText).attr('class', `status-pill status-${statusText}`);
            
            $('#ma_ref_start_date').val(p.deliver_work_date);
            $('#ma_ref_end_date').val(p.end_date);
            renderMAModern(res.ma);
        }
    }, 'json');
    $('#maManageModal').addClass('show');
};

window.closeMAModal = function () { $('#maManageModal').removeClass('show'); };

function renderMAModern(data) {
    const container = $('#maManageContainer');
    container.empty();

    if (!data || data.length === 0) {
        container.html('<div class="text-center p-5 text-muted"><p>ยังไม่มีแผนการบำรุงรักษา (MA)</p></div>');
    } else {
        data.forEach((item, index) => {
            // เรียกใช้ฟังก์ชันกลาง เพื่อให้หน้าตาการ์ดเหมือนกันทุกจุด
            const html = generateMARowHTML(index + 1, item.ma_date, item.note, item.remark, item.file_path);
            container.append(html);
        });
    }
}

function setFreq(m) {
    $('#ma_calc_frequency').val(m);
}

window.viewProject = function (id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (!res.success) return;

        const p = res.data;
        const statusMap = { 1: 'รอการตรวจสอบ', 2: 'กำลังดำเนินการ', 3: 'ดำเนินการเสร็จสิ้น' };
        const statusText = statusMap[p.status] || 'ไม่ระบุ';

        // กรอกข้อมูลทั่วไป
        $('#view_project_name').text(p.project_name);
        $('#view_number').text(p.number);
        $('#view_customer_name').text(p.customers_name || '-');
        $('#view_responsible').text(p.responsible_person || '-');
        $('#view_contract_period_display').text(p.contract_period || '-');
        $('#view_deliver_date').html(`<i class="far fa-calendar-check"></i> ${formatDate(p.deliver_work_date)}`);
        $('#view_end_date').html(`<i class="far fa-calendar-times"></i> ${formatDate(p.end_date)}`);

        // จัดการสถานะ Pill
        $('#view_status_container').html(`<span class="status-pill status-${statusText}">${statusText}</span>`);

        // รายละเอียดงาน
        $('#view_going_ma').html(p.going_ma ? p.going_ma : '<span style="color:#ccc;">ไม่มีข้อมูลรายละเอียด</span>');

        // จัดการหมายเหตุการตรวจสอบ
        if (p.status_remark && p.status_remark.trim() !== '') {
            $('#view_status_remark').text(p.status_remark);
            $('#view_remark_wrapper').show();
        } else {
            $('#view_remark_wrapper').hide();
        }

        // จัดการตาราง MA
        let maHtml = '';
        if (res.ma && res.ma.length > 0) {
            // เปลี่ยนจากของเดิม ให้เช็คค่า m.is_done
            res.ma.forEach((m, i) => {
                // 1. ตรวจสอบสถานะจาก is_done
                const isDone = (m.is_done == 1);

                // 2. กำหนดสีแถวและข้อความสถานะ
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
                <span style="font-weight: 600; color: ${statusColor}; font-size: 0.85rem;">
                    ${statusIcon}${statusText}
                </span>
            </td>

            <td style="padding: 12px; color: #475569; font-size: 0.85rem;">
                ${m.remark || '-'}
            </td>

            <td style="padding: 12px; text-align: center;">${fileLink}</td>
        </tr>`;
            });
        } else {
            maHtml = '<tr><td colspan="4" style="padding: 30px; text-align: center; color: #94a3b8;">ยังไม่มีข้อมูลแผน MA</td></tr>';
        }
        $('#view_ma_table tbody').html(maHtml);

        $('#viewProjectModal').addClass('show');
    }, 'json');
};

window.closeViewModal = function () { $('#viewProjectModal').removeClass('show'); };

// --- Helpers ---
// --- Helpers ---
function formatDate(dateStr) {
    // [แก้ไขตรงนี้] เพิ่มเงื่อนไขเช็คว่าถ้าค่าเป็น "-" ให้ส่งกลับเป็น "ไม่ได้ระบุ"
    if (!dateStr || dateStr === '-' || dateStr === '0000-00-00' || dateStr === 'null' || dateStr === null) {
        return 'ไม่ได้ระบุ';
    }
    const d = new Date(dateStr);
    // ตรวจสอบว่า Date แข็งแรงพอที่จะแสดงผลไหม (ป้องกัน Invalid Date)
    if (isNaN(d.getTime())) return 'ไม่ได้ระบุ'; 
    
    return d.toLocaleDateString('th-TH', { day: '2-digit', month: 'short', year: 'numeric' });
}

function updateStats(data) {
    // 1. คำนวณยอดต่างๆ เก็บไว้ก่อน
    const total = data.length;
    const pending = data.filter(x => parseInt(x.status) === 1).length;
    const processing = data.filter(x => parseInt(x.status) === 2).length;
    const completed = data.filter(x => parseInt(x.status) === 3).length;

    // 2. ส่งค่าไปให้ฟังก์ชัน animateCount ทำให้ตัวเลขวิ่ง
    animateCount('#stat_total', total);
    animateCount('#stat_pending', pending);
    animateCount('#stat_processing', processing);
    animateCount('#stat_completed', completed);
}

// --- ฟังก์ชันเพิ่มแถว MA เอง ---
window.addMARow = function (containerSelector) {
    const container = $(containerSelector);
    if (container.find('.text-center').length > 0) container.empty();

    const idx = container.children('.ma-card').length + 1;
    // ใช้ฟังก์ชันกลางที่เราสร้างไว้เพื่อความสม่ำเสมอ
    const html = generateMARowHTML(idx, '', `งวดพิเศษ / เพิ่มเติม`, '');
    container.append(html);

    // เลื่อนลงไปที่แถวใหม่ล่าสุด
    const scrollTarget = document.querySelector('.custom-scroll');
    if (scrollTarget) {
        scrollTarget.scrollTo({ top: scrollTarget.scrollHeight, behavior: 'smooth' });
    }

    // โฟกัสไปที่ช่องวันที่ของแถวใหม่
    container.find('.ma-card').last().find('input[type="date"]').focus();
};

function reIndexMA() {
    $('#maManageContainer .ma-card').each(function (index) {
        $(this).find('.ma-card-index').text(index + 1);
        // ปรับแก้ข้อความ 'MA ครั้งที่...' ให้รันเลขใหม่ด้วยก็ได้ถ้าต้องการ
        let noteInput = $(this).find('input[name="ma_notes[]"]');
        if (noteInput.val().includes('MA ครั้งที่')) {
            noteInput.val(`MA ครั้งที่ ${index + 1}`);
        }
    });
}

window.saveMAOnly = function () {
    const form = document.getElementById('maManageForm');
    const formData = new FormData(form);
    formData.append('action', 'save_ma_only');

    $.ajax({
        url: API_URL,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1000, showConfirmButton: false });
                closeMAModal();
                fetchAllData();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    });
};

window.addCheckRemark = function (id, oldRemark = '') {
    Swal.fire({
        title: 'หมายเหตุรอการตรวจสอบ',
        input: 'textarea',
        inputValue: oldRemark,
        inputPlaceholder: 'กรอกหมายเหตุ...',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
            if (!value) return 'กรุณากรอกหมายเหตุ';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, {
                action: 'save_check_remark',
                id: id,
                remark: result.value
            }, function (res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกแล้ว',
                        timer: 1000,
                        showConfirmButton: false
                    });
                    fetchAllData();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
};

window.addCheckRemarkFromEl = function (el) {
    const id = el.getAttribute('data-id');
    const oldRemark = decodeURIComponent(el.getAttribute('data-remark') || '');
    addCheckRemark(id, oldRemark);
};

function parseLocalDate(dateStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d); // สร้างแบบ Local time
}
// ดักจับการพิมพ์ในหมายเหตุ MA ทุกตัว (ใช้ Delegation เพราะแถวถูกสร้างใหม่ได้)
$(document).on('keyup', '.ma-remark-input', function () {
    const remark = $(this).val().trim();
    const card = $(this).closest('.ma-card');

    if (remark !== "") {
        // เปลี่ยนสีขอบเป็นสีเขียวเข้มเพื่อบอกว่า "กำลังจะเสร็จ"
        card.css('border-left', '4px solid #059669');
        card.css('background', '#f0fdf4');
    } else {
        // กลับเป็นสีเขียวปกติ
        card.css('border-left', '4px solid #10b981');
        card.css('background', '#fff');
    }
});
function animateCount(elementId, targetValue) {
    // 1. เก็บ element ไว้ในตัวแปร $el ก่อน (สำคัญมาก! ช่วยให้ลื่นขึ้น)
    const $el = $(elementId);

    // ดึงค่าเดิมมาเป็นจุดเริ่มต้น
    let startValue = parseInt($el.text().replace(/,/g, ''));
    // ถ้าค่าเดิมไม่ใช่ตัวเลข (เช่น เป็นค่าว่าง) ให้เริ่มที่ 0
    if (isNaN(startValue)) startValue = 0;

    $({ countNum: startValue }).animate({ countNum: targetValue }, {
        duration: 800, // ปรับลดเวลาลงเหลือ 800ms จะดู กระชับขึ้น
        easing: 'swing',
        step: function () {
            // 2. เรียกใช้ $el ที่เราเก็บไว้ แทนการค้นหาใหม่
            // ใช้ Math.floor จะดูเป็นธรรมชาติกว่า Math.ceil
            $el.text(Math.floor(this.countNum).toLocaleString());
        },
        complete: function () {
            $el.text(targetValue.toLocaleString());
        }
    });
}
filterTabs.forEach(tab => {
    tab.addEventListener('click', () => {
        filterTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const status = tab.dataset.status;
        const rows = document.querySelectorAll('#projectTableBody tr');

        rows.forEach(row => {
            if (status === 'all') {
                row.style.display = '';
            } else {
                const statusCell = row.querySelector('.status-pill');
                if (statusCell && statusCell.textContent.trim() === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });
});
// ===== Filter by Status Card =====
$(document).on('click', '.stat-card', function () {

    const status = $(this).data('status');

    // ทำ Active Effect
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

