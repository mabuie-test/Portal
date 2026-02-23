async function getJSON(url){ const r = await fetch(url); return r.json(); }
async function postJSON(url,p){ const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(p)}); return r.json(); }

document.getElementById('admin-users')?.addEventListener('click', async ()=>{
  document.getElementById('admin-users-result').textContent = JSON.stringify(await getJSON('/api/admin/users'), null, 2);
});
document.getElementById('admin-pending')?.addEventListener('click', async ()=>{
  document.getElementById('admin-pending-result').textContent = JSON.stringify(await getJSON('/api/admin/payments/pending'), null, 2);
});
document.getElementById('admin-report')?.addEventListener('click', async ()=>{
  document.getElementById('admin-report-result').textContent = JSON.stringify(await getJSON('/api/admin/reports/financial'), null, 2);
});

document.getElementById('admin-verify-form')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const p = Object.fromEntries(new FormData(e.target));
  document.getElementById('admin-action-result').textContent = JSON.stringify(await postJSON('/api/admin/payments/verify', p), null, 2);
});
document.getElementById('admin-reject-form')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const p = Object.fromEntries(new FormData(e.target));
  document.getElementById('admin-action-result').textContent = JSON.stringify(await postJSON('/api/admin/payments/reject', p), null, 2);
});
document.getElementById('admin-game-form')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const p = Object.fromEntries(new FormData(e.target));
  document.getElementById('admin-game-result').textContent = JSON.stringify(await postJSON('/api/admin/games/toggle', p), null, 2);
});

function parseJsonField(value, fallback = []) {
  if (!value) return fallback;
  try { return JSON.parse(value); } catch (_) { return fallback; }
}

document.getElementById('admin-football-create')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const p = Object.fromEntries(new FormData(e.target));
  p.odds = parseJsonField(p.odds_json, []);
  delete p.odds_json;
  document.getElementById('admin-football-output').textContent = JSON.stringify(await postJSON('/api/admin/football/events', p), null, 2);
});

document.getElementById('admin-football-delete')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const p = Object.fromEntries(new FormData(e.target));
  document.getElementById('admin-football-output').textContent = JSON.stringify(await postJSON('/api/admin/football/events/delete', p), null, 2);
});

document.getElementById('admin-football-postpone')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const p = Object.fromEntries(new FormData(e.target));
  document.getElementById('admin-football-output').textContent = JSON.stringify(await postJSON('/api/admin/football/events/postpone', p), null, 2);
});

document.getElementById('admin-football-result-form')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const p = Object.fromEntries(new FormData(e.target));
  document.getElementById('admin-football-output').textContent = JSON.stringify(await postJSON('/api/admin/football/events/result', p), null, 2);
});

document.getElementById('admin-football-odds')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const p = Object.fromEntries(new FormData(e.target));
  p.odds = parseJsonField(p.odds_json, []);
  delete p.odds_json;
  document.getElementById('admin-football-output').textContent = JSON.stringify(await postJSON('/api/admin/football/events/odds', p), null, 2);
});
