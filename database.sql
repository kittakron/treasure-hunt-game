-- =========================================================
-- เกมล่าขุมสมบัติ (Treasure Hunt Classroom Game) — MySQL Schema
-- Import ไฟล์นี้เข้า phpMyAdmin / mysql CLI ก่อนใช้งานระบบ
-- =========================================================


-- ---------- Settings (ตั้งค่าทั่วไปของเกม) ----------
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(64) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- GameStatus (สถานะเกมแบบเรียลไทม์) ----------
CREATE TABLE IF NOT EXISTS game_status (
  status_key   VARCHAR(64) PRIMARY KEY,
  status_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Teams ----------
CREATE TABLE IF NOT EXISTS teams (
  team_id      VARCHAR(32) PRIMARY KEY,
  team_name    VARCHAR(100) NOT NULL UNIQUE,
  color        VARCHAR(20),
  logo         VARCHAR(255),
  score        INT DEFAULT 0,
  letter_count INT DEFAULT 0,
  status       VARCHAR(20) DEFAULT 'active',
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Players (ผู้เล่นรายบุคคลในทีม) ----------
CREATE TABLE IF NOT EXISTS players (
  player_id  VARCHAR(32) PRIMARY KEY,
  team_id    VARCHAR(32),
  name       VARCHAR(100),
  device_id  VARCHAR(150),
  login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
  INDEX idx_device (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Locations (ด่าน/จุดต่างๆ บนแผนที่) ----------
CREATE TABLE IF NOT EXISTS locations (
  id              VARCHAR(32) PRIMARY KEY,
  location_order  INT NOT NULL,
  name            VARCHAR(150),
  description     TEXT,
  image           VARCHAR(255),
  letter          VARCHAR(10),
  unlock_status          VARCHAR(20) DEFAULT 'locked' -- locked | unlocked | complete
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Questions ----------
CREATE TABLE IF NOT EXISTS questions (
  question_id  VARCHAR(32) PRIMARY KEY,
  location_id  VARCHAR(32),
  question     TEXT,
  image        VARCHAR(255),
  a            VARCHAR(255),
  b            VARCHAR(255),
  c            VARCHAR(255),
  d            VARCHAR(255),
  correct      VARCHAR(10),
  score        INT DEFAULT 100,
  time         INT DEFAULT 30,
  hint         TEXT,
  explanation  TEXT,
  difficulty   VARCHAR(20),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Story (เนื้อเรื่องเปิดเกม) ----------
CREATE TABLE IF NOT EXISTS story (
  story_order INT PRIMARY KEY,
  title       VARCHAR(150),
  content     TEXT,
  image       VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- HowToPlay (วิธีเล่น) ----------
CREATE TABLE IF NOT EXISTS how_to_play (
  step_order INT PRIMARY KEY,
  text       TEXT,
  image      VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Scores (log คะแนนทุกครั้งที่ตอบ) ----------
CREATE TABLE IF NOT EXISTS scores (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  team_id    VARCHAR(32),
  location   VARCHAR(32),
  question   VARCHAR(32),
  score      INT DEFAULT 0,
  total      INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Letters (log ตัวอักษรที่แต่ละทีมได้รับ) ----------
CREATE TABLE IF NOT EXISTS letters (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  team_id      VARCHAR(32),
  location     VARCHAR(32),
  letter       VARCHAR(10),
  receive_time DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Puzzle (ปริศนาห้องสมบัติสุดท้าย - มีแถวเดียว) ----------
CREATE TABLE IF NOT EXISTS puzzle (
  id      INT PRIMARY KEY DEFAULT 1,
  puzzle  TEXT,
  answer  VARCHAR(255),
  bonus   INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Buzzers (log การกดบัซเซอร์ ระดับ millisecond) ----------
CREATE TABLE IF NOT EXISTS buzzers (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  timestamp_ms BIGINT,
  team_id      VARCHAR(32),
  question     VARCHAR(32),
  rank_num     INT,
  status       VARCHAR(20) DEFAULT 'pending' -- pending | correct | wrong
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Logs (ประวัติการทำงานของระบบ) ----------
CREATE TABLE IF NOT EXISTS logs (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
  action   VARCHAR(50),
  user     VARCHAR(100),
  detail   TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Media / Sounds ----------
CREATE TABLE IF NOT EXISTS media (
  name VARCHAR(100) PRIMARY KEY,
  type VARCHAR(20),
  url  VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sounds (
  name VARCHAR(100) PRIMARY KEY,
  url  VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Achievements ----------
CREATE TABLE IF NOT EXISTS achievements (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  team_id     VARCHAR(32),
  achievement VARCHAR(100),
  status      VARCHAR(20) DEFAULT 'unlocked'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Events (log เหตุการณ์สำคัญของเกม) ----------
CREATE TABLE IF NOT EXISTS events (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  event      VARCHAR(100),
  value      TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Themes ----------
CREATE TABLE IF NOT EXISTS themes (
  theme      VARCHAR(50) PRIMARY KEY,
  background VARCHAR(20),
  color      VARCHAR(20),
  font       VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- SEED DATA (ข้อมูลตัวอย่าง — ครูสามารถแก้ไข/ลบทิ้งภายหลังได้)
-- =========================================================

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('GameName', 'เกมล่าขุมสมบัติ'),
  ('RoomCode', '1234'),
  ('TotalLocations', '3'),
  ('TeacherPassword', 'admin123'),
  ('Theme', 'Default'),
  ('DefaultQuestionTime', '30'),
  ('BonusWinnerDoubleBuzz', 'TRUE'),
  ('LogoURL', ''),
  ('BackgroundMusicURL', '');

INSERT IGNORE INTO game_status (status_key, status_value) VALUES
  ('Phase', 'LOBBY'),
  ('CurrentLocationIndex', '0'),
  ('CurrentQuestionID', ''),
  ('BuzzerOpen', 'FALSE'),
  ('BuzzerLockedTeamID', ''),
  ('QuestionStartTime', ''),
  ('BonusActive', 'FALSE'),
  ('BonusTeamID', '');

INSERT IGNORE INTO locations (id, location_order, name, description, image, letter, unlock_status) VALUES
  ('L1', 1, 'วัดภูมินทร์', 'จุดเริ่มต้นการผจญภัย', '', 'ข', 'unlocked'),
  ('L2', 2, 'พระธาตุแช่แห้ง', 'ด่านที่สองแห่งความศักดิ์สิทธิ์', '', 'ม', 'locked'),
  ('L3', 3, 'ถนนคนเดินน่าน', 'ด่านสุดท้ายก่อนถึงขุมทรัพย์', '', 'บ', 'locked');

INSERT IGNORE INTO questions (question_id, location_id, question, image, a, b, c, d, correct, score, time, hint, explanation, difficulty) VALUES
  ('Q1', 'L1', 'จังหวัดน่านอยู่ภาคใดของประเทศไทย?', '', 'ภาคเหนือ', 'ภาคใต้', 'ภาคอีสาน', 'ภาคกลาง', 'A', 100, 30, 'ลองนึกถึงแผนที่ประเทศไทย', 'จังหวัดน่านอยู่ภาคเหนือตอนบน', 'ง่าย'),
  ('Q2', 'L2', 'พระธาตุแช่แห้งเป็นพระธาตุประจำปีเกิดใด?', '', 'ปีกุน', 'ปีเถาะ', 'ปีชวด', 'ปีมะเส็ง', 'B', 100, 30, '', '', 'ปานกลาง'),
  ('Q3', 'L3', 'ถนนคนเดินน่านเปิดวันใด?', '', 'ศุกร์-อาทิตย์', 'จันทร์-อังคาร', 'ทุกวัน', 'พุธเท่านั้น', 'A', 100, 30, '', '', 'ง่าย');

INSERT IGNORE INTO story (story_order, title, content, image) VALUES
  (1, 'จุดเริ่มต้น', 'นานมาแล้ว ณ ดินแดนล้านนา มีขุมสมบัติลึกลับซ่อนอยู่ทั่วเมืองน่าน...', ''),
  (2, 'ภารกิจ', 'ผู้กล้าทั้งหลายจะต้องออกเดินทางไขปริศนาในแต่ละสถานที่ เพื่อเก็บรวบรวมตัวอักษรลับ', ''),
  (3, 'เป้าหมาย', 'เมื่อรวบรวมครบทุกตัวอักษรแล้ว จงนำไปไขรหัสในห้องสมบัติ เพื่อรับชัยชนะ!', '');

INSERT IGNORE INTO how_to_play (step_order, text, image) VALUES
  (1, 'แต่ละทีมจะได้เดินทางผ่านด่านต่างๆ บนแผนที่ เพื่อตอบคำถามในแต่ละจุด', ''),
  (2, 'เมื่อครูเปิดคำถาม ให้รอสัญญาณ 🔴 แล้วรีบกดบัซเซอร์ให้เร็วที่สุดเพื่อแย่งสิทธิ์ตอบ', ''),
  (3, 'ตอบถูกจะได้คะแนนและตัวอักษรลับประจำด่าน เก็บให้ครบเพื่อไขรหัสในห้องสมบัติ', '');

INSERT IGNORE INTO puzzle (id, puzzle, answer, bonus) VALUES
  (1, 'นำตัวอักษรที่ได้จากทุกด่านมาเรียงกันให้ถูกต้อง', 'ขมบ', 200);

INSERT IGNORE INTO themes (theme, background, color, font) VALUES
  ('Default', '#0B1F3A', '#D4AF37', 'Kanit');
