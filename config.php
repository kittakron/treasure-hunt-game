<?php
/**
 * config.php
 * ตั้งค่าการเชื่อมต่อฐานข้อมูล — แก้ไขให้ตรงกับ hosting ของคุณ
 */

// แก้ 4 บรรทัดนี้ให้ตรงกับข้อมูลฐานข้อมูล MySQL ของคุณ (เช่นจาก cPanel / phpMyAdmin)
define('DB_HOST', 'sql305.infinityfree.com');  // MySQL hostname ที่จดไว้
define('DB_NAME', 'if0_42438499_treasure');
define('DB_USER', 'if0_42438499');
define('DB_PASS', 'tampkittakhon05');

// เขตเวลา (ใช้ให้ตรงกับเวลาห้องเรียน)
date_default_timezone_set('Asia/Bangkok');

// เปิด/ปิดการแสดง error (ปิดเป็น false เมื่อใช้งานจริงบน production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
}
