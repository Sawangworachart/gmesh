// js ˹�� Service �ͧ admin
const API_URL = 'service_project.php?api=true';

function getFilePaths(data) {
    if (Array.isArray(data.file_paths) && data.file_paths.length) {
        return data.file_paths;
    }
    if (data.file_path && data.file_path !== '') {
        return [data.file_path];
    }
    return [];
}

function renderFileLinks(filePaths, emptyHtml) {
    if (!filePaths.length) {
        return emptyHtml;
    }

    const links = filePaths.map((filePath, index) => `
        <a href="uploads/${filePath}" target="_blank"
           style="text-decoration:none; display:inline-flex; align-items:center; gap:8px; background:#4361ee; padding:8px 16px; border-radius:8px; color:white; font-weight:600; font-size:0.9rem; transition:0.3s; box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);"
           onmouseover="this.style.background='#3651d1'"
           onmouseout="this.style.background='#4361ee'">
            <i class="fas fa-file-pdf"></i> PDF ${index + 1}
        </a>
    `).join('');

    return `<div style="display:flex; flex-wrap:wrap; gap:10px;">${links}</div>`;
}

$(document).ready(function () {
    loadSummary();
    loadTable();

    $('#serviceForm').on('submit', function (e) {
        e.preventDefault();

        let formData = new FormData(this);
        formData.append('action', 'save_data');

        Swal.fire({
            title: '���ѧ�ѹ�֡...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: (typeof API_URL !== 'undefined' ? API_URL : 'service_project.php?api=true'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '�����',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    if (typeof closeModal === 'function') {
                        closeModal();
                    } else {
                        $('#serviceModal').fadeOut();
                        $('.modal-overlay').removeClass('active');
                    }

                    loadSummary();
                    loadTable();
                    $('#serviceForm')[0].reset();
                    $('#filePreview').html('');
                } else {
                    Swal.fire('�Դ��ͼԴ��Ҵ', res.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire('�Դ��ͼԴ��Ҵ', '����������͢Ѵ��ͧ: ' + error, 'error');
            }
        });
    });
});

function loadCustomers() {
    $.post(API_URL, { action: 'fetch_customers' }, function (res) {
        if (res.success) {
            let opts = '<option value="">-- ���͡�١��� --</option>';
            res.data.forEach(c => {
                let contact = c.contact_name ? ` (${c.contact_name})` : '';
                opts += `<option value="${c.customers_id}">${c.customers_name}${contact}</option>`;
            });
            $('#customers_id').html(opts);
        }
    }, 'json');
}

function viewData(id) {
    $('#viewModal').addClass('show');
    $('#view_project_name_header').text('���ѧ��Ŵ������...');
    $('#view_customer_name_header').text('-');
    $('#view_ref_number_header').text('-');

    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const data = res.data;

            $('#view_project_name_header').text(data.project_name || '����кت����ç���');
            $('#view_customer_name_header').text(data.customers_name || '-');
            $('#view_ref_number_header').text(data.number || '-');

            let statusBadge = '';
            if (data.status_val === 'On-site') {
                statusBadge = '<span style="color:#4361ee;"><i class="fas fa-building"></i> On-site</span>';
            } else if (data.status_val === 'Remote') {
                statusBadge = '<span style="color:#0ea5e9;"><i class="fas fa-laptop-house"></i> Remote</span>';
            } else {
                statusBadge = '<span style="color:#f59e0b;"><i class="fas fa-user-friends"></i> Subcontractor</span>';
            }
            $('#view_status_badge').html(statusBadge);

            const start = formatDate(data.start_date);
            const end = data.end_date ? formatDate(data.end_date) : '�Ѩ�غѹ';
            $('#view_date_range').text(`${start} - ${end}`);

            const sn = data.sn || data['s/n'] || '-';
            $('#view_equipment_sn').html(`${data.equipment || '-'}<br><small style="color:#64748b; font-weight:400;">SN: ${sn}</small>`);

            $('#view_symptom').text(data.symptom || '-');
            $('#view_action').text(data.action_taken || '-');

            const filePaths = getFilePaths(data);
            $('#view_file_container').html(renderFileLinks(
                filePaths,
                `<div style="text-align: center; padding: 20px; border: 2px dashed #e2e8f0; border-radius: 12px; color: #cbd5e1;">
                    <i class="fas fa-file-alt" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <div style="font-size: 0.85rem; font-style: italic;">��������Ṻ</div>
                </div>`
            ));
        }
    }, 'json');
}

function closeViewModal() { $('#viewModal').removeClass('show'); }

function openModal() {
    $('#serviceForm')[0].reset();
    $('#service_id').val('0');
    $('#detail_id').val('0');
    $('#filePreview').html('');
    $('#modalTitle').text('�����ҹ��ԡ������');
    $('#start_date').val(new Date().toISOString().split('T')[0]);
    $('#serviceModal').addClass('show');
}

function closeModal() { $('#serviceModal').removeClass('show'); }

function editData(id) {
    $.get(API_URL, { action: 'fetch_single', id: id }, function (res) {
        if (res.success) {
            const data = res.data;
            $('#service_id').val(data.service_id);
            $('#detail_id').val(data.detail_id);
            $('#customers_id').val(data.customers_id);
            $('#project_name').val(data.project_name);
            $('#equipment').val(data.equipment);
            $('#sn').val(data.sn || data['s/n']);
            $('#number').val(data.number);
            $('#symptom').val(data.symptom);
            $('#action_taken').val(data.action_taken);
            $('#status').val(data.status_val);
            $('#start_date').val(data.start_date);
            $('#end_date').val(data.end_date);

            const filePaths = getFilePaths(data);
            if (filePaths.length) {
                const links = filePaths.map((filePath, index) =>
                    `<div><a href="uploads/${filePath}" target="_blank" class="text-primary"><i class="fas fa-file-pdf"></i> ������ ${index + 1}</a></div>`
                ).join('');
                $('#filePreview').html(links);
            } else {
                $('#filePreview').html('');
            }

            $('#modalTitle').text('��䢢�����');
            $('#serviceModal').addClass('show');
        }
    }, 'json');
}

function animateCounter(id, start, end, duration) {
    let obj = document.getElementById(id);
    if (!obj) return;
    let target = obj.querySelector('.count');
    if (!target) target = obj;

    let range = end - start;
    let current = start;
    let increment = end > start ? 1 : -1;
    let stepTime = Math.abs(Math.floor(duration / range));

    if (range === 0) {
        target.innerText = end;
        return;
    }
    stepTime = Math.max(stepTime, 20);

    let timer = setInterval(function () {
        current += increment;
        target.innerText = current;
        if (current == end) {
            clearInterval(timer);
        }
    }, stepTime);
}

function loadSummary() {
    $.get(API_URL, { action: 'fetch_status_summary' }, function (res) {
        if (res.success) {
            animateCounter('cardOnsite', 0, res.data['On-site'] || 0, 1000);
            animateCounter('cardRemote', 0, res.data['Remote'] || 0, 1000);
            animateCounter('cardSub', 0, res.data['แ�� Subcontractor'] || 0, 1000);
            animateCounter('cardTotal', 0, res.data['Total'] || 0, 1000);
        }
    }, 'json');
}

function loadTable() {
    $.get(API_URL, { action: 'fetch_all' }, function (res) {
        if (res.success) {
            let html = '';

            if (!res.data || res.data.length === 0) {
                html = '<tr><td colspan="7" class="text-center p-4 text-muted">��辺�����ŧҹ��ԡ��</td></tr>';
            } else {
                res.data.forEach((item, index) => {
                    const id = item.detail_id ?? 0;
                    const number = item.number ?? '-';
                    const startDate = item.start_date ?? null;
                    const endDate = item.end_date ?? null;
                    const project = item.project_name ?? '-';
                    const customer = item.customers_name ?? '-';
                    const agency = item.agency ?? '-';
                    const equipment = item.equipment ?? '-';
                    const sn = item.sn ?? '-';
                    const status = item.status ?? 'On-site';

                    let badgeClass = 'badge-onsite';
                    let statusIcon = 'fa-building';

                    if (status === 'Remote') {
                        badgeClass = 'badge-remote';
                        statusIcon = 'fa-laptop-house';
                    } else if (status === 'แ�� Subcontractor') {
                        badgeClass = 'badge-sub';
                        statusIcon = 'fa-user-friends';
                    }

                    html += `
<tr class="table-row-anim" style="animation-delay: ${index * 0.05}s">
    <td class="text-center text-muted" style="font-weight: 500; font-size: 0.85rem;">
        ${number}
    </td>

    <td style="min-width: 110px;">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div style="font-size: 0.82rem; color: #10b981; font-weight: 700; display: flex; align-items: center; gap: 5px;">
                <i class="fas fa-play-circle" style="font-size: 0.7rem;"></i>
                ${startDate ? formatDate(startDate) : '-'}
            </div>
            <div style="font-size: 0.82rem; color: ${endDate ? '#ef4444' : '#94a3b8'}; font-weight: 700; display: flex; align-items: center; gap: 5px; border-top: 1px dashed #f1f5f9; padding-top: 2px;">
                <i class="fas fa-check-circle" style="font-size: 0.7rem;"></i>
                ${endDate ? formatDate(endDate) : '<span style="font-weight:400; font-style: italic; font-size: 0.75rem;">����к�</span>'}
            </div>
        </div>
    </td>

    <td>
        <div style="font-weight:700; color:#1e293b; font-size: 0.9rem; line-height: 1.3;">${project}</div>
    </td>

    <td>
        <div style="font-weight:600; color:#475569; font-size: 0.88rem;">${customer}</div>
        <div style="font-size:0.78rem; color:#94a3b8;">${agency}</div>
    </td>

   <td style="max-width: 150px; vertical-align: top; padding: 12px 8px;">
    <div style="font-size: 0.82rem; color: #64748b; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; margin-bottom: 4px;" title="${equipment}">
        <i class="fas fa-microchip" style="font-size:0.7rem; margin-right:3px; color: #94a3b8;"></i>
        ${equipment}
    </div>
    <div style="font-size: 0.72rem; color: #cbd5e1; font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
        SN: ${sn}
    </div>
</td>

    <td class="text-center">
        <span class="status-badge ${badgeClass}" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 8px;">
            <i class="fas ${statusIcon}"></i> ${status}
        </span>
    </td>

    <td class="text-center">
        <div class="action-buttons" style="display: flex; justify-content: center; gap: 5px;">
            <button class="btn-icon btn-att" onclick="viewData(${id})" title="����������´" style="width: 30px; height: 30px; font-size: 0.8rem;">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn-icon btn-edit" onclick="editData(${id})" title="���" style="width: 30px; height: 30px; font-size: 0.8rem;">
                <i class="fas fa-pencil-alt"></i>
            </button>
        </div>
    </td>
</tr>`;
                });
            }

            $('#tableBody').html(html);
        } else {
            $('#tableBody').html('<tr><td colspan="7" class="text-center text-danger">��Ŵ��������������</td></tr>');
        }
    }, 'json')
        .fail(function (xhr) {
            console.error(xhr.responseText);
            $('#tableBody').html('<tr><td colspan="7" class="text-center text-danger">API Error</td></tr>');
        });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
}

let selectedStatusFilter = '';

function filterByStatus(status) {
    if (status === 'Subcontractor') status = 'Subcontractor';
    selectedStatusFilter = status.toUpperCase();

    document.querySelectorAll('.status-card').forEach(c => c.classList.remove('active-filter'));
    if (status === '') document.querySelector('.card-total').classList.add('active-filter');
    else if (status === 'On-site') document.querySelector('.card-onsite').classList.add('active-filter');
    else if (status === 'Remote') document.querySelector('.card-remote').classList.add('active-filter');
    else if (status === 'Subcontractor') document.querySelector('.card-sub').classList.add('active-filter');

    filterTable();
}

function filterTable() {
    const searchVal = $('#searchInput').val().toUpperCase();
    const projectVal = $('#projectFilter').val().toUpperCase();

    $('#tableBody tr').each(function () {
        if ($(this).find('td').length < 2) return;

        const allText = $(this).text().toUpperCase();
        const rowProject = $(this).find('td:eq(2)').text().trim().toUpperCase();
        const rowStatus = $(this).find('td:eq(5)').text().trim().toUpperCase();

        const matchSearch = allText.includes(searchVal);
        const matchProject = projectVal === '' || rowProject === projectVal;
        const matchStatus = selectedStatusFilter === '' || rowStatus.includes(selectedStatusFilter.toUpperCase());

        if (matchSearch && matchProject && matchStatus) {
            $(this).fadeIn(200);
        } else {
            $(this).hide();
        }
    });
}

window.onclick = function (e) {
    if (e.target == document.getElementById('serviceModal')) closeModal();
    if (e.target == document.getElementById('viewModal')) closeViewModal();
}
