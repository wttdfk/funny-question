<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Playful Yes/No — No never disappears</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<style>
  :root { --bg1:#ff6a00; --bg2:#ee0979; --accent:#6a11cb; }
  html,body{height:100%;margin:0;font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,var(--bg1),var(--bg2));display:flex;align-items:center;justify-content:center;}
  .container{
    width:min(720px,92%);
    background:linear-gradient(180deg,rgba(255,255,255,0.98),rgba(255,255,255,0.95));
    padding:36px;border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,0.35);position:relative;z-index:1;
  }
  h1{margin:0 0 18px;font-size:1.8rem;color:#222;text-align:center;}
  .hint{color:#444;text-align:center;margin-bottom:22px;}
  .btn{
    --pad:14px 22px;
    background:var(--accent);color:#fff;border:none;padding:var(--pad);border-radius:10px;font-size:1.05rem;cursor:pointer;
    transition:transform .18s ease,box-shadow .18s ease;
  }
  .btn:active{transform:scale(.98);}
  #yes{display:inline-block;position:relative;z-index:2;margin:auto;box-shadow:0 8px 20px rgba(0,0,0,0.18);}
  #yes.clicked{animation:pulse .9s ease .04s 3; background:#28a745;}
  @keyframes pulse { from{transform:scale(1)} to{transform:scale(1.25) rotate(-4deg)} }
  /* NO button: fixed so it is relative to viewport and cannot be clipped */
  #no{
    position:fixed;
    z-index:9999;                 /* very high so it's always visible on top */
    will-change:left,top,transform;
    box-shadow:0 12px 30px rgba(0,0,0,0.35);
  }

  /* confetti (below No's z-index so No stays on top) */
  .confetti{ position:fixed; width:12px; height:12px; border-radius:3px; top:0; z-index:9998; animation:fall linear forwards; }
  @keyframes fall { to{ transform: translateY(110vh) rotate(720deg); opacity:0 } }

  /* small helpers */
  .row{display:flex;justify-content:center;gap:18px;align-items:center;min-height:72px;position:relative;}
  #greet{ text-align:center;margin-top:20px;font-size:1.3rem;color:#2b7a3a; font-weight:700; opacity:0; transition:opacity .7s ease; }
  @media (max-width:420px){ h1{font-size:1.3rem} .btn{padding:10px 14px;font-size:.95rem} .confetti{width:10px;height:10px} }
</style>
</head>
<body>
  <div class="container">
    <h1>Do you love this silly game? 😏</h1>
    <div class="hint">Try to tap/click <strong>No</strong> — but it won't leave the screen 😉</div>

    <div class="row">
      <button id="yes" class="btn">Yes 💖</button>
      <!-- No is visually separated but actually fixed (viewport). -->
      <button id="no" class="btn">No ❌</button>
    </div>

    <div id="greet">You picked YES — you're amazing! 🎉💖</div>
  </div>

<script>
/*
  Behavior summary:
  - #no is fixed and always placed inside viewport bounds (margin).
  - on pointerenter / touchstart it jumps to a new random visible spot.
  - avoids overlapping the Yes button (tries several times).
  - on window resize it ensures No stays visible.
*/

const noBtn = document.getElementById('no');
const yesBtn = document.getElementById('yes');
const greet = document.getElementById('greet');

const MARGIN = 10;            // space from viewport edges
const AVOID_OVERLAP = true;   // try to avoid overlapping Yes
const MAX_ATTEMPTS = 60;      // attempts to find a non-overlapping pos

// ensure #no starts visible at load
window.addEventListener('DOMContentLoaded', () => placeNoRandomly(true));

// Move "No" when pointer enters it (desktop) or touch starts (mobile)
noBtn.addEventListener('pointerenter', () => placeNoRandomly(true));
noBtn.addEventListener('touchstart', (e) => {
  // prevent the following click from triggering yes accidentally on some devices
  e.preventDefault();
  placeNoRandomly(true);
}, {passive:false});

// extra cheeky: if pointer moves extremely close to the button, it jumps (makes it harder)
document.addEventListener('pointermove', (ev) => {
  const rect = noBtn.getBoundingClientRect();
  const cx = rect.left + rect.width/2, cy = rect.top + rect.height/2;
  const dx = ev.clientX - cx, dy = ev.clientY - cy;
  const dist = Math.hypot(dx,dy);
  // threshold depends on button size (smaller screens -> smaller threshold)
  const threshold = Math.max(100, Math.min(220, Math.max(rect.width, rect.height) * 3));
  if (dist < threshold) {
    placeNoRandomly(true);
  }
});

// Keep No visible if window resizes (or orientation change)
window.addEventListener('resize', () => {
  ensureNoInBounds();
});

// Clicking Yes: celebration
yesBtn.addEventListener('click', () => {
  yesBtn.classList.add('clicked');
  greet.style.opacity = '1';
  explodeConfetti();
  // optional: after some seconds remove pulse
  setTimeout(()=> yesBtn.classList.remove('clicked'), 2400);
});

// Function: place No randomly while keeping inside viewport and avoiding Yes
function placeNoRandomly(avoidYes = true) {
  // measure button size
  // Use offsetWidth/Height for reliable numbers before layout settles
  const btnW = noBtn.offsetWidth || 80;
  const btnH = noBtn.offsetHeight || 40;

  // compute safe ranges
  const maxX = Math.max(MARGIN, window.innerWidth - btnW - MARGIN);
  const maxY = Math.max(MARGIN, window.innerHeight - btnH - MARGIN);

  let attempt = 0, x = MARGIN, y = MARGIN;

  while (attempt < MAX_ATTEMPTS) {
    attempt++;
    x = Math.floor(Math.random() * (maxX - MARGIN + 1)) + MARGIN;
    y = Math.floor(Math.random() * (maxY - MARGIN + 1)) + MARGIN;

    if (!avoidYes) break;

    const yesRect = yesBtn.getBoundingClientRect();
    // check overlap: if new rect intersects yes rect, it's overlapping
    const willOverlap = !(x + btnW < yesRect.left || x > yesRect.right || y + btnH < yesRect.top || y > yesRect.bottom);
    if (!willOverlap) break;
    // else continue trying
  }

  // ensure final coordinates are within bounds (safety)
  x = Math.min(Math.max(MARGIN, x), maxX);
  y = Math.min(Math.max(MARGIN, y), maxY);

  // apply to No button
  noBtn.style.left = x + 'px';
  noBtn.style.top  = y + 'px';
  noBtn.style.visibility = 'visible';
  // ensure it sits above confetti / container
  noBtn.style.zIndex = '9999';
}

// Ensure current position fits the viewport (called on resize)
function ensureNoInBounds() {
  const rect = noBtn.getBoundingClientRect();
  const btnW = rect.width, btnH = rect.height;
  const maxX = Math.max(MARGIN, window.innerWidth - btnW - MARGIN);
  const maxY = Math.max(MARGIN, window.innerHeight - btnH - MARGIN);

  let newLeft = rect.left, newTop = rect.top;
  if (rect.left < MARGIN || rect.left > maxX) newLeft = Math.min(Math.max(MARGIN, rect.left), maxX);
  if (rect.top  < MARGIN || rect.top  > maxY) newTop  = Math.min(Math.max(MARGIN, rect.top), maxY);

  noBtn.style.left = newLeft + 'px';
  noBtn.style.top  = newTop + 'px';
}

// Confetti generator (simple colorful circles)
function explodeConfetti() {
  const count = Math.min(120, Math.floor(window.innerWidth / 6)); // scale confetti by width
  for (let i=0;i<count;i++){
    const c = document.createElement('div');
    c.className = 'confetti';
    c.style.left = (Math.random() * window.innerWidth) + 'px';
    c.style.background = `hsl(${Math.random()*360} 90% 55%)`;
    c.style.width = (8 + Math.random()*8) + 'px';
    c.style.height = c.style.width;
    c.style.borderRadius = (Math.random()*50)+'%';
    c.style.animationDuration = (1.6 + Math.random()*2.2) + 's';
    c.style.opacity = '0.95';
    document.body.appendChild(c);
    // cleanup
    setTimeout(()=> c.remove(), 4200);
  }
}
</script>
</body>
</html>
