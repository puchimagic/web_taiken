const accountForm = document.getElementById('account-form');
const usernameInput = document.getElementById('account-username');
const newPasswordInput = document.getElementById('account-new-password');
const emailInput = document.getElementById('account-email');
const phoneInput = document.getElementById('account-phone');
const postalCodeInput = document.getElementById('account-postal-code');
const prefectureSelect = document.getElementById('account-prefecture');
const cityInput = document.getElementById('account-city');
const addressLineInput = document.getElementById('account-address-line');
const errorEl = document.getElementById('account-error');
const successEl = document.getElementById('account-success');

const postalLookupBtn = document.getElementById('postal-lookup-btn');
const postalLookupStatus = document.getElementById('postal-lookup-status');

postalLookupBtn.addEventListener('click', async () => {
  const code = postalCodeInput.value.replace(/[^0-9]/g, '');
  if (code.length !== 7) {
    postalLookupStatus.textContent = '郵便番号は7桁の数字で入力してください';
    return;
  }

  postalLookupStatus.textContent = '住所を検索中...';

  try {
    const res = await fetch(`postal_lookup.php?code=${code}`);
    const data = await res.json();

    if (res.ok) {
      prefectureSelect.value = data.prefecture;
      cityInput.value = data.city + data.town;
      postalLookupStatus.textContent = '住所を自動入力しました';
    } else {
      postalLookupStatus.textContent = data.error || '住所が見つかりませんでした';
    }
  } catch (e) {
    postalLookupStatus.textContent = '住所の検索に失敗しました';
  }
});

accountForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  errorEl.textContent = '';
  successEl.textContent = '';

  const res = await fetch('auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'update_profile',
      username: usernameInput.value.trim(),
      new_password: newPasswordInput.value,
      email: emailInput.value.trim(),
      phone: phoneInput.value.trim(),
      postal_code: postalCodeInput.value.trim(),
      prefecture: prefectureSelect.value,
      city: cityInput.value.trim(),
      address_line: addressLineInput.value.trim(),
    }),
  });
  const data = await res.json();

  if (res.ok) {
    successEl.textContent = '保存しました';
    newPasswordInput.value = '';
    setTimeout(() => window.location.reload(), 800);
  } else {
    errorEl.textContent = data.error || '保存に失敗しました';
  }
});
