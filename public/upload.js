const form = document.getElementById('upload-form');
const titleInput = document.getElementById('title');
const descriptionInput = document.getElementById('description');
const fileInput = document.getElementById('video-file');
const errorEl = document.getElementById('upload-error');

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  errorEl.textContent = '';

  const formData = new FormData();
  formData.append('title', titleInput.value.trim());
  formData.append('description', descriptionInput.value.trim());
  formData.append('video', fileInput.files[0]);

  const res = await fetch('videos.php', {
    method: 'POST',
    body: formData,
  });

  if (res.ok) {
    window.location.href = 'index.php';
  } else {
    const data = await res.json();
    errorEl.textContent = data.error || '投稿に失敗しました';
  }
});
