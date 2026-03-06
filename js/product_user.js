// js หน้า Product ของ user
        function filterTable() {
            let input = document.getElementById("searchInput").value.toUpperCase();
            let tr = document.getElementById("productTable").getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let found = false;
                let tds = tr[i].getElementsByTagName("td");
                for(let j=0; j<tds.length; j++) {
                    if(tds[j].textContent.toUpperCase().includes(input)) found = true;
                }
                tr[i].style.display = found ? "" : "none";
            }
        }

        function viewDetail(data) {
            let fileHtml = "";
            if (data.file_path && data.file_path !== "") {
                fileHtml = `
                    <div class="file-attachment-section">
                        <label style="font-weight:700; color:#475569; font-size:0.8rem; text-transform:uppercase; display:block; margin-bottom:8px;">
                            <i class="fas fa-paperclip"></i> ไฟล์แนบ / รูปภาพประกอบ
                        </label>
                        <a href="${data.file_path}" target="_blank" class="file-link-btn">
                            <i class="fas fa-external-link-alt"></i> เปิดดูไฟล์แนบ
                        </a>
                    </div>
                `;
            }

            let html = `
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div><label style="font-weight:700; color:#94a3b8; font-size:0.8rem; text-transform:uppercase;">อุปกรณ์</label><p style="margin:5px 0; font-weight:700; font-size:1.2rem; color:#1e293b;">${data.device_name}</p></div>
                    <div><label style="font-weight:700; color:#94a3b8; font-size:0.8rem; text-transform:uppercase;">Serial Number (S/N)</label><p style="margin:5px 0; font-weight:700; font-size:1.2rem; color:#2563eb;">${data.sn || '-'}</p></div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div><label style="font-weight:700; color:#94a3b8; font-size:0.8rem; text-transform:uppercase;">ลูกค้า / เเผนก</label><p style="margin:5px 0; font-weight:600; font-size:1.1rem;">${data.customer}</p></div>
                    <div><label style="font-weight:700; color:#94a3b8; font-size:0.8rem; text-transform:uppercase;">สถานะ</label><div style="margin-top:5px;"><span class="status-pill ${data.badge_class}">${data.status_th}</span></div></div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px; background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0;">
                    <div><label style="font-weight:700; color:#2563eb; font-size:0.8rem;">วันที่เริ่มงาน</label><p style="margin:5px 0; font-weight:700; font-size:1rem;">${data.start_date}</p></div>
                    <div><label style="font-weight:700; color:#dc2626; font-size:0.8rem;">วันที่สิ้นสุดงาน</label><p style="margin:5px 0; font-weight:700; font-size:1rem;">${data.end_date}</p></div>
                </div>
                <hr style="border:0; border-top:1px solid #f1f5f9; margin:20px 0;">
                <div style="background:#fff1f2; padding:15px; border-radius:12px; border: 1px solid #fecaca;"><label style="font-weight:700; color:#e11d48; font-size:0.8rem; text-transform:uppercase;">อาการ / รายละเอียดการซ่อม</label><p style="margin:5px 0; font-size:1rem; line-height:1.6; color:#333;">${data.symptom || 'ไม่ระบุรายละเอียด'}</p></div>
                
                ${fileHtml}
            `;
            document.getElementById('v_content').innerHTML = html;
            const modal = document.getElementById('viewModal');
            modal.style.display = 'flex'; 
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function closeModal() { 
            const modal = document.getElementById('viewModal');
            modal.classList.remove('show'); 
            setTimeout(() => modal.style.display = 'none', 300);
        }
