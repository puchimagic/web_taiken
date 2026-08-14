const form = document.getElementById('comment-form');
const messageInput = document.getElementById('comment-message');
const errorEl = document.getElementById('comment-error');
const videoId = form.dataset.videoId;

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  errorEl.textContent = '';

  const message = messageInput.value.trim();
  if (!message) return;

  const res = await fetch('comments.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ video_id: Number(videoId), message }),
  });

  if (res.ok) {
    window.location.reload();
  } else {
    const data = await res.json();
    errorEl.textContent = data.error || 'コメントに失敗しました';
  }
});
