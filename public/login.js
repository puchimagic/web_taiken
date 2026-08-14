const tabBtns = document.querySelectorAll('.tab-btn');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const loginError = document.getElementById('login-error');
const registerError = document.getElementById('register-error');

tabBtns.forEach((btn) => {
  btn.addEventListener('click', () => {
    tabBtns.forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');

    if (btn.dataset.tab === 'login') {
      loginForm.classList.remove('hidden');
      registerForm.classList.add('hidden');
    } else {
      loginForm.classList.add('hidden');
      registerForm.classList.remove('hidden');
    }
  });
});

loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  loginError.textContent = '';

  const username = document.getElementById('login-username').value.trim();
  const password = document.getElementById('login-password').value;

  const res = await fetch('auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'login', username, password }),
  });
  const data = await res.json();

  if (res.ok) {
    window.location.href = 'index.php';
  } else {
    loginError.textContent = data.error || 'ログインに失敗しました';
  }
});

registerForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  registerError.textContent = '';

  const username = document.getElementById('register-username').value.trim();
  const password = document.getElementById('register-password').value;

  const res = await fetch('auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'register', username, password }),
  });
  const data = await res.json();

  if (res.ok) {
    window.location.href = 'index.php';
  } else {
    registerError.textContent = data.error || '登録に失敗しました';
  }
});
