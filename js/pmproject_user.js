// =========================================
// JS สำหรับหน้า PM Project (User)
// =========================================

const API_URL = 'pmproject_user.php';

$(document).ready(function() {
    // Mouse Glow Effect
    const body = document.querySelector('body');
    document.addEventListener('mousemove', (e) => {
        body.style.setProperty('--x', e.clientX + 'px');
        body.style.setProperty('--y', e.clientY + 'px');
    });

    // Close Modal on Overlay Click
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal-overlay')) {
            closeViewModal();
        }
    });
});

// --- Search Function ---
function searchTable(tableId, inputId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    var tbody = table.getElementsByTagName("tbody")[0];
    var rows = tbody.getElementsByTagName("tr");

    for (var i = 0; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName("td");
        var match = false;
        
        // Search across all columns
        for (var j = 0; j < cells.length; j++) {
            var cellText = cells[j].textContent || cells[j].innerText;
            if (cellText.toUpperCase().indexOf(filter) > -1) {
                match = true;
                break;
            }
        }
        
        rows[i].style.display = match ? "" : "none";
    }
}

// --- View Detail Modal ---
function viewDetail(btn) {
    const data = btn.dataset;
    
    // Fill Modal Data
    $('#view_no').text(data.no || '-');
    $('#view_name').text(data.name);
    $('#view_customer').text(data.customer);
    $('#view_responsible').text(data.responsible || '-');
    $('#view_start').text(data.start);
    $('#view_end').text(data.end);
    $('#view_contract').text(data.contract || '-');
    $('#view_ma').text(data.ma || '-');

    // Status Badge Logic
    const statusEl = $('#view_status_badge');
    let statusHtml = data.status;
    
    if (data.status === 'กำลังดำเนินการ') {
        statusHtml = `<span style="color:#4e73df; font-weight:700;"><i class="fas fa-spinner fa-spin"></i> ${data.status}</span>`;
    } else if (data.status === 'ดำเนินการเสร็จสิ้น') {
        statusHtml = `<span style="color:#1cc88a; font-weight:700;"><i class="fas fa-check-circle"></i> ${data.status}</span>`;
    } else if (data.status === 'รอการตรวจสอบ') {
        statusHtml = `<span style="color:#f6c23e; font-weight:700;"><i class="fas fa-clock"></i> ${data.status}</span>`;
    }
    statusEl.html(statusHtml);

    // Fetch MA Schedule Details
    const tbody = $('#ma_table_body');
    tbody.html('<tr><td colspan="5" class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...</td></tr>');

    $.get(API_URL, { action: 'get_ma_detail', id: data.id }, function(res) {
        tbody.empty();
        
        if (res.success && res.schedule && res.schedule.length > 0) {
            res.schedule.forEach((item, index) => {
                let fileBtn = item.has_file 
                    ? `<a href="${item.file_path}" target="_blank" class="btn-action" title="ดาวน์โหลดไฟล์"><i class="fas fa-file-download"></i></a>` 
                    : '<span style="color:#ccc;">-</span>';

                tbody.append(`
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td style="font-weight:600; color:#4e73df;">${item.formatted_date}</td>
                        <td>${item.note || '-'}</td>
                        <td>${item.remark || '-'}</td>
                        <td class="text-center">${fileBtn}</td>
                    </tr>
                `);
            });
        } else {
            tbody.html('<tr><td colspan="5" class="text-center p-4 text-muted">- ไม่มีข้อมูลแผนการบำรุงรักษา -</td></tr>');
        }
    }, 'json').fail(function() {
        tbody.html('<tr><td colspan="5" class="text-center p-4 text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>');
    });

    // Show Modal
    const modal = document.getElementById('viewModal');
    modal.style.display = 'flex';
    // Small delay to allow display:flex to apply before adding show class for transition
    setTimeout(() => { modal.classList.add('show'); }, 10);
}

function closeViewModal() {
    const modal = document.getElementById('viewModal');
    modal.classList.remove('show');
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}
