// =========================================
// JS สำหรับหน้า PM Schedule (User)
// =========================================

$(document).ready(function() {
    // Mouse Glow Effect
    const body = document.querySelector('body');
    document.addEventListener('mousemove', (e) => {
        body.style.setProperty('--x', e.clientX + 'px');
        body.style.setProperty('--y', e.clientY + 'px');
    });

    // Optional: Add table row animation or interactivity here
    $('tbody tr').each(function(index) {
        $(this).css('animation', `fadeIn 0.3s ease forwards ${index * 0.05}s`);
        $(this).css('opacity', '0'); // Start hidden for animation
    });
});

// Add keyframes for fadeIn if not exists in CSS
const styleSheet = document.createElement("style");
styleSheet.innerText = `
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
`;
document.head.appendChild(styleSheet);
