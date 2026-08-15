const form = document.getElementById('comment-form');

if (form) {
  const messageInput = document.getElementById('comment-message');
  const errorEl = document.getElementById('comment-error');
  const spotId = form.dataset.spotId;
  const commentList = document.getElementById('comment-list');
  const commentCountEl = document.querySelector('.comment-panel-title .count');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    errorEl.textContent = '';

    const message = messageInput.value.trim();

    const res = await fetch('comments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ spot_id: Number(spotId), message }),
    });

    const data = await res.json();

    if (res.ok) {
      const emptyItem = commentList.querySelector('.empty');
      if (emptyItem) emptyItem.remove();

      const li = document.createElement('li');
      li.className = 'comment';
      li.innerHTML = `
        <div class="comment-avatar">${escapeHtml(data.username.slice(0, 1))}</div>
        <div class="comment-body">
          <div class="comment-meta">
            ${escapeHtml(data.username)}さん
            <span class="comment-time">${escapeHtml(data.created_at)}</span>
          </div>
          <div class="comment-message">${escapeHtml(data.message)}</div>
        </div>
      `;
      commentList.appendChild(li);

      if (commentCountEl) {
        commentCountEl.textContent = String(Number(commentCountEl.textContent) + 1);
      }

      messageInput.value = '';
    } else {
      errorEl.textContent = data.error || 'コメントに失敗しました';
    }
  });
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

document.querySelectorAll('.pill-btn[data-type]').forEach((btn) => {
  btn.addEventListener('click', async () => {
    if (btn.disabled) return;

    const spotId = Number(btn.dataset.spotId);
    const type = btn.dataset.type;

    const res = await fetch('reactions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ spot_id: spotId, type }),
    });

    if (!res.ok) return;

    const data = await res.json();
    btn.classList.toggle('active', data.active);
    const countEl = btn.querySelector('.reaction-count');
    if (countEl) countEl.textContent = data.count;
  });
});
