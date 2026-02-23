async function getJSON(url){ const r = await fetch(url); return r.json(); }
async function postJSON(url,payload){ const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}); return r.json(); }

const profileForm = document.getElementById('profile-form');
profileForm?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const userId = Object.fromEntries(new FormData(profileForm)).user_id;
  const d = await getJSON(`/api/account/profile?user_id=${userId}`);
  document.getElementById('profile-result').textContent = JSON.stringify(d, null, 2);
  if (d?.data?.user_code) document.getElementById('user-code').textContent = d.data.user_code;
  if (d?.data?.avatar_url) document.getElementById('avatar-preview').src = d.data.avatar_url;
});

const prefsForm = document.getElementById('prefs-form');
prefsForm?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const data = Object.fromEntries(new FormData(prefsForm));
  const payload = {
    user_id: Number(data.user_id),
    preferences: {
      display_name: data.display_name || '',
      theme: data.theme || 'dark',
      sound_enabled: data.sound_enabled === 'true',
      avatar_url: data.avatar_url || ''
    }
  };
  const d = await postJSON('/api/account/preferences', payload);
  document.getElementById('prefs-result').textContent = JSON.stringify(d, null, 2);
});

const betsForm = document.getElementById('bets-history-form');
betsForm?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const userId = Object.fromEntries(new FormData(betsForm)).user_id;
  const d = await getJSON(`/api/account/bets/history?user_id=${userId}`);
  document.getElementById('bets-history-result').textContent = JSON.stringify(d, null, 2);
});

const wForm = document.getElementById('withdrawals-form');
wForm?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const userId = Object.fromEntries(new FormData(wForm)).user_id;
  const d = await getJSON(`/api/account/withdrawals/history?user_id=${userId}`);
  document.getElementById('withdrawals-result').textContent = JSON.stringify(d, null, 2);
});
