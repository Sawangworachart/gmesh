<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Products / Service</title>

    <!-- =========================
         FONTS & ICONS
    ========================== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <!-- =========================
         GLOBAL STYLE
    ========================== -->
    <style>
        :root {
            --primary: #3b82f6;
            --primary-soft: #eaf1ff;
            --success: #22c55e;
            --success-soft: #e9f9ef;
            --warning: #f97316;
            --warning-soft: #fff3e8;
            --purple: #a855f7;
            --purple-soft: #f4eefe;
            --bg: #f6f7fb;
            --text-main: #0f172a;
            --text-sub: #64748b;
            --border: #e5e7eb;
            --radius: 14px;
        }

        * {
            box-sizing: border-box;
            font-family: 'Sarabun', sans-serif;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text-main);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* =========================
           LAYOUT
        ========================== */
        .container {
            max-width: 1400px;
            margin: auto;
            padding: 24px;
        }

        /* =========================
           HEADER
        ========================== */
        .page-header {
            background: #fff;
            border-radius: var(--radius);
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 20px rgba(0,0,0,0.04);
            margin-bottom: 24px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .page-title .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .page-title h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .page-title span {
            font-size: 14px;
            color: var(--text-sub);
        }

        .btn-add {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 8px 16px rgba(59,130,246,0.3);
        }

        /* =========================
           SUMMARY CARDS
        ========================== */
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 2px solid transparent;
        }

        .card.active {
            border-color: var(--primary);
            background: #f0f6ff;
        }

        .card .icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .card .info h3 {
            margin: 0;
            font-size: 14px;
            color: var(--text-sub);
            font-weight: 500;
        }

        .card .info strong {
            font-size: 26px;
            font-weight: 700;
        }

        .icon-blue { background: var(--primary-soft); color: var(--primary); }
        .icon-green { background: var(--success-soft); color: var(--success); }
        .icon-orange { background: var(--warning-soft); color: var(--warning); }
        .icon-purple { background: var(--purple-soft); color: var(--purple); }

        /* =========================
           SEARCH
        ========================== */
        .search-box {
            background: #fff;
            border-radius: 999px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 420px;
            border: 1px solid var(--border);
            margin-bottom: 16px;
        }

        .search-box input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 14px;
        }

        /* =========================
           TABLE
        ========================== */
        .table-wrapper {
            background: #fff;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
        }

        th, td {
            padding: 16px 14px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        th {
            color: var(--text-sub);
            font-weight: 600;
            text-align: left;
        }

        tbody tr:hover {
            background: #f9fbff;
        }

        .customer {
            font-weight: 600;
        }

        .sub {
            font-size: 12px;
            color: var(--text-sub);
        }

        /* =========================
           STATUS BADGE
        ========================== */
        .badge {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge.blue { background: var(--primary-soft); color: var(--primary); }
        .badge.green { background: var(--success-soft); color: var(--success); }
        .badge.orange { background: var(--warning-soft); color: var(--warning); }
        .badge.purple { background: var(--purple-soft); color: var(--purple); }

        /* =========================
           ACTIONS
        ========================== */
        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-view { background: var(--primary-soft); color: var(--primary); }
        .btn-edit { background: var(--warning-soft); color: var(--warning); }

        /* =========================
           RESPONSIVE
        ========================== */
        @media (max-width: 900px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead { display: none; }

            tr {
                border-bottom: 1px solid var(--border);
            }

            td {
                display: flex;
                justify-content: space-between;
                padding: 10px 14px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fa-solid fa-cubes"></i></div>
            <div>
                <h1>Products / Service</h1>
                <span>จัดการข้อมูลสถานะการซ่อมบำรุง</span>
            </div>
        </div>
        <button class="btn-add"><i class="fa-solid fa-plus"></i> เพิ่มข้อมูล</button>
    </div>

    <!-- SUMMARY -->
    <div class="summary">
        <div class="card active">
            <div class="icon icon-blue"><i class="fa-solid fa-layer-group"></i></div>
            <div class="info"><h3>ทั้งหมด</h3><strong>5</strong></div>
        </div>
        <div class="card">
            <div class="icon icon-blue"><i class="fa-regular fa-clock"></i></div>
            <div class="info"><h3>รอสินค้าจากลูกค้า</h3><strong>2</strong></div>
        </div>
        <div class="card">
            <div class="icon icon-purple"><i class="fa-solid fa-magnifying-glass"></i></div>
            <div class="info"><h3>ตรวจสอบ</h3><strong>1</strong></div>
        </div>
        <div class="card">
            <div class="icon icon-orange"><i class="fa-solid fa-users"></i></div>
            <div class="info"><h3>รอสินค้าจาก supplier</h3><strong>1</strong></div>
        </div>
        <div class="card">
            <div class="icon icon-green"><i class="fa-solid fa-check"></i></div>
            <div class="info"><h3>ส่งคืนลูกค้า</h3><strong>1</strong></div>
        </div>
    </div>

    <!-- SEARCH -->
    <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="ค้นหาชื่อลูกค้า, อุปกรณ์ หรือ S/N..." />
    </div>

    <!-- TABLE -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อลูกค้า / หน่วยงาน</th>
                    <th>ชื่ออุปกรณ์</th>
                    <th>S/N</th>
                    <th>สถานะ</th>
                    <th>วันที่เริ่ม</th>
                    <th>อาการ / รายละเอียด</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>(5)</td>
                    <td><div class="customer">อื่นๆ</div><div class="sub">อื่นๆ</div></td>
                    <td>ติดตั้งโปรแกรม</td>
                    <td>151</td>
                    <td><span class="badge blue"><i class="fa-regular fa-clock"></i> รอสินค้าจากลูกค้า</span></td>
                    <td>4/2/2026</td>
                    <td>ทดลอง</td>
                    <td>
                        <div class="actions">
                            <div class="btn-icon btn-view"><i class="fa-regular fa-eye"></i></div>
                            <div class="btn-icon btn-edit"><i class="fa-regular fa-pen-to-square"></i></div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>(4)</td>
                    <td><div class="customer">อื่นๆ</div><div class="sub">อื่นๆ</div></td>
                    <td>คอม ทดลอง</td>
                    <td>-</td>
                    <td><span class="badge green"><i class="fa-solid fa-check"></i> ส่งคืนลูกค้า</span></td>
                    <td>4/2/2026</td>
                    <td>เสียทดลอง</td>
                    <td>
                        <div class="actions">
                            <div class="btn-icon btn-view"><i class="fa-regular fa-eye"></i></div>
                            <div class="btn-icon btn-edit"><i class="fa-regular fa-pen-to-square"></i></div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>(3)</td>
                    <td><div class="customer">อื่นๆ</div><div class="sub">อื่นๆ</div></td>
                    <td>com ทดลอง</td>
                    <td>-</td>
                    <td><span class="badge blue"><i class="fa-regular fa-clock"></i> รอสินค้าจากลูกค้า</span></td>
                    <td>3/2/2026</td>
                    <td>เสีย</td>
                    <td>
                        <div class="actions">
                            <div class="btn-icon btn-view"><i class="fa-regular fa-eye"></i></div>
                            <div class="btn-icon btn-edit"><i class="fa-regular fa-pen-to-square"></i></div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>(2)</td>
                    <td><div class="customer">Global Mesh</div><div class="sub">แอดมินเซอร์วิส</div></td>
                    <td>ติดตั้งโปรแกรม</td>
                    <td>151</td>
                    <td><span class="badge purple"><i class="fa-solid fa-magnifying-glass"></i> ตรวจสอบ</span></td>
                    <td>1/1/2025</td>
                    <td>ติดตั้งโปรแกรม Adobe Creative Cloud</td>
                    <td>
                        <div class="actions">
                            <div class="btn-icon btn-view"><i class="fa-regular fa-eye"></i></div>
                            <div class="btn-icon btn-edit"><i class="fa-regular fa-pen-to-square"></i></div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>(1)</td>
                    <td><div class="customer">Global Mesh</div><div class="sub">แอดมินเซอร์วิส</div></td>
                    <td>สาย LAN</td>
                    <td>6666</td>
                    <td><span class="badge orange"><i class="fa-solid fa-users"></i> รอสินค้าจาก supplier</span></td>
                    <td>1/2/2025</td>
                    <td>เข้าหัวสาย LAN ห้องประชุมชั้น 3</td>
                    <td>
                        <div class="actions">
                            <div class="btn-icon btn-view"><i class="fa-regular fa-eye"></i></div>
                            <div class="btn-icon btn-edit"><i class="fa-regular fa-pen-to-square"></i></div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
