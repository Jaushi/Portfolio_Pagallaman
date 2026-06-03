(function () {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;

  const toggler = document.querySelector('.navbar-toggler');
  const collapse = document.querySelector('.navbar-collapse');
  if (toggler && collapse) {
    toggler.addEventListener('click', function () {
      const expanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', String(!expanded));
      collapse.classList.toggle('show');
    });
  }

  let lastY = window.scrollY;
  let ticking = false;

  function update() {
    const currentY = window.scrollY;

    if (currentY > 20) navbar.classList.add('navbar--scrolled');
    else navbar.classList.remove('navbar--scrolled');

    if (currentY > lastY && currentY > 100) {
      navbar.classList.add('navbar--hidden');
    } else {
      navbar.classList.remove('navbar--hidden');
    }

    lastY = Math.max(0, currentY);
    ticking = false;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) {
      window.requestAnimationFrame(update);
      ticking = true;
    }
  }, { passive: true });

  update();
})();
