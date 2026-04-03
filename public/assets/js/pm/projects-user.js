// js หน้า Preventive Maintenance ของ user
        function searchTable(tableId, inputId) {
            var input = document.getElementById(inputId);
            var filter = input.value.toUpperCase(); 
            var table = document.getElementById(tableId);
            var tbody = table.getElementsByTagName("tbody")[0]; 
            var rows = tbody.getElementsByTagName("tr");
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName("td");
                var match = false;
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

        function viewDetail(btn) {
            const data = btn.dataset;
            document.getElementById('view_no').innerText = data.no || '-';
            document.getElementById('view_name').innerText = data.name;
            document.getElementById('view_customer').innerText = data.customer;
            document.getElementById('view_responsible').innerText = data.responsible;
            document.getElementById('view_start').innerText = data.start;
            document.getElementById('view_end').innerText = data.end;
            document.getElementById('view_contract').innerText = data.contract || '-';
            document.getElementById('view_ma').innerText = data.ma || '-';
            
            const statusEl = document.getElementById('view_status_badge');
            let statusHtml = data.status;
            if(data.status === 'กำลังดำเนินการ') statusHtml = `<span style="color:#7c3aed;">${data.status}</span>`;
            if(data.status === 'ดำเนินการเสร็จสิ้น') statusHtml = `<span style="color:#d97706;">${data.status}</span>`;
            if(data.status === 'รอการตรวจสอบ') statusHtml = `<span style="color:#059669;">${data.status}</span>`;
            statusEl.innerHTML = statusHtml;

            const tbody = document.getElementById('ma_table_body');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">กำลังโหลด...</td></tr>';

            fetch(`pmproject_user.php?action=get_ma_detail&id=${data.id}`)
                .then(response => response.json())
                .then(res => {
                    tbody.innerHTML = '';
                    if (res.schedule && res.schedule.length > 0) {
                        res.schedule.forEach((item, index) => {
                            let fileBtn = item.has_file 
                                ? `<a href="${item.file_path}" target="_blank" class="btn-action" style="width:30px; height:30px; margin:auto;"><i class="fas fa-file-download" style="color:#2ecc71;"></i></a>` 
                                : '<span style="color:#ccc;">-</span>';

                            tbody.innerHTML += `
                                <tr>
                                    <td align="center">${index+1}</td>
                                    <td>${item.formatted_date}</td>
                                    <td>${item.note || '-'}</td>
                                    <td>${item.remark || '-'}</td>
                                    <td align="center">${fileBtn}</td>
                                </tr>`;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">- ไม่มีข้อมูล -</td></tr>';
                    }
                });

            const modal = document.getElementById('viewModal');
            modal.style.display = 'flex';
            setTimeout(() => { modal.classList.add('show'); }, 10);
        }

        function closeViewModal() {
            const modal = document.getElementById('viewModal');
            modal.classList.remove('show');
            setTimeout(() => { modal.style.display = 'none'; }, 300);
        }

        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target == modal) closeViewModal();
        }
