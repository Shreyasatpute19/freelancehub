document.addEventListener('DOMContentLoaded', function () {
  // Confirm password match on forms that have both fields
  document.querySelectorAll('form[data-validate-password]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      var p = form.querySelector('[name=password]');
      var c = form.querySelector('[name=confirm]');
      if (p && c && p.value !== c.value) {
        ev.preventDefault();
        alert('Passwords do not match.');
      }
    });
  });

  // Confirm before destructive actions
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (ev) {
      if (!confirm(el.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  // Hero depth stack: tilts toward the pointer
  var hero = document.querySelector('.hero');
  var stack = document.querySelector('.stack-inner');
  var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (hero && stack && !calm) {
    hero.addEventListener('pointermove', function (ev) {
      var r = hero.getBoundingClientRect();
      var x = (ev.clientX - r.left) / r.width - 0.5;
      var y = (ev.clientY - r.top) / r.height - 0.5;
      stack.style.setProperty('--ry', (-10 + x * 14).toFixed(1) + 'deg');
      stack.style.setProperty('--rx', (6 - y * 12).toFixed(1) + 'deg');
    });
    hero.addEventListener('pointerleave', function () {
      stack.style.removeProperty('--ry');
      stack.style.removeProperty('--rx');
    });
  }

  // Chat auto-refresh
  var chat = document.getElementById('chat-box');
  if (chat) {
    var url = chat.getAttribute('data-url');
    chat.scrollTop = chat.scrollHeight;
    setInterval(function () {
      fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var atBottom = chat.scrollHeight - chat.scrollTop - chat.clientHeight < 40;
          if (chat.innerHTML.trim() !== html.trim()) {
            chat.innerHTML = html;
            if (atBottom) chat.scrollTop = chat.scrollHeight;
          }
        });
    }, 5000);
  }
});
