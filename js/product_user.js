/**
 * ไฟล์: assets/js/product_user.js
 * คำอธิบาย: สคริปต์สำหรับหน้า Product Claim (User View)
 * จัดการการกรองข้อมูล และ Modal แสดงรายละเอียด
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mouse Glow Effect
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

// ฟังก์ชันกรองตาราง
function filterTable() {
    let input = document.getElementById("searchInput").value.toUpperCase();
    let tr = document.getElementById("productTable").getElementsByTagName("tr");
    
    for (let i = 1; i < tr.length; i++) { // Skip header
        let found = false;
        let tds = tr[i].getElementsByTagName("td");
        
        // ค้นหาในทุกคอลัมน์
        for(let j=0; j<tds.length; j++) {
            if(tds[j].textContent.toUpperCase().includes(input)) {
                found = true;
                break;
            }
        }
        
        tr[i].style.display = found ? "" : "none";
    }
}

// ฟังก์ชันแสดง Modal รายละเอียด
function viewDetail(data) {
    let fileHtml = "";
    if (data.file_path && data.file_path !== "") {
        fileHtml = `
            <div class="file-attachment-section">
                <label style="font-weight:700; color:#475569; font-size:0.8rem; text-transform:uppercase; display:block; margin-bottom:10px;">
                    <i class="fas fa-paperclip"></i> ไฟล์แนบ / รูปภาพประกอบ
                </label>
                <a href="${data.file_path}" target="_blank" class="file-link-btn">
                    <i class="fas fa-external-link-alt"></i> เปิดดูไฟล์แนบ
                </a>
            </div>
        `;
    }

    let html = `
        <div class="detail-grid">
            <div class="detail-item">
                <label>อุปกรณ์</label>
                <p style="font-size:1.2rem; color:#1e293b;">${data.device_name}</p>
            </div>
            <div class="detail-item">
                <label>Serial Number (S/N)</label>
                <p style="font-size:1.2rem; color:var(--primary); font-family:monospace;">${data.sn || '-'}</p>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <label>ลูกค้า / หน่วยงาน</label>
                <p>${data.customer}</p>
                <small style="color:#64748b;">${data.department}</small>
            </div>
            <div class="detail-item">
                <label>สถานะปัจจุบัน</label>
                <div style="margin-top:5px;">
                    <span class="status-pill ${data.badge_class}">${data.status_th}</span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-grid" style="margin-bottom:0; gap:15px;">
                <div class="detail-item">
                    <label style="color:var(--primary);">วันที่เริ่มงาน</label>
                    <p>${data.start_date}</p>
                </div>
                <div class="detail-item">
                    <label style="color:#e11d48;">วันที่สิ้นสุดงาน</label>
                    <p>${data.end_date}</p>
                </div>
            </div>
        </div>
        
        <div style="background:#fff1f2; padding:20px; border-radius:12px; border: 1px solid #fecaca;">
            <label style="font-weight:700; color:#e11d48; font-size:0.8rem; text-transform:uppercase; display:block; margin-bottom:8px;">
                <i class="fas fa-exclamation-triangle"></i> อาการ / รายละเอียดการซ่อม
            </label>
            <p style="margin:0; font-size:1rem; line-height:1.6; color:#333;">${data.symptom || 'ไม่ระบุรายละเอียด'}</p>
        </div>
        
        ${fileHtml}
    `;
    
    document.getElementById('v_content').innerHTML = html;
    
    const modal = document.getElementById('viewModal');
    modal.style.display = 'flex'; 
    requestAnimationFrame(() => {
        modal.classList.add('show');
    });
}

// ฟังก์ชันปิด Modal
function closeModal() { 
    const modal = document.getElementById('viewModal');
    modal.classList.remove('show'); 
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// ปิดเมื่อคลิกข้างนอก
window.onclick = function(event) {
    const modal = document.getElementById('viewModal');
    if (event.target == modal) {
        closeModal();
    }
}
