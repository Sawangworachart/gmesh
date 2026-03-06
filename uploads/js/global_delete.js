/* global_delete.js */

/**
 * ฟังก์ชันสำหรับแจ้งเตือนและลบข้อมูล (ใช้ร่วมกันได้ทุกหน้า)
 * * @param {number|string} id - ID ของข้อมูลที่จะลบ
 * @param {string} apiUrl - URL ไฟล์ PHP ที่รับ request (เช่น 'service_project.php?api=true')
 * @param {function} successCallback - ฟังก์ชันที่จะทำงานเมื่อลบสำเร็จ (เช่น รีโหลดตาราง)
 */
function confirmDelete(id, apiUrl, successCallback) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลนี้จะถูกลบถาวรและไม่สามารถกู้คืนได้",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c', // สีแดง (Danger)
        cancelButtonColor: '#95a5a6',  // สีเทา
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: false, // ปุ่มลบอยู่ซ้าย หรือขวาตามต้องการ
        focusCancel: true // โฟกัสที่ปุ่มยกเลิกกันมือลั่น
    }).then((result) => {
        if (result.isConfirmed) {
            // ส่ง Request ไปลบข้อมูล
            $.post(apiUrl, { action: 'delete', id: id }, function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบสำเร็จ!',
                        text: res.message || 'ข้อมูลถูกลบเรียบร้อยแล้ว',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // เรียกฟังก์ชัน Callback (เช่น โหลดตารางใหม่)
                    if (typeof successCallback === 'function') {
                        successCallback();
                    }
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
            });
        }
    });
}