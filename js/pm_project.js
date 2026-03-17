
// js/pm_project.js

$(document).ready(function() {
    loadInitialData();
    loadCustomers();

    $('#searchInput').on('keyup', function() {
        filterTable($(this).val());
    });

    $('#pmProjectForm').on('submit', function(e) {
        e.preventDefault();
        saveProject();
    });

    $('#maManageForm').on('submit', function(e) {
        e.preventDefault();
        saveMASchedule();
    });

    flatpickr(".flatpickr-input", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        allowInput: true
    });
});

let allProjects = [];

function loadInitialData() {
    $.getJSON('pm_project.php?action=get_all', function(data) {
        if (data.success) {
            allProjects = data.projects;
            updateStats(data.stats);
            renderTable(allProjects);
        }
    });
}

function loadCustomers() {
    $.getJSON('customers.php?api=true&action=fetch_for_dropdown', function(data) {
        if (data.success) {
            const select = $('#customers_id');
            select.empty().append('<option value="">-- เลือกลูกค้า --</option>');
            data.data.forEach(c => {
                select.append(`<option value="${c.id}">${c.name}</option>`);
            });
        }
    });
}

function updateStats(stats) {
    $('#stat_total').text(stats.total || 0);
    $('#stat_pending').text(stats.pending || 0);
    $('#stat_processing').text(stats.processing || 0);
    $('#stat_completed').text(stats.completed || 0);
}

function renderTable(projects) {
    const tableBody = $('#tableBody');
    tableBody.empty();
    if (!projects || projects.length === 0) {
        tableBody.html('<tr><td colspan="7" class="text-center p-5 text-muted">ไม่พบข้อมูล</td></tr>');
        return;
    }

    projects.forEach(p => {
        const statusMap = {
            1: { text: 'รอตรวจสอบ', class: 'status-1' },
            2: { text: 'กำลังดำเนินการ', class: 'status-2' },
            3: { text: 'เสร็จสิ้น', class: 'status-3' }
        };
        const status = statusMap[p.status] || { text: 'N/A', class: '' };

        const row = `
            <tr>
                <td>${p.number || '-'}</td>
                <td><strong>${p.project_name}</strong></td>
                <td>${p.customers_name}</td>
                <td class="text-center"><span class="status-pill ${status.class}">${status.text}</span></td>
                <td>${p.contract_period || '-'}</td>
                <td>
                    <div><i class="fas fa-play-circle text-primary"></i> ${p.deliver_work_date || '-'}</div>
                    <div><i class="fas fa-flag-checkered text-danger"></i> ${p.end_date || '-'}</div>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-success" onclick="openMAModal(${p.pmproject_id})"><i class="fas fa-calendar-alt"></i></button>
                    <button class="btn btn-sm btn-info" onclick="openViewModal(${p.pmproject_id})"><i class="far fa-eye"></i></button>
                    <button class="btn btn-sm btn-warning" onclick="openModal(${p.pmproject_id})"><i class="far fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="deleteProject(${p.pmproject_id})"><i class="far fa-trash-alt"></i></button>
                </td>
            </tr>
        `;
        tableBody.append(row);
    });
}

function filterTable(searchTerm) {
    const lowercasedTerm = searchTerm.toLowerCase();
    const filteredProjects = allProjects.filter(p => 
        p.project_name.toLowerCase().includes(lowercasedTerm) || 
        p.customers_name.toLowerCase().includes(lowercasedTerm)
    );
    renderTable(filteredProjects);
}

function openModal(id) {
    $('#pmProjectForm')[0].reset();
    $('#pmproject_id').val(id);
    $('#maScheduleContainer').empty();

    if (id > 0) {
        $('#modalTitle').text('แก้ไขข้อมูลโครงการ');
        $('#maSectionWrapper').hide();
        $.getJSON(`pm_project.php?action=get_project&id=${id}`, function(data) {
            if (data.success) {
                const p = data.project;
                $('#project_name').val(p.project_name);
                $('#number').val(p.number);
                $('#customers_id').val(p.customers_id);
                $('#responsible_person').val(p.responsible_person);
                $('#going_ma').val(p.going_ma);
                $('#status').val(p.status);
                $('#status_remark').val(p.status_remark);
                $('#contract_period').val(p.contract_period);
                flatpickr("#deliver_work_date", {}).setDate(p.deliver_work_date);
                flatpickr("#end_date", {}).setDate(p.end_date);
            }
        });
    } else {
        $('#modalTitle').text('เพิ่มข้อมูลโครงการ');
        $('#maSectionWrapper').show();
    }
    $('#pmProjectModal').addClass('show');
}

function closeModal() {
    $('#pmProjectModal').removeClass('show');
}

function saveProject() {
    const formData = new FormData($('#pmProjectForm')[0]);
    const maData = [];
    $('#maScheduleContainer .ma-row').each(function() {
        maData.push({
            ma_date: $(this).find('input[name="ma_date[]"]').val(),
            ma_detail: $(this).find('input[name="ma_detail[]"]').val(),
            ma_remark: $(this).find('input[name="ma_remark[]"]').val(),
            ma_status: $(this).find('select[name="ma_status[]"]').val()
        });
    });
    formData.append('ma_schedule', JSON.stringify(maData));

    $.ajax({
        url: 'pm_project.php?action=save_project',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire('สำเร็จ', 'บันทึกข้อมูลโครงการเรียบร้อย', 'success');
                closeModal();
                loadInitialData();
            } else {
                Swal.fire('ผิดพลาด', response.message, 'error');
            }
        }
    });
}

function deleteProject(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลโครงการและแผน MA ที่เกี่ยวข้องจะถูกลบทั้งหมด!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'ยกเลิก',
        confirmButtonText: 'ใช่, ลบเลย!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('pm_project.php?action=delete_project', { id: id }, function(response) {
                if (response.success) {
                    Swal.fire('สำเร็จ', 'ข้อมูลถูกลบแล้ว', 'success');
                    loadInitialData();
                } else {
                    Swal.fire('ผิดพลาด', response.message, 'error');
                }
            });
        }
    });
}

// --- MA Schedule Functions --- //

function calculateMA() {
    const startDate = $('#deliver_work_date').val();
    const endDate = $('#end_date').val();
    const frequency = parseInt($('#calc_frequency').val());
    if (!startDate || !endDate) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเลือกวันเริ่มและสิ้นสุดประกันก่อน', 'warning');
        return;
    }

    const container = $('#maScheduleContainer');
    container.empty();
    let currentDate = new Date(startDate);
    let i = 1;
    while (currentDate <= new Date(endDate)) {
        const formattedDate = currentDate.toISOString().split('T')[0];
        addMARow(container, i, formattedDate);
        currentDate.setMonth(currentDate.getMonth() + frequency);
        i++;
    }
}

function addMARow(container, index, date = '') {
    const i = container.children().length + 1;
    const row = `
        <div class="ma-row">
            <span class="ma-row-num">${i}</span>
            <input type="date" name="ma_date[]" class="form-control" value="${date}">
            <input type="text" name="ma_detail[]" class="form-control" placeholder="รายละเอียด">
            <input type="text" name="ma_remark[]" class="form-control" placeholder="หมายเหตุ">
            <select name="ma_status[]" class="form-control">
                <option value="0">ยังไม่ทำ</option>
                <option value="1">เรียบร้อย</option>
            </select>
            <button type="button" class="btn btn-sm btn-danger" onclick="$(this).parent().remove();"><i class="fas fa-trash"></i></button>
        </div>
    `;
    container.append(row);
}

// --- Manage MA Modal --- //

function openMAModal(id) {
    $('#ma_pmproject_id').val(id);
    $('#maManageContainer').empty();

    $.getJSON(`pm_project.php?action=get_ma_schedule&id=${id}`, function(data) {
        if (data.success) {
            $('#ma_project_title').text(data.project.project_name);
            const statusMap = { 1: 'รอตรวจสอบ', 2: 'กำลังดำเนินการ', 3: 'เสร็จสิ้น' };
            $('#ma_project_status').text(statusMap[data.project.status] || 'N/A');

            if (data.schedule.length > 0) {
                data.schedule.forEach(ma => addExistingMARow(ma));
            } else {
                $('#maManageContainer').html('<p class="text-center text-muted">ยังไม่มีแผน MA</p>');
            }
            $('#maManageModal').addClass('show');
        }
    });
}

function closeMAModal() {
    $('#maManageModal').removeClass('show');
}

function addExistingMARow(ma) {
    const container = $('#maManageContainer');
    const i = container.children('.ma-row').length + 1;
    const row = `
        <div class="ma-row">
            <span class="ma-row-num">${i}</span>
            <input type="hidden" name="m-id[]" value="${ma.pm_id}">
            <input type="date" name="ma_date[]" class="form-control" value="${ma.ma_date}">
            <input type="text" name="ma_detail[]" class="form-control" value="${ma.ma_detail}">
            <input type="text" name="ma_remark[]" class="form-control" value="${ma.ma_remark}">
            <select name="ma_status[]" class="form-control">
                <option value="0" ${ma.ma_status == 0 ? 'selected' : ''}>ยังไม่ทำ</option>
                <option value="1" ${ma.ma_status == 1 ? 'selected' : ''}>เรียบร้อย</option>
            </select>
            <button type="button" class="btn btn-sm btn-danger" onclick="$(this).parent().remove();"><i class="fas fa-trash"></i></button>
        </div>
    `;
    container.append(row);
}

function saveMASchedule() {
    const formData = new FormData($('#maManageForm')[0]);
    $.ajax({
        url: 'pm_project.php?action=save_ma_schedule',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire('สำเร็จ', 'บันทึกแผน MA เรียบร้อย', 'success');
                closeMAModal();
            } else {
                Swal.fire('ผิดพลาด', response.message, 'error');
            }
        }
    });
}

// --- View Modal --- //

function openViewModal(id) {
    $.getJSON(`pm_project.php?action=get_project_details&id=${id}`, function(data) {
        if (data.success) {
            renderViewModal(data.details);
            $('#viewProjectModal').addClass('show');
        }
    });
}

function closeViewModal() {
    $('#viewProjectModal').removeClass('show');
}

function renderViewModal(d) {
    $('#view_project_name').text(d.project_name);
    const statusMap = { 1: { text: 'รอตรวจสอบ', class: 'status-1' }, 2: { text: 'กำลังดำเนินการ', class: 'status-2' }, 3: { text: 'เสร็จสิ้น', class: 'status-3' } };
    const status = statusMap[d.status] || { text: 'N/A', class: '' };

    let maTableBody = '';
    if (d.ma_schedule.length > 0) {
        d.ma_schedule.forEach((ma, i) => {
            maTableBody += `
                <tr>
                    <td class="text-center">${i + 1}</td>
                    <td>${ma.ma_date}</td>
                    <td>${ma.ma_detail || '-'}</td>
                    <td>${ma.ma_remark || '-'}</td>
                    <td class="text-center">${ma.ma_status == 1 ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'}</td>
                </tr>
            `;
        });
    } else {
        maTableBody = '<tr><td colspan="5" class="text-center text-muted p-3">ไม่มีแผน MA</td></tr>';
    }

    const content = `
        <div class="view-grid">
            <div class="view-item"><label>ลูกค้า</label><div>${d.customers_name}</div></div>
            <div class="view-item"><label>ผู้รับผิดชอบ</label><div>${d.responsible_person || '-'}</div></div>
            <div class="view-item"><label>สถานะ</label><div><span class="status-pill ${status.class}">${status.text}</span></div></div>
            <div class="view-item"><label>สัญญา</label><div>${d.contract_period || '-'}</div></div>
            <div class="view-item"><label>เริ่มประกัน</label><div>${d.deliver_work_date}</div></div>
            <div class="view-item"><label>สิ้นสุดประกัน</label><div>${d.end_date}</div></div>
        </div>
        <h4><i class="fas fa-clipboard-list"></i> แผนการบำรุงรักษา</h4>
        <div class="table-responsive">
            <table class="table table-sm view-ma-table">
                <thead><tr><th>#</th><th>วันที่</th><th>รายละเอียด</th><th>หมายเหตุ</th><th class="text-center">สถานะ</th></tr></thead>
                <tbody>${maTableBody}</tbody>
            </table>
        </div>
    `;
    $('#view_modal_content').html(content);
}
