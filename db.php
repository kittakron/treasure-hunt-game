<?php
/**
 * includes/db.php
 * เชื่อมต่อฐานข้อมูล MySQL ผ่าน PDO (ใช้ Prepared Statements ทั้งระบบเพื่อความปลอดภัย)
 */
require_once __DIR__ . '/../config.php';

function db() {
  static $pdo = null;
  if ($pdo === null) {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
      $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]);
    } catch (PDOException $e) {
      http_response_code(500);
      die(json_encode(['success' => false, 'message' => 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ: ' . $e->getMessage()]));
    }
  }
  return $pdo;
}
