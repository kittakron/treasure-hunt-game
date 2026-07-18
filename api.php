<?php
/**
 * api.php
 * จุดเดียวสำหรับรับคำขอ AJAX ทั้งหมดจากฝั่ง Client (ครู/นักเรียน/โปรเจกเตอร์)
 * เรียกผ่าน POST หรือ GET พร้อมพารามิเตอร์ action=ชื่อฟังก์ชัน
 */

require_once __DIR__ . '/includes/game_logic.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_REQUEST['action'] ?? '';

// รายชื่อ action ที่ต้องล็อกอินครูก่อนถึงจะเรียกได้
$teacherOnlyActions = [
  'actionStartGame', 'actionShowHowToPlay', 'actionShowStory', 'actionShowMap', 'actionOpenLocation',
  'actionNextLocation', 'actionPreviousLocation', 'actionOpenQuestion', 'actionOpenBuzzer', 'actionCloseBuzzer',
  'actionResetBuzzer', 'actionAllowNextTeam', 'actionMarkCorrect', 'actionMarkWrong', 'actionShowLeaderboard',
  'actionOpenPuzzle', 'actionShowWinner', 'actionEndGame', 'getTeacherDashboard', 'getAllLogs', 'getAllQuestions',
  'actionResetGame',
];

if (in_array($action, $teacherOnlyActions, true)) {
  requireTeacherAuth();
}

switch ($action) {

  /* ---------------- Public / shared ---------------- */
  case 'getGameName':
    jsonOut(ok(getSettings()['GameName'] ?? 'เกมล่าขุมสมบัติ'));

  case 'getStory':
    jsonOut(ok(getStory()));

  case 'getHowToPlay':
    jsonOut(ok(getHowToPlay()));

  case 'getActiveTheme':
    jsonOut(ok(getActiveTheme()));

  case 'getProjectorState':
    $settings = getSettings();
    $status = getGameStatus();
    $locations = getLocations();
    $currentLocation = getCurrentLocation();
    $currentQuestion = !empty($status['CurrentQuestionID']) ? sanitizeQuestionForClient(getQuestionById($status['CurrentQuestionID'])) : null;
    $lockedTeamName = null;
    if (!empty($status['BuzzerLockedTeamID'])) {
      $t = getTeamById($status['BuzzerLockedTeamID']);
      $lockedTeamName = $t ? $t['team_name'] : null;
    }
    jsonOut(ok([
      'settings' => $settings,
      'status' => $status,
      'locations' => $locations,
      'currentLocation' => $currentLocation,
      'currentQuestion' => $currentQuestion,
      'lockedTeamName' => $lockedTeamName,
      'leaderboard' => getLeaderboard(),
      'teams' => db()->query('SELECT team_id, team_name, color FROM teams')->fetchAll(),
      'story' => getStory(),
      'howToPlay' => getHowToPlay(),
      'puzzleHint' => getPuzzleData()['puzzle'],
    ]));

  /* ---------------- Teacher auth ---------------- */
  case 'teacherLogin':
    $password = $_POST['password'] ?? '';
    $settings = getSettings();
    if ((string) $password === (string) ($settings['TeacherPassword'] ?? '')) {
      $_SESSION['teacher_logged_in'] = true;
      writeLog('LOGIN', 'ครูล็อกอินสำเร็จ', 'Teacher');
      jsonOut(ok(true));
    }
    jsonOut(fail('รหัสผ่านไม่ถูกต้อง'));

  case 'teacherLogout':
    unset($_SESSION['teacher_logged_in']);
    jsonOut(ok(true));

  /* ---------------- Teacher dashboard & game control ---------------- */
  case 'getTeacherDashboard':
    $settings = getSettings();
    $status = getGameStatus();
    $locations = getLocations();
    $currentLocation = getCurrentLocation();
    $currentQuestion = !empty($status['CurrentQuestionID']) ? getQuestionById($status['CurrentQuestionID']) : null;
    $buzzerLog = !empty($status['CurrentQuestionID']) ? getBuzzerLog($status['CurrentQuestionID']) : [];
    jsonOut(ok([
      'settings' => $settings,
      'status' => $status,
      'locations' => $locations,
      'currentLocation' => $currentLocation,
      'currentQuestion' => $currentQuestion,
      'buzzerLog' => $buzzerLog,
      'leaderboard' => getLeaderboard(),
      'teams' => db()->query('SELECT * FROM teams')->fetchAll(),
    ]));

  case 'actionStartGame':
    setGameStatus(['Phase' => GAME_PHASE_HOWTOPLAY, 'CurrentLocationIndex' => '0']);
    writeLog('GAME', 'เริ่มเกม', 'Teacher');
    unlockLocationByIndex(0);
    jsonOut(ok(true));

  case 'actionShowHowToPlay':
    setGameStatus(['Phase' => GAME_PHASE_HOWTOPLAY]);
    jsonOut(ok(true));

  case 'actionShowStory':
    setGameStatus(['Phase' => GAME_PHASE_STORY]);
    jsonOut(ok(true));

  case 'actionShowMap':
    setGameStatus(['Phase' => GAME_PHASE_MAP]);
    jsonOut(ok(true));

  case 'actionOpenLocation':
    jsonOut(unlockLocationByIndex((int) ($_POST['index'] ?? 0)));

  case 'actionNextLocation':
    $status = getGameStatus();
    $nextIndex = (int) ($status['CurrentLocationIndex'] ?? 0) + 1;
    $locations = getLocations();
    if ($nextIndex >= count($locations)) jsonOut(fail('ผ่านด่านสุดท้ายแล้ว ให้เปิดห้องสมบัติต่อได้เลย'));
    jsonOut(unlockLocationByIndex($nextIndex));

  case 'actionPreviousLocation':
    $status = getGameStatus();
    $prevIndex = max(0, (int) ($status['CurrentLocationIndex'] ?? 0) - 1);
    jsonOut(unlockLocationByIndex($prevIndex));

  case 'actionOpenQuestion':
    jsonOut(openCurrentQuestion());

  case 'actionOpenBuzzer':
    jsonOut(openBuzzer());

  case 'actionCloseBuzzer':
    jsonOut(closeBuzzer());

  case 'actionResetBuzzer':
    jsonOut(resetBuzzer());

  case 'actionAllowNextTeam':
    jsonOut(allowNextTeam());

  case 'actionMarkCorrect':
    jsonOut(awardCorrectAnswer($_POST['teamId'] ?? ''));

  case 'actionMarkWrong':
    jsonOut(recordWrongAnswer($_POST['teamId'] ?? ''));

  case 'actionShowLeaderboard':
    setGameStatus(['Phase' => GAME_PHASE_LEADERBOARD]);
    jsonOut(ok(getLeaderboard()));

  case 'actionOpenPuzzle':
    jsonOut(openPuzzleRoom());

  case 'actionShowWinner':
    setGameStatus(['Phase' => GAME_PHASE_WINNER]);
    jsonOut(ok(true));

  case 'actionEndGame':
    setGameStatus(['Phase' => GAME_PHASE_ENDED]);
    writeLog('GAME', 'จบเกม', 'Teacher');
    jsonOut(ok(true));

  case 'actionResetGame':
    jsonOut(resetGameData());

  case 'getAllLogs':
    jsonOut(ok(db()->query('SELECT * FROM logs ORDER BY id DESC LIMIT 200')->fetchAll()));

  case 'getAllQuestions':
    jsonOut(ok(db()->query('SELECT * FROM questions')->fetchAll()));

  /* ---------------- Student ---------------- */
  case 'joinRoom':
    jsonOut(joinRoom(
      $_POST['roomCode'] ?? '',
      $_POST['teamName'] ?? '',
      $_POST['playerName'] ?? '',
      $_POST['deviceId'] ?? ''
    ));

  case 'getStudentState':
    $teamId = $_REQUEST['teamId'] ?? '';
    $team = getTeamById($teamId);
    if (!$team) jsonOut(fail('ไม่พบทีมนี้ อาจถูกรีเซ็ตเกม กรุณาเข้าร่วมใหม่'));

    $settings = getSettings();
    $status = getGameStatus();
    $currentLocation = getCurrentLocation();
    $currentQuestion = null;
    $activePhases = [GAME_PHASE_QUESTION, GAME_PHASE_BUZZER_OPEN, GAME_PHASE_ANSWER_RESULT];
    if (!empty($status['CurrentQuestionID']) && in_array($status['Phase'], $activePhases, true)) {
      $currentQuestion = sanitizeQuestionForClient(getQuestionById($status['CurrentQuestionID']));
    }

    jsonOut(ok([
      'settings' => $settings,
      'status' => $status,
      'team' => $team,
      'currentLocation' => $currentLocation,
      'currentQuestion' => $currentQuestion,
      'isMyBuzzLocked' => ($status['BuzzerLockedTeamID'] ?? '') === $teamId,
      'letters' => getTeamLetters($teamId),
      'puzzle' => $status['Phase'] === GAME_PHASE_PUZZLE ? ['hint' => getPuzzleData()['puzzle']] : null,
      'leaderboard' => in_array($status['Phase'], [GAME_PHASE_LEADERBOARD, GAME_PHASE_WINNER], true) ? getLeaderboard() : null,
      'howToPlay' => $status['Phase'] === GAME_PHASE_HOWTOPLAY ? getHowToPlay() : null,
      'story' => $status['Phase'] === GAME_PHASE_STORY ? getStory() : null,
    ]));

  case 'studentPressBuzzer':
    jsonOut(pressBuzzer($_POST['teamId'] ?? ''));

  case 'submitPuzzleAnswer':
    jsonOut(submitPuzzleAnswer($_POST['teamId'] ?? '', $_POST['answer'] ?? ''));

  default:
    http_response_code(400);
    jsonOut(fail('ไม่รู้จัก action นี้: ' . $action));
}
