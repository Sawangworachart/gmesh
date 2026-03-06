/**
 * ไฟล์: assets/js/service_user.js
 * คำอธิบาย: สคริปต์สำหรับหน้า Service (User View)
 * จัดการการกรองข้อมูล (Filter) และการแสดง Modal รายละเอียด
 */

document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม Mouse Glow Effect
    const cards = document.querySelectorAll('.stat-card, .page-header-card');
    cards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--x', `${x}px`);
            card.style.setProperty('--y', `${y}px`);
        });
    });
});

// ฟังก์ชันกรองข้อมูลในตาราง
function filterUserTable() {
    let search = document.getElementById("searchInput").value.toUpperCase();
    let project = document.getElementById("projectFilter").value.toUpperCase();
    let table = document.getElementById("serviceTable");
    let tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) { // เริ่มที่ 1 ข้าม Header
        let txtValue = tr[i].textContent || tr[i].innerText;
        let projectTd = tr[i].getElementsByTagName("td")[1]; // Column ชื่อโครงการ (index 1)
        let projectValue = projectTd ? (projectTd.textContent || projectTd.innerText) : "";
        
        let matchSearch = txtValue.toUpperCase().indexOf(search) > -1;
        let matchProject = project === "" || projectValue.toUpperCase().trim() === project;
        
        if (matchSearch && matchProject) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

// ฟังก์ชันแสดงรายละเอียดใน Modal
function viewDetail(data) {
    // กำหนด Class ของ Status Pill ตามสถานะ
    let stClass = '';
    if (data.status === 'Remote') stClass = 'st-remote';
    else if (data.status === 'Subcontractor') stClass = 'st-subcon';
    else stClass = 'st-onsite';

    // สร้าง HTML สำหรับไฟล์แนบ (ถ้ามี)
    let fileHtml = '';
    if (data.file_path && data.file_path !== '') {
        fileHtml = `
            <div class="detail-box" style="margin-top:20px; border-color:#bfdbfe; background:#eff6ff;">
                <label style="color:#2563eb; margin-bottom:10px; display:block; font-weight:700;">
                    <i class="fas fa-paperclip"></i> เอกสาร/รูปภาพแนบ
                </label>
                <a href="uploads/${data.file_path}" target="_blank" class="file-link">
                    <i class="fas fa-file-download"></i> คลิกเพื่อเปิดดูไฟล์แนบ
                </a>
            </div>
        `;
    }

    // สร้าง HTML เนื้อหา Modal
    let html = `
        <div class="detail-grid">
            <div class="detail-item">
                <label>เลขที่โครงการ</label>
                <p style="font-size:1.2rem; color:#1e293b;">${data.ref_number}</p>
            </div>
            <div class="detail-item">
                <label>ระยะเวลาโครงการ</label>
                <p>
                    <span style="color:#2563eb;"><i class="fas fa-play"></i> ${data.start_date}</span> 
                    &nbsp;-&nbsp; 
                    <span style="color:#dc2626;"><i class="fas fa-flag"></i> ${data.end_date}</span>
                </p>
            </div>
        </div>

        <div class="detail-item" style="margin-bottom:20px;">
            <label>ชื่อโครงการ</label>
            <p style="font-size:1.1rem; color:var(--primary);">${data.project_name}</p>
        </div>
        
        <div class="detail-box">
            <div class="detail-grid" style="margin-bottom:0; gap:15px;">
                <div class="detail-item">
                    <label>ลูกค้า / หน่วยงาน</label>
                    <p>${data.customer}</p>
                    <small style="color:#64748b;">${data.department}</small>
                </div>
                <div class="detail-item">
                    <label>สถานะงาน</label>
                    <div style="margin-top:5px;">
                        <span class="status-pill ${stClass}">${data.status_th}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="detail-grid">
            <div class="detail-item">
                <label>อุปกรณ์ (Model)</label>
                <p>${data.device_model}</p>
            </div>
            <div class="detail-item">
                <label>Serial Number (S/N)</label>
                <p style="font-family:monospace;">${data.serial_number}</p>
            </div>
        </div>

        <div class="detail-box problem">
            <label style="color:#e11d48;"><i class="fas fa-exclamation-circle"></i> อาการเสีย / ปัญหาที่พบ</label>
            <p style="margin-top:10px; line-height:1.6;">${data.symptom}</p>
        </div>

        <div class="detail-box solution">
            <label style="color:#16a34a;"><i class="fas fa-tools"></i> การแก้ไข / สิ่งที่ดำเนินการ</label>
            <p style="margin-top:10px; line-height:1.6;">${data.solution}</p>
        </div>
        
        ${fileHtml}
    `;

    document.getElementById('v_content').innerHTML = html;
    
    // แสดง Modal
    const modal = document.getElementById('viewModal');
    modal.style.display = 'flex';
    // ใช้ requestAnimationFrame เพื่อให้ transition ทำงาน
    requestAnimationFrame(() => {
        modal.classList.add('show');
    });
}

// ฟังก์ชันปิด Modal
function closeViewModal() { 
    const modal = document.getElementById('viewModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300); // รอให้ transition จบ (0.3s)
}

// ปิด Modal เมื่อคลิกพื้นที่ว่างข้างนอก
window.onclick = function(event) {
    const modal = document.getElementById('viewModal');
    if (event.target == modal) {
        closeViewModal();
    }
}
