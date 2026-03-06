// =========================================
// JS สำหรับ Sidebar (User)
// =========================================

// LocalStorage Script
(function() {
    const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (isCollapsed && window.innerWidth > 992) { 
        document.body.classList.add('sidebar-collapsed'); 
    }
})();

function toggleMainSidebar() {
    if (window.innerWidth > 992) {
        const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', isCollapsed);
    } else {
        document.body.classList.toggle('sidebar-mobile-open');
        const overlay = document.querySelector('.sidebar-overlay');
        overlay.style.display = document.body.classList.contains('sidebar-mobile-open') ? 'block' : 'none';
    }
}
