/* ANED Roma - App JS */

document.addEventListener('DOMContentLoaded', function () {

  // Sidebar toggle mobile
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  const toggleBtn = document.getElementById('sidebarToggle');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('show');
    });
    overlay.addEventListener('click', function () {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
    });
  }

  // Auto-hide alerts after 5s
  document.querySelectorAll('.alert-dismissible').forEach(function (el) {
    setTimeout(function () {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      if (bsAlert) bsAlert.close();
    }, 5000);
  });

  // Confirm delete
  document.querySelectorAll('[data-confirm]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || 'Confermi l\'eliminazione?')) {
        e.preventDefault();
      }
    });
  });

  // Upload zone drag & drop visual
  document.querySelectorAll('.upload-zone').forEach(function (zone) {
    const input = zone.querySelector('input[type=file]');
    zone.addEventListener('click', function () { if(input) input.click(); });
    zone.addEventListener('dragover', function (e) {
      e.preventDefault();
      zone.classList.add('dragover');
    });
    zone.addEventListener('dragleave', function () { zone.classList.remove('dragover'); });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      zone.classList.remove('dragover');
      if (input && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        const label = zone.querySelector('.upload-label');
        if (label) label.textContent = e.dataTransfer.files[0].name;
      }
    });
    if (input) {
      input.addEventListener('change', function () {
        const label = zone.querySelector('.upload-label');
        if (label && input.files.length) label.textContent = input.files[0].name;
      });
    }
  });

  // Tooltips Bootstrap
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
  });

  // Codice fiscale uppercase
  document.querySelectorAll('input[name="codice_fiscale"]').forEach(function(el){
    el.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
  });

  // Anno iscrizione: evidenzia riga attiva
  const yearBtns = document.querySelectorAll('.year-filter-btn');
  yearBtns.forEach(function(btn){
    btn.addEventListener('click', function(){
      yearBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
    });
  });

});

// Utility: conferma form
function confirmForm(formId, msg) {
  const form = document.getElementById(formId);
  if (form && confirm(msg || 'Confermi l\'operazione?')) form.submit();
}
