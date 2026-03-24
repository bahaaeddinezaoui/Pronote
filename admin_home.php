<?php
// admin_home.php - Welcome page for Admin showing basic dashboard statistics
session_start();
date_default_timezone_set('Africa/Algiers');
require_once __DIR__ . '/lang/i18n.php';

// Check if user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username_db = "root";
$password_db = "08212001";
$dbname = "edutrack";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get admin info
$admin_name = "Admin";
$admin_position = t('administrator');
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT ADMINISTRATOR_FIRST_NAME_EN, ADMINISTRATOR_LAST_NAME_EN, ADMINISTRATOR_POSITION FROM administrator WHERE USER_ID = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $admin_name = htmlspecialchars($row['ADMINISTRATOR_FIRST_NAME_EN']) . ' ' . htmlspecialchars($row['ADMINISTRATOR_LAST_NAME_EN']);
        $admin_position = htmlspecialchars($row['ADMINISTRATOR_POSITION']);
    }
    $stmt->close();
}

// Get statistics from database
$students_by_category = [];
$cat_col = ($LANG === 'ar') ? "c.CATEGORY_NAME_AR" : "c.CATEGORY_NAME_EN";
$result = $conn->query("
    SELECT IFNULL($cat_col, 'Unknown') as category, COUNT(*) as count 
    FROM student s 
    LEFT JOIN category c ON s.CATEGORY_ID = c.CATEGORY_ID 
    GROUP BY s.CATEGORY_ID
    ORDER BY count DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students_by_category[] = $row;
    }
}

$absences_by_motif = [];
$motif_col_abs = ($LANG === 'ar') ? "am.ABSENCE_MOTIF_AR" : "am.ABSENCE_MOTIF_EN";
$result = $conn->query("
    SELECT IFNULL($motif_col_abs, 'Unknown') as motif, COUNT(*) as count 
    FROM absence a 
    LEFT JOIN absence_motif am ON a.ABSENCE_MOTIF_ID = am.ABSENCE_MOTIF_ID 
    GROUP BY am.ABSENCE_MOTIF_ID
    ORDER BY count DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $absences_by_motif[] = $row;
    }
}

$observations_by_motif = [];
$motif_col_obs = ($LANG === 'ar') ? "om.OBSERVATION_MOTIF_AR" : "om.OBSERVATION_MOTIF_EN";
$result = $conn->query("
    SELECT IFNULL($motif_col_obs, 'Unknown') as motif, COUNT(*) as count 
    FROM teacher_makes_an_observation_for_a_student tmo
    LEFT JOIN observation_motif om ON tmo.OBSERVATION_MOTIF_ID = om.OBSERVATION_MOTIF_ID 
    GROUP BY om.OBSERVATION_MOTIF_ID
    ORDER BY count DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $observations_by_motif[] = $row;
    }
}

$students_by_sex = [];
$result_sex = $conn->query("SELECT STUDENT_SEX, COUNT(*) as count FROM student GROUP BY STUDENT_SEX");
if ($result_sex) {
    while ($row = $result_sex->fetch_assoc()) {
        $key = empty($row['STUDENT_SEX']) ? 'Unknown' : $row['STUDENT_SEX'];
        $students_by_sex[] = ['sex' => $key, 'count' => $row['count']];
    }
}

$students_by_grade = [];
$grade_col = ($LANG === 'ar') ? "g.GRADE_LABEL_AR" : "g.GRADE_LABEL_EN";
$result_grade = $conn->query("
    SELECT IFNULL($grade_col, 'Unknown') as grade, COUNT(*) as count 
    FROM student s 
    LEFT JOIN grade g ON s.STUDENT_GRADE_ID = g.GRADE_ID
    GROUP BY s.STUDENT_GRADE_ID
    ORDER BY count DESC
    LIMIT 10
");
if ($result_grade) {
    while ($row = $result_grade->fetch_assoc()) {
        $students_by_grade[] = $row;
    }
}

$absences_trend = [];
$result_trend = $conn->query("
    SELECT DATE(ABSENCE_DATE_AND_TIME) as date, COUNT(*) as count 
    FROM absence 
    WHERE ABSENCE_DATE_AND_TIME >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(ABSENCE_DATE_AND_TIME)
    ORDER BY DATE(ABSENCE_DATE_AND_TIME) ASC
");
if ($result_trend) {
    while ($row = $result_trend->fetch_assoc()) {
        $absences_trend[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('home'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        /* Match admin_dashboard.php look & feel */
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px 30px;
        }

        .welcome-section {
            background: var(--glass-bg-strong);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            box-shadow: var(--glass-shadow);
            padding: 2.25rem 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(var(--glass-blur)) saturate(160%);
            -webkit-backdrop-filter: blur(var(--glass-blur)) saturate(160%);
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            inset: -40%;
            background:
                radial-gradient(520px 360px at 25% 25%, rgba(99, 102, 241, 0.22), transparent 60%),
                radial-gradient(560px 380px at 85% 20%, rgba(139, 92, 246, 0.18), transparent 62%),
                radial-gradient(600px 420px at 55% 90%, rgba(16, 185, 129, 0.14), transparent 62%);
            filter: blur(18px);
            opacity: 0.9;
            pointer-events: none;
        }

        .welcome-section > * {
            position: relative;
            z-index: 1;
        }

        .welcome-section h1 {
            margin: 0 0 0.75rem 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text-primary);
        }

        .welcome-section p {
            margin: 0;
            font-size: 15px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl, 15px);
            padding: 24px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .col-span-12 { grid-column: span 12; }
        .col-span-8 { grid-column: span 8; }
        .col-span-7 { grid-column: span 7; }
        .col-span-6 { grid-column: span 6; }
        .col-span-5 { grid-column: span 5; }
        .col-span-4 { grid-column: span 4; }
        .col-span-3 { grid-column: span 3; }

        @media (max-width: 1024px) {
            .col-span-12 { grid-column: span 12; }
            .col-span-8, .col-span-7, .col-span-6, .col-span-5, .col-span-4 { grid-column: span 6; }
            .col-span-3 { grid-column: span 4; }
        }
        
        @media (max-width: 768px) {
            .col-span-8, .col-span-7, .col-span-6, .col-span-5, .col-span-4, .col-span-3, .col-span-12 { grid-column: span 12; }
        }

        .chart-card:hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 16px;
            color: var(--text-primary);
            text-align: center;
            font-weight: 600;
            width: 100%;
        }
        
        .chart-container {
            position: relative;
            width: 100%;
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 250px;
        }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">

<div class="admin-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1><?php echo t('welcome_admin', $admin_position); ?></h1>
        <p><?php echo t('welcome_admin_sub'); ?></p>
    </div>

    <!-- Analytics Dashboard - Bento Layout -->
    <div class="charts-grid">
        <!-- Wide Line Chart (Absences Trend) -->
        <div class="chart-card col-span-12">
            <h3><?php echo t('absences_trend') !== 'absences_trend' ? t('absences_trend') : 'Absences Trend (Last 14 Days)'; ?></h3>
            <div class="chart-container" style="min-height: 320px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        
        <!-- Category (Bar) -->
        <div class="chart-card col-span-5">
            <h3><?php echo t('students_by_category') !== 'students_by_category' ? t('students_by_category') : 'Students by Category'; ?></h3>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Grade (Bar) -->
        <div class="chart-card col-span-7">
            <h3><?php echo t('students_by_grade') !== 'students_by_grade' ? t('students_by_grade') : 'Students by Grade'; ?></h3>
            <div class="chart-container">
                <canvas id="gradeChart"></canvas>
            </div>
        </div>

        <!-- Doughnut (Students by Sex) -->
        <div class="chart-card col-span-4">
            <h3><?php echo t('students_by_sex') !== 'students_by_sex' ? t('students_by_sex') : 'Students by Gender'; ?></h3>
            <div class="chart-container">
                <canvas id="sexChart"></canvas>
            </div>
        </div>

        <!-- Absences Motif (Doughnut) -->
        <div class="chart-card col-span-4">
            <h3><?php echo t('absences_by_motif') !== 'absences_by_motif' ? t('absences_by_motif') : 'Absences by Motif'; ?></h3>
            <div class="chart-container">
                <canvas id="absencesChart"></canvas>
            </div>
        </div>

        <!-- Observations Motif (Doughnut) -->
        <div class="chart-card col-span-4">
            <h3><?php echo t('observations_by_motif') !== 'observations_by_motif' ? t('observations_by_motif') : 'Observations by Motif'; ?></h3>
            <div class="chart-container">
                <canvas id="observationsChart"></canvas>
            </div>
        </div>
    </div>
</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const studentsByCategory = <?php echo json_encode($students_by_category); ?>;
    const absencesByMotif = <?php echo json_encode($absences_by_motif); ?>;
    const observationsByMotif = <?php echo json_encode($observations_by_motif); ?>;
    const studentsBySex = <?php echo json_encode($students_by_sex); ?>;
    const studentsByGrade = <?php echo json_encode($students_by_grade); ?>;
    const absencesTrend = <?php echo json_encode($absences_trend); ?>;

    const textColor = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#333';
    const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim() || '#e5e7eb';
    
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Inter', sans-serif";

    const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#3b82f6', '#14b8a6', '#f43f5e', '#a855f7'];

    // 1. Absences Trend Line Chart
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: absencesTrend.map(item => item.date),
            datasets: [{
                label: 'Absences',
                data: absencesTrend.map(item => item.count),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(context) { return context.raw; } } }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { color: textColor, precision: 0 },
                    grid: { color: gridColor, drawBorder: false }
                },
                x: {
                    ticks: { color: textColor },
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Category Chart
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'bar',
        data: {
            labels: studentsByCategory.map(item => item.category),
            datasets: [{
                label: '<?php echo t("student"); ?>',
                data: studentsByCategory.map(item => item.count),
                backgroundColor: colors[1],
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { color: textColor, precision: 0 }, grid: { color: gridColor } },
                x: { ticks: { color: textColor }, grid: { display: false } }
            }
        }
    });

    // 3. Grade Chart
    const ctxGrade = document.getElementById('gradeChart').getContext('2d');
    new Chart(ctxGrade, {
        type: 'bar',
        data: {
            labels: studentsByGrade.map(item => item.grade),
            datasets: [{
                label: '<?php echo t("student"); ?>',
                data: studentsByGrade.map(item => item.count),
                backgroundColor: colors[2],
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { color: textColor, precision: 0 }, grid: { color: gridColor } },
                y: { ticks: { color: textColor }, grid: { display: false } }
            }
        }
    });

    // 4. Students by Sex Chart
    const ctxSex = document.getElementById('sexChart').getContext('2d');
    new Chart(ctxSex, {
        type: 'doughnut',
        data: {
            labels: studentsBySex.map(item => item.sex),
            datasets: [{
                data: studentsBySex.map(item => item.count),
                backgroundColor: ['#3b82f6', '#ec4899', '#9ca3af'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: textColor } } },
            cutout: '65%'
        }
    });

    // 5. Absences Chart
    const ctxAbs = document.getElementById('absencesChart').getContext('2d');
    new Chart(ctxAbs, {
        type: 'doughnut',
        data: {
            labels: absencesByMotif.map(item => item.motif),
            datasets: [{
                data: absencesByMotif.map(item => item.count),
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: textColor } } },
            cutout: '65%'
        }
    });

    // 6. Observations Chart
    const ctxObs = document.getElementById('observationsChart').getContext('2d');
    new Chart(ctxObs, {
        type: 'polarArea',
        data: {
            labels: observationsByMotif.map(item => item.motif),
            datasets: [{
                data: observationsByMotif.map(item => item.count),
                backgroundColor: colors.map(c => c + '80'),
                borderColor: colors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: textColor } } },
            scales: {
                r: { grid: { color: gridColor }, ticks: { display: false } }
            }
        }
    });
</script>

</body>
</html>
