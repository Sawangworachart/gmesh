// =========================================
// Login Page Scripts (สคริปต์สำหรับหน้าเข้าสู่ระบบ)
// =========================================

document.addEventListener('DOMContentLoaded', () => {
    // 1. เริ่มต้นเอฟเฟกต์เมาส์เรืองแสง (Mouse Glow Effect)
    initMouseGlow();

    // 2. เริ่มต้นเอฟเฟกต์โลโก้ 3D (3D Tilt Effect)
    init3DTilt();
});

/**
 * ฟังก์ชันจัดการเอฟเฟกต์เมาส์เรืองแสงติดตามเคอร์เซอร์
 */
function initMouseGlow() {
    const glow = document.getElementById('mouseGlow');
    if (!glow) return; // ถ้าไม่เจอ Element ให้จบฟังก์ชัน

    // ขยับแสงตามเมาส์
    document.addEventListener('mousemove', (e) => {
        // ใช้ requestAnimationFrame เพื่อประสิทธิภาพที่ดีขึ้น (ลื่นไหล)
        requestAnimationFrame(() => {
            glow.style.left = e.clientX + 'px';
            glow.style.top = e.clientY + 'px';
        });
    });

    // เพิ่มเอฟเฟกต์ขยายเมื่อชี้ไปที่องค์ประกอบที่โต้ตอบได้ (ปุ่ม, ช่องกรอก)
    const interactiveElements = document.querySelectorAll('button, input, a, label, .login-card-inner');
    interactiveElements.forEach(el => {
        el.addEventListener('mouseenter', () => {
            glow.classList.add('active'); // เพิ่ม Class ขยายแสง
        });
        el.addEventListener('mouseleave', () => {
            glow.classList.remove('active'); // ลบ Class กลับเป็นปกติ
        });
    });
}

/**
 * ฟังก์ชันจัดการเอฟเฟกต์เอียงการ์ดแบบ 3D (Parallax Tilt)
 */
function init3DTilt() {
    const card = document.getElementById('loginCard');
    const logo = document.getElementById('mainLogo');
    
    if (!card || !logo) return;

    // เมื่อขยับเมาส์บนการ์ด Login
    card.addEventListener('mousemove', (e) => {
        // หาจุดกึ่งกลางของการ์ด
        const rect = card.getBoundingClientRect();
        const cardCenterX = rect.left + rect.width / 2;
        const cardCenterY = rect.top + rect.height / 2;

        // หาตำแหน่งเมาส์เทียบกับจุดกึ่งกลาง (Offset)
        const mouseX = e.clientX - cardCenterX;
        const mouseY = e.clientY - cardCenterY;

        // คำนวณองศาการเอียง (หาร 15 เพื่อลดความไว ไม่ให้หมุนเยอะเกินไป)
        // rotateX ต้องกลับด้าน (เมาส์ขึ้น = เงยหน้า)
        const rotateY = mouseX / 15;
        const rotateX = (mouseY / 15) * -1;

        // ใส่ค่า Transform ให้โลโก้ (ทำให้ดูลอยออกมา)
        // scale3d(1.1, 1.1, 1.1) ช่วยขยายเล็กน้อยให้ดูเด้ง
        logo.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.1, 1.1, 1.1)`;
    });

    // เมื่อเมาส์ออกจากการ์ด ให้กลับมาตรงเหมือนเดิม (Reset)
    card.addEventListener('mouseleave', () => {
        logo.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
    });
}
