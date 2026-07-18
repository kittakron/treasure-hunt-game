<?php
/**
 * includes/helpers.php
 * ฟังก์ชันช่วยเหลือทั่วไปที่ใช้ร่วมกันทุกโมดูล
 */
require_once __DIR__ . '/db.php';

const GAME_PHASE_LOBBY = 'LOBBY';
const GAME_PHASE_HOWTOPLAY = 'HOWTOPLAY';
const GAME_PHASE_STORY = 'STORY';
const GAME_PHASE_MAP = 'MAP';
const GAME_PHASE_QUESTION = 'QUESTION';
const GAME_PHASE_BUZZER_OPEN = 'BUZZER_OPEN';
const GAME_PHASE_ANSWER_RESULT = 'ANSWER_RESULT';
const GAME_PHASE_LEADERBOARD = 'LEADERBOARD';
const GAME_PHASE_PUZZLE = 'PUZZLE';
const GAME_PHASE_WINNER = 'WINNER';
const GAME_PHASE_ENDED = 'ENDED';

function ok($data = true) {
  return ['success' => true, 'data' => $data];
}

function fail($message) {
  return ['success' => false, 'message' => $message];
}

function jsonOut($arr) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function generateId($prefix) {
  return $prefix . '_' . round(microtime(true) * 1000) . '_' . random_int(1000, 9999);
}

function nowMillis() {
  return (int) round(microtime(true) * 1000);
}

function toBool($val) {
  if (is_bool($val)) return $val;
  return strtoupper(trim((string) $val)) === 'TRUE';
}

/* ---------------- Settings (key/value table) ---------------- */

function getSettings() {
  $stmt = db()->query('SELECT setting_key, setting_value FROM settings');
  $out = [];
  foreach ($stmt->fetchAll() as $row) {
    $out[$row['setting_key']] = $row['setting_value'];
  }
  return $out;
}

function setSetting($key, $value) {
  $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
    ON DUPLICATE KEY UPDATE setting_value = :v2');
  $stmt->execute(['k' => $key, 'v' => $value, 'v2' => $value]);
}

/* ---------------- GameStatus (key/value table, live game state) ---------------- */

function getGameStatus() {
  $stmt = db()->query('SELECT status_key, status_value FROM game_status');
  $out = [];
  foreach ($stmt->fetchAll() as $row) {
    $out[$row['status_key']] = $row['status_value'];
  }
  return $out;
}

function setGameStatus($updates) {
  $stmt = db()->prepare('INSERT INTO game_status (status_key, status_value) VALUES (:k, :v)
    ON DUPLICATE KEY UPDATE status_value = :v2');
  foreach ($updates as $key => $value) {
    $stmt->execute(['k' => $key, 'v' => $value, 'v2' => $value]);
  }
}

/* ---------------- Logs ---------------- */

function writeLog($action, $detail, $user = 'System') {
  try {
    $stmt = db()->prepare('INSERT INTO logs (action, user, detail) VALUES (:a, :u, :d)');
    $stmt->execute(['a' => $action, 'u' => $user, 'd' => is_string($detail) ? $detail : json_encode($detail, JSON_UNESCAPED_UNICODE)]);
  } catch (Exception $e) {
    // การบันทึก log ไม่ควรทำให้ระบบหลักล่ม
  }
}

/* ---------------- Teacher auth (PHP session) ---------------- */

function requireTeacherAuth() {
  if (session_status() === PHP_SESSION_NONE) session_start();
  if (empty($_SESSION['teacher_logged_in'])) {
    jsonOut(fail('กรุณาเข้าสู่ระบบครูก่อนใช้งาน'));
  }
}
