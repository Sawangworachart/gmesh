// =========================================
// JS สำหรับ Sidebar (Admin)
// =========================================

if (typeof jQuery == 'undefined') {
    console.error("jQuery is required.");
} else {
    $(document).ready(function() {
        function adjustContent(isCollapsed) {
            if ($(window).width() <= 768) {
                $('.main-content').css('margin-left', '0px');
                return;
            }
            const width = isCollapsed ? '70px' : '250px';
            $('.main-content').css({
                'margin-left': width,
                'transition': 'margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1)'
            });
        }

        adjustContent($('#sidebar').hasClass('collapsed'));

        $('#mobile-menu-btn').click(function() {
            $('#sidebar').addClass('mobile-active');
            $('#mobile-overlay-backdrop').addClass('active');
        });

        $('#mobile-overlay-backdrop').click(function() {
            $('#sidebar').removeClass('mobile-active');
            $('#mobile-overlay-backdrop').removeClass('active');
        });

        $('#sidebar-toggle').click(function() {
            const sidebar = $('.sidebar');
            sidebar.toggleClass('collapsed');

            const isCollapsed = sidebar.hasClass('collapsed');
            adjustContent(isCollapsed);

            if (isCollapsed) {
                $(this).find('i').removeClass('fa-bars').addClass('fa-chevron-right');
            } else {
                $(this).find('i').removeClass('fa-chevron-right').addClass('fa-bars');
            }
        });

        $(window).resize(function() {
            if ($(window).width() > 768) {
                $('#sidebar').removeClass('mobile-active');
                $('#mobile-overlay-backdrop').removeClass('active');
            }
            adjustContent($('#sidebar').hasClass('collapsed'));
        });

        $('.has-submenu > .toggle-btn').click(function(e) {
            e.preventDefault();
            if ($('.sidebar').hasClass('collapsed') && $(window).width() > 768) return;

            const parent = $(this).parent();
            const submenu = $(this).next('.submenu');

            $('.has-submenu').not(parent).find('.submenu').slideUp(250);
            $('.has-submenu').not(parent).removeClass('active-parent').find('.fa-caret-down').css('transform', 'rotate(0deg)');

            submenu.slideToggle(250);
            parent.toggleClass('active-parent');

            const icon = $(this).find('.fa-caret-down');
            if (parent.hasClass('active-parent')) {
                icon.css('transform', 'rotate(180deg)');
            } else {
                icon.css('transform', 'rotate(0deg)');
            }
        });

        if ($('.has-submenu').hasClass('active-parent')) {
            $('.has-submenu .submenu').show();
            $('.has-submenu .fa-caret-down').css('transform', 'rotate(180deg)');
        }

        $('#logout-btn').click(function(e) {
            e.preventDefault();
            $('#custom-logout-modal').addClass('active');
        });

        $('#modal-cancel-btn').click(function() {
            $('#custom-logout-modal').removeClass('active');
        });

        $('#modal-logout-btn').click(function() {
            window.location.href = 'includes/logout.php';
        });

        $('#custom-logout-modal').click(function(e) {
            if ($(e.target).is(this)) {
                $(this).removeClass('active');
            }
        });
    });
}
