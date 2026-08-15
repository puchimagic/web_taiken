const form = document.getElementById('upload-form');
const titleInput = document.getElementById('title');
const descriptionInput = document.getElementById('description');
const tagsInput = document.getElementById('tags');
const addressInput = document.getElementById('address');
const latitudeInput = document.getElementById('latitude');
const longitudeInput = document.getElementById('longitude');
const fileInput = document.getElementById('image-file');
const errorEl = document.getElementById('upload-error');

const geoBtn = document.getElementById('geo-btn');
const geoStatus = document.getElementById('geo-status');

geoBtn.addEventListener('click', () => {
  if (!navigator.geolocation) {
    geoStatus.textContent = 'このブラウザは位置情報取得に対応していません';
    return;
  }

  geoStatus.textContent = '現在地を取得中...';

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const lat = position.coords.latitude;
      const lon = position.coords.longitude;
      latitudeInput.value = lat;
      longitudeInput.value = lon;
      geoStatus.textContent = `緯度${lat.toFixed(4)} / 経度${lon.toFixed(4)} を取得。住所を調べています...`;

      try {
        const res = await fetch(`geocode.php?lat=${lat}&lon=${lon}`);
        const data = await res.json();
        if (res.ok && data.address) {
          addressInput.value = data.address;
          geoStatus.textContent = '現在地から住所を自動入力しました';
        } else {
          geoStatus.textContent = '住所の取得に失敗しました。手入力してください';
        }
      } catch (e) {
        geoStatus.textContent = '住所の取得に失敗しました。手入力してください';
      }
    },
    () => {
      geoStatus.textContent = '位置情報の取得が許可されませんでした';
    }
  );
});

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  errorEl.textContent = '';

  const formData = new FormData();
  formData.append('title', titleInput.value.trim());
  formData.append('description', descriptionInput.value.trim());
  formData.append('tags', tagsInput.value.trim());
  formData.append('address', addressInput.value.trim());
  formData.append('latitude', latitudeInput.value);
  formData.append('longitude', longitudeInput.value);
  formData.append('image', fileInput.files[0]);

  const res = await fetch('spots.php', {
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
