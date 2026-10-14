const header = document.querySelector('.site-header');
const toggle = document.querySelector('.menu-toggle');
const backdrop = document.querySelector('.nav-backdrop');

function setMenu(open) {
  if (!header || !toggle) return;
  header.classList.toggle('menu-open', open);
  toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  if (backdrop) backdrop.hidden = !open;
  document.body.classList.toggle('nav-open', open);
}

toggle?.addEventListener('click', () => setMenu(!header.classList.contains('menu-open')));
backdrop?.addEventListener('click', () => setMenu(false));
document.querySelectorAll('.site-nav a').forEach((link) => {
  link.addEventListener('click', () => setMenu(false));
});
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') setMenu(false);
});
document.querySelectorAll('[data-avatar-form] input[type="file"]').forEach((input) => {
  input.addEventListener('change', () => {
    if (input.files && input.files.length) input.form?.requestSubmit();
  });
});

function fold(value) {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[\s'"’.,!?！？。，、:：;；]/g, '');
}

document.querySelectorAll('[data-check]').forEach((button) => {
  button.addEventListener('click', () => {
    const root = button.closest('[data-exercise]');
    root?.querySelectorAll('label').forEach((label) => {
      const input = label.querySelector('input');
      const note = label.querySelector('.exercise-note');
      if (!input || !note) return;
      const value = fold(input.value.trim());
      const answer = fold(input.dataset.answer || '');
      const hint = fold(input.dataset.hint || '');
      const ok = value !== '' && (value === answer || value === hint || input.value.trim() === input.dataset.answer);
      note.textContent = ok ? 'Bien' : 'Revisala';
      note.className = 'exercise-note ' + (ok ? 'ok' : 'bad');
    });
  });
});

document.querySelectorAll('.vocab-tile').forEach((tile) => {
  tile.addEventListener('click', () => {
    const on = tile.classList.toggle('revealed');
    tile.setAttribute('aria-pressed', on ? 'true' : 'false');
  });
});

const players = [];
document.querySelectorAll('.play').forEach((button) => {
  const audio = new Audio(button.dataset.src || '');
  audio.preload = 'none';
  players.push(audio);
  const stopOthers = () => {
    players.forEach((item) => {
      if (item !== audio) item.pause();
    });
    document.querySelectorAll('audio').forEach((item) => {
      if (!item.paused) item.pause();
    });
  };
  button.addEventListener('click', () => {
    if (!audio.paused) {
      audio.pause();
      return;
    }
    stopOthers();
    audio.currentTime = 0;
    audio.play();
  });
  audio.addEventListener('play', () => button.classList.add('is-on'));
  audio.addEventListener('pause', () => button.classList.remove('is-on'));
  audio.addEventListener('ended', () => button.classList.remove('is-on'));
});

document.querySelectorAll('.audio-block audio').forEach((audio) => {
  audio.addEventListener('play', () => {
    players.forEach((item) => item.pause());
    document.querySelectorAll('.audio-block audio').forEach((item) => {
      if (item !== audio) item.pause();
    });
  });
});

const scrollTop = document.querySelector('.scroll-top');
scrollTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
window.addEventListener('scroll', () => {
  scrollTop?.classList.toggle('is-on', window.scrollY > 480);
}, { passive: true });
