// js/service_project.js
const API_URL = 'service_project.php?api=true';

$(document).ready(function() {
    loadSummary();
    loadTable();

    // จัดการ Submit Form (รองรับไฟล์แนบ)
    $('#serviceForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.append('action', 'save_data');

        $.ajax({
            url: API_URL,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1500, showConfirmButton: false });
                    closeModal();
                    loadSummary();
                    loadTable();
                } else { 
                    Swal.fire('Error', res.message, 'error'); 
                }
            },
            error: function() {
                Swal.fire('Error', 'Connect Error', 'error');
            }
        });
    });
});

// --- View Data Function (ฟังก์ชันสำหรับเปิด Modal รายละเอียด) ---
function viewData(id) {
    // แสดงสถานะ Loading ก่อน
    $('#viewContent').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><span class="text-muted mt-2">กำลังโหลดข้อมูล...</span></div>');
    $('#viewModal').addClass('show');

    $.get(API_URL, { action: 'fetch_single', id: id }, function(res) {
        if (res.success) {
            const data = res.data;
            
            // กำหนดสี Badge ตามสถานะ
            let statusBadge = '';
            if(data.status === 'On-site') statusBadge = '<span class="status-badge-lg" style="background:#e0f2fe; color:#0284c7;"><i class="fas fa-building"></i> On-site เข้าตรวจหน้างาน</span>';
            else if(data.status === 'Remote') statusBadge = '<span class="status-badge-lg" style="background:#f3e8ff; color:#9333ea;"><i class="fas fa-laptop-house"></i> Remote ไม่ต้องเข้าหน้างาน</span>';
            else statusBadge = '<span class="status-badge-lg" style="background:#ffedd5; color:#ea580c;"><i class="fas fa-user-friends"></i> Subcontractor เเจ้ง Sub</span>';

            // ตรวจสอบไฟล์แนบ
            let fileSection = '';
            if (data.file_path) {
                fileSection = `
                    <div class="view-section" style="margin-top: 30px; border-top: 1px dashed #cbd5e1; padding-top: 25px;">
                        <span class="view-label"><i class="fas fa-paperclip"></i> ไฟล์แนบ (Attachments)</span>
                        <div style="margin-top: 10px;">
                            <a href="uploads/${data.file_path}" target="_blank" class="btn-file-download">
                                <i class="fas fa-cloud-download-alt"></i> ดาวน์โหลด / เปิดดูไฟล์แนบ
                            </a>
                        </div>
                    </div>`;
            } else {
                fileSection = `
                    <div class="view-section" style="margin-top: 30px; border-top: 1px dashed #cbd5e1; padding-top: 25px;">
                        <span class="view-label">ไฟล์แนบ</span>
                        <span class="text-muted"><i class="fas fa-times-circle"></i> ไม่พบไฟล์แนบในรายการนี้</span>
                    </div>`;
            }

            // สร้าง HTML Mockup แบบ Pro Design
            let html = `
                <div class="view-header-main">
                    <div>
                        <span class="view-label">Customer Info</span>
                        <div class="view-customer-name">${data.customers_name}</div>
                        <div class="view-agency"><i class="fas fa-building"></i> ${data.agency || '-'} | <i class="fas fa-phone"></i> ${data.phone || '-'}</div>
                    </div>
                    <div class="text-right">
                        <div class="view-label" style="text-align:right;">ID</div>
                        <div style="font-size:1.5rem; font-weight:800; color:#cbd5e1;">#${data.service_id}</div>
                    </div>
                </div>

                <div class="view-grid">
                    <div class="view-section">
                        <span class="view-label">Project Name</span>
                        <div class="view-value">${data.project_name || '-'}</div>
                    </div>
                    <div class="view-section">
                        <span class="view-label">Equipment</span>
                        <div class="view-value"><i class="fas fa-server"></i> ${data.equipment || '-'}</div>
                    </div>
                </div>

                <div class="view-grid">
                    <div class="view-section">
                        <span class="view-label">Service Date</span>
                        <div class="view-value">${formatDate(data.start_date)} - ${data.end_date ? formatDate(data.end_date) : 'ปัจจุบัน'}</div>
                    </div>
                    <div class="view-section">
                        <span class="view-label">Status</span>
                        <div>${statusBadge}</div>
                    </div>
                </div>

                <div class="view-box-symptom">
                    <span class="view-label" style="color:#e11d48;"><i class="fas fa-exclamation-circle"></i> อาการเสีย (Symptom)</span>
                    <div class="view-value" style="margin-top:5px; color:#be123c;">${data.symptom || '-'}</div>
                </div>

                <div class="view-box-action">
                    <span class="view-label" style="color:#15803d;"><i class="fas fa-tools"></i> การแก้ไข (Action Taken)</span>
                    <div class="view-value" style="margin-top:5px; color:#166534;">${data.action_taken || '-'}</div>
                </div>

                ${fileSection}
            `;
            
            $('#viewContent').html(html);
        }
    }, 'json');
}

function closeViewModal() {
    $('#viewModal').removeClass('show');
}

// --- ฟังก์ชันพื้นฐานอื่นๆ ---

function deleteData(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลนี้จะหายไปตลอดกาล!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'ลบข้อมูล'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete_data', id: id }, function(res) {
                if(res.success) {
                    loadSummary();
                    loadTable();
                    Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', timer: 1000, showConfirmButton: false });
                } else { Swal.fire('Error', res.message, 'error'); }
            }, 'json');
        }
    });
}

function openModal() {
    $('#serviceForm')[0].reset();
    $('#service_id').val('0');
    $('#file_status_msg').text('รองรับไฟล์ภาพ, PDF (สูงสุด 10MB)');
    $('#modalTitle').text('เพิ่มงานบริการใหม่');
    $('#start_date').val(new Date().toISOString().split('T')[0]);
    $('#serviceModal').addClass('show');
}

function closeModal() {
    $('#serviceModal').removeClass('show');
}

function editData(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function(res) {
        if (res.success) {
            const data = res.data;
            $('#service_id').val(data.service_id);
            $('#customers_id').val(data.customers_id);
            $('#project_name').val(data.project_name);
            $('#equipment').val(data.equipment);
            $('#symptom').val(data.symptom);
            $('#action_taken').val(data.action_taken);
            $('#status').val(data.status);
            $('#start_date').val(data.start_date);
            $('#end_date').val(data.end_date);
            
            if(data.file_path) {
                $('#file_status_msg').html(`<span class="text-success"><i class="fas fa-check"></i> มีไฟล์เดิมแล้ว: ${data.file_path}</span>`);
            } else {
                $('#file_status_msg').text('ยังไม่มีไฟล์แนบ');
            }

            $('#modalTitle').text('แก้ไขข้อมูล');
            $('#serviceModal').addClass('show');
        }
    }, 'json');
}

function loadSummary() {
    $.get(API_URL, { action: 'fetch_status_summary' }, function(res) {
        if(res.success) {
            $('#cardOnsite .count').text(res.data['On-site'] || 0);
            $('#cardRemote .count').text(res.data['Remote'] || 0);
            $('#cardSub .count').text(res.data['Subcontractor'] || 0);
            $('#cardTotal .count').text(res.data['Total'] || 0);
        }
    }, 'json');
}

// ------------------------------------------------------------------
// ส่วนสำคัญ: เปลี่ยน HTML ไอคอนที่นี่
// ------------------------------------------------------------------
function loadTable() {
    $.get(API_URL, { action: 'fetch_all' }, function(res) {
        if (res.success) {
            let html = '';
            if (res.data.length === 0) {
                html = '<tr><td colspan="7" class="text-center p-4 text-muted">ไม่พบข้อมูลในระบบ</td></tr>';
            } else {
                res.data.forEach((item, index) => {
                    let badgeClass = 'badge-remote'; 
                    let statusIcon = 'fa-laptop';
                    if (item.status === 'On-site') { badgeClass = 'badge-onsite'; statusIcon = 'fa-building'; }
                    else if (item.status === 'Subcontractor') { badgeClass = 'badge-sub'; statusIcon = 'fa-user-friends'; }

                    let badgeHtml = `<span class="status-badge ${badgeClass}"><i class="fas ${statusIcon}"></i> ${item.status}</span>`;
                    
                    // *** แก้ไขตรงนี้ครับ: เปลี่ยน fa-paperclip เป็น fa-eye ***
                    html += `
                        <tr class="table-row-anim" style="animation-delay: ${index * 0.05}s">
                            <td class="text-center text-muted">#${item.service_id}</td>
                            <td>${formatDate(item.start_date)}</td>
                            <td>
                                <div style="font-weight:600; color:#1e293b;">${item.customers_name || 'ไม่ระบุ'}</div>
                                <div style="font-size:0.8rem; color:#64748b;">${item.agency || '-'}</div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#334155;">${item.project_name || '-'}</div>
                                <div style="font-size:0.8rem; color:#94a3b8;"><i class="fas fa-microchip"></i> ${item.equipment || '-'}</div>
                            </td>
                            <td><span style="display:inline-block; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#475569;">${item.symptom || '-'}</span></td>
                            <td>${badgeHtml}</td>
                            <td class="text-center">
                                <div class="action-buttons">
                                    <button class="btn-icon btn-att" onclick="viewData(${item.service_id})" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i> </button>
                                    <button class="btn-icon btn-edit" onclick="editData(${item.service_id})" title="แก้ไข">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="btn-icon btn-delete" onclick="deleteData(${item.service_id})" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                });
            }
            $('#tableBody').html(html);
        }
    }, 'json');
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
}

window.onclick = function(e) { 
    if (e.target == document.getElementById('serviceModal')) closeModal();
    if (e.target == document.getElementById('viewModal')) closeViewModal();
}