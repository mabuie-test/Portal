// Lobby interactions
const tiles = document.querySelectorAll('.game-tile');
tiles.forEach((tile, idx) => {
  tile.style.animation = `fadeIn .3s ease ${idx * 0.04}s both`;
});

document.addEventListener('DOMContentLoaded', ()=> {
  const style = document.createElement('style');
  style.textContent = '@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}';
  document.head.appendChild(style);
});
