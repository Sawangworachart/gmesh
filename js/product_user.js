
// js/product_user.js

function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toUpperCase();
    const table = document.getElementById("productTable");
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        let tdArray = tr[i].getElementsByTagName("td");
        let textValue = "";
        for (let j = 0; j < tdArray.length; j++) {
            if (tdArray[j]) {
                textValue += tdArray[j].textContent || tdArray[j].innerText;
            }
        }
        if (textValue.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

function viewDetail(data) {
    const modal = document.getElementById('viewModal');
    const content = document.getElementById('v_content');
    
    let fileHtml = '';
    if (data.file_path) {
        fileHtml = `<a href="${data.file_path}" target="_blank" class="file-link"><i class="fas fa-paperclip"></i> ดูไฟล์แนบ</a>`;
    }

    content.innerHTML = `
        <div class="view-grid">
            <div class="detail-box">
                <span class="detail-label">ลูกค้า</span>
                <p class="detail-value">${data.customer || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">แผนก</span>
                <p class="detail-value">${data.department || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">อุปกรณ์</span>
                <p class="detail-value">${data.device_name || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">Serial Number</span>
                <p class="detail-value">${data.sn || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">วันที่เริ่ม</span>
                <p class="detail-value">${data.start_date || '-'}</p>
            </div>
            <div class="detail-box">
                <span class="detail-label">วันที่สิ้นสุด</span>
                <p class="detail-value">${data.end_date || '-'}</p>
            </div>
            <div class="detail-box col-span-2">
                <span class="detail-label">สถานะ</span>
                <p class="detail-value"><span class="status-badge ${data.badge_class}">${data.status_th}</span></p>
            </div>
            <div class="detail-box col-span-2">
                <span class="detail-label">อาการเสีย / สิ่งที่พบ</span>
                <p class="detail-value">${data.symptom || '-'}</p>
            </div>
            <div class="detail-box col-span-2">
                <span class="detail-label">ไฟล์แนบ</span>
                ${fileHtml || '<p class="detail-value text-muted">ไม่มีไฟล์แนบ</p>'}
            </div>
        </div>
    `;

    modal.classList.add('show');
}

function closeModal() {
    document.getElementById('viewModal').classList.remove('show');
}

// Close modal if clicked outside of the modal-box
window.onclick = function(event) {
    const modal = document.getElementById('viewModal');
    if (event.target == modal) {
        closeModal();
    }
}
