/**
 * ไฟล์: assets/js/index_user.js
 * คำอธิบาย: สคริปต์สำหรับจัดการหน้า Dashboard User (แจ้งเตือน Toast)
 */

document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามีตัวแปร notifications จาก PHP หรือไม่
    if (typeof notifications !== 'undefined' && notifications.length > 0) {
        const container = document.querySelector('.toast-container');
        
        // วนลูปแสดง Toast สำหรับแต่ละการแจ้งเตือน
        notifications.forEach((item, index) => {
            // กำหนดสีตามความเร่งด่วน
            const isDanger = item.badge_color.includes('danger');
            const headerClass = isDanger ? 'bg-danger text-white' : 'bg-white text-dark';
            const iconClass = isDanger ? 'text-white' : 'text-primary';
            const closeBtnClass = isDanger ? 'btn-close-white' : '';

            // สร้าง HTML ของ Toast
            const html = `
                <div id="toast-${index}" class="toast show border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="10000">
                    <div class="toast-header ${headerClass}">
                        <i class="fas fa-exclamation-circle me-2 ${iconClass}"></i>
                        <strong class="me-auto">${item.title}</strong>
                        <small>${item.time_text}</small>
                        <button type="button" class="btn-close ${closeBtnClass}" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body bg-white">
                        <strong>ลูกค้า:</strong> ${item.customer}<br>
                        <span class="text-muted small">กำหนด: ${item.date_str}</span>
                        ${item.note ? `<br><span class="text-muted small"><i class="far fa-comment-dots"></i> ${item.note}</span>` : ''}
                    </div>
                </div>
            `;
            
            // เพิ่ม Toast ลงใน Container
            container.insertAdjacentHTML('beforeend', html);
        });

        // สั่งให้ Toast หายไปอัตโนมัติ (Optional: ถ้าต้องการให้หายเอง)
        // setTimeout(() => {
        //     document.querySelectorAll('.toast').forEach(el => el.classList.remove('show'));
        // }, 10000);
    }
});
