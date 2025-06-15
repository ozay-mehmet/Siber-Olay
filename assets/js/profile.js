document.addEventListener('DOMContentLoaded', function() {
  const fileInput = document.getElementById('avatarUploadInput');
  const avatarPreview = document.getElementById('avatarPreview');

  if (fileInput && avatarPreview) {
    fileInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(event) {
          if (avatarPreview.tagName.toLowerCase() === 'img') {
            avatarPreview.src = event.target.result;
          } else if (avatarPreview.tagName.toLowerCase() === 'svg') {
          }
        }
        reader.readAsDataURL(this.files[0]);
      }
    });
  }

  document.querySelectorAll('.btn-edit').forEach(function(btn) {
  btn.addEventListener('click', function() {
    const eventItem = btn.closest('.event-item');
    const editForm = eventItem.querySelector('.edit-event-form');
    const eventTitle = eventItem.querySelector('.event-title');
    const eventDesc = eventItem.querySelector('.event-desc');
    document.querySelectorAll('.edit-event-form').forEach(f => { if (f !== editForm) f.style.display = 'none'; });
    document.querySelectorAll('.event-title').forEach(t => { if (t !== eventTitle) t.style.opacity = '1'; });
    document.querySelectorAll('.event-desc').forEach(d => { if (d !== eventDesc) d.style.opacity = '1'; });
    // Animasyonlu geçiş
    eventTitle.style.transition = 'opacity 0.3s';
    eventDesc.style.transition = 'opacity 0.3s';
    eventTitle.style.opacity = '0.3';
    eventDesc.style.opacity = '0.3';
    setTimeout(() => {
      editForm.style.display = 'block';
      eventTitle.style.opacity = '0';
      eventDesc.style.opacity = '0';
      }, 200);
    });
  });
  document.querySelectorAll('.btn-cancel-edit').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const editForm = btn.closest('.edit-event-form');
      const eventItem = btn.closest('.event-item');
      const eventTitle = eventItem.querySelector('.event-title');
      const eventDesc = eventItem.querySelector('.event-desc');
      editForm.style.display = 'none';
      eventTitle.style.opacity = '1';
      eventDesc.style.opacity = '1';
    });
  });

  const avatarForm = document.querySelector('form.avatar-form');
  if (avatarForm) {
    avatarForm.addEventListener('submit', function() {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
      }
    });
  }

  const messageElement = document.querySelector('.message');
  if (messageElement) {
    setTimeout(() => {
      messageElement.style.transition = 'opacity 0.7s, transform 0.7s, height 0.7s, padding 0.7s, margin 0.7s';
      messageElement.style.opacity = '0';
      messageElement.style.transform = 'scale(0.9)';
      messageElement.style.paddingTop = '0';
      messageElement.style.paddingBottom = '0';
      messageElement.style.marginTop = '0';
      messageElement.style.marginBottom = '0';
      messageElement.style.height = '0';
      messageElement.style.borderWidth = '0';
      setTimeout(() => {
        messageElement.remove();
      }, 700);
    }, 6000);
  }
});