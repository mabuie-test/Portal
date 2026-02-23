async function postJSON(url, payload){
  const r = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
  return r.json();
}

function switchTab(name){
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === name));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.id === `tab-${name}`));
}

document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', ()=> switchTab(btn.dataset.tab));
});

document.querySelectorAll('[data-switch]').forEach(link => {
  link.addEventListener('click', (e)=>{
    e.preventDefault();
    switchTab(link.dataset.switch);
  });
});

const reg = document.getElementById('register-form');
reg?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const payload = Object.fromEntries(new FormData(reg));
  const d = await postJSON('/api/account/register', payload);
  const pretty = d?.user_code || (d?.user_id ? String(d.user_id).padStart(5,'0') : null);
  document.getElementById('register-result').textContent = pretty ? `Conta criada! ID Jogador: ${pretty}\n` + JSON.stringify(d, null, 2) : JSON.stringify(d, null, 2);
});

const login = document.getElementById('login-form');
login?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const payload = Object.fromEntries(new FormData(login));
  const d = await postJSON('/api/account/login', payload);
  document.getElementById('login-result').textContent = JSON.stringify(d, null, 2);
});

const req = document.getElementById('reset-request-form');
req?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const payload = Object.fromEntries(new FormData(req));
  const d = await postJSON('/api/account/password/request-reset', payload);
  document.getElementById('reset-result').textContent = JSON.stringify(d, null, 2);
});

const reset = document.getElementById('reset-form');
reset?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const payload = Object.fromEntries(new FormData(reset));
  const d = await postJSON('/api/account/password/reset', payload);
  document.getElementById('reset-result').textContent = JSON.stringify(d, null, 2);
});
