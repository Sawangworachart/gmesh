$(document).ready(function() {
    const $searchInput = $('#searchInput');
    const $projectFilter = $('#projectFilter');
    const $serviceTableRows = $('#serviceTable tbody tr');

    function filterTable() {
        const searchTerm = $searchInput.val().toUpperCase();
        const projectTerm = $projectFilter.val();

        $serviceTableRows.each(function() {
            const row = $(this);
            if (row.find('td[colspan="7"]').length) {
                row.show();
                return;
            }

            const projectName = row.find('td[data-label="ชื่อโครงการ"]').text().trim();
            const rowText = row.text().toUpperCase();

            const matchesSearch = rowText.includes(searchTerm);
            const matchesProject = (projectTerm === '' || projectName === projectTerm);

            if (matchesSearch && matchesProject) {
                row.show();
            } else {
                row.hide();
            }
        });
    }

    $searchInput.on('keyup', filterTable);
    $projectFilter.on('change', filterTable);
});

function viewDetail(data) {
    let badgeClass = '';
    switch (data.status) {
        case 'On-site': badgeClass = 'badge-onsite'; break;
        case 'Remote': badgeClass = 'badge-remote'; break;
        case 'Subcontractor': badgeClass = 'badge-sub'; break;
    }

    let fileHtml = '';
    if (data.file_path && data.file_path !== '') {
        fileHtml = `
            <div class="detail-section">
                <h4 class="detail-section-title"><i class="fas fa-paperclip"></i> เอกสารแนบ</h4>
                <a href="uploads/${data.file_path}" target="_blank" class="file-attachment-link">
                    <i class="fas fa-file-download"></i> ดูไฟล์แนบ
                </a>
            </div>`;
    }

    const modalBody = `
        <div class="modal-row">
            <div class="modal-col">
                <div class="detail-item">
                    <span class="detail-label">เลขที่โครงการ</span>
                    <span class="detail-value text-bold">${data.ref_number}</span>
                </div>
            </div>
            <div class="modal-col">
                 <div class="detail-item">
                    <span class="detail-label">สถานะ</span>
                    <span class="badge-status ${badgeClass}">${data.status_th}</span>
                </div>
            </div>
        </div>

        <div class="detail-item">
            <span class="detail-label">ชื่อโครงการ</span>
            <span class="detail-value">${data.project_name}</span>
        </div>

        <div class="modal-row">
            <div class="modal-col">
                <div class="detail-item">
                    <span class="detail-label">วันที่เริ่ม</span>
                    <span class="detail-value text-success"><i class="fas fa-play-circle"></i> ${data.start_date}</span>
                </div>
            </div>
            <div class="modal-col">
                <div class="detail-item">
                    <span class="detail-label">วันที่สิ้นสุด</span>
                    <span class="detail-value text-danger"><i class="fas fa-stop-circle"></i> ${data.end_date}</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
             <h4 class="detail-section-title"><i class="fas fa-user-tie"></i> ข้อมูลลูกค้า</h4>
             <div class="modal-row">
                <div class="modal-col">
                    <div class="detail-item">
                        <span class="detail-label">ลูกค้า</span>
                        <span class="detail-value">${data.customer}</span>
                    </div>
                </div>
                <div class="modal-col">
                    <div class="detail-item">
                        <span class="detail-label">หน่วยงาน</span>
                        <span class="detail-value">${data.department}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h4 class="detail-section-title"><i class="fas fa-desktop"></i> ข้อมูลอุปกรณ์</h4>
            <div class="modal-row">
                <div class="modal-col">
                    <div class="detail-item">
                        <span class="detail-label">อุปกรณ์</span>
                        <span class="detail-value">${data.device_model}</span>
                    </div>
                </div>
                <div class="modal-col">
                    <div class="detail-item">
                        <span class="detail-label">Serial Number</span>
                        <span class="detail-value">${data.serial_number}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-item-block problem">
                <h5 class="detail-item-title"><i class="fas fa-exclamation-triangle"></i> อาการเสีย / ปัญหาที่พบ</h5>
                <p>${data.symptom}</p>
            </div>
            <div class="detail-item-block solution">
                <h5 class="detail-item-title"><i class="fas fa-check-circle"></i> รายละเอียดการแก้ไข</h5>
                <p>${data.solution}</p>
            </div>
        </div>
        ${fileHtml}
    `;

    $('#v_content').html(modalBody);
    $('#viewModal').addClass('show');
}

function closeViewModal() {
    $('#viewModal').removeClass('show');
}

// Close modal on outside click
$(window).on('click', function(event) {
    if ($(event.target).is('#viewModal')) {
        closeViewModal();
    }
});
