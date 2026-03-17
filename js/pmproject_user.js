
// js/pmproject_user.js

function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toUpperCase();
    const table = document.getElementById("projectTable");
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        let rowText = tr[i].textContent || tr[i].innerText;
        if (rowText.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

function openViewModal(data) {
    document.getElementById('view_project_name').innerText = data.project_name;
    
    const statusMap = { 
        1: { text: 'รอตรวจสอบ', class: 'status-1' }, 
        2: { text: 'กำลังดำเนินการ', class: 'status-2' }, 
        3: { text: 'เสร็จสิ้น', class: 'status-3' } 
    };
    const status = statusMap[data.status_id] || { text: 'N/A', class: '' };

    let maTableBody = '';
    if (data.ma_schedule && data.ma_schedule.length > 0) {
        data.ma_schedule.forEach((ma, i) => {
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
            <div class="view-item"><label>ลูกค้า</label><div>${data.customers_name}</div></div>
            <div class="view-item"><label>ผู้รับผิดชอบ</label><div>${data.responsible_person || '-'}</div></div>
            <div class="view-item"><label>สถานะ</label><div><span class="status-pill ${status.class}">${status.text}</span></div></div>
            <div class="view-item"><label>สัญญา</label><div>${data.contract_period || '-'}</div></div>
            <div class="view-item"><label>เริ่มประกัน</label><div>${data.start_date}</div></div>
            <div class="view-item"><label>สิ้นสุดประกัน</label><div>${data.end_date}</div></div>
        </div>
        <h4><i class="fas fa-clipboard-list"></i> แผนการบำรุงรักษา</h4>
        <div class="table-responsive">
            <table class="table table-sm view-ma-table">
                <thead><tr><th>#</th><th>วันที่</th><th>รายละเอียด</th><th>หมายเหตุ</th><th class="text-center">สถานะ</th></tr></thead>
                <tbody>${maTableBody}</tbody>
            </table>
        </div>
    `;

    document.getElementById('view_modal_content').innerHTML = content;
    document.getElementById('viewProjectModal').classList.add('show');
}

function closeViewModal() {
    document.getElementById('viewProjectModal').classList.remove('show');
}
