/* ===================================================================
   hero-scene.js — خلفية ثلاثية الأبعاد لصفحة الهبوط (three.js r128)
   - جمرات ذهبية تطفو في العمق وتتحرك مع مؤشر الفأرة (Parallax)
   - نجمة ثمانية (زخرفة إسلامية) تدور ببطء خلف المبخرة
   - دخان بخور يتصاعد من غطاء المبخرة (#hero-mabkhara) ويتلاشى تدريجيًا
   إذا لم يتوفر WebGL أو فشل تحميل three.js تبقى خطوط الدخان SVG كما هي.
   =================================================================== */
(() => {
  const hero = document.getElementById('landing-hero');
  const canvas = document.getElementById('hero-scene');
  const mabkhara = document.getElementById('hero-mabkhara');
  if (!hero || !canvas || !mabkhara || !window.THREE) return;

  const THREE = window.THREE;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isSmall = window.matchMedia('(max-width: 960px)').matches;

  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: false, powerPreference: 'low-power' });
  } catch (e) {
    return; // WebGL غير مدعوم — نكتفي بالنسخة الثابتة
  }
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
  renderer.setClearColor(0x000000, 0);

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
  camera.position.set(0, 0, 14);

  /* ---------- قوام (Textures) مولّدة داخل المتصفح بدل ملفات صور ---------- */
  function radialTexture(size, stops) {
    const c = document.createElement('canvas');
    c.width = c.height = size;
    const g = c.getContext('2d');
    const grad = g.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    stops.forEach(([o, col]) => grad.addColorStop(o, col));
    g.fillStyle = grad;
    g.fillRect(0, 0, size, size);
    return new THREE.CanvasTexture(c);
  }

  // نفخة دخان غير منتظمة: عدة دوائر ناعمة متداخلة
  function smokeTexture(size) {
    const c = document.createElement('canvas');
    c.width = c.height = size;
    const g = c.getContext('2d');
    for (let i = 0; i < 9; i++) {
      const x = size / 2 + (Math.random() - 0.5) * size * 0.32;
      const y = size / 2 + (Math.random() - 0.5) * size * 0.32;
      const r = size * (0.18 + Math.random() * 0.2);
      const grad = g.createRadialGradient(x, y, 0, x, y, r);
      grad.addColorStop(0, 'rgba(255,255,255,0.32)');
      grad.addColorStop(1, 'rgba(255,255,255,0)');
      g.fillStyle = grad;
      g.fillRect(0, 0, size, size);
    }
    return new THREE.CanvasTexture(c);
  }

  const emberTex = radialTexture(64, [[0, 'rgba(255,255,255,1)'], [0.25, 'rgba(255,230,180,0.8)'], [1, 'rgba(255,200,120,0)']]);
  const smokeTex = smokeTexture(128);

  /* ---------- الجمرات الذهبية ---------- */
  const EMBERS = isSmall ? 140 : 320;
  const emberPos = new Float32Array(EMBERS * 3);
  const emberSpeed = new Float32Array(EMBERS);
  const emberPhase = new Float32Array(EMBERS);
  for (let i = 0; i < EMBERS; i++) {
    emberPos[i * 3] = (Math.random() - 0.5) * 30;
    emberPos[i * 3 + 1] = (Math.random() - 0.5) * 18;
    emberPos[i * 3 + 2] = -12 + Math.random() * 16;
    emberSpeed[i] = 0.12 + Math.random() * 0.35;
    emberPhase[i] = Math.random() * Math.PI * 2;
  }
  const emberGeo = new THREE.BufferGeometry();
  emberGeo.setAttribute('position', new THREE.BufferAttribute(emberPos, 3));
  const embers = new THREE.Points(emberGeo, new THREE.PointsMaterial({
    size: 0.16, map: emberTex, color: 0xE8B868, transparent: true, opacity: 0.85,
    depthWrite: false, blending: THREE.AdditiveBlending, sizeAttenuation: true,
  }));
  const emberGroup = new THREE.Group();
  emberGroup.add(embers);
  scene.add(emberGroup);

  /* ---------- النجمة الثمانية ---------- */
  function eightPointStar(R) {
    const shape = new THREE.Shape();
    const inner = R * Math.cos(Math.PI / 4) / Math.cos(Math.PI / 8); // تقاطع مربعين متراكبين
    for (let i = 0; i < 16; i++) {
      const a = (i / 16) * Math.PI * 2 + Math.PI / 8;
      const r = i % 2 === 0 ? R : inner;
      const x = Math.cos(a) * r, y = Math.sin(a) * r;
      i === 0 ? shape.moveTo(x, y) : shape.lineTo(x, y);
    }
    shape.closePath();
    const geo = new THREE.ExtrudeGeometry(shape, { depth: 0.35, bevelEnabled: false });
    geo.center();
    return new THREE.LineSegments(new THREE.EdgesGeometry(geo), new THREE.LineBasicMaterial({
      color: 0xC6A15B, transparent: true, opacity: 0.28, depthWrite: false,
    }));
  }
  const ornament = new THREE.Group();
  const starOuter = eightPointStar(3.6);
  const starInner = eightPointStar(2.2);
  starInner.material.opacity = 0.18;
  const ring = new THREE.LineLoop(
    new THREE.BufferGeometry().setFromPoints(
      Array.from({ length: 96 }, (_, i) => new THREE.Vector3(Math.cos(i / 96 * Math.PI * 2) * 4.3, Math.sin(i / 96 * Math.PI * 2) * 4.3, 0))
    ),
    new THREE.LineBasicMaterial({ color: 0xC6A15B, transparent: true, opacity: 0.14, depthWrite: false })
  );
  ornament.add(starOuter, starInner, ring);
  ornament.position.z = -5;
  scene.add(ornament);

  /* ---------- دخان البخور ---------- */
  const SMOKE = isSmall ? 55 : 95;
  const SPAWN_EVERY = isSmall ? 0.11 : 0.065; // ثانية بين كل نفخة وأخرى
  const colorBase = new THREE.Color(0xEBCB98);  // دافئ قرب الجمرة
  const colorTop = new THREE.Color(0xCFC6BA);   // رمادي عاجي عند التلاشي
  const puffs = [];
  for (let i = 0; i < SMOKE; i++) {
    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
      map: smokeTex, color: colorBase.clone(), transparent: true, opacity: 0, depthWrite: false,
    }));
    sprite.visible = false;
    scene.add(sprite);
    puffs.push({ sprite, alive: false, age: 0, life: 1, seed: 0, vy: 0, spin: 0, x0: 0 });
  }

  /* ---------- تحويل موضع غطاء المبخرة من الشاشة إلى إحداثيات المشهد ---------- */
  const source = new THREE.Vector3();
  const tmp = new THREE.Vector3();
  let width = 1, height = 1;

  function updateSource() {
    const c = canvas.getBoundingClientRect();
    const m = mabkhara.getBoundingClientRect();
    // غطاء المبخرة في الـ SVG عند (120, 84) من viewBox 240×400
    const px = m.left - c.left + m.width * (120 / 240);
    const py = m.top - c.top + m.height * (84 / 400);
    tmp.set((px / width) * 2 - 1, -(py / height) * 2 + 1, 0.5).unproject(camera);
    tmp.sub(camera.position).normalize();
    const dist = -camera.position.z / tmp.z;
    source.copy(camera.position).addScaledVector(tmp, dist);
  }

  function resize() {
    width = canvas.clientWidth || hero.clientWidth;
    height = canvas.clientHeight || hero.clientHeight;
    renderer.setSize(width, height, false);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    camera.updateMatrixWorld(); // ضروري قبل unproject — وإلا تُحسب المصفوفة فقط عند أول render
    updateSource();
    // النجمة خلف المبخرة، أعلى قليلًا منها
    ornament.position.x = source.x * 1.25;
    ornament.position.y = source.y + 0.6;
  }

  /* ---------- المحاكاة ---------- */
  const smoothstep = (a, b, x) => { const t = Math.min(Math.max((x - a) / (b - a), 0), 1); return t * t * (3 - 2 * t); };
  let spawnTimer = 0;
  let time = 0;
  const pointer = { x: 0, y: 0 };

  function spawn() {
    const p = puffs.find(q => !q.alive);
    if (!p) return;
    p.alive = true;
    p.age = 0;
    p.life = 4.8 + Math.random() * 2.4;
    p.seed = Math.random() * 100;
    p.vy = 0.85 + Math.random() * 0.45;
    p.spin = (Math.random() - 0.5) * 0.6;
    p.x0 = source.x + (Math.random() - 0.5) * 0.12;
    p.sprite.position.set(p.x0, source.y, (Math.random() - 0.5) * 0.4);
    p.sprite.material.rotation = Math.random() * Math.PI * 2;
    p.sprite.visible = true;
  }

  function step(dt) {
    time += dt;

    // الجمرات: صعود بطيء مع تمايل، وتعود للأسفل عند خروجها من المشهد
    for (let i = 0; i < EMBERS; i++) {
      emberPos[i * 3 + 1] += emberSpeed[i] * dt;
      emberPos[i * 3] += Math.sin(time * 0.4 + emberPhase[i]) * 0.004;
      if (emberPos[i * 3 + 1] > 9) emberPos[i * 3 + 1] = -9;
    }
    emberGeo.attributes.position.needsUpdate = true;
    embers.material.opacity = 0.7 + Math.sin(time * 1.3) * 0.12;

    // Parallax ناعم مع حركة المؤشر
    emberGroup.rotation.y += (pointer.x * 0.18 - emberGroup.rotation.y) * 0.04;
    emberGroup.rotation.x += (-pointer.y * 0.1 - emberGroup.rotation.x) * 0.04;

    ornament.rotation.y = Math.sin(time * 0.25) * 0.55 + pointer.x * 0.2;
    ornament.rotation.x = Math.cos(time * 0.2) * 0.25 - pointer.y * 0.12;
    starOuter.rotation.z = time * 0.05;
    starInner.rotation.z = -time * 0.08;

    // الدخان
    spawnTimer += dt;
    while (spawnTimer >= SPAWN_EVERY) { spawnTimer -= SPAWN_EVERY; spawn(); }
    const wind = Math.sin(time * 0.35) * 0.25;
    for (const p of puffs) {
      if (!p.alive) continue;
      p.age += dt;
      const t = p.age / p.life;
      if (t >= 1) { p.alive = false; p.sprite.visible = false; continue; }
      const s = p.sprite;
      s.position.y += p.vy * dt * (1 - t * 0.45);
      // التواء الدخان: يتسع التمايل كلما ارتفع
      const sway = Math.sin(p.age * 1.3 + p.seed) * (0.08 + t * 0.9) + Math.sin(p.age * 0.55 + p.seed * 2) * t * 0.5;
      s.position.x = p.x0 + sway + wind * t * 2.2;
      // خيط رفيع قرب الجمرة يتسع تدريجيًا كلما ارتفع
      const scale = 0.22 + Math.pow(t, 1.15) * 2.8;
      s.scale.set(scale, scale, 1);
      s.material.rotation += p.spin * dt;
      s.material.opacity = 0.24 * smoothstep(0, 0.1, t) * Math.pow(1 - t, 1.4);
      s.material.color.copy(colorBase).lerp(colorTop, smoothstep(0, 0.6, t));
    }
  }

  /* ---------- التشغيل ---------- */
  resize();
  if ('ResizeObserver' in window) new ResizeObserver(resize).observe(hero);
  else window.addEventListener('resize', resize);

  // تشغيل مسبق حتى يظهر الدخان مكتملًا من أول إطار
  for (let i = 0; i < 150; i++) step(1 / 30);
  renderer.render(scene, camera);
  hero.classList.add('is-3d');

  if (reduceMotion) return; // إطار ثابت فقط لمن يفضّل تقليل الحركة

  window.addEventListener('pointermove', (e) => {
    pointer.x = (e.clientX / window.innerWidth) * 2 - 1;
    pointer.y = (e.clientY / window.innerHeight) * 2 - 1;
  }, { passive: true });

  let visible = true;
  let rafId = null;
  let last = performance.now();

  function frame(now) {
    const dt = Math.min((now - last) / 1000, 0.05);
    last = now;
    updateSource(); // المبخرة تطفو بحركة CSS — نتبعها في كل إطار
    step(dt);
    renderer.render(scene, camera);
    rafId = requestAnimationFrame(frame);
  }

  function setRunning(run) {
    if (run && rafId === null) { last = performance.now(); rafId = requestAnimationFrame(frame); }
    if (!run && rafId !== null) { cancelAnimationFrame(rafId); rafId = null; }
  }

  // إيقاف الرسم عند التمرير بعيدًا عن الواجهة أو إخفاء التبويب (توفير البطارية)
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; setRunning(visible && !document.hidden); })
      .observe(hero);
  }
  document.addEventListener('visibilitychange', () => setRunning(visible && !document.hidden));
  setRunning(true);
})();
