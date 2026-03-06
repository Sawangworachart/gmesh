/**
 * ไฟล์: assets/js/notification_widget.js
 * คำอธิบาย: สคริปต์จัดการ Animation และการปิด Widget แจ้งเตือน
 */

document.addEventListener("DOMContentLoaded", function() {
    // วนลูปเช็ค Toast ทุกตัวที่เรนเดอร์ออกมา
    const toasts = document.querySelectorAll('.ma-toast');
    
    toasts.forEach((toast, index) => {
        const id = toast.getAttribute('data-id');
        const storageKey = 'ma_notification_closed_' + id;

        // ถ้ายังไม่เคยปิด -> ให้แสดงผล
        if (!localStorage.getItem(storageKey)) {
            // ตั้งเวลาให้เด้งขึ้นมาทีละอัน (Stagger Animation)
            setTimeout(() => {
                toast.classList.add('show');
            }, 100 + (index * 200)); // ดีเลย์เพิ่มขึ้นทีละ 200ms
        }
    });
});

/**
 * ฟังก์ชันปิด Toast (แยกราย ID)
 * @param {Event} e - Event Object
 * @param {number} id - MA ID
 */
function closeToast(e, id) {
    if(e) e.stopPropagation();
    
    const toast = document.getElementById('toast-' + id);
    const storageKey = 'ma_notification_closed_' + id;

    if(toast) {
        // Animation ปิด
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px) scale(0.9)';
        toast.style.height = '0';
        toast.style.padding = '0';
        toast.style.margin = '0';

        // บันทึกว่าปิดแล้ว
        localStorage.setItem(storageKey, 'true');

        // ลบออกจาก DOM เพื่อความสะอาด (หลังจาก Animation จบ)
        setTimeout(() => {
            toast.remove();
        }, 500);
    }
}

/**
 * ฟังก์ชันคลิกดูรายละเอียด (Redirect หรือเรียก Modal)
 * @param {Event} e - Event Object
 * @param {number} id - MA ID
 */
function handleToastClick(e, id) {
    if (typeof viewProject === "function") {
        viewProject(id);
    } else {
        window.location.href = 'warn_admin.php?focus_ma=' + id;
    }
}
