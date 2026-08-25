// Waste Management System - Main JS

document.addEventListener('DOMContentLoaded', function () {

  // Auto-dismiss alerts after 5s
  document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
    setTimeout(function () {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }, 5000);
  });

  // Confirm before status-changing actions (admin)
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!confirm(this.dataset.confirm)) e.preventDefault();
    });
  });

  // Image preview on file input
  const imageInput = document.querySelector('input[type="file"][name="image"]');
  if (imageInput) {
    imageInput.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;

      // Validate size (5MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('File size must be under 5MB.');
        this.value = '';
        return;
      }

      let preview = document.getElementById('imagePreview');
      if (!preview) {
        preview = document.createElement('img');
        preview.id = 'imagePreview';
        preview.className = 'img-thumbnail mt-2';
        preview.style.maxHeight = '200px';
        this.parentNode.appendChild(preview);
      }
      const reader = new FileReader();
      reader.onload = e => (preview.src = e.target.result);
      reader.readAsDataURL(file);
    });
  }

  // Highlight active nav link
  const currentPath = window.location.pathname;
  document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
    if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('/').pop())) {
      link.classList.add('fw-bold');
    }
  });

});
