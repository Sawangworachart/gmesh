// pm_project.js

const API_URL = 'pm_project.php?api=true';
const FORM_ID = '#pmProjectForm';
const TABLE_BODY_ID = '#tableBody';

$(document).ready(function() {
    fetchAllData();

    $(FORM_ID).on('submit', function(e) {
        e.preventDefault();
        saveProject(); 
    });

    $('#btnTriggerUpload').click(() => $('#fileInput').click());
    $('#fileInput').change(function() { handleFileSelect(this); });

    // Search
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#tableBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});

function fetchAllData() {
    $(TABLE_BODY_ID).html('<tr><td colspan="8" class="text-center" style="padding:40px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:#ddd;"></i></td></tr>');
    
    $.post(API_URL, { action: 'fetch_all' }, function(res) {
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
    }, 'json').fail(function() {
        $(TABLE_BODY_ID).html(`<tr><td colspan="8" class="text-center text-danger">Server Error</td></tr>`);
    });
}

function limitText(text, length) {
    if(!text) return '-';
    return text.length > length ? text.substring(0, length) + '...' : text;
}

// --- VIEW PROJECT FUNCTION (UPDATED for Modern Layout) ---
window.viewProject = function(id) {
    // Show Modal
    $('#viewProjectModal').addClass('show');
    $('#modalBackdrop').addClass('show');
    
    // Set Loading State
    $('#view_project_name').text('Loading...');
    $('#view_status_badge').text('...');
    
    $.get(API_URL, { action: 'fetch_single', id: id }, function(res) {
        if(res.success) {
            const p = res.data;
            
            // 1. Header Banner
            $('#view_project_name').text(p.project_name);
            $('#view_number').text(p.number);
            $('#view_status_badge').text(p.status)
                .attr('class', `status-badge status-${p.status.toLowerCase().replace(' ', '-')}`);
            
            // 2. Info Grid
            $('#view_customer_name').text(p.customers_name || '-');
            $('#view_responsible').text(p.responsible_person || '-');
            $('#view_deliver_date').text(formatDate(p.deliver_work_date));
            $('#view_end_date').text(formatDate(p.end_date));
            $('#view_contract_period').text(p.contract_period || '-');

            // 3. Description
            $('#view_going_ma').text(p.going_ma || '-');

            // 4. MA Table (Modern Clean)
            let maHtml = '';
            if(res.ma && res.ma.length > 0) {
                res.ma.forEach((m, i) => {
                    maHtml += `<tr>
                        <td style="color:#888;">${i+1}</td>
                        <td style="font-weight:500;">${formatDate(m.ma_date)}</td>
                        <td>${m.note || '-'}</td>
                    </tr>`;
                });
            } else {
                maHtml = '<tr><td colspan="3" class="text-center text-muted" style="padding:20px;">ไม่มีแผน MA</td></tr>';
            }
            $('#view_ma_table tbody').html(maHtml);

            // 5. File Attachment (Modern Box)
            const fileBox = $('#view_file_container');
            if(p.file_path) {
                const ext = p.file_path.split('.').pop().toLowerCase();
                const isImg = ['jpg','jpeg','png','gif','webp'].includes(ext);
                const fileName = p.file_path.split('/').pop();
                
                if(isImg) {
                    fileBox.html(`
                        <img src="${p.file_path}" onclick="window.open('${p.file_path}')" style="cursor:zoom-in;">
                        <a href="${p.file_path}" target="_blank" class="btn-dl-pill"><i class="fas fa-download"></i> ดาวน์โหลด</a>
                    `);
                } else {
                    fileBox.html(`
                        <i class="fas fa-file-pdf fa-3x text-danger" style="margin-bottom:10px;"></i>
                        <div style="word-break:break-all; font-size:0.8rem; text-align:center; margin-bottom:5px; color:#555;">${fileName}</div>
                        <a href="${p.file_path}" target="_blank" class="btn-dl-pill"><i class="fas fa-download"></i> ดาวน์โหลด</a>
                    `);
                }
            } else {
                fileBox.html('<i class="fas fa-folder-open fa-2x" style="color:#ddd; margin-bottom:5px;"></i><span class="text-muted small">ไม่มีเอกสารแนบ</span>');
            }
        }
    }, 'json');
}

window.closeViewModal = function() {
    $('#viewProjectModal').removeClass('show');
    if(!$('#pmProjectModal').hasClass('show')) {
        $('#modalBackdrop').removeClass('show');
    }
}

// --- EXISTING FUNCTIONS (Unchanged) ---
function saveProject() {
    let form = $(FORM_ID)[0];
    let formData = new FormData(form);
    formData.append('action', 'save');

    $.ajax({
        url: API_URL, type: 'POST', data: formData,
        contentType: false, processData: false, dataType: 'json',
        success: function(res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย', showConfirmButton: false, timer: 1500 })
                .then(() => { closeModal(); fetchAllData(); });
            } else {
                Swal.fire('Error', res.message || 'บันทึกไม่สำเร็จ', 'error');
            }
        },
        error: function() { Swal.fire('Error', 'Server Error', 'error'); }
    });
}

window.openModal = function(id) {
    if (id == 0) {
        $('#modalTitle').html('<i class="fas fa-plus-circle"></i> เพิ่มโครงการใหม่');
        $('#pmProjectForm')[0].reset();
        $('#pmproject_id').val(0);
        $('#maScheduleContainer').html('<div class="text-center text-muted py-3">ยังไม่มีแผน MA</div>');
        showFilePreview(null, false);
    } else {
        $('#modalTitle').html('<i class="fas fa-edit"></i> แก้ไขข้อมูลโครงการ');
        fetchProjectData(id);
    }
    $('#modalBackdrop').addClass('show');
    $('#pmProjectModal').addClass('show');
}

window.closeModal = function() {
    $('#pmProjectModal').removeClass('show');
    $('#modalBackdrop').removeClass('show');
}

function fetchProjectData(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function(res) {
        if(res.success) {
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
            
            showFilePreview(p.file_path, !!p.file_path);
            renderMASchedule(res.ma);
        }
    }, 'json');
}

function calculateMA() {
    const startDate = $('#deliver_work_date').val();
    const endDate = $('#end_date').val();
    const freq = parseInt($('#calc_frequency').val());

    if(!startDate || !endDate) {
        Swal.fire('แจ้งเตือน', 'กรุณาระบุวันส่งมอบงาน และ วันสิ้นสุดสัญญา', 'warning');
        return;
    }
    let currDate = new Date(startDate);
    const stopDate = new Date(endDate);
    currDate.setMonth(currDate.getMonth() + freq);
    let list = [];
    let count = 1;
    while(currDate <= stopDate) {
        list.push({ ma_date: currDate.toISOString().split('T')[0], note: `MA ครั้งที่ ${count}` });
        currDate.setMonth(currDate.getMonth() + freq);
        count++;
    }
    renderMASchedule(list);
}

function addMARow() {
    $('#maScheduleContainer').append(`
        <div style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
            <span style="color:#888; font-size:0.9rem; min-width:30px;">#</span>
            <input type="date" name="ma_dates[]" class="form-control" style="width:160px;">
            <input type="text" name="ma_notes[]" class="form-control" placeholder="รายละเอียด">
            <button type="button" class="btn-icon delete" onclick="$(this).parent().remove()"><i class="fas fa-times"></i></button>
        </div>
    `);
}

function renderMASchedule(data) {
    const container = $('#maScheduleContainer');
    container.empty();
    if(!data || data.length === 0) {
        container.html('<div class="text-center text-muted py-3">ไม่มีรอบ MA ในช่วงเวลานี้</div>');
        return;
    }
    data.forEach((item, index) => {
        container.append(`
            <div style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                <span style="color:#888; font-size:0.9rem; min-width:30px;">#${index+1}</span>
                <input type="date" name="ma_dates[]" class="form-control" value="${item.ma_date}" style="width:160px;">
                <input type="text" name="ma_notes[]" class="form-control" value="${item.note}" placeholder="รายละเอียด">
                <button type="button" class="btn-icon delete" onclick="$(this).parent().remove()"><i class="fas fa-times"></i></button>
            </div>
        `);
    });
}

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        showFilePreview(input.files[0].name, true, true);
    }
}

function showFilePreview(filenameOrPath, hasFile, isNewUpload = false) {
    if (hasFile) {
        $('#noFileContent').hide();
        let name = isNewUpload ? filenameOrPath : 'มีไฟล์แนบอยู่แล้ว';
        let html = `
            <div style="text-align:center;">
                <i class="fas fa-check-circle text-success" style="font-size:2rem;"></i>
                <p class="mt-2">${name}</p>
                ${!isNewUpload ? '<small class="text-muted">(อัปโหลดไฟล์ใหม่เพื่อแทนที่)</small>' : ''}
            </div>
        `;
        $('#hasFileContent').html(html).show();
        $('#btnTriggerUpload').text('เปลี่ยนไฟล์');
    } else {
        $('#noFileContent').show();
        $('#hasFileContent').hide();
        $('#btnTriggerUpload').text('เลือกไฟล์');
    }
}

function formatDate(dateStr) {
    if(!dateStr || dateStr === '0000-00-00') return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

window.deleteProject = function(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลโครงการและไฟล์แนบจะถูกลบถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef476f',
        confirmButtonText: 'ลบข้อมูล'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete', id: id }, function(res) {
                if(res.success) {
                    Swal.fire('Deleted!', 'ลบข้อมูลเรียบร้อย', 'success');
                    fetchAllData();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    })
}