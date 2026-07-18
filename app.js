/**
 * assets/app.js
 * ฟังก์ชัน JavaScript ที่ใช้ร่วมกันทุกหน้า (เรียก api.php ผ่าน fetch)
 */

// เรียก api.php แบบ POST พร้อมพารามิเตอร์ คืนค่าเป็น Promise ของ { success, data|message }
async function apiCall(action, params) {
  const body = new URLSearchParams({ action, ...(params || {}) });
  const res = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body,
  });
  if (!res.ok && res.status !== 400) {
    return { success: false, message: 'เซิร์ฟเวอร์ผิดพลาด (' + res.status + ')' };
  }
  try {
    return await res.json();
  } catch (e) {
    return { success: false, message: 'ไม่สามารถอ่านข้อมูลจากเซิร์ฟเวอร์ได้' };
  }
}

// เรียก api.php แบบ GET (สำหรับ action ที่ต้องส่ง query string เช่น polling บ่อยๆ)
async function apiGet(action, params) {
  const query = new URLSearchParams({ action, ...(params || {}) });
  const res = await fetch('api.php?' + query.toString());
  try {
    return await res.json();
  } catch (e) {
    return { success: false, message: 'ไม่สามารถอ่านข้อมูลจากเซิร์ฟเวอร์ได้' };
  }
}

// สร้าง/อ่าน Device ID ที่ผูกกับเบราว์เซอร์นี้ (ใช้จดจำผู้เล่นเวลาเข้าเกมซ้ำ)
function getDeviceId() {
  let id = localStorage.getItem('thg_deviceId');
  if (!id) {
    id = (crypto.randomUUID ? crypto.randomUUID() : ('dev_' + Date.now() + '_' + Math.random().toString(16).slice(2)));
    localStorage.setItem('thg_deviceId', id);
  }
  return id;
}

// เล่นเสียงเอฟเฟกต์แบบง่าย ๆ ด้วย Web Audio API (ไม่ต้องพึ่งไฟล์เสียงภายนอก)
function playTone(freq, duration, type) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = type || 'sine';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0.15, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + duration);
  } catch (e) { /* เบราว์เซอร์บางตัวอาจไม่รองรับ */ }
}
function playCorrectSound() { playTone(880, 0.35, 'triangle'); setTimeout(() => playTone(1320, 0.3, 'triangle'), 150); }
function playWrongSound() { playTone(160, 0.4, 'sawtooth'); }
function playBuzzSound() { playTone(440, 0.15, 'square'); }
function playWinSound() {
  [523, 659, 784, 1047].forEach((f, i) => setTimeout(() => playTone(f, 0.4, 'triangle'), i * 150));
}

// เอฟเฟกต์ Confetti แบบเบาๆ ด้วย DOM ล้วน
function launchConfetti(count) {
  const colors = ['#D4AF37', '#0F6B4C', '#C0392B', '#FFFFFF', '#5C3A21'];
  for (let i = 0; i < (count || 80); i++) {
    const el = document.createElement('div');
    el.className = 'confetti-piece';
    const size = 6 + Math.random() * 6;
    el.style.width = size + 'px';
    el.style.height = (size * 0.4) + 'px';
    el.style.left = Math.random() * 100 + 'vw';
    el.style.background = colors[Math.floor(Math.random() * colors.length)];
    el.style.animationDuration = (2.5 + Math.random() * 2) + 's';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 5000);
  }
}

// Typewriter animation สำหรับหน้า Story
function typewrite(el, text, speed, onDone) {
  el.textContent = '';
  let i = 0;
  const interval = setInterval(() => {
    el.textContent += text.charAt(i);
    i++;
    if (i >= text.length) {
      clearInterval(interval);
      if (onDone) onDone();
    }
  }, speed || 35);
}

function formatMs(ms) {
  return (ms / 1000).toFixed(2) + 's';
}

// ดึงธีมที่ตั้งค่าไว้ใน Settings.Theme (จากตาราง Themes) มาปรับสี/ฟอนต์แบบไดนามิก
async function applyTheme() {
  try {
    const res = await apiGet('getActiveTheme');
    if (res.success && res.data) {
      const t = res.data;
      if (t.background) document.documentElement.style.setProperty('--dark-blue', t.background);
      if (t.color) document.documentElement.style.setProperty('--gold', t.color);
      if (t.font) document.body.style.fontFamily = `'${t.font}', 'Kanit', sans-serif`;
    }
  } catch (e) { /* ใช้ธีมค่าเริ่มต้นถ้าดึงไม่สำเร็จ */ }
}
