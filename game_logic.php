<?php
/**
 * includes/game_logic.php
 * ตรรกะหลักของเกมทั้งหมด: แผนที่, คำถาม, บัซเซอร์, คะแนน, ปริศนา, ทีม/ผู้เล่น
 */
require_once __DIR__ . '/helpers.php';

/* ==================== MAP / LOCATIONS ==================== */

function getLocations() {
  $stmt = db()->query('SELECT * FROM locations ORDER BY location_order ASC');
  return $stmt->fetchAll();
}

function getCurrentLocation() {
  $status = getGameStatus();
  $locations = getLocations();
  $index = (int) ($status['CurrentLocationIndex'] ?? 0);
  return $locations[$index] ?? null;
}

function unlockLocationByIndex($index) {
  $locations = getLocations();
  if ($index < 0 || $index >= count($locations)) return fail('ไม่พบด่านนี้');
  $loc = $locations[$index];
  $stmt = db()->prepare('UPDATE locations SET unlock_status = "unlocked" WHERE id = :id');
  $stmt->execute(['id' => $loc['id']]);
  setGameStatus(['CurrentLocationIndex' => (string) $index, 'Phase' => GAME_PHASE_MAP]);
  writeLog('MAP', 'เปิดด่าน: ' . $loc['name'], 'Teacher');
  return ok($loc);
}

function markCurrentLocationComplete() {
  $loc = getCurrentLocation();
  if ($loc) {
    $stmt = db()->prepare('UPDATE locations SET unlock_status = "complete" WHERE id = :id');
    $stmt->execute(['id' => $loc['id']]);
  }
}

function isAllLocationsComplete() {
  $locations = getLocations();
  foreach ($locations as $loc) {
    if ($loc['unlock_status'] !== 'complete') return false;
  }
  return count($locations) > 0;
}

/* ==================== QUESTIONS ==================== */

function getQuestionByLocation($locationId) {
  $stmt = db()->prepare('SELECT * FROM questions WHERE location_id = :lid LIMIT 1');
  $stmt->execute(['lid' => $locationId]);
  return $stmt->fetch() ?: null;
}

function getQuestionById($questionId) {
  $stmt = db()->prepare('SELECT * FROM questions WHERE question_id = :qid');
  $stmt->execute(['qid' => $questionId]);
  return $stmt->fetch() ?: null;
}

function sanitizeQuestionForClient($question) {
  if (!$question) return null;
  $safe = $question;
  unset($safe['correct'], $safe['explanation']);
  return $safe;
}

function openCurrentQuestion() {
  $loc = getCurrentLocation();
  if (!$loc) return fail('ยังไม่ได้เปิดด่านใดๆ');
  $question = getQuestionByLocation($loc['id']);
  if (!$question) return fail('ไม่พบคำถามสำหรับด่านนี้');

  setGameStatus([
    'Phase' => GAME_PHASE_QUESTION,
    'CurrentQuestionID' => $question['question_id'],
    'BuzzerOpen' => 'FALSE',
    'BuzzerLockedTeamID' => '',
    'QuestionStartTime' => (string) nowMillis(),
  ]);
  writeLog('QUESTION', 'เปิดคำถาม: ' . $question['question_id'], 'Teacher');
  return ok(sanitizeQuestionForClient($question));
}

/* ==================== BUZZER ==================== */

function openBuzzer() {
  setGameStatus(['Phase' => GAME_PHASE_BUZZER_OPEN, 'BuzzerOpen' => 'TRUE', 'BuzzerLockedTeamID' => '']);
  writeLog('BUZZER', 'เปิดบัซเซอร์', 'Teacher');
  return ok(true);
}

function closeBuzzer() {
  setGameStatus(['BuzzerOpen' => 'FALSE']);
  writeLog('BUZZER', 'ปิดบัซเซอร์', 'Teacher');
  return ok(true);
}

function resetBuzzer() {
  setGameStatus(['BuzzerLockedTeamID' => '', 'BuzzerOpen' => 'FALSE']);
  writeLog('BUZZER', 'รีเซ็ตบัซเซอร์', 'Teacher');
  return ok(true);
}

function allowNextTeam() {
  setGameStatus(['BuzzerLockedTeamID' => '', 'BuzzerOpen' => 'TRUE']);
  writeLog('BUZZER', 'เปิดสิทธิ์ให้ทีมถัดไป', 'Teacher');
  return ok(true);
}

function pressBuzzer($teamId) {
  $status = getGameStatus();
  if (!toBool($status['BuzzerOpen'] ?? 'FALSE')) return fail('บัซเซอร์ยังไม่เปิด');

  $lockedTeam = $status['BuzzerLockedTeamID'] ?? '';
  if ($lockedTeam && $lockedTeam !== $teamId) return fail('มีทีมอื่นกดก่อนแล้ว');
  if ($lockedTeam === $teamId) return fail('คุณกดไปแล้ว รอผลการตัดสิน');

  $timestamp = nowMillis();
  $loc = getCurrentLocation();
  $questionId = $status['CurrentQuestionID'] ?? '';

  $countStmt = db()->prepare('SELECT COUNT(*) AS c FROM buzzers WHERE question = :q');
  $countStmt->execute(['q' => $questionId]);
  $rank = (int) $countStmt->fetch()['c'] + 1;

  $stmt = db()->prepare('INSERT INTO buzzers (timestamp_ms, team_id, question, rank_num, status) VALUES (:t, :tid, :q, :r, "pending")');
  $stmt->execute(['t' => $timestamp, 'tid' => $teamId, 'q' => $questionId, 'r' => $rank]);

  setGameStatus(['BuzzerLockedTeamID' => $teamId, 'BuzzerOpen' => 'FALSE']);
  writeLog('BUZZER', "ทีม $teamId กดบัซเซอร์สำเร็จ (อันดับ $rank)", 'Student');
  return ok(['locked' => true, 'timestamp' => $timestamp, 'rank' => $rank]);
}

function getBuzzerLog($questionId) {
  $stmt = db()->prepare('
    SELECT b.*, t.team_name FROM buzzers b
    LEFT JOIN teams t ON t.team_id = b.team_id
    WHERE b.question = :q
    ORDER BY b.timestamp_ms ASC
  ');
  $stmt->execute(['q' => $questionId]);
  $rows = $stmt->fetchAll();
  if (!$rows) return [];
  $first = $rows[0]['timestamp_ms'];
  $out = [];
  foreach ($rows as $i => $r) {
    $out[] = [
      'rank' => $i + 1,
      'teamId' => $r['team_id'],
      'teamName' => $r['team_name'] ?: $r['team_id'],
      'timestamp' => $r['timestamp_ms'],
      'latencyMs' => $r['timestamp_ms'] - $first,
      'status' => $r['status'],
    ];
  }
  return $out;
}

function updateLatestBuzzerStatus($teamId, $questionId, $newStatus) {
  $stmt = db()->prepare('
    UPDATE buzzers SET status = :s
    WHERE team_id = :tid AND question = :q
    ORDER BY id DESC LIMIT 1
  ');
  // MySQL ไม่รองรับ ORDER BY/LIMIT ใน UPDATE ตรงๆ ในบาง config จึงใช้ subquery แทน
  $stmt = db()->prepare('
    UPDATE buzzers SET status = :s
    WHERE id = (
      SELECT id FROM (
        SELECT id FROM buzzers WHERE team_id = :tid AND question = :q ORDER BY id DESC LIMIT 1
      ) AS sub
    )
  ');
  $stmt->execute(['s' => $newStatus, 'tid' => $teamId, 'q' => $questionId]);
}

/* ==================== SCORE / LEADERBOARD ==================== */

function awardCorrectAnswer($teamId) {
  $status = getGameStatus();
  $question = getQuestionById($status['CurrentQuestionID'] ?? '');
  $loc = getCurrentLocation();
  if (!$question) return fail('ไม่พบคำถามปัจจุบัน');

  $teamStmt = db()->prepare('SELECT * FROM teams WHERE team_id = :tid');
  $teamStmt->execute(['tid' => $teamId]);
  $team = $teamStmt->fetch();
  if (!$team) return fail('ไม่พบทีมนี้');

  $newScore = (int) $team['score'] + (int) $question['score'];
  $letter = $question['location_id'] ? ($loc['letter'] ?? '') : '';

  // เช็คว่าทีมนี้เคยได้ตัวอักษรของด่านนี้ไปแล้วหรือยัง (กันซ้ำ)
  $checkStmt = db()->prepare('SELECT COUNT(*) AS c FROM letters WHERE team_id = :tid AND location = :loc');
  $checkStmt->execute(['tid' => $teamId, 'loc' => $loc['id'] ?? '']);
  $alreadyHas = (int) $checkStmt->fetch()['c'] > 0;

  db()->prepare('UPDATE teams SET score = :s WHERE team_id = :tid')->execute(['s' => $newScore, 'tid' => $teamId]);

  db()->prepare('INSERT INTO scores (team_id, location, question, score, total) VALUES (:tid, :loc, :q, :sc, :tot)')
    ->execute(['tid' => $teamId, 'loc' => $loc['id'] ?? '', 'q' => $question['question_id'], 'sc' => $question['score'], 'tot' => $newScore]);

  if ($letter && !$alreadyHas) {
    db()->prepare('INSERT INTO letters (team_id, location, letter) VALUES (:tid, :loc, :l)')
      ->execute(['tid' => $teamId, 'loc' => $loc['id'], 'l' => $letter]);
    db()->prepare('UPDATE teams SET letter_count = letter_count + 1 WHERE team_id = :tid')->execute(['tid' => $teamId]);
  }

  updateLatestBuzzerStatus($teamId, $question['question_id'], 'correct');
  markCurrentLocationComplete();
  setGameStatus(['Phase' => GAME_PHASE_ANSWER_RESULT]);
  writeLog('SCORE', "ทีม $teamId ตอบถูก ได้ {$question['score']} คะแนน + ตัวอักษร \"$letter\"", 'Teacher');

  maybeGrantAchievement($teamId, 'นักไขปริศนาสายฟ้า', isFirstCorrectThisQuestion($question['question_id'], $teamId));

  return ok(['teamId' => $teamId, 'newScore' => $newScore, 'letter' => $letter]);
}

function isFirstCorrectThisQuestion($questionId, $teamId) {
  $stmt = db()->prepare('SELECT COUNT(*) AS c FROM scores WHERE question = :q');
  $stmt->execute(['q' => $questionId]);
  return (int) $stmt->fetch()['c'] === 1;
}

function recordWrongAnswer($teamId) {
  $status = getGameStatus();
  $loc = getCurrentLocation();
  $questionId = $status['CurrentQuestionID'] ?? '';

  $teamStmt = db()->prepare('SELECT score FROM teams WHERE team_id = :tid');
  $teamStmt->execute(['tid' => $teamId]);
  $team = $teamStmt->fetch();
  $currentScore = $team ? (int) $team['score'] : 0;

  db()->prepare('INSERT INTO scores (team_id, location, question, score, total) VALUES (:tid, :loc, :q, 0, :tot)')
    ->execute(['tid' => $teamId, 'loc' => $loc['id'] ?? '', 'q' => $questionId, 'tot' => $currentScore]);

  updateLatestBuzzerStatus($teamId, $questionId, 'wrong');
  setGameStatus(['Phase' => GAME_PHASE_ANSWER_RESULT]);
  writeLog('SCORE', "ทีม $teamId ตอบผิด", 'Teacher');
  return ok(true);
}

function getLeaderboard() {
  $teams = db()->query('SELECT * FROM teams ORDER BY score DESC')->fetchAll();
  $out = [];
  $rank = 1;
  foreach ($teams as $team) {
    $correctStmt = db()->prepare('SELECT COUNT(*) AS c FROM scores WHERE team_id = :tid AND score > 0');
    $correctStmt->execute(['tid' => $team['team_id']]);
    $lettersStmt = db()->prepare('SELECT letter FROM letters WHERE team_id = :tid');
    $lettersStmt->execute(['tid' => $team['team_id']]);
    $letters = array_column($lettersStmt->fetchAll(), 'letter');

    $out[] = [
      'rank' => $rank++,
      'teamId' => $team['team_id'],
      'teamName' => $team['team_name'],
      'color' => $team['color'],
      'score' => (int) $team['score'],
      'correctCount' => (int) $correctStmt->fetch()['c'],
      'letters' => $letters,
    ];
  }
  return $out;
}

/* ==================== PUZZLE ==================== */

function getPuzzleData() {
  $row = db()->query('SELECT * FROM puzzle WHERE id = 1')->fetch();
  return $row ?: ['puzzle' => '', 'answer' => '', 'bonus' => 0];
}

function openPuzzleRoom() {
  if (!isAllLocationsComplete()) return fail('ยังผ่านด่านไม่ครบ ไม่สามารถเปิดห้องสมบัติได้');
  setGameStatus(['Phase' => GAME_PHASE_PUZZLE]);
  writeLog('PUZZLE', 'เปิดห้องสมบัติ', 'Teacher');
  $puzzle = getPuzzleData();
  return ok(['hint' => $puzzle['puzzle']]);
}

function normalizeAnswer($str) {
  return preg_replace('/\s+/u', '', trim((string) $str));
}

function submitPuzzleAnswer($teamId, $answer) {
  $puzzle = getPuzzleData();
  $correct = normalizeAnswer($answer) === normalizeAnswer($puzzle['answer']);
  writeLog('PUZZLE', "ทีม $teamId ส่งคำตอบ \"$answer\" -> " . ($correct ? 'ถูกต้อง' : 'ไม่ถูกต้อง'), 'Student');

  if ($correct) {
    if ((int) $puzzle['bonus'] > 0) {
      db()->prepare('UPDATE teams SET score = score + :b WHERE team_id = :tid')->execute(['b' => $puzzle['bonus'], 'tid' => $teamId]);
    }
    setGameStatus(['Phase' => GAME_PHASE_WINNER, 'BonusTeamID' => $teamId]);
    grantAchievement($teamId, 'ผู้ไขปริศนาสำเร็จ');
    writeLog('WINNER', "ทีม $teamId ไขปริศนาสำเร็จ เป็นผู้ชนะ!", 'System');
  }
  return ok(['correct' => $correct]);
}

/* ==================== TEAMS / PLAYERS ==================== */

$TEAM_COLORS = ['#D4AF37', '#0F6B4C', '#5C3A21', '#C0392B', '#2980B9', '#8E44AD', '#16A085', '#E67E22'];

function joinRoom($roomCode, $teamName, $playerName = '', $deviceId = '') {
  global $TEAM_COLORS;
  $settings = getSettings();
  if (trim(strtoupper($roomCode)) !== trim(strtoupper($settings['RoomCode'] ?? ''))) {
    return fail('รหัสห้องไม่ถูกต้อง');
  }
  $teamName = trim($teamName);
  if ($teamName === '') return fail('กรุณาใส่ชื่อทีม');

  $teamStmt = db()->prepare('SELECT * FROM teams WHERE team_name = :n');
  $teamStmt->execute(['n' => $teamName]);
  $team = $teamStmt->fetch();

  if (!$team) {
    $countStmt = db()->query('SELECT COUNT(*) AS c FROM teams');
    $count = (int) $countStmt->fetch()['c'];
    $teamId = generateId('TEAM');
    $color = $TEAM_COLORS[$count % count($TEAM_COLORS)];
    db()->prepare('INSERT INTO teams (team_id, team_name, color, score, letter_count, status) VALUES (:id, :n, :c, 0, 0, "active")')
      ->execute(['id' => $teamId, 'n' => $teamName, 'c' => $color]);
    writeLog('LOGIN', "ทีมใหม่เข้าร่วม: $teamName", 'Student');
    $team = ['team_id' => $teamId, 'team_name' => $teamName, 'color' => $color];
  }

  // ลงทะเบียนผู้เล่นรายบุคคล (ใช้ device_id เพื่อจดจำการเข้าร่วมซ้ำ)
  if ($deviceId) {
    $pStmt = db()->prepare('SELECT * FROM players WHERE device_id = :d');
    $pStmt->execute(['d' => $deviceId]);
    $player = $pStmt->fetch();
    if ($player) {
      db()->prepare('UPDATE players SET team_id = :tid, name = :n, login_time = NOW() WHERE player_id = :pid')
        ->execute(['tid' => $team['team_id'], 'n' => $playerName ?: $player['name'], 'pid' => $player['player_id']]);
    } else {
      $playerId = generateId('PLAYER');
      db()->prepare('INSERT INTO players (player_id, team_id, name, device_id, login_time) VALUES (:pid, :tid, :n, :d, NOW())')
        ->execute(['pid' => $playerId, 'tid' => $team['team_id'], 'n' => $playerName ?: 'ผู้เล่น', 'd' => $deviceId]);
    }
  }

  return ok(['teamId' => $team['team_id'], 'teamName' => $team['team_name'], 'color' => $team['color']]);
}

function getTeamById($teamId) {
  $stmt = db()->prepare('SELECT * FROM teams WHERE team_id = :tid');
  $stmt->execute(['tid' => $teamId]);
  return $stmt->fetch() ?: null;
}

function getTeamLetters($teamId) {
  $stmt = db()->prepare('SELECT letter FROM letters WHERE team_id = :tid ORDER BY receive_time ASC');
  $stmt->execute(['tid' => $teamId]);
  return array_column($stmt->fetchAll(), 'letter');
}

/* ==================== STORY / HOW TO PLAY ==================== */

function getStory() {
  return db()->query('SELECT * FROM story ORDER BY story_order ASC')->fetchAll();
}

function getHowToPlay() {
  return db()->query('SELECT * FROM how_to_play ORDER BY step_order ASC')->fetchAll();
}

/* ==================== ACHIEVEMENTS / EVENTS / THEMES ==================== */

function grantAchievement($teamId, $achievement) {
  $checkStmt = db()->prepare('SELECT COUNT(*) AS c FROM achievements WHERE team_id = :tid AND achievement = :a');
  $checkStmt->execute(['tid' => $teamId, 'a' => $achievement]);
  if ((int) $checkStmt->fetch()['c'] > 0) return; // มีอยู่แล้ว ไม่ต้องให้ซ้ำ
  db()->prepare('INSERT INTO achievements (team_id, achievement, status) VALUES (:tid, :a, "unlocked")')
    ->execute(['tid' => $teamId, 'a' => $achievement]);
  recordEvent('AchievementUnlocked', "$teamId:$achievement");
}

function maybeGrantAchievement($teamId, $achievement, $condition) {
  if ($condition) grantAchievement($teamId, $achievement);
}

function getAchievements($teamId = null) {
  if ($teamId) {
    $stmt = db()->prepare('SELECT * FROM achievements WHERE team_id = :tid');
    $stmt->execute(['tid' => $teamId]);
  } else {
    $stmt = db()->query('SELECT * FROM achievements');
  }
  return $stmt->fetchAll();
}

function recordEvent($event, $value) {
  db()->prepare('INSERT INTO events (event, value) VALUES (:e, :v)')->execute(['e' => $event, 'v' => $value]);
}

function getActiveTheme() {
  $settings = getSettings();
  $themeName = $settings['Theme'] ?? 'Default';
  $stmt = db()->prepare('SELECT * FROM themes WHERE theme = :t');
  $stmt->execute(['t' => $themeName]);
  return $stmt->fetch() ?: ['theme' => 'Default', 'background' => '#0B1F3A', 'color' => '#D4AF37', 'font' => 'Kanit'];
}

/* ==================== RESET GAME ==================== */

/**
 * รีเซ็ตเกม: ล้างทีม/ผู้เล่น/คะแนน/ตัวอักษร/บัซเซอร์/ความสำเร็จ กลับไปเริ่มเกมใหม่
 * (ไม่ลบคำถาม/ด่าน/เนื้อเรื่อง/วิธีเล่น/ปริศนา/ธีม ที่ครูตั้งค่าไว้)
 */
function resetGameData() {
  $pdo = db();
  foreach (['scores', 'letters', 'buzzers', 'achievements', 'players', 'teams', 'events'] as $table) {
    $pdo->exec("DELETE FROM $table");
  }
  $pdo->exec("UPDATE locations SET unlock_status = 'locked'");
  $locations = getLocations();
  if ($locations) {
    $pdo->prepare('UPDATE locations SET unlock_status = "unlocked" WHERE id = :id')->execute(['id' => $locations[0]['id']]);
  }
  setGameStatus([
    'Phase' => GAME_PHASE_LOBBY,
    'CurrentLocationIndex' => '0',
    'CurrentQuestionID' => '',
    'BuzzerOpen' => 'FALSE',
    'BuzzerLockedTeamID' => '',
    'QuestionStartTime' => '',
    'BonusActive' => 'FALSE',
    'BonusTeamID' => '',
  ]);
  writeLog('SYSTEM', 'เกมถูกรีเซ็ตใหม่', 'Teacher');
  return ok(true);
}
