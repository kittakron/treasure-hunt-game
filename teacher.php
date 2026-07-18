<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ครูผู้ควบคุมเกม | เกมล่าขุมสมบัติ</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <!-- ================= LOGIN VIEW ================= -->
  <div id="loginView" class="center-screen">
    <div class="glass card fade-in" style="max-width: 400px; width: 100%;">
      <h2 class="gold-text">🧑‍🏫 เข้าสู่ระบบครู</h2>
      <input id="pwInput" type="password" placeholder="รหัสผ่านครู" onkeydown="if(event.key==='Enter')doLogin()">
      <button class="btn btn-gold btn-block" onclick="doLogin()">เข้าสู่ระบบ</button>
      <p id="loginError" style="color:#ff8a80; margin-top:10px;"></p>
      <a href="index.php" style="color:var(--gold); font-size:14px;">&larr; กลับหน้าแรก</a>
    </div>
  </div>

  <!-- ================= DASHBOARD VIEW ================= -->
  <div id="dashboardView" class="app-wrap" style="display:none;">
    <div class="glass card fade-in" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <div>
        <h2 class="gold-text" style="margin:0;">🏆 <span id="gameNameLabel">แดชบอร์ดครู</span></h2>
        <span class="badge" id="phaseBadge">-</span>
        <span class="badge" id="roomCodeBadge">-</span>
      </div>
      <div>
        <a href="projector.php" target="_blank" style="text-decoration:none;">
          <button class="btn btn-outline">🖥️ เปิดหน้าโปรเจกเตอร์</button>
        </a>
        <button class="btn btn-danger" onclick="doResetGame()">🔄 รีเซ็ตเกมใหม่</button>
        <button class="btn btn-outline" onclick="doLogout()">🚪 ออกจากระบบ</button>
      </div>
    </div>

    <div class="grid grid-2">
      <!-- ---------- GAME FLOW CONTROL ---------- -->
      <div class="glass card fade-in">
        <h3 class="gold-text">🎮 ควบคุมลำดับเกม</h3>
        <div class="grid grid-2">
          <button class="btn btn-gold" onclick="callAction('actionStartGame')">▶️ เริ่มเกม</button>
          <button class="btn btn-outline" onclick="callAction('actionShowHowToPlay')">📋 วิธีเล่น</button>
          <button class="btn btn-outline" onclick="callAction('actionShowStory')">📖 เนื้อเรื่อง</button>
          <button class="btn btn-outline" onclick="callAction('actionShowMap')">🗺️ แสดงแผนที่</button>
          <button class="btn btn-outline" onclick="callAction('actionPreviousLocation')">⬅️ ด่านก่อนหน้า</button>
          <button class="btn btn-outline" onclick="callAction('actionNextLocation')">➡️ ด่านถัดไป</button>
          <button class="btn btn-emerald" onclick="callAction('actionOpenQuestion')">❓ เปิดคำถาม</button>
          <button class="btn btn-outline" onclick="callAction('actionShowLeaderboard')">📊 กระดานคะแนน</button>
          <button class="btn btn-brown" onclick="callAction('actionOpenPuzzle')">🧩 เปิดห้องสมบัติ</button>
          <button class="btn btn-danger" onclick="callAction('actionEndGame')">⏹️ จบเกม</button>
        </div>
      </div>

      <!-- ---------- BUZZER PANEL ---------- -->
      <div class="glass card fade-in">
        <h3 class="gold-text">🔴 ควบคุมบัซเซอร์</h3>
        <div class="grid grid-2" style="margin-bottom: 14px;">
          <button class="btn btn-danger" onclick="callAction('actionOpenBuzzer')">🔓 เปิดบัซเซอร์</button>
          <button class="btn btn-outline" onclick="callAction('actionCloseBuzzer')">🔒 ปิดบัซเซอร์</button>
          <button class="btn btn-outline" onclick="callAction('actionResetBuzzer')">🔄 รีเซ็ตบัซเซอร์</button>
          <button class="btn btn-outline" onclick="callAction('actionAllowNextTeam')">⏭️ ทีมถัดไป</button>
        </div>
        <table>
          <thead><tr><th>#</th><th>ทีม</th><th>เวลา (ms)</th><th>Latency</th><th>ผล</th></tr></thead>
          <tbody id="buzzerLogBody"><tr><td colspan="5" style="opacity:.6;">ยังไม่มีทีมกด</td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- ---------- CURRENT QUESTION / ANSWER JUDGE ---------- -->
    <div class="glass card fade-in" id="questionPanel" style="display:none;">
      <h3 class="gold-text">📍 ด่านปัจจุบัน: <span id="currentLocationName">-</span></h3>
      <p id="currentQuestionText" style="font-size:18px;"></p>
      <div id="currentChoices" class="grid grid-2"></div>
      <p style="opacity:.7;">เฉลย: <b id="answerReveal" style="color:var(--gold);">••••</b> &nbsp;|&nbsp; คำอธิบาย: <span id="explanationReveal">-</span></p>
      <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <span>ทีมที่กดก่อน: <b id="lockedTeamName" class="gold-text">-</b></span>
        <button class="btn btn-emerald" onclick="judgeAnswer(true)">✅ ตอบถูก</button>
        <button class="btn btn-danger" onclick="judgeAnswer(false)">❌ ตอบผิด</button>
      </div>
    </div>

    <!-- ---------- MAP OVERVIEW ---------- -->
    <div class="glass card fade-in">
      <h3 class="gold-text">🗺️ ภาพรวมด่านทั้งหมด</h3>
      <div class="grid grid-3" id="locationsGrid"></div>
    </div>

    <!-- ---------- LEADERBOARD ---------- -->
    <div class="glass card fade-in">
      <h3 class="gold-text">📊 กระดานผู้นำ</h3>
      <table>
        <thead><tr><th>อันดับ</th><th>ทีม</th><th>คะแนน</th><th>ข้อถูก</th><th>ตัวอักษร</th></tr></thead>
        <tbody id="leaderboardBody"></tbody>
      </table>
    </div>
  </div>

  <script src="assets/app.js"></script>
  <script>
    let currentTeamId = null;
    let pollTimer = null;

    async function doLogin() {
      const password = document.getElementById('pwInput').value;
      const res = await apiCall('teacherLogin', { password });
      if (res.success) {
        showDashboard();
      } else {
        document.getElementById('loginError').textContent = res.message;
      }
    }

    async function doLogout() {
      await apiCall('teacherLogout');
      clearInterval(pollTimer);
      document.getElementById('dashboardView').style.display = 'none';
      document.getElementById('loginView').style.display = 'flex';
    }

    function showDashboard() {
      document.getElementById('loginView').style.display = 'none';
      document.getElementById('dashboardView').style.display = 'block';
      applyTheme();
      refreshDashboard();
      pollTimer = setInterval(refreshDashboard, 1500);
    }

    async function callAction(fnName, extraParams) {
      const res = await apiCall(fnName, extraParams || {});
      if (!res.success) alert(res.message);
      refreshDashboard();
    }

    async function judgeAnswer(correct) {
      if (!currentTeamId) { alert('ยังไม่มีทีมกดบัซเซอร์'); return; }
      const fn = correct ? 'actionMarkCorrect' : 'actionMarkWrong';
      await apiCall(fn, { teamId: currentTeamId });
      refreshDashboard();
    }

    async function refreshDashboard() {
      const res = await apiCall('getTeacherDashboard');
      if (!res.success) {
        if (res.message && res.message.includes('เข้าสู่ระบบ')) {
          clearInterval(pollTimer);
          document.getElementById('dashboardView').style.display = 'none';
          document.getElementById('loginView').style.display = 'flex';
        }
        return;
      }
      const d = res.data;
      document.getElementById('gameNameLabel').textContent = d.settings.GameName || 'แดชบอร์ดครู';
      document.getElementById('phaseBadge').textContent = 'สถานะ: ' + d.status.Phase;
      document.getElementById('roomCodeBadge').textContent = 'รหัสห้อง: ' + d.settings.RoomCode;

      // Question panel
      const qPanel = document.getElementById('questionPanel');
      if (d.currentQuestion) {
        qPanel.style.display = 'block';
        document.getElementById('currentLocationName').textContent = d.currentLocation ? d.currentLocation.name : '-';
        document.getElementById('currentQuestionText').textContent = d.currentQuestion.question;
        document.getElementById('answerReveal').textContent = d.currentQuestion.correct || '-';
        document.getElementById('explanationReveal').textContent = d.currentQuestion.explanation || '-';
        const choicesDiv = document.getElementById('currentChoices');
        choicesDiv.innerHTML = '';
        ['a','b','c','d'].forEach(k => {
          const val = d.currentQuestion[k];
          if (val) {
            const div = document.createElement('div');
            div.className = 'glass';
            div.style.padding = '10px';
            div.textContent = k.toUpperCase() + '. ' + val;
            choicesDiv.appendChild(div);
          }
        });
        currentTeamId = d.status.BuzzerLockedTeamID || null;
        const lockedTeam = d.teams.find(t => t.team_id === currentTeamId);
        document.getElementById('lockedTeamName').textContent = lockedTeam ? lockedTeam.team_name : '(ยังไม่มีทีมกด)';
      } else {
        qPanel.style.display = 'none';
      }

      // Buzzer log
      const buzzBody = document.getElementById('buzzerLogBody');
      if (d.buzzerLog.length) {
        buzzBody.innerHTML = d.buzzerLog.map(b =>
          `<tr><td>${b.rank}</td><td>${b.teamName}</td><td>${b.timestamp}</td><td>${b.latencyMs} ms</td><td>${b.status}</td></tr>`
        ).join('');
      } else {
        buzzBody.innerHTML = '<tr><td colspan="5" style="opacity:.6;">ยังไม่มีทีมกด</td></tr>';
      }

      // Locations grid
      const locGrid = document.getElementById('locationsGrid');
      locGrid.innerHTML = d.locations.map((loc, i) => {
        const cls = loc.unlock_status === 'complete' ? 'complete' : (loc.unlock_status === 'unlocked' ? 'unlocked' : 'locked');
        return `<div class="glass map-node ${cls}">
          <div style="font-size:28px;">${loc.unlock_status === 'complete' ? '✅' : (loc.unlock_status === 'unlocked' ? '🔓' : '🔒')}</div>
          <b>${loc.name}</b><br><span class="badge">${loc.letter || ''}</span>
          <div style="margin-top:8px;"><button class="btn btn-outline" style="padding:6px 12px;font-size:13px;" onclick="callAction('actionOpenLocation', {index: ${i}})">เปิดด่านนี้</button></div>
        </div>`;
      }).join('');

      // Leaderboard
      document.getElementById('leaderboardBody').innerHTML = d.leaderboard.map(t =>
        `<tr><td>${t.rank}</td><td>${t.teamName}</td><td>${t.score}</td><td>${t.correctCount}</td><td>${t.letters.join(' ')}</td></tr>`
      ).join('');
    }

    async function doResetGame() {
      if (!confirm('ยืนยันรีเซ็ตเกม? ทีม/คะแนน/บัซเซอร์/ตัวอักษรทั้งหมดจะถูกล้าง (คำถาม/ด่าน/เนื้อเรื่องจะไม่ถูกลบ)')) return;
      const res = await apiCall('actionResetGame');
      if (!res.success) alert(res.message);
      refreshDashboard();
    }

    async function checkSessionAndInit() {
      const res = await apiCall('getTeacherDashboard');
      if (res.success) {
        showDashboard();
      }
      // ถ้ายังไม่ได้ล็อกอิน จะค้างอยู่ที่หน้า loginView (ค่าเริ่มต้นของ HTML) โดยอัตโนมัติ
    }
    checkSessionAndInit();
  </script>
</body>
</html>
