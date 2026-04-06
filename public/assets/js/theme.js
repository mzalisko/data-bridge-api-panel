(function () {
  // Apply saved theme immediately (before DOMContentLoaded) to avoid flash
  var saved = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);

    var btnLight = document.getElementById('btn-light');
    var btnDark  = document.getElementById('btn-dark');
    if (!btnLight || !btnDark) return;

    if (theme === 'dark') {
      btnLight.classList.remove('is-active');
      btnDark.classList.add('is-active');
    } else {
      btnLight.classList.add('is-active');
      btnDark.classList.remove('is-active');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Set initial button state
    applyTheme(saved);

    var btnLight = document.getElementById('btn-light');
    var btnDark  = document.getElementById('btn-dark');

    if (btnLight) btnLight.addEventListener('click', function () { applyTheme('light'); });
    if (btnDark)  btnDark.addEventListener('click',  function () { applyTheme('dark'); });
  });
})();
