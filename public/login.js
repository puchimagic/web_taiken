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

const registerPostalCodeInput = document.getElementById('register-postal-code');
const registerPrefectureSelect = document.getElementById('register-prefecture');
const registerCityInput = document.getElementById('register-city');
const registerPostalLookupBtn = document.getElementById('register-postal-lookup-btn');
const registerPostalLookupStatus = document.getElementById('register-postal-lookup-status');

registerPostalLookupBtn.addEventListener('click', async () => {
  const code = registerPostalCodeInput.value.replace(/[^0-9]/g, '');
  if (code.length !== 7) {
    registerPostalLookupStatus.textContent = '郵便番号は7桁の数字で入力してください';
    return;
  }

  registerPostalLookupStatus.textContent = '住所を検索中...';

  try {
    const res = await fetch(`postal_lookup.php?code=${code}`);
    const data = await res.json();

    if (res.ok) {
      registerPrefectureSelect.value = data.prefecture;
      registerCityInput.value = data.city + data.town;
      registerPostalLookupStatus.textContent = '住所を自動入力しました';
    } else {
      registerPostalLookupStatus.textContent = data.error || '住所が見つかりませんでした';
    }
  } catch (e) {
    registerPostalLookupStatus.textContent = '住所の検索に失敗しました';
  }
});

registerForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  registerError.textContent = '';

  const username = document.getElementById('register-username').value.trim();
  const password = document.getElementById('register-password').value;
  const email = document.getElementById('register-email').value.trim();
  const phone = document.getElementById('register-phone').value.trim();
  const postalCode = registerPostalCodeInput.value.trim();
  const prefecture = registerPrefectureSelect.value;
  const city = registerCityInput.value.trim();
  const addressLine = document.getElementById('register-address-line').value.trim();

  const res = await fetch('auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'register',
      username,
      password,
      email,
      phone,
      postal_code: postalCode,
      prefecture,
      city,
      address_line: addressLine,
    }),
  });
  const data = await res.json();

  if (res.ok) {
    window.location.href = 'index.php';
  } else {
    registerError.textContent = data.error || '登録に失敗しました';
  }
});
