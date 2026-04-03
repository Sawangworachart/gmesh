// js หน้า Service ของ user
        function filterUserTable() {
            let search = document.getElementById("searchInput").value.toUpperCase();
            let project = document.getElementById("projectFilter").value.toUpperCase();
            let tr = document.getElementById("serviceTable").getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let txtValue = tr[i].textContent || tr[i].innerText;
                let projectTd = tr[i].getElementsByTagName("td")[1];
                let projectValue = projectTd ? projectTd.textContent : "";
                let matchSearch = txtValue.toUpperCase().indexOf(search) > -1;
                let matchProject = project === "" || projectValue.toUpperCase().trim() === project;
                tr[i].style.display = (matchSearch && matchProject) ? "" : "none";
            }
        }

        function viewDetail(data) {
            let stClass = (data.status === 'Remote') ? 'st-remote' : ((data.status === 'Subcontractor') ? 'st-subcon' : 'st-onsite');
            let fileHtml = '';
            if (data.file_path && data.file_path !== '') {
                fileHtml = `
                    <div class="attachment-box">
                        <label style="font-weight:700; color:#475569; font-size:0.75rem; text-transform:uppercase; display:block; margin-bottom:10px;">
                            <i class="fas fa-paperclip"></i> เอกสาร/รูปภาพแนบ
                        </label>
                        <a href="uploads/${data.file_path}" target="_blank" class="file-link">
                            <i class="fas fa-file-download"></i> คลิกเพื่อเปิดดูไฟล์แนบ
                        </a>
                    </div>
                `;
            }

            let html = `
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div>
                        <label style="font-weight:700; color:#94a3b8; font-size:0.75rem; text-transform:uppercase;">เลขที่โครงการ</label>
                        <p style="margin:5px 0; font-weight:700; font-size:1.1rem; color:#1e293b;">${data.ref_number}</p>
                    </div>
                    <div style="display:flex; gap:25px;">
                        <div>
                            <label style="font-weight:700; color:#94a3b8; font-size:0.75rem; text-transform:uppercase;">วันที่เริ่ม</label>
                            <p style="margin:5px 0; font-weight:700; color:#2563eb;">${data.start_date}</p>
                        </div>
                        <div>
                            <label style="font-weight:700; color:#94a3b8; font-size:0.75rem; text-transform:uppercase;">วันที่สิ้นสุด</label>
                            <p style="margin:5px 0; font-weight:700; color:#dc2626;">${data.end_date}</p>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="font-weight:700; color:#94a3b8; font-size:0.75rem; text-transform:uppercase;">ชื่อโครงการ</label>
                    <p style="margin:5px 0; font-weight:700; font-size:1.05rem; color:var(--primary);">${data.project_name}</p>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0;">
                    <div>
                        <label style="font-weight:700; color:#475569; font-size:0.75rem; text-transform:uppercase;">ลูกค้า</label>
                        <p style="margin:5px 0; font-weight:700;">${data.customer}</p>
                        <p style="margin:0; font-weight:400; color:#64748b; font-size:0.9rem;">${data.department}</p>
                    </div>
                    <div>
                        <label style="font-weight:700; color:#475569; font-size:0.75rem; text-transform:uppercase;">สถานะ</label>
                        <div style="margin-top:8px;"><span class="status-pill ${stClass}" style="font-size:0.85rem; padding:8px 16px;">${data.status_th}</span></div>
                    </div>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div>
                        <label style="font-weight:700; color:#94a3b8; font-size:0.75rem; text-transform:uppercase;">อุปกรณ์</label>
                        <p style="margin:5px 0; font-weight:600;">${data.device_model}</p>
                    </div>
                    <div>
                        <label style="font-weight:700; color:#94a3b8; font-size:0.75rem; text-transform:uppercase;">S/N (Serial Number)</label>
                        <p style="margin:5px 0; font-weight:600;">${data.serial_number}</p>
                    </div>
                </div>

                <div style="background:#fff1f2; padding:18px; border-radius:12px; margin-bottom:15px; border-left:5px solid #e11d48;">
                    <label style="font-weight:700; color:#e11d48; font-size:0.75rem; text-transform:uppercase;">อาการเสีย / ปัญหาที่พบ</label>
                    <p style="margin:8px 0 0; font-size:0.95rem; line-height:1.5;">${data.symptom}</p>
                </div>

                <div style="background:#f0fdf4; padding:18px; border-radius:12px; border-left:5px solid #16a34a;">
                    <label style="font-weight:700; color:#16a34a; font-size:0.75rem; text-transform:uppercase;">รายละเอียดการแก้ไข</label>
                    <p style="margin:8px 0 0; font-size:0.95rem; line-height:1.5;">${data.solution}</p>
                </div>
                
                ${fileHtml}
            `;
            document.getElementById('v_content').innerHTML = html;
            document.getElementById('viewModal').style.display = 'flex';
        }

        function closeViewModal() { document.getElementById('viewModal').style.display = 'none'; }
  