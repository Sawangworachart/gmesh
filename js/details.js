/**
 * ไฟล์: assets/js/details.js
 * คำอธิบาย: สคริปต์สำหรับหน้า Project Details
 */

document.addEventListener('DOMContentLoaded', function() {
    // สามารถเพิ่ม Validation หรือ Interactivity อื่นๆ ได้ที่นี่
    console.log("Details page loaded.");

    // ตัวอย่าง: ยืนยันก่อนส่งฟอร์ม (ถ้าต้องการ)
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // e.preventDefault();
            // Swal.fire(...)
        });
    }
});
