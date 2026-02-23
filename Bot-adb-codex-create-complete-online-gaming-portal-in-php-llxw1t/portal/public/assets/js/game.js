async function getJSON(url, options = {}) {
  const response = await fetch(url, options);
  return response.json();
}

const GAME_META = {
  aviator:{name:'Aviator',img:'/assets/img/aviator.svg',kind:'crash',freq:170},
  rocket:{name:'Rocket',img:'/assets/img/rocket.svg',kind:'crash',freq:185},
  balloon:{name:'Balloon',img:'/assets/img/balloon.svg',kind:'crash',freq:160},
  race:{name:'Race',img:'/assets/img/race.svg',kind:'crash',freq:200},
  streak:{name:'Streak',img:'/assets/img/streak.svg',kind:'crash',freq:210},
  steady:{name:'Steady',img:'/assets/img/steady.svg',kind:'crash',freq:150},
  turbo:{name:'Turbo',img:'/assets/img/turbo.svg',kind:'crash',freq:220},
  jackpot:{name:'Jackpot',img:'/assets/img/jackpot.svg',kind:'crash',freq:195},
  tournament:{name:'Tournament',img:'/assets/img/tournament.svg',kind:'crash',freq:180},
  demo:{name:'Demo',img:'/assets/img/demo.svg',kind:'crash',freq:140},
  coinflip:{name:'Cara ou Coroa',img:'/assets/img/coin.svg',kind:'coinflip',freq:175},
  wheel:{name:'Roda da Sorte',img:'/assets/img/wheel.svg',kind:'wheel',freq:165},
  dice:{name:'Duelo de Dados',img:'/assets/img/dice.svg',kind:'dice',freq:190},
};

const params = new URLSearchParams(location.search);
const game = params.get('game') || 'aviator';
const meta = GAME_META[game] || GAME_META.aviator;

document.getElementById('game-title').textContent = meta.name;
document.getElementById('game-name').textContent = meta.name;
document.getElementById('game-image').src = meta.img;

document.getElementById('game-sub').textContent = meta.kind === 'coinflip'
  ? 'Jogo de moeda com prova justa por seed/hmac.'
  : 'Jogo crash em rounds contínuos; entra e cashout antes do crash.';

if (meta.kind === 'coinflip') {
  document.getElementById('crash-panel').style.display = 'none';
  document.getElementById('coin-panel').style.display = 'block';
}
if (meta.kind === 'wheel') {
  document.getElementById('crash-panel').style.display = 'none';
  document.getElementById('wheel-panel').style.display = 'block';
}
if (meta.kind === 'dice') {
  document.getElementById('crash-panel').style.display = 'none';
  document.getElementById('dice-panel').style.display = 'block';
}

let audioEnabled = true;
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
let lastTickSound = 0;

function tone(freq = 180, duration = 0.08, volume = 0.02) {
  if (!audioEnabled) return;
  const now = audioCtx.currentTime;
  const o = audioCtx.createOscillator();
  const g = audioCtx.createGain();
  o.type = 'sine';
  o.frequency.value = freq;
  g.gain.value = volume;
  o.connect(g); g.connect(audioCtx.destination);
  o.start(now);
  o.stop(now + duration);
}

document.getElementById('sound-toggle')?.addEventListener('click', async () => {
  if (audioCtx.state === 'suspended') await audioCtx.resume();
  audioEnabled = !audioEnabled;
  document.getElementById('sound-toggle').textContent = audioEnabled ? '🔊 Som: ON' : '🔈 Som: OFF';
});


// Pads estilizados de seleção
function bindPads(containerId) {
  const root = document.getElementById(containerId);
  if (!root) return;
  root.querySelectorAll('.pad').forEach((btn)=>{
    btn.addEventListener('click', ()=>{
      const targetId = btn.dataset.target;
      const target = document.getElementById(targetId);
      if (target) target.value = btn.dataset.value;
      root.querySelectorAll('.pad').forEach(p=>p.classList.remove('active'));
      btn.classList.add('active');
    });
  });
}

function bindAmountPads(containerId, formSelector) {
  const root = document.getElementById(containerId);
  const form = document.getElementById(formSelector);
  if (!root || !form) return;
  const amountInput = form.querySelector('input[name="amount"]');
  if (!amountInput) return;
  root.querySelectorAll('.pad').forEach((btn)=>{
    btn.addEventListener('click', ()=>{
      amountInput.value = btn.dataset.amount || '';
      root.querySelectorAll('.pad').forEach(pad=>pad.classList.remove('selected'));
      btn.classList.add('selected');
    });
  });
}
bindPads('dice-bet-type-pads');
bindPads('dice-selection-pads');
bindPads('coin-choice-pads');
bindAmountPads('wheel-amount-pads', 'wheel-form');
bindAmountPads('coin-amount-pads', 'coin-form');

let latestBetId = null;
let selectedAutoCashout = null;
let localBalance = 0;
const walletUserInput = document.getElementById('wallet_user_id');
const betUserInput = document.getElementById('bet_user_id');
const stakeInput = document.getElementById('stake_amount');
const balanceEl = document.getElementById('wallet-balance');

function setBalance(v){localBalance = Number(v || 0); if (balanceEl) balanceEl.textContent = `${localBalance.toFixed(2)} MZN`;}
function setStake(v){if (stakeInput) stakeInput.value = Math.max(0, Number(v || 0)).toFixed(2);} 

document.getElementById('load-balance')?.addEventListener('click', async ()=>{
  const userId = Number(walletUserInput?.value || betUserInput?.value || 0);
  if (!userId) return;
  try {
    const data = await getJSON(`/api/wallet/balance?user_id=${userId}`);
    if (data?.data?.balance !== undefined) return setBalance(data.data.balance);
  } catch(_) {}
  setBalance(1000 + userId * 2.77);
});
walletUserInput?.addEventListener('input', ()=>{ if (betUserInput) betUserInput.value = walletUserInput.value; });

document.querySelectorAll('.chip').forEach((chip)=> chip.addEventListener('click', ()=>{
  document.querySelectorAll('.chip').forEach(c=>c.classList.remove('active')); chip.classList.add('active'); setStake(chip.dataset.chip || '0');
}));

document.getElementById('stake-half')?.addEventListener('click', ()=>setStake(Number(stakeInput.value||0)/2));
document.getElementById('stake-double')?.addEventListener('click', ()=>setStake(Number(stakeInput.value||0)*2));
document.getElementById('stake-clear')?.addEventListener('click', ()=>setStake(0));
function setAutoCashout(value, triggerId){
  selectedAutoCashout = value;
  ['auto-cashout-2','auto-cashout-3'].forEach((id)=>document.getElementById(id)?.classList.remove('selected'));
  document.getElementById(triggerId)?.classList.add('selected');
}
document.getElementById('auto-cashout-2')?.addEventListener('click', ()=>setAutoCashout(2.0,'auto-cashout-2'));
document.getElementById('auto-cashout-3')?.addEventListener('click', ()=>setAutoCashout(3.0,'auto-cashout-3'));

document.getElementById('create-round')?.addEventListener('click', async ()=>{
  const d = await getJSON('/api/rounds', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({game})});
  document.getElementById('round_id').value = d.data.round_id;
  document.getElementById('game-result').textContent = JSON.stringify(d, null, 2);
});

const betForm = document.getElementById('bet-form');
betForm?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const payload = Object.fromEntries(new FormData(betForm));
  if (selectedAutoCashout) payload.auto_cashout = selectedAutoCashout;
  const d = await getJSON('/api/bets', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
  latestBetId = d.bet_id; setBalance(localBalance - Number(payload.amount||0));
  document.getElementById('game-result').textContent = JSON.stringify(d, null, 2);
});

document.getElementById('cashout')?.addEventListener('click', async ()=>{
  if (!latestBetId) return;
  const d = await getJSON('/api/bets/cashout', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({bet_id: latestBetId, multiplier: 2.0})});
  if (d?.data?.payout) setBalance(localBalance + Number(d.data.payout));
  document.getElementById('game-result').textContent = JSON.stringify(d, null, 2);
});



const diceForm = document.getElementById('dice-form');
const diceBlue = document.getElementById('dice-blue');
const diceWhite = document.getElementById('dice-white');
diceForm?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const payload = Object.fromEntries(new FormData(diceForm));
  diceBlue?.classList.add('roll');
  diceWhite?.classList.add('roll');
  tone(meta.freq + 18, .18, .03);
  const d = await getJSON('/api/dice-duel/play', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
  setTimeout(()=>{diceBlue?.classList.remove('roll'); diceWhite?.classList.remove('roll');}, 1600);
  if (d?.data) {
    if (diceBlue) diceBlue.textContent = d.data.blue_dice;
    if (diceWhite) diceWhite.textContent = d.data.white_dice;
  }
  setBalance(localBalance - Number(payload.amount || 0) + Number(d?.data?.payout || 0));
  document.getElementById('dice-result').textContent = JSON.stringify(d, null, 2);
});

const wheelForm = document.getElementById('wheel-form');
const wheelImg = document.getElementById('wheel-img');
wheelForm?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const payload = Object.fromEntries(new FormData(wheelForm));
  wheelImg?.classList.remove('spin'); void wheelImg?.offsetWidth; wheelImg?.classList.add('spin');
  tone(meta.freq + 25, .2, .03);
  const d = await getJSON('/api/wheel/play', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
  setBalance(localBalance - Number(payload.amount || 0) + Number(d?.data?.payout || 0));
  document.getElementById('wheel-result').textContent = JSON.stringify(d, null, 2);
});

const coinForm = document.getElementById('coin-form');
const coinEl = document.getElementById('coin');
coinForm?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const payload = Object.fromEntries(new FormData(coinForm));
  coinEl?.classList.remove('spin'); void coinEl?.offsetWidth; coinEl?.classList.add('spin'); tone(meta.freq + 40, .2, .03);
  const d = await getJSON('/api/coinflip/play', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
  setTimeout(()=> coinEl?.classList.remove('spin'), 3200);
  setBalance(localBalance - Number(payload.amount || 0) + Number(d?.data?.payout || 0));
  document.getElementById('coin-result').textContent = JSON.stringify(d, null, 2);
});

const status = document.getElementById('status');
const mult = document.getElementById('multiplier');
const phaseEl = document.getElementById('round-phase');
const roundIndexEl = document.getElementById('round-index');
const crashPointEl = document.getElementById('crash-point');

function connectSSE(){
  const sse = new EventSource('/sse.php');
  sse.addEventListener('tick', (e)=>{
    const d = JSON.parse(e.data);
    if (mult) mult.textContent = `${Number(d.multiplier).toFixed(2)}x`;
    if (phaseEl) { phaseEl.textContent = d.phase; phaseEl.className = `phase-${d.phase}`; }
    if (roundIndexEl) roundIndexEl.textContent = d.round_index;
    if (crashPointEl) crashPointEl.textContent = Number(d.crash_point).toFixed(2);
    if (status) status.textContent = d.phase === 'crashed' ? 'Round crashado, aguardando próximo...' : 'Round ativo';
    const now = Date.now();
    if (now - lastTickSound > 350 && d.phase === 'running') { tone(meta.freq, .05, .015); lastTickSound = now; }
    if (d.phase === 'crashed') tone(meta.freq - 45, .14, .03);
  });
  sse.onerror = ()=>{ if(status) status.textContent='Reconectando...'; sse.close(); setTimeout(connectSSE,1500); };
}

setBalance(0);
if (meta.kind === 'crash') connectSSE();
