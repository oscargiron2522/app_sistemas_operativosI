// Utilidades compartidas por todas las vistas
const LC = {
  csrf: document.querySelector('meta[name="csrf-token"]')?.content,

  esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  },

  async get(url) {
    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!r.ok) throw new Error((await r.json().catch(() => ({}))).message || ('Error ' + r.status));
    return r.json();
  },

  async post(url, body) {
    const r = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': LC.csrf },
      body: JSON.stringify(body || {})
    });
    const j = await r.json().catch(() => ({}));
    if (!r.ok) {
      const detalle = j.errors ? Object.values(j.errors).flat().join(' ') : '';
      throw new Error(detalle || j.message || ('Error ' + r.status));
    }
    return j;
  },

  // kB -> texto legible
  kb(v) {
    v = Number(v) || 0;
    if (v >= 1048576) return (v / 1048576).toFixed(1) + ' GB';
    if (v >= 1024) return (v / 1024).toFixed(0) + ' MB';
    return v + ' kB';
  },

  segs(s) {
    s = Math.floor(s);
    const d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60);
    return (d ? d + ' d ' : '') + String(h).padStart(2, '0') + ' h ' + (d ? '' : String(m).padStart(2, '0') + ' m');
  },

  toast(msg, tipo = 'success') {
    let c = document.getElementById('toasts');
    if (!c) { c = document.createElement('div'); c.id = 'toasts'; c.className = 'toast-container position-fixed top-0 end-0 p-3'; document.body.appendChild(c); }
    const el = document.createElement('div');
    el.className = 'toast align-items-center text-bg-' + tipo + ' border-0';
    el.innerHTML = '<div class="d-flex"><div class="toast-body">' + LC.esc(msg) + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
    c.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 4000 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }
};

// Menu lateral (hamburguesa)
document.getElementById('btn-menu')?.addEventListener('click', () => {
  document.body.classList.toggle('menu-cerrado');
  document.body.classList.toggle('menu-abierto');
});
