/**
 * ไฟล์: assets/js/customers_user.js
 * คำอธิบาย: ควบคุมการทำงานหน้า Customers (User View)
 * จัดการ AJAX polling เพื่อ sync ข้อมูลแบบ Real-time และการค้นหา
 */

// เก็บสถานะว่ากลุ่มไหนเปิดอยู่บ้าง (Group ID -> boolean)
var openedGroupsMap = {};

// ฟังก์ชันโหลดข้อมูลตาราง
function loadTableData() {
    // ถ้า User กำลังพิมพ์ค้นหาอยู่ ห้าม Refresh เพื่อไม่ให้ UI กระตุกหรือ Input หาย
    if ($('#searchInput').val().trim() !== "") {
        return; 
    }

    $.ajax({
        url: 'customers_user.php',
        type: 'GET',
        data: { action: 'fetch_data' },
        success: function(response) {
            
            // 1. Snapshot: ตรวจสอบก่อนว่าตอนนี้ User เปิดกลุ่มไหนค้างไว้บ้าง
            $('.arrow-icon.rotated').each(function() {
                var tr = $(this).closest('tr');
                var groupClass = tr.attr('data-group-id'); 
                if(groupClass) {
                    openedGroupsMap[groupClass] = true;
                }
            });

            // 2. Render: แทนที่ข้อมูลใหม่ในตาราง
            $('#tableBody').html(response);
            
            // 3. Restore: เปิดกลุ่มเดิมที่เคยเปิดค้างไว้
            for (var groupClass in openedGroupsMap) {
                if (openedGroupsMap[groupClass] === true) {
                    // แสดงรายการลูก
                    $('.' + groupClass).show();
                    
                    // หมุนลูกศรให้ชี้ลง
                    $('tr[data-group-id="' + groupClass + '"]').find('.arrow-icon').addClass('rotated');
                }
            }

            // 4. Update Stats: อัปเดตตัวเลขจำนวนกลุ่ม (นับเฉพาะ Header ที่ไม่ใช่ uncategorized)
            let total = $('.group-header').not('[data-group-id="group-uncat"]').length;
            $('#totalCountDisplay').text(total);
        },
        error: function(xhr, status, error) {
            console.warn("Sync Error: " + error);
        }
    });
}

$(document).ready(function() {
    loadTableData(); // โหลดครั้งแรกทันที
    
    // ตั้งค่า Polling ทุก 5 วินาที (ลดภาระ Server จากเดิม 3 วิ)
    setInterval(loadTableData, 5000);

    // Mouse Glow Effect
    const cards = document.querySelectorAll('.hero-card, .page-header-card');
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

// ฟังก์ชันเปิด-ปิดกลุ่ม (Accordion)
function toggleGroup(groupClass, headerElement) {
    var icon = $(headerElement).find('.arrow-icon');
    var isOpening = !icon.hasClass('rotated'); // เช็คว่ากำลังจะเปิด

    icon.toggleClass('rotated');
    $('.' + groupClass).slideToggle(200);

    // บันทึกสถานะลงตัวแปร (เพื่อใช้ตอน Auto Sync)
    if (isOpening) {
        openedGroupsMap[groupClass] = true;
    } else {
        delete openedGroupsMap[groupClass];
    }
}

// ฟังก์ชันค้นหาในตาราง
function filterTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var rows = document.querySelectorAll(".customer-row");
    
    rows.forEach(function(row) {
        var text = row.textContent || row.innerText;
        if (text.toUpperCase().indexOf(filter) > -1) {
            // ถ้าค้นหาเจอ
            if(filter !== "") {
                row.style.display = ""; // แสดงแถวนี้
                $(row).show(); // บังคับ show (เผื่อ parent ซ่อนอยู่)
                
                // ต้องเปิด Header ของกลุ่มด้วย เพื่อให้เห็นข้อมูล
                // หา class ของกลุ่มจาก row นี้
                var classes = row.className.split(' ');
                var groupClass = classes.find(c => c.startsWith('group-'));
                if(groupClass) {
                    // หา Header ที่คุมกลุ่มนี้แล้วสั่งโชว์
                    $('tr[data-group-id="' + groupClass + '"]').show();
                }
            } else {
                // ถ้าไม่ได้ค้นหา (ช่องว่าง) ปล่อยให้เป็นหน้าที่ของ Logic ปกติ
                row.style.display = "none";
            }
        } else {
            row.style.display = "none";
        }
    });

    // ถ้าลบคำค้นหาจนหมด ให้โหลดข้อมูลใหม่เพื่อรีเซ็ตการแสดงผลกลุ่มให้สวยงาม
    if(filter === "") {
        $('.customer-row').hide();
        $('.arrow-icon').removeClass('rotated');
        openedGroupsMap = {}; // รีเซ็ตสถานะการเปิด
        loadTableData(); 
    }
}
