// js หน้า customers ของ user
        // ตัวแปรเก็บสถานะว่ากลุ่มไหนเปิดอยู่บ้าง
        var openedGroupsMap = {};

        function loadTableData() {
            // ถ้ากำลังพิมพ์ค้นหาอยู่ อย่าเพิ่ง Refresh (เพื่อไม่ให้ UI กระตุก)
            if ($('#searchInput').val().trim() !== "") {
                return; 
            }

            $.ajax({
                url: 'customers_user.php',
                type: 'GET',
                data: { action: 'fetch_data' },
                success: function(response) {
                    
                    // 1. จำค่า: ตรวจสอบว่าตอนนี้เปิดกลุ่มไหนอยู่บ้าง
                    $('.arrow-icon.rotated').each(function() {
                        var tr = $(this).closest('tr');
                        // ดึงข้อมูล ID กลุ่มจาก attribute data-group-id ที่เราสร้างไว้
                        var groupClass = tr.attr('data-group-id'); 
                        if(groupClass) {
                            openedGroupsMap[groupClass] = true;
                        }
                    });

                    // 2. แทนที่ข้อมูลใหม่
                    $('#tableBody').html(response);
                    
                    // 3. คืนค่า: เปิดกลุ่มเดิมที่เคยเปิดค้างไว้
                    for (var groupClass in openedGroupsMap) {
                        if (openedGroupsMap[groupClass] === true) {
                            // แสดงรายการลูก
                            $('.' + groupClass).show();
                            
                            // หมุนลูกศรให้ชี้ลง
                            $('tr[data-group-id="' + groupClass + '"]').find('.arrow-icon').addClass('rotated');
                        }
                    }

                    // 4. [แก้ไขจุดที่ 3] อัปเดตตัวเลขโดยนับเฉพาะ "หัวข้อกลุ่ม" (ไม่นับกลุ่ม uncategorized)
                    // เพื่อให้ตัวเลขตรงกับตาราง customer_groups (9)
                    let total = $('.group-header').not('[data-group-id="group-uncat"]').length;
                    $('#totalCountDisplay').text(total);
                },
                error: function(xhr, status, error) {
                    console.log("Sync Error: " + error);
                }
            });
        }

        $(document).ready(function() {
            loadTableData(); // โหลดครั้งแรกทันที
            
            // โหลดข้อมูลใหม่ทุก 3 วินาที (Sync)
            setInterval(loadTableData, 3000);
        });

        // ฟังก์ชันเปิด-ปิดกลุ่ม
        function toggleGroup(groupClass, headerElement) {
            var icon = $(headerElement).find('.arrow-icon');
            var isOpening = !icon.hasClass('rotated'); // เช็คว่ากำลังจะเปิด หรือจะปิด

            icon.toggleClass('rotated');
            $('.' + groupClass).slideToggle(200);

            // บันทึกสถานะลงตัวแปร (เพื่อใช้ตอน Auto Sync)
            if (isOpening) {
                openedGroupsMap[groupClass] = true;
            } else {
                delete openedGroupsMap[groupClass];
            }
        }

        // ฟังก์ชันค้นหา
        function filterTable() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toUpperCase();
            var rows = document.querySelectorAll(".customer-row");
            
            rows.forEach(function(row) {
                var text = row.textContent || row.innerText;
                if (text.toUpperCase().indexOf(filter) > -1) {
                    row.style.display = ""; 
                    // ถ้าค้นหาเจอ ให้แสดงบรรทัดนั้น
                    if(filter !== "") {
                        $(row).show();
                    }
                } else {
                    row.style.display = "none";
                }
            });

            // ถ้าลบคำค้นหาจนหมด ให้โหลดข้อมูลใหม่เพื่อรีเซ็ตการแสดงผลกลุ่ม
            if(filter === "") {
                $('.customer-row').hide();
                $('.arrow-icon').removeClass('rotated');
                openedGroupsMap = {}; // รีเซ็ตสถานะการเปิด
                loadTableData(); 
            }
        }
