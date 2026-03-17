// pm_project.js - Updated Widths for MA Row

const API_URL = 'pm_project.php?api=true';
const FORM_ID = '#pmProjectForm';
const TABLE_BODY_ID = '#tableBody';

$(document).ready(function () {
    fetchAllData();

    $(FORM_ID).on('submit', function (e) {
        e.preventDefault();
        saveProject();
    });

    $('#searchInput').on('keyup', function () {
        var value = $(this).val().toLowerCase();
        $("#tableBody tr").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});

function fetchAllData() {
    $(TABLE_BODY_ID).html('<tr><td colspan="8" class="text-center" style="padding:40px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:#ddd;"></i></td></tr>');

    $.post(API_URL, { action: 'fetch_all' }, function (res) {
        if (res.success) {
            let html = '';
            if (res.data.length === 0) {
                html = '<tr><td colspan="8" class="text-center text-muted" style="padding:40px;">ไม่พบข้อมูลโครงการ</td></tr>';
            } else {
                res.data.forEach((p, index) => {
                    html += `
                        <tr>
                            <td><span style="font-weight:600; color:#2b2d42;">${p.number}</span></td>
                            <td><div style="font-weight:500;">${p.project_name}</div></td>
                            <td>${limitText(p.going_ma, 30)}</td>
                            <td>${p.customers_name || '<span class="text-muted">-</span>'}</td>
                            <td>${p.responsible_person}</td>
                            <td><span class="status-badge status-${p.status.toLowerCase().replace(' ', '-')}">${p.status}</span></td>
                            <td>${p.contract_period}</td>
                            <td class="text-center" style="white-space:nowrap;">
                                <button class="btn-icon info" onclick="viewProject(${p.pmproject_id})" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>
                                <button class="btn-icon view" onclick="window.openModal(${p.pmproject_id})" title="แก้ไข"><i class="fas fa-pen"></i></button>
                                <button class="btn-icon delete" onclick="deleteProject(${p.pmproject_id})" title="ลบ"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
                });
            }
            $(TABLE_BODY_ID).html(html);
        } else {
            $(TABLE_BODY_ID).html(`<tr><td colspan="8" class="text-center text-danger">Error: ${res.message}</td></tr>`);
        }
    }, 'json').fail(function () {
        $(TABLE_BODY_ID).html(`<tr><td colspan="8" class="text-center text-danger">Server Error</td></tr>`);
    });
}

function limitText(text, length) {
    if (!text) return '-';
    return text.length > length ? text.substring(0, length) + '...' : text;
}

// --- VIEW PROJECT FUNCTION ---
window.viewProject = function (id) {
    $('#viewProjectModal').addClass('show');
    $('#modalBackdrop').addClass('show');

    $('#view_project_name').text('Loading...');

    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const p = res.data;

            $('#view_project_name').text(p.project_name);
            $('#view_number').text(p.number);
            $('#view_status_badge').text(p.status)
                .attr('class', `status-badge status-${p.status.toLowerCase().replace(' ', '-')}`);

            $('#view_customer_name').text(p.customers_name || '-');
            $('#view_responsible').text(p.responsible_person || '-');
            $('#view_deliver_date').text(formatDate(p.deliver_work_date));
            $('#view_end_date').text(formatDate(p.end_date));
            $('#view_contract_period').text(p.contract_period || '-');
            $('#view_going_ma').text(p.going_ma || '-');

            let maHtml = '';
            if (res.ma && res.ma.length > 0) {
                res.ma.forEach((m, i) => {
                    let fileLink = '<span class="text-muted">-</span>';
                    if (m.file_path) {
                        fileLink = `<a href="${m.file_path}" target="_blank" class="text-primary" title="ดาวน์โหลด/ดูไฟล์"><i class="fas fa-file-alt"></i> ดูไฟล์</a>`;
                    }

                    maHtml += `<tr>
                        <td style="color:#888;">${i + 1}</td>
                        <td style="font-weight:500;">${formatDate(m.ma_date)}</td>
                        <td>${m.note || '-'}</td>
                        <td>${m.remark || '-'}</td>
                        <td class="text-center">${fileLink}</td>
                    </tr>`;
                });
            } else {
                maHtml = '<tr><td colspan="5" class="text-center text-muted" style="padding:20px;">ไม่มีแผน MA</td></tr>';
            }
            $('#view_ma_table tbody').html(maHtml);
        }
    }, 'json');
}

window.closeViewModal = function () {
    $('#viewProjectModal').removeClass('show');
    if (!$('#pmProjectModal').hasClass('show')) {
        $('#modalBackdrop').removeClass('show');
    }
}

// --- SAVE PROJECT ---
function saveProject() {
    let form = $(FORM_ID)[0];
    let formData = new FormData(form);
    formData.append('action', 'save');

    $.ajax({
        url: API_URL, type: 'POST', data: formData,
        contentType: false, processData: false, dataType: 'json',
        success: function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย', showConfirmButton: false, timer: 1500 })
                    .then(() => { closeModal(); fetchAllData(); });
            } else {
                Swal.fire('Error', res.message || 'บันทึกไม่สำเร็จ', 'error');
            }
        },
        error: function () { Swal.fire('Error', 'Server Error', 'error'); }
    });
}

window.openModal = function (id) {
    if (id == 0) {
        $('#modalTitle').html('<i class="fas fa-plus-circle"></i> เพิ่มโครงการใหม่');
        $('#pmProjectForm')[0].reset();
        $('#pmproject_id').val(0);
        $('#maScheduleContainer').html('<div class="text-center text-muted py-3">ยังไม่มีแผน MA</div>');
    } else {
        $('#modalTitle').html('<i class="fas fa-edit"></i> แก้ไขข้อมูลโครงการ');
        fetchProjectData(id);
    }
    $('#modalBackdrop').addClass('show');
    $('#pmProjectModal').addClass('show');
}

window.closeModal = function () {
    $('#pmProjectModal').removeClass('show');
    $('#modalBackdrop').removeClass('show');
}

function fetchProjectData(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const p = res.data;
            $('#pmproject_id').val(p.pmproject_id);
            $('#number').val(p.number);
            $('#project_name').val(p.project_name);
            $('#customers_id').val(p.customers_id);
            $('#responsible_person').val(p.responsible_person);
            $('#status').val(p.status);
            $('#going_ma').val(p.going_ma);
            $('#deliver_work_date').val(p.deliver_work_date);
            $('#end_date').val(p.end_date);
            $('#contract_period').val(p.contract_period);

            renderMASchedule(res.ma);
        }
    }, 'json');
}

function calculateMA() {
    const startDate = $('#deliver_work_date').val();
    const endDate = $('#end_date').val();
    const freq = parseInt($('#calc_frequency').val());

    if (!startDate || !endDate) {
        Swal.fire('แจ้งเตือน', 'กรุณาระบุวันส่งมอบงาน และ วันสิ้นสุดสัญญา', 'warning');
        return;
    }
    let currDate = new Date(startDate);
    const stopDate = new Date(endDate);
    currDate.setMonth(currDate.getMonth() + freq);
    let list = [];
    let count = 1;
    while (currDate <= stopDate) {
        list.push({
            ma_date: currDate.toISOString().split('T')[0],
            note: `MA ครั้งที่ ${count}`,
            remark: '',
            file_path: ''
        });
        currDate.setMonth(currDate.getMonth() + freq);
        count++;
    }
    renderMASchedule(list);
}

// --- UPDATED: addMARow with Adjusted Widths ---
function addMARow(data = {}) {
    const dateVal = data.ma_date || '';
    const noteVal = data.note || '';
    const remarkVal = data.remark || '';
    const filePath = data.file_path || '';

    let fileHtml = '';
    if (filePath) {
        const fileName = filePath.split('/').pop();
        fileHtml = `
            <div style="margin-bottom:2px;">
                <a href="${filePath}" target="_blank" class="ma-existing-file" title="${fileName}"><i class="fas fa-paperclip"></i> ${fileName}</a>
            </div>
        `;
    }

    const hiddenFile = `<input type="hidden" name="ma_existing_files[]" value="${filePath}">`;
    const rowCount = $('#maScheduleContainer').children().length + 1;

    // Adjusted Widths here to match PHP Header
    const rowHtml = `
        <div class="ma-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:flex-start; border-bottom:1px dashed #eee; padding-bottom:10px;">
            <div style="width:35px; padding-top:8px; color:#888; font-size:0.9rem;">#${rowCount}</div>
            
            <div style="width:120px;">
                <input type="date" name="ma_dates[]" class="form-control" value="${dateVal}" required>
            </div>
            
            <div style="width:140px;">
                <input type="text" name="ma_notes[]" class="form-control" value="${noteVal}" placeholder="รายละเอียด">
            </div>

            <div style="flex:1;">
                <input type="text" name="ma_remarks[]" class="form-control" value="${remarkVal}" placeholder="หมายเหตุ" style="width:100%;">
            </div>

            <div style="width:210px; display:flex; flex-direction:column;">
                ${fileHtml}
                ${hiddenFile}
                <input type="file" name="ma_files[]" class="ma-file-input" style="width:100%;">
            </div>

            <div style="width:35px; text-align:center; padding-top:5px;">
                <button type="button" class="btn-icon delete" onclick="$(this).closest('.ma-row').remove()"><i class="fas fa-times"></i></button>
            </div>
        </div>
    `;

    $('#maScheduleContainer').append(rowHtml);
}

function renderMASchedule(data) {
    const container = $('#maScheduleContainer');
    container.empty();
    if (!data || data.length === 0) return;
    data.forEach((item) => {
        addMARow(item);
    });
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

window.deleteProject = function (id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลโครงการและไฟล์แนบจะถูกลบถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef476f',
        confirmButtonText: 'ลบข้อมูล'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete', id: id }, function (res) {
                if (res.success) {
                    Swal.fire('Deleted!', 'ลบข้อมูลเรียบร้อย', 'success');
                    fetchAllData();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    })
}