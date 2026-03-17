/* js/main_user.js */

document.addEventListener("DOMContentLoaded", function() {
    
    // --- 1. ฟังก์ชันค้นหาข้อมูลในตาราง ---
    window.searchTable = function(tableId, inputId) {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById(inputId);
        if(!input) return;
        
        filter = input.value.toUpperCase();
        table = document.getElementById(tableId);
        if(!table) return;

        tr = table.getElementsByTagName("tr");
        for (i = 1; i < tr.length; i++) {
            var rowContent = tr[i].textContent || tr[i].innerText;
            if (rowContent.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    };

    // --- 2. ฟังก์ชัน Toggle เมนู Project (Dropdown) ---
    window.toggleProject = function() {
        var submenu = document.getElementById("projectSubmenu");
        var parent = submenu.parentElement; 
        
        if (submenu.classList.contains("show")) {
            submenu.classList.remove("show");
            if(parent) parent.classList.remove("active");
        } else {
            submenu.classList.add("show");
            if(parent) parent.classList.add("active");
        }
    };

    // --- 3. ฟังก์ชัน Toggle Sidebar (Hamburger Button) ---
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const body = document.body;

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            // 1. สลับ class 'collapsed' ที่ Sidebar
            sidebar.classList.toggle('collapsed');
            
            // 2. สลับ class 'expanded' ที่เนื้อหาหลัก
            if(mainContent) {
                mainContent.classList.toggle('expanded');
            }

            // 3. สลับ class ที่ Body เพื่อให้ CSS รู้ว่าเมนูเปิดหรือปิด (ใช้ย้ายปุ่ม)
            body.classList.toggle('sidebar-closed');

            // --- Mobile Logic ---
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active');
                body.classList.toggle('sidebar-open-mobile');
            }
        });
    }
});