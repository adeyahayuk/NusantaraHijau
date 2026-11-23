document.addEventListener('DOMContentLoaded', function () {
  const accountMenu = document.getElementById('accountMenu');
  const accountTrigger = document.getElementById('accountTrigger');
  const accountPopover = document.getElementById('accountPopover');

  if (!accountMenu || !accountTrigger || !accountPopover) return;

  accountTrigger.addEventListener('click', function () {
    const isOpen = accountTrigger.getAttribute('aria-expanded') === 'true';
    accountTrigger.setAttribute('aria-expanded', String(!isOpen));
    accountPopover.hidden = isOpen;
  });

  document.addEventListener('click', function (event) {
    if (!accountMenu.contains(event.target)) {
      accountTrigger.setAttribute('aria-expanded', 'false');
      accountPopover.hidden = true;
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      accountTrigger.setAttribute('aria-expanded', 'false');
      accountPopover.hidden = true;
      accountTrigger.focus();
    }
  });
});
