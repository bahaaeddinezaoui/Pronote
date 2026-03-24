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

// ── 1. Absences Trend — last 30 days ──────────────────────────────
$absences_trend = [];
$result = $conn->query("
    SELECT DATE(a.ABSENCE_DATE_AND_TIME) as date, COUNT(*) as count
    FROM absence a
    WHERE a.ABSENCE_DATE_AND_TIME >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(a.ABSENCE_DATE_AND_TIME)
    ORDER BY date ASC
");
if ($result) while ($row = $result->fetch_assoc()) $absences_trend[] = $row;

// ── 2. Top 10 Most Absent Students ────────────────────────────────
$top_absent_students = [];
$result = $conn->query("
    SELECT CONCAT(s.STUDENT_FIRST_NAME_EN, ' ', s.STUDENT_LAST_NAME_EN) as student,
           COUNT(sga.ABSENCE_ID) as total
    FROM student_gets_absent sga
    JOIN student s ON sga.STUDENT_SERIAL_NUMBER = s.STUDENT_SERIAL_NUMBER
    GROUP BY sga.STUDENT_SERIAL_NUMBER
    ORDER BY total DESC
    LIMIT 10
");
if ($result) while ($row = $result->fetch_assoc()) $top_absent_students[] = $row;

// ── 3. Absences by Motif ──────────────────────────────────────────
$absences_by_motif = [];
$motif_col_abs = ($LANG === 'ar') ? "am.ABSENCE_MOTIF_AR" : "am.ABSENCE_MOTIF_EN";
$result = $conn->query("
    SELECT IFNULL($motif_col_abs, 'Unspecified') as motif, COUNT(*) as count
    FROM absence a
    LEFT JOIN absence_motif am ON a.ABSENCE_MOTIF_ID = am.ABSENCE_MOTIF_ID
    GROUP BY am.ABSENCE_MOTIF_ID
    ORDER BY count DESC
");
if ($result) while ($row = $result->fetch_assoc()) $absences_by_motif[] = $row;

// ── 4. Punishments by Type ────────────────────────────────────────
$punishments_by_type = [];
$pub_col = ($LANG === 'ar') ? "pt.PUNISHMENT_LABEL_AR" : "pt.PUNISHMENT_LABEL_EN";
$result = $conn->query("
    SELECT IFNULL($pub_col, 'Unknown') as type, COUNT(*) as count
    FROM secretary_punishes_student sps
    LEFT JOIN punishment_type pt ON sps.PUNISHMENT_TYPE_ID = pt.PUNISHMENT_TYPE_ID
    GROUP BY sps.PUNISHMENT_TYPE_ID
    ORDER BY count DESC
");
if ($result) while ($row = $result->fetch_assoc()) $punishments_by_type[] = $row;

// ── 5. Rewards by Type ────────────────────────────────────────────
$rewards_by_type = [];
$rew_col = ($LANG === 'ar') ? "rt.REWARD_LABEL_AR" : "rt.REWARD_LABEL_EN";
$result = $conn->query("
    SELECT IFNULL($rew_col, 'Unknown') as type, COUNT(*) as count
    FROM secretary_rewards_student srs
    LEFT JOIN reward_type rt ON srs.REWARD_TYPE_ID = rt.REWARD_TYPE_ID
    GROUP BY srs.REWARD_TYPE_ID
    ORDER BY count DESC
");
if ($result) while ($row = $result->fetch_assoc()) $rewards_by_type[] = $row;

// ── 6. Punishment vs Reward — last 6 months (monthly grouped) ─────
$monthly_punish_reward = [];
$result = $conn->query("
    SELECT month, SUM(punishments) as punishments, SUM(rewards) as rewards FROM (
        SELECT DATE_FORMAT(PUNISHMENT_SUGGESTED_AT,'%Y-%m') as month,
               COUNT(*) as punishments, 0 as rewards
        FROM secretary_punishes_student
        WHERE PUNISHMENT_SUGGESTED_AT >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        UNION ALL
        SELECT DATE_FORMAT(REWARD_SUGGESTED_AT,'%Y-%m') as month,
               0 as punishments, COUNT(*) as rewards
        FROM secretary_rewards_student
        WHERE REWARD_SUGGESTED_AT >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
    ) combined
    GROUP BY month
    ORDER BY month ASC
");
if ($result) while ($row = $result->fetch_assoc()) $monthly_punish_reward[] = $row;

// ── 7. Observations by Motif ──────────────────────────────────────
$observations_by_motif = [];
$motif_col_obs = ($LANG === 'ar') ? "om.OBSERVATION_MOTIF_AR" : "om.OBSERVATION_MOTIF_EN";
$result = $conn->query("
    SELECT IFNULL($motif_col_obs, 'Unspecified') as motif, COUNT(*) as count
    FROM teacher_makes_an_observation_for_a_student tmo
    LEFT JOIN observation_motif om ON tmo.OBSERVATION_MOTIF_ID = om.OBSERVATION_MOTIF_ID
    GROUP BY om.OBSERVATION_MOTIF_ID
    ORDER BY count DESC
");
if ($result) while ($row = $result->fetch_assoc()) $observations_by_motif[] = $row;

// ── 8. Most Active Teachers (by observation count) ────────────────
$top_observing_teachers = [];
$result = $conn->query("
    SELECT CONCAT(t.TEACHER_FIRST_NAME_EN,' ',t.TEACHER_LAST_NAME_EN) as teacher,
           COUNT(*) as total
    FROM teacher_makes_an_observation_for_a_student tmo
    JOIN teacher t ON tmo.TEACHER_SERIAL_NUMBER = t.TEACHER_SERIAL_NUMBER
    GROUP BY tmo.TEACHER_SERIAL_NUMBER
    ORDER BY total DESC
    LIMIT 10
");
if ($result) while ($row = $result->fetch_assoc()) $top_observing_teachers[] = $row;

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
            margin-bottom: 16px;
            font-size: 15px;
            color: var(--text-primary);
            text-align: left;
            font-weight: 700;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .chart-subtitle {
            font-size: 12px;
            font-weight: 400;
            color: var(--text-secondary);
            margin-left: 4px;
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

    <!-- Analytics Dashboard - Bento Grid -->
    <div class="charts-grid">

        <!-- ROW 1: Absences Trend – full width -->
        <div class="chart-card col-span-12">
            <h3>📈 Absences Trend <span class="chart-subtitle">(Last 30 Days)</span></h3>
            <div class="chart-container" style="min-height:300px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- ROW 2: Top Absentees (8) + Absences by Motif (4) -->
        <div class="chart-card col-span-8">
            <h3>🔴 Top 10 Most Absent Students</h3>
            <div class="chart-container" style="min-height:300px;">
                <canvas id="topAbsentChart"></canvas>
            </div>
        </div>
        <div class="chart-card col-span-4">
            <h3>📋 Absences by Motif</h3>
            <div class="chart-container" style="min-height:300px;">
                <canvas id="absencesChart"></canvas>
            </div>
        </div>

        <!-- ROW 3: Punishment vs Reward Monthly – full width -->
        <div class="chart-card col-span-12">
            <h3>⚖️ Punishments vs Rewards <span class="chart-subtitle">(Last 6 Months)</span></h3>
            <div class="chart-container" style="min-height:280px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <!-- ROW 4: Punishments by Type (6) + Rewards by Type (6) -->
        <div class="chart-card col-span-6">
            <h3>🔒 Punishments by Type</h3>
            <div class="chart-container" style="min-height:280px;">
                <canvas id="punishChart"></canvas>
            </div>
        </div>
        <div class="chart-card col-span-6">
            <h3>🏅 Rewards by Type</h3>
            <div class="chart-container" style="min-height:280px;">
                <canvas id="rewardChart"></canvas>
            </div>
        </div>

        <!-- ROW 5: Top Observing Teachers (8) + Observations by Motif (4) -->
        <div class="chart-card col-span-8">
            <h3>👁️ Most Active Observing Teachers</h3>
            <div class="chart-container" style="min-height:300px;">
                <canvas id="topTeachersChart"></canvas>
            </div>
        </div>
        <div class="chart-card col-span-4">
            <h3>📝 Observations by Motif</h3>
            <div class="chart-container" style="min-height:300px;">
                <canvas id="observationsChart"></canvas>
            </div>
        </div>

    </div>
</div>

</div>
</div>
</div>

<script src="js/chart.umd.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof Chart === "undefined") {
            console.error("Chart.js failed to load from CDN.");
            return;
        }

    // ── PHP data injected into JS ──────────────────────────────────
    const absencesTrend       = <?php echo json_encode($absences_trend); ?>;
    const topAbsentStudents   = <?php echo json_encode($top_absent_students); ?>;
    const absencesByMotif     = <?php echo json_encode($absences_by_motif); ?>;
    const monthlyPunishReward = <?php echo json_encode($monthly_punish_reward); ?>;
    const punishmentsByType   = <?php echo json_encode($punishments_by_type); ?>;
    const rewardsByType       = <?php echo json_encode($rewards_by_type); ?>;
    const observationsByMotif = <?php echo json_encode($observations_by_motif); ?>;
    const topTeachers         = <?php echo json_encode($top_observing_teachers); ?>;

    const getThemeColors = () => {
        const root = document.documentElement;
        const isDark = root.getAttribute('data-theme') === 'dark';
        const style = getComputedStyle(root);
        
        const textColor = style.getPropertyValue('--text-primary').trim() || (isDark ? '#f8fafc' : '#1e293b');
        const subColor  = style.getPropertyValue('--text-secondary').trim() || (isDark ? '#94a3b8' : '#64748b');
        const gridColor = style.getPropertyValue('--border-color').trim() || (isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.06)');
        
        return { textColor, subColor, gridColor };
    };

    const { textColor, subColor, gridColor } = getThemeColors();

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;

    const C = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#3b82f6','#14b8a6','#f43f5e','#a855f7'];
    const scaleY = { beginAtZero:true, ticks:{ color:textColor, precision:0 }, grid:{ color:gridColor } };
    const scaleX = { ticks:{ color:textColor }, grid:{ display:false } };
    const scaleXint = { beginAtZero:true, ticks:{ color:textColor, precision:0 }, grid:{ color:gridColor } };
    const scaleYno  = { ticks:{ color:textColor }, grid:{ display:false } };

    // ── 1. Absences Trend (line, 30 days) ─────────────────────────
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: absencesTrend.map(d => d.date),
            datasets: [{
                label: 'Absences',
                data: absencesTrend.map(d => d.count),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.12)',
                borderWidth: 2.5,
                tension: 0.45,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ display:false } },
            scales:{ y:scaleY, x:scaleX }
        }
    });

    // ── 2. Top 10 Most Absent Students (horizontal bar) ───────────
    new Chart(document.getElementById('topAbsentChart'), {
        type: 'bar',
        data: {
            labels: topAbsentStudents.map(d => d.student),
            datasets: [{
                label: 'Absences',
                data: topAbsentStudents.map(d => d.total),
                backgroundColor: topAbsentStudents.map((_,i) => `${C[0]}${Math.round(255 - i*18).toString(16).padStart(2,'0')}`),
                borderRadius: 5,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y',
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ display:false } },
            scales:{ x:scaleXint, y:scaleYno }
        }
    });

    // ── 3. Absences by Motif (doughnut) ──────────────────────────
    new Chart(document.getElementById('absencesChart'), {
        type: 'doughnut',
        data: {
            labels: absencesByMotif.map(d => d.motif),
            datasets: [{ data: absencesByMotif.map(d => d.count), backgroundColor: C, borderWidth:0, hoverOffset:6 }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ position:'bottom', labels:{ color:textColor, boxWidth:12, padding:8 } } },
            cutout: '65%'
        }
    });

    // ── 4. Punishment vs Reward Monthly (grouped bar) ─────────────
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthlyPunishReward.map(d => d.month),
            datasets: [
                {
                    label: 'Punishments',
                    data: monthlyPunishReward.map(d => d.punishments),
                    backgroundColor: '#ef4444cc',
                    borderRadius: 5,
                    borderSkipped: false
                },
                {
                    label: 'Rewards',
                    data: monthlyPunishReward.map(d => d.rewards),
                    backgroundColor: '#10b981cc',
                    borderRadius: 5,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ position:'top', labels:{ color:textColor, boxWidth:12 } } },
            scales:{ y:scaleY, x:scaleX }
        }
    });

    // ── 5. Punishments by Type (bar) ──────────────────────────────
    new Chart(document.getElementById('punishChart'), {
        type: 'bar',
        data: {
            labels: punishmentsByType.map(d => d.type),
            datasets: [{
                label: 'Punishments',
                data: punishmentsByType.map(d => d.count),
                backgroundColor: punishmentsByType.map((_,i) => C[(i+3)%C.length]),
                borderRadius: 5,
                borderSkipped: false
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ display:false } },
            scales:{ y:scaleY, x:scaleX }
        }
    });

    // ── 6. Rewards by Type (bar) ──────────────────────────────────
    new Chart(document.getElementById('rewardChart'), {
        type: 'bar',
        data: {
            labels: rewardsByType.map(d => d.type),
            datasets: [{
                label: 'Rewards',
                data: rewardsByType.map(d => d.count),
                backgroundColor: rewardsByType.map((_,i) => C[(i+1)%C.length]),
                borderRadius: 5,
                borderSkipped: false
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ display:false } },
            scales:{ y:scaleY, x:scaleX }
        }
    });

    // ── 7. Top Observing Teachers (horizontal bar) ────────────────
    new Chart(document.getElementById('topTeachersChart'), {
        type: 'bar',
        data: {
            labels: topTeachers.map(d => d.teacher),
            datasets: [{
                label: 'Observations',
                data: topTeachers.map(d => d.total),
                backgroundColor: topTeachers.map((_,i) => `${C[4]}${Math.round(255 - i*18).toString(16).padStart(2,'0')}`),
                borderRadius: 5,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y',
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ display:false } },
            scales:{ x:scaleXint, y:scaleYno }
        }
    });

    // ── 8. Observations by Motif (polar area) ────────────────────
    new Chart(document.getElementById('observationsChart'), {
        type: 'polarArea',
        data: {
            labels: observationsByMotif.map(d => d.motif),
            datasets: [{
                data: observationsByMotif.map(d => d.count),
                backgroundColor: C.map(c => c + 'a0'),
                borderColor: C,
                borderWidth: 1
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ position:'bottom', labels:{ color:textColor, boxWidth:12, padding:8 } } },
            scales:{ r:{ grid:{ color:gridColor }, ticks:{ display:false } } }
        }
    });
});
</script>

</body>
</html>
