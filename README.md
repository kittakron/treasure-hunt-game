# 🏆 เกมล่าขุมสมบัติ — Treasure Hunt Game Engine (PHP + MySQL)

เวอร์ชัน PHP + MySQL ของระบบเกมล่าขุมสมบัติสำหรับห้องเรียน
(แทนที่เวอร์ชัน Google Apps Script เดิม) ใช้ schema ตรงกับไฟล์เทมเพลต
`Google_Sheets_Template_Treasure_Hunt.xlsx` ที่ให้มา แปลงเป็นตาราง MySQL

> ระบบนี้**ทดสอบจริงแล้ว**: รันเซิร์ฟเวอร์ PHP + MariaDB ในเครื่อง, import schema,
> เล่นเกมครบทุกขั้นตอน (login ครู → เข้าร่วมทีม → เปิดด่าน → เปิดคำถาม → กดบัซเซอร์ →
> ตัดสินถูก/ผิด → เก็บตัวอักษร → ไขปริศนา → ผู้ชนะ → รีเซ็ตเกม) ผ่านทุกจุดจริง

## โครงสร้างไฟล์

```
config.php              ตั้งค่าการเชื่อมต่อฐานข้อมูล (แก้ 4 บรรทัดแรกให้ตรงกับ hosting ของคุณ)
database.sql             สคีมา MySQL ทั้งหมด + ข้อมูลตัวอย่าง (import ครั้งแรกครั้งเดียว)
includes/db.php          เชื่อมต่อฐานข้อมูลผ่าน PDO
includes/helpers.php     ฟังก์ชันช่วยเหลือ (settings, game_status, log, auth)
includes/game_logic.php  ตรรกะหลักทั้งหมด (แผนที่, คำถาม, บัซเซอร์, คะแนน, ปริศนา, ทีม/ผู้เล่น)
api.php                  จุดเดียวรับคำขอ AJAX ทั้งหมด (action=ชื่อฟังก์ชัน)

index.php                หน้าเลือกบทบาท
teacher.php              แดชบอร์ดครู + ปุ่มควบคุมเกมทั้งหมด (ต้องล็อกอิน)
student.php               หน้าจอทีมนักเรียน (join → howtoplay → story → map → question → buzzer → puzzle → winner)
projector.php             หน้าจอฉายหน้าห้อง (ไม่ต้องล็อกอิน, auto-refresh ทุก 1 วิ)

assets/style.css          ธีม Glassmorphism สีทอง/น้ำเงินเข้ม/เขียวมรกต/น้ำตาล
assets/app.js             fetch() wrapper เรียก api.php, เสียงเอฟเฟกต์, confetti, typewriter, deviceId
```

## ตารางฐานข้อมูล (ตรงกับไฟล์เทมเพลตต้นฉบับ)

| ตาราง | หน้าที่ |
|---|---|
| `settings` | ตั้งค่าทั่วไป (ชื่อเกม, รหัสห้อง, รหัสผ่านครู, ธีม ฯลฯ) |
| `game_status` | สถานะเกมแบบเรียลไทม์ (เฟสปัจจุบัน, ด่านปัจจุบัน, บัซเซอร์เปิด/ปิด) |
| `teams` | ทีม (คะแนน, จำนวนตัวอักษรที่เก็บได้, โลโก้) |
| `players` | ผู้เล่นรายบุคคลในทีม ผูกกับ `device_id` เพื่อจดจำการเข้าร่วมซ้ำ |
| `locations` | ด่าน/จุดบนแผนที่ (คอลัมน์สถานะชื่อ `unlock_status` เพราะ `unlock` เป็นคำสงวนใน MySQL) |
| `questions` | คำถามของแต่ละด่าน |
| `story` / `how_to_play` | เนื้อเรื่องเปิดเกม / วิธีเล่น |
| `scores` / `letters` | log คะแนนและตัวอักษรที่แต่ละทีมได้รับ |
| `puzzle` | ปริศนาห้องสมบัติสุดท้าย (คำตอบ = ตัวอักษรจากทุกด่านเรียงกัน) |
| `buzzers` | log การกดบัซเซอร์ระดับ millisecond |
| `logs` | ประวัติการทำงานของระบบ |
| `media` / `sounds` | URL รูป/เสียงเสริม (ใส่ URL แล้วอ้างอิงในหน้า HTML เพิ่มได้) |
| `achievements` | ความสำเร็จพิเศษของแต่ละทีม (ให้อัตโนมัติ เช่น ตอบเร็วที่สุด, ไขปริศนาสำเร็จ) |
| `events` | log เหตุการณ์สำคัญของเกม |
| `themes` | ชุดสี/ฟอนต์ที่สลับได้จาก `settings.Theme` |

## วิธี Deploy ด้วย GitHub (ฟรี 100%)

⚠️ **สำคัญก่อนอ่านต่อ**: GitHub Pages (`ชื่อผู้ใช้.github.io`) โฮสต์ได้แค่ไฟล์ static (HTML/CSS/JS) เท่านั้น
**รัน PHP และ MySQL ไม่ได้** ดังนั้นระบบนี้จะขึ้นบน GitHub Pages ตรงๆ ไม่ได้

วิธีที่ใช้ได้จริงคือ: เก็บโค้ดไว้บน **GitHub repository** แล้วให้ **GitHub Actions** (ไฟล์ `.github/workflows/deploy.yml` ที่แถมมาให้แล้ว) อัปโหลดไฟล์ขึ้นโฮสติ้งฟรีที่รองรับ PHP+MySQL จริงโดยอัตโนมัติทุกครั้งที่ push โค้ด

### ขั้นตอน

1. **สมัครโฮสติ้งฟรีที่รองรับ PHP+MySQL** เช่น [InfinityFree](https://infinityfree.net) (ฟรี ไม่ต้องใส่บัตรเครดิต)
   - สร้างเว็บไซต์ใหม่ จะได้โดเมนแบบ `yourname.infinityfreeapp.com`
   - เข้าเมนู **MySQL Databases** สร้างฐานข้อมูลใหม่ (จด host, ชื่อ DB, user, password ไว้)
   - เปิด **phpMyAdmin** จากแผงควบคุม แล้ว import ไฟล์ `database.sql`
     (เลือก Character set เป็น `utf8mb4` ตอน import ไม่งั้นภาษาไทยจะเพี้ยน)
   - เข้าเมนู **FTP Accounts** จด FTP host, username, password ไว้ (จะใช้ในขั้นตอนที่ 4)

2. **สร้าง GitHub repository** แล้วอัปโหลดโค้ดทั้งหมดในโฟลเดอร์นี้ขึ้นไป
   ```bash
   git init
   git add .
   git commit -m "Initial commit: เกมล่าขุมสมบัติ"
   git branch -M main
   git remote add origin https://github.com/ชื่อคุณ/treasure-hunt-game.git
   git push -u origin main
   ```

3. **แก้ `config.php`** ให้ตรงกับฐานข้อมูลที่สร้างในข้อ 1 แล้ว commit + push อีกครั้ง

4. **ตั้งค่า GitHub Secrets** สำหรับให้ GitHub Actions รู้จัก FTP ของโฮสติ้ง
   ไปที่ repo → **Settings → Secrets and variables → Actions → New repository secret** แล้วเพิ่ม 3 ตัวนี้:
   - `FTP_SERVER` = FTP host จากข้อ 1 (เช่น `ftpupload.net`)
   - `FTP_USERNAME` = FTP username จากข้อ 1
   - `FTP_PASSWORD` = FTP password จากข้อ 1

5. **push โค้ดครั้งถัดไป** (หรือกดรันเองที่แท็บ **Actions** ของ repo) ระบบจะอัปโหลดไฟล์ทั้งหมดขึ้นโฮสติ้งให้อัตโนมัติ
   เข้าเว็บที่ `http://yourname.infinityfreeapp.com/index.php` ได้เลย

จากนี้ไปทุกครั้งที่แก้โค้ดแล้ว push ขึ้น GitHub เว็บจะอัปเดตให้อัตโนมัติ โดยไม่ต้องอัปโหลดไฟล์เองอีก

### ทางเลือกอื่นถ้าไม่อยากยุ่งกับ GitHub Actions

อัปโหลดไฟล์ในโฟลเดอร์นี้ขึ้นโฮสติ้งตรงๆ ผ่าน **FTP client** (เช่น FileZilla) หรือ **File Manager**
ในแผงควบคุมโฮสติ้งก็ได้ ไม่จำเป็นต้องใช้ GitHub เลยก็ได้ผลลัพธ์เหมือนกัน — GitHub มีประโยชน์ตรงที่
เก็บประวัติการแก้ไขโค้ดและ deploy อัตโนมัติเวลามีการอัปเดตบ่อยๆ

## วิธีติดตั้งแบบทั่วไป (ไม่ผ่าน GitHub)

1. **สร้างฐานข้อมูล**: import `database.sql` เข้า MySQL/MariaDB
   ```bash
   mysql --default-character-set=utf8mb4 -u root -p < database.sql
   ```
   ⚠️ **สำคัญ**: ต้องระบุ `--default-character-set=utf8mb4` ตอน import ไม่งั้นข้อความภาษาไทยจะเพี้ยน
   (หรือถ้าใช้ phpMyAdmin ให้เลือก Character set เป็น `utf8mb4` ตอน import)

2. **ตั้งค่าการเชื่อมต่อ**: แก้ไฟล์ `config.php`
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'treasure_hunt');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

3. **อัปโหลดไฟล์ทั้งหมด** ขึ้น hosting ที่รองรับ PHP 7.4+ กับ MySQL/MariaDB (เช่น shared hosting ทั่วไป, VPS ที่ลง Apache/Nginx + PHP, หรือทดสอบในเครื่องด้วย `php -S localhost:8000`)

4. **เปิดใช้งาน**:
   - หน้าแรก: `http://your-domain/index.php`
   - ครู: `http://your-domain/teacher.php` (รหัสผ่านเริ่มต้น `admin123` แก้ได้ในตาราง `settings`)
   - นักเรียน: `http://your-domain/student.php` (รหัสห้องเริ่มต้น `1234` แก้ได้ในตาราง `settings`)
   - โปรเจกเตอร์: `http://your-domain/projector.php` (เปิดเต็มจอด้วย F11 ฉายหน้าห้อง ไม่ต้องล็อกอิน)

## การปรับแต่งเกมใหม่ (ไม่ต้องแก้โค้ด)

แก้ไขข้อมูลตรงในตาราง MySQL ผ่าน phpMyAdmin หรือ SQL client ใดก็ได้:

- `settings` → เปลี่ยนชื่อเกม, รหัสห้อง, ธีม
- `story`, `how_to_play` → แก้เนื้อเรื่อง/วิธีเล่น
- `locations`, `questions` → เพิ่ม/ลบ/แก้ด่านและคำถามได้ไม่จำกัดจำนวน (แค่ให้ `location_id` ในตาราง `questions` ตรงกับ `id` ในตาราง `locations`)
- `puzzle` → เปลี่ยนคำตอบปริศนาสุดท้าย (ค่าเริ่มต้นคือตัวอักษรจากทุกด่านเรียงกัน)
- `themes` → เพิ่มธีมสีใหม่ แล้วเปลี่ยนค่า `settings.Theme` ให้ตรงชื่อธีม

เมื่อจะเริ่มเกมรอบใหม่ ให้กดปุ่ม **"🔄 รีเซ็ตเกมใหม่"** ในแดชบอร์ดครู (ล้างทีม/คะแนน/บัซเซอร์/ตัวอักษร/ความสำเร็จ แต่ไม่ลบคำถาม/ด่าน/เนื้อเรื่อง/ปริศนาที่ตั้งไว้)

## หมายเหตุด้านเทคนิค

- **Realtime**: ใช้ polling ผ่าน `fetch()` (นักเรียน/ครูทุก 700-1500ms, โปรเจกเตอร์ทุก 1000ms) เพื่อ sync สถานะ เนื่องจากเป็นระบบ AJAX ธรรมดา ไม่ใช้ WebSocket
- **บัซเซอร์**: บันทึกเวลาระดับ millisecond ด้วย `microtime(true)` ฝั่ง PHP และล็อกสิทธิ์ทีมแรกที่กดในตาราง `game_status.BuzzerLockedTeamID` ทันที ป้องกันทีมอื่นกดซ้ำ
- **ความปลอดภัย**: ทุก query ใช้ PDO Prepared Statements ป้องกัน SQL Injection; หน้าครูใช้ PHP Session (`$_SESSION['teacher_logged_in']`) ป้องกันการเรียก action ควบคุมเกมโดยไม่ล็อกอิน; หน้าโปรเจกเตอร์เป็น read-only ไม่ต้องล็อกอินแต่ไม่มีปุ่มควบคุมใดๆ และคำถามที่ส่งให้จะไม่มีเฉลยติดไปด้วย
- **Device ID**: ฝั่งนักเรียนสร้าง UUID เก็บใน `localStorage` เพื่อจดจำผู้เล่นเวลาเข้าเกมซ้ำ (บันทึกในตาราง `players.device_id`)
- **คำสงวนของ MySQL**: คอลัมน์สถานะด่านใช้ชื่อ `unlock_status` แทน `unlock` เพราะ `UNLOCK` เป็นคำสงวน (`UNLOCK TABLES`) — ถ้าจะเพิ่มคอลัมน์ใหม่ควรเลี่ยงคำสงวนเช่น `ORDER`, `KEY`, `RANK`, `GROUP` เป็นต้น
- **เสียงเอฟเฟกต์**: สร้างสดด้วย Web Audio API ในเบราว์เซอร์ ไม่ต้องอัปโหลดไฟล์เสียง หากต้องการเพลง/เสียงจริง ใส่ URL ในตาราง `sounds` หรือ `settings.BackgroundMusicURL` แล้วเพิ่ม `<audio>` tag ในหน้า HTML ที่ต้องการเอง

## ทดสอบแล้วว่าทำงานได้จริง (End-to-End)

ระบบผ่านการทดสอบจริงด้วยการรัน PHP built-in server + MariaDB แล้วจำลองเกมเต็มรูปแบบ:
ครูล็อกอิน → เริ่มเกม → 2 ทีมเข้าร่วม → เปิด 3 ด่านตามลำดับ → เปิดคำถาม/บัซเซอร์ทุกด่าน →
ทีมกดบัซเซอร์ (ยืนยันว่าทีมอื่นกดซ้ำไม่ได้) → ครูตัดสินถูก → คะแนน/ตัวอักษรอัปเดตถูกต้อง →
เปิดห้องสมบัติหลังผ่านครบ 3 ด่าน → ไขปริศนาผิดแล้วถูก → ประกาศผู้ชนะ → รีเซ็ตเกม
ทุกขั้นตอนทำงานถูกต้องตามที่ออกแบบไว้

## ส่วนที่ยังต่อยอดได้

- Import/Export คำถามเป็น Excel/CSV ผ่านหน้าเว็บโดยตรง (ตอนนี้แก้ไขผ่าน phpMyAdmin/SQL ซึ่งทำหน้าที่เดียวกัน)
- Drag & Drop ตัวอักษรในห้องปริศนา (ตอนนี้ใช้พิมพ์คำตอบ ซึ่งใช้งานง่ายกว่าบนมือถือ)
- หน้าแก้ไขข้อมูลเกม (CMS) ในตัวสำหรับครูที่ไม่ถนัด SQL โดยตรง
