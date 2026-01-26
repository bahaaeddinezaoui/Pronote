<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
<meta charset="UTF-8">
<title><?php echo t('observations'); ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="parent">
    <div class="div1" id="navbar">
        <div style="font-weight:bold; font-size:20px;"><?php echo t('teacher_label'); ?> Panel</div>
        <a href="fill_form.php">⬅ <?php echo t('previous'); ?></a>
        <a href="logout.php" class="logout-btn"><?php echo t('logout'); ?></a>
    </div>

    <div class="div2">
        <h2><?php echo t('record_observation'); ?></h2>

        <form id="observationForm">
            <label for="student"><?php echo t('student_name'); ?></label><br>
            <input type="text" id="student" name="student" placeholder="<?php echo t('placeholder_type_student_name'); ?>" autocomplete="off" required>
            <div id="studentList"></div>
            <br>

            <label for="motif"><?php echo t('observation_motif'); ?></label><br>
            <input type="text" id="motif" name="motif" maxlength="30" required><br><br>

            <label for="note"><?php echo t('observation_note'); ?></label><br>
            <textarea id="note" name="note" maxlength="256" rows="4"></textarea><br><br>

            <button type="submit"><?php echo t('submit_observation'); ?></button>
        </form>

        <div id="responseMsg"></div>
    </div>
</div>

<script>
document.getElementById('student').addEventListener('input', function() {
    const name = this.value.trim();
    if (name.length < 2) return;
    
    fetch('student_search.php?query=' + encodeURIComponent(name))
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('studentList');
            list.innerHTML = '';
            data.forEach(stu => {
                const item = document.createElement('div');
                item.textContent = stu.name;
                item.dataset.serial = stu.serial;
                item.className = 'student-item';
                item.onclick = () => {
                    document.getElementById('student').value = stu.name;
                    document.getElementById('student').dataset.serial = stu.serial;
                    list.innerHTML = '';
                };
                list.appendChild(item);
            });
        });
});

document.getElementById('observationForm').addEventListener('submit', e => {
    e.preventDefault();
    const studentSerial = document.getElementById('student').dataset.serial;
    const motif = document.getElementById('motif').value;
    const note = document.getElementById('note').value;

    fetch('submit_observation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ studentSerial, motif, note })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('responseMsg').textContent = data.message;
        if (data.success) {
            document.getElementById('observationForm').reset();
        }
    })
    .catch(err => {
        document.getElementById('responseMsg').textContent = 'Error: ' + err;
    });
});
</script>

<style>
.student-item {
    padding: 4px;
    cursor: pointer;
}
.student-item:hover {
    background-color: #eee;
}
</style>

</body>
</html>
