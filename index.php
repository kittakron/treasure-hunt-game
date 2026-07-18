<?php require_once __DIR__ . '/includes/game_logic.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เกมล่าขุมสมบัติ</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="center-screen">
    <div class="glass card fade-in" style="max-width: 480px; width: 100%;">
      <div style="font-size: 60px;">🗺️💰</div>
      <h1 class="gold-text">เกมล่าขุมสมบัติ</h1>
      <p style="opacity:.8; margin-bottom: 24px;">Treasure Hunt Classroom Game Engine (PHP + MySQL)</p>

      <a href="student.php" style="text-decoration:none;">
        <button class="btn btn-gold btn-block btn-lg" style="margin-bottom: 14px;">🧑‍🤝‍🧑 เข้าร่วมเป็นทีมนักเรียน</button>
      </a>
      <a href="teacher.php" style="text-decoration:none;">
        <button class="btn btn-emerald btn-block" style="margin-bottom: 14px;">🧑‍🏫 เข้าสู่ระบบครูผู้ควบคุมเกม</button>
      </a>
      <a href="projector.php" style="text-decoration:none;">
        <button class="btn btn-outline btn-block">🖥️ เปิดหน้าจอโปรเจกเตอร์ (สำหรับฉายหน้าห้อง)</button>
      </a>
    </div>
  </div>
  <script src="assets/app.js"></script>
  <script>applyTheme();</script>
</body>
</html>
