const logoutBtn = document.getElementById('logout-btn');

logoutBtn.addEventListener('click', async () => {
  await fetch('auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'logout' }),
  });
  window.location.href = 'login.php';
});
