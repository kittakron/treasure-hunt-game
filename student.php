<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ทีมนักเรียน | เกมล่าขุมสมบัติ</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <!-- ================= JOIN VIEW ================= -->
  <div id="joinView" class="center-screen">
    <div class="glass card fade-in" style="max-width: 420px; width: 100%;">
      <div style="font-size: 50px;">🧑‍🤝‍🧑</div>
      <h2 class="gold-text">เข้าร่วมทีมผจญภัย</h2>
      <input id="roomCodeInput" placeholder="รหัสห้อง (Room Code)" style="text-transform:uppercase;">
      <input id="teamNameInput" placeholder="ชื่อทีมของคุณ">
      <input id="playerNameInput" placeholder="ชื่อของคุณ (ไม่บังคับ)" onkeydown="if(event.key==='Enter')doJoin()">
      <button class="btn btn-gold btn-block" onclick="doJoin()">🚀 เข้าร่วมเกม</button>
      <p id="joinError" style="color:#ff8a80;"></p>
      <a href="index.php" style="color:var(--gold); font-size:14px;">&larr; กลับหน้าแรก</a>
    </div>
  </div>

  <!-- ================= GAME VIEW ================= -->
  <div id="gameView" class="app-wrap" style="display:none;">

    <div class="glass card fade-in" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
      <div>
        <h3 style="margin:0;" id="teamNameLabel">ทีมของฉัน</h3>
        <span class="badge" id="scoreLabel">0 คะแนน</span>
      </div>
      <div id="lettersInventory"></div>
    </div>

    <!-- LOBBY -->
    <div id="phase_LOBBY" class="glass card center-screen fade-in" style="min-height:40vh;">
      <div style="font-size:50px;">⏳</div>
      <h3>รอครูเริ่มเกม...</h3>
    </div>

    <!-- HOW TO PLAY -->
    <div id="phase_HOWTOPLAY" class="glass card fade-in" style="display:none; min-height:40vh;">
      <h3 class="gold-text">📋 วิธีเล่น</h3>
      <ol id="howToPlayList" style="font-size:18px; line-height:1.8;"></ol>
    </div>

    <!-- STORY -->
    <div id="phase_STORY" class="glass card fade-in" style="display:none; min-height:40vh;">
      <h3 class="gold-text">📖 เนื้อเรื่อง</h3>
      <div id="storyContent" style="font-size:18px; line-height:1.7;"></div>
    </div>

    <!-- MAP -->
    <div id="phase_MAP" class="glass card fade-in" style="display:none;">
      <h3 class="gold-text">🗺️ แผนที่การผจญภัย</h3>
      <p>รอครูเปิดคำถามของด่านถัดไป...</p>
      <div id="currentLocCard" class="glass zoom-in" style="padding:20px; text-align:center;"></div>
    </div>

    <!-- QUESTION + BUZZER -->
    <div id="phase_QUESTION" class="glass card fade-in" style="display:none;">
      <h3 class="gold-text">❓ คำถาม</h3>
      <p id="questionText" style="font-size:20px;"></p>
      <div id="choicesBox" class="grid grid-2"></div>
      <div class="center-screen" style="min-height:0; padding:24px 0;">
        <button id="buzzerBtn" class="buzzer-btn" onclick="pressBuzz()" disabled>
          🔴<br>รอเปิดสัญญาณ
        </button>
        <p id="buzzerStatusText" style="margin-top:14px; opacity:.8;"></p>
      </div>
    </div>

    <!-- ANSWER RESULT -->
    <div id="phase_ANSWER_RESULT" class="glass card center-screen fade-in" style="display:none; min-height:30vh;">
      <div id="resultIcon" style="font-size:60px;">📢</div>
      <h2 id="resultText">รอครูประกาศผล...</h2>
    </div>

    <!-- LEADERBOARD -->
    <div id="phase_LEADERBOARD" class="glass card fade-in" style="display:none;">
      <h3 class="gold-text">📊 กระดานผู้นำ</h3>
      <table><tbody id="lbBody"></tbody></table>
    </div>

    <!-- PUZZLE -->
    <div id="phase_PUZZLE" class="glass card fade-in" style="display:none;">
      <h3 class="gold-text">🧩 ห้องสมบัติ</h3>
      <p>นำตัวอักษรที่เก็บได้มาเรียงเป็นคำตอบ!</p>
      <p style="opacity:.7;">คำใบ้: <span id="puzzleHint"></span></p>
      <input id="puzzleAnswerInput" placeholder="พิมพ์คำตอบที่นี่...">
      <button class="btn btn-gold btn-block" onclick="submitPuzzle()">🔓 ไขปริศนา</button>
      <p id="puzzleResult" style="margin-top:10px;"></p>
    </div>

    <!-- WINNER -->
    <div id="phase_WINNER" class="glass card center-screen fade-in" style="display:none; min-height:40vh;">
      <div style="font-size:70px;" class="pop-in">🏆</div>
      <h1 class="gold-text">THE WINNER!</h1>
      <div id="winnerTeamName" style="font-size:24px;"></div>
    </div>

  </div>

  <script src="assets/app.js"></script>
  <script>
    let teamId = null;
    let lastPhase = null;
    let winSoundPlayed = false;

    async function doJoin() {
      const roomCode = document.getElementById('roomCodeInput').value;
      const teamName = document.getElementById('teamNameInput').value;
      const playerName = document.getElementById('playerNameInput').value;
      const deviceId = getDeviceId();

      const res = await apiCall('joinRoom', { roomCode, teamName, playerName, deviceId });
      if (res.success) {
        teamId = res.data.teamId;
        localStorage.setItem('thg_teamId', teamId);
        document.getElementById('teamNameLabel').textContent = '🚩 ' + res.data.teamName;
        showGame();
      } else {
        document.getElementById('joinError').textContent = res.message;
      }
    }

    function showGame() {
      document.getElementById('joinView').style.display = 'none';
      document.getElementById('gameView').style.display = 'block';
      applyTheme();
      poll();
      setInterval(poll, 700);
    }

    async function pressBuzz() {
      playBuzzSound();
      const res = await apiCall('studentPressBuzzer', { teamId });
      document.getElementById('buzzerStatusText').textContent = res.success ? '✅ คุณกดสำเร็จ! รอครูตัดสิน' : ('❌ ' + res.message);
    }

    async function submitPuzzle() {
      const answer = document.getElementById('puzzleAnswerInput').value;
      const res = await apiCall('submitPuzzleAnswer', { teamId, answer });
      const el = document.getElementById('puzzleResult');
      if (res.data && res.data.correct) {
        el.textContent = '🎉 ถูกต้อง! ทีมของคุณคือผู้ชนะ!';
        el.style.color = 'var(--emerald)';
      } else {
        el.textContent = '❌ ยังไม่ถูกต้อง ลองอีกครั้ง';
        el.style.color = '#ff8a80';
      }
    }

    function showPhase(phase) {
      ['LOBBY','HOWTOPLAY','STORY','MAP','QUESTION','ANSWER_RESULT','LEADERBOARD','PUZZLE','WINNER'].forEach(p => {
        document.getElementById('phase_' + p).style.display = (p === phase) ? 'block' : 'none';
      });
    }

    async function poll() {
      if (!teamId) return;
      const res = await apiCall('getStudentState', { teamId });
      if (!res.success) { document.getElementById('joinError').textContent = res.message; return; }
      const d = res.data;
      let phase = d.status.Phase;
      if (phase === 'BUZZER_OPEN') phase = 'QUESTION';
      const known = ['LOBBY','HOWTOPLAY','STORY','MAP','QUESTION','ANSWER_RESULT','LEADERBOARD','PUZZLE','WINNER'];
      if (known.indexOf(phase) === -1) phase = 'LOBBY';

      showPhase(phase);

      document.getElementById('scoreLabel').textContent = (d.team.score || 0) + ' คะแนน';
      document.getElementById('lettersInventory').innerHTML = d.letters.map(l => `<span class="letter-chip">${l}</span>`).join('');

      if (phase === 'HOWTOPLAY' && d.howToPlay) {
        document.getElementById('howToPlayList').innerHTML = d.howToPlay.map(s => `<li>${s.text}</li>`).join('');
      }

      if (phase === 'STORY' && d.story) {
        document.getElementById('storyContent').innerHTML = d.story.map(s => `<p><b>${s.title}</b><br>${s.content}</p>`).join('');
      }

      if (phase === 'MAP' && d.currentLocation) {
        document.getElementById('currentLocCard').innerHTML =
          `<h2>${d.currentLocation.name}</h2><p>${d.currentLocation.description || ''}</p>`;
      }

      if (phase === 'QUESTION' && d.currentQuestion) {
        document.getElementById('questionText').textContent = d.currentQuestion.question;
        const box = document.getElementById('choicesBox');
        box.innerHTML = '';
        ['a','b','c','d'].forEach(k => {
          const val = d.currentQuestion[k];
          if (val) {
            const div = document.createElement('div');
            div.className = 'glass';
            div.style.padding = '10px';
            div.textContent = k.toUpperCase() + '. ' + val;
            box.appendChild(div);
          }
        });

        const btn = document.getElementById('buzzerBtn');
        const buzzOpen = String(d.status.BuzzerOpen).toUpperCase() === 'TRUE';
        if (d.isMyBuzzLocked) {
          btn.disabled = true;
          btn.innerHTML = '🔒<br>คุณกดแล้ว';
        } else if (buzzOpen) {
          btn.disabled = false;
          btn.innerHTML = '🔴<br>กดเพื่อขอตอบ!';
          btn.classList.add('glow');
        } else {
          btn.disabled = true;
          btn.classList.remove('glow');
          btn.innerHTML = '🔴<br>รอเปิดสัญญาณ';
        }
      }

      if (phase === 'PUZZLE') {
        document.getElementById('puzzleHint').textContent = d.puzzle ? d.puzzle.hint : '';
      }

      if (phase === 'LEADERBOARD' && d.leaderboard) {
        document.getElementById('lbBody').innerHTML = d.leaderboard.map(t =>
          `<tr><td>${t.rank}</td><td>${t.teamName}</td><td>${t.score}</td></tr>`).join('');
      }

      if (phase === 'WINNER') {
        if (d.leaderboard && d.leaderboard[0]) {
          document.getElementById('winnerTeamName').textContent = '🎊 ' + d.leaderboard[0].teamName + ' 🎊';
        }
        if (!winSoundPlayed) {
          winSoundPlayed = true;
          playWinSound();
          launchConfetti(120);
        }
      }

      lastPhase = phase;
    }

    // พยายาม resume session อัตโนมัติถ้าเคยเข้าร่วมแล้ว
    const savedTeamId = localStorage.getItem('thg_teamId');
    if (savedTeamId) {
      teamId = savedTeamId;
      showGame();
    }
  </script>
</body>
</html>
