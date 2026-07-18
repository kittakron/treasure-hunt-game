<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>โปรเจกเตอร์ | เกมล่าขุมสมบัติ</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body { overflow: hidden; }
    .proj-wrap { padding: 40px; min-height: 100vh; }
    .proj-title { font-size: 42px; text-align:center; }
    .proj-sub { font-size: 24px; text-align:center; opacity:.85; }
    .story-text { font-size: 28px; line-height:1.8; text-align:center; max-width:900px; margin:0 auto; }
    .loc-node-big { font-size: 20px; padding: 24px; text-align:center; }
    .big-state { font-size: 60px; text-align:center; font-weight:700; }
  </style>
</head>
<body>
  <div class="proj-wrap">

    <div id="p_LOBBY" class="center-screen" style="min-height:90vh;">
      <div style="font-size:80px;">🗺️💰</div>
      <h1 class="proj-title gold-text" id="p_gameName">เกมล่าขุมสมบัติ</h1>
      <p class="proj-sub">รหัสห้อง: <b class="gold-text" id="p_roomCode">-</b> — เข้าร่วมได้ที่หน้าเว็บ</p>
      <div id="p_teamsJoined" style="margin-top:20px; font-size:20px;"></div>
    </div>

    <div id="p_HOWTOPLAY" style="display:none;">
      <h1 class="proj-title gold-text">📋 วิธีเล่น</h1>
      <ol id="p_howToPlayList" class="story-text" style="text-align:left; max-width:800px; margin:30px auto;"></ol>
    </div>

    <div id="p_STORY" class="center-screen" style="min-height:90vh; display:none;">
      <h2 class="gold-text">📖 เนื้อเรื่อง</h2>
      <div class="story-text" id="p_storyText"></div>
    </div>

    <div id="p_MAP" style="display:none;">
      <h1 class="proj-title gold-text">🗺️ แผนที่การผจญภัย</h1>
      <div class="grid grid-3" id="p_mapGrid" style="margin-top:30px;"></div>
    </div>

    <div id="p_QUESTION" style="display:none;">
      <h2 class="proj-sub gold-text" id="p_locName">ด่าน</h2>
      <h1 class="proj-title" id="p_questionText" style="margin:20px 0;"></h1>
      <div class="grid grid-2" id="p_choices" style="max-width:800px; margin:0 auto;"></div>
      <div class="big-state gold-text" id="p_buzzerState" style="margin-top:30px;">🔒 รอเปิดสัญญาณ</div>
    </div>

    <div id="p_ANSWER_RESULT" class="center-screen" style="min-height:90vh; display:none;">
      <div style="font-size:100px;">📢</div>
      <h1 class="proj-title">ครูกำลังตัดสินคำตอบ...</h1>
    </div>

    <div id="p_LEADERBOARD" style="display:none;">
      <h1 class="proj-title gold-text">📊 กระดานผู้นำ</h1>
      <table style="max-width:700px; margin:30px auto; font-size:22px;"><tbody id="p_lbBody"></tbody></table>
    </div>

    <div id="p_PUZZLE" class="center-screen" style="min-height:90vh; display:none;">
      <h1 class="proj-title gold-text">🧩 ห้องสมบัติ</h1>
      <p class="proj-sub" id="p_puzzleHint"></p>
    </div>

    <div id="p_WINNER" class="center-screen" style="min-height:90vh; display:none;">
      <div style="font-size:120px;" class="pop-in">🏆</div>
      <h1 class="proj-title gold-text">THE WINNER!</h1>
      <div style="font-size:40px;" id="p_winnerName"></div>
    </div>

  </div>

  <script src="assets/app.js"></script>
  <script>
    let lastPhase = null;
    let winSoundPlayed = false;

    function showPhase(phase) {
      ['LOBBY','HOWTOPLAY','STORY','MAP','QUESTION','ANSWER_RESULT','LEADERBOARD','PUZZLE','WINNER'].forEach(p => {
        document.getElementById('p_' + p).style.display = (p === phase) ? 'block' : 'none';
      });
    }

    async function poll() {
      const res = await apiGet('getProjectorState');
      if (!res.success) return;
      const d = res.data;
      let phase = d.status.Phase;
      if (phase === 'BUZZER_OPEN') phase = 'QUESTION';
      const known = ['LOBBY','HOWTOPLAY','STORY','MAP','QUESTION','ANSWER_RESULT','LEADERBOARD','PUZZLE','WINNER'];
      if (known.indexOf(phase) === -1) phase = 'LOBBY';
      showPhase(phase);

      document.getElementById('p_gameName').textContent = d.settings.GameName;
      document.getElementById('p_roomCode').textContent = d.settings.RoomCode;
      document.getElementById('p_teamsJoined').textContent = d.teams.length ? ('ทีมที่เข้าร่วม: ' + d.teams.map(t => t.team_name).join(', ')) : 'รอทีมเข้าร่วม...';

      if (phase === 'HOWTOPLAY') {
        document.getElementById('p_howToPlayList').innerHTML = d.howToPlay.map(s => `<li>${s.text}</li>`).join('');
      }

      if (phase === 'STORY') {
        document.getElementById('p_storyText').innerHTML = d.story.map(s => `<p><b>${s.title}</b><br>${s.content}</p>`).join('');
      }

      if (phase === 'MAP') {
        document.getElementById('p_mapGrid').innerHTML = d.locations.map(loc => {
          const cls = loc.unlock_status === 'complete' ? 'complete' : (loc.unlock_status === 'unlocked' ? 'unlocked' : 'locked');
          const icon = loc.unlock_status === 'complete' ? '✅' : (loc.unlock_status === 'unlocked' ? '🔓' : '🔒');
          return `<div class="glass map-node loc-node-big ${cls}"><div style="font-size:40px;">${icon}</div><b>${loc.name}</b></div>`;
        }).join('');
      }

      if (phase === 'QUESTION' && d.currentQuestion) {
        document.getElementById('p_locName').textContent = 'ด่าน: ' + (d.currentLocation ? d.currentLocation.name : '');
        document.getElementById('p_questionText').textContent = d.currentQuestion.question;
        const box = document.getElementById('p_choices');
        box.innerHTML = '';
        ['a','b','c','d'].forEach(k => {
          const val = d.currentQuestion[k];
          if (val) {
            const div = document.createElement('div');
            div.className = 'glass';
            div.style.padding = '16px';
            div.style.fontSize = '20px';
            div.textContent = k.toUpperCase() + '. ' + val;
            box.appendChild(div);
          }
        });
        const buzzOpen = String(d.status.BuzzerOpen).toUpperCase() === 'TRUE';
        const stateEl = document.getElementById('p_buzzerState');
        if (d.lockedTeamName) {
          stateEl.textContent = '🙋 ' + d.lockedTeamName + ' กดก่อน!';
          stateEl.classList.add('glow');
        } else if (buzzOpen) {
          stateEl.textContent = '🔴 เปิดสัญญาณแล้ว!';
          stateEl.classList.add('glow');
        } else {
          stateEl.textContent = '🔒 รอเปิดสัญญาณ';
          stateEl.classList.remove('glow');
        }
      }

      if (phase === 'LEADERBOARD') {
        document.getElementById('p_lbBody').innerHTML = d.leaderboard.map(t =>
          `<tr><td>#${t.rank}</td><td>${t.teamName}</td><td>${t.score} คะแนน</td></tr>`).join('');
      }

      if (phase === 'PUZZLE') {
        document.getElementById('p_puzzleHint').textContent = d.puzzleHint || '';
      }

      if (phase === 'WINNER') {
        if (d.leaderboard && d.leaderboard[0]) {
          document.getElementById('p_winnerName').textContent = '🎊 ' + d.leaderboard[0].teamName + ' 🎊';
        }
        if (!winSoundPlayed) {
          winSoundPlayed = true;
          playWinSound();
          launchConfetti(150);
        }
      }

      lastPhase = phase;
    }

    applyTheme();
    poll();
    setInterval(poll, 1000);
  </script>
</body>
</html>
