  <script>
    // Mobile menu toggle
    (function() {
      const menuToggle = document.getElementById('menuToggle');
      const navMenu = document.getElementById('navMenu');
      if (!menuToggle || !navMenu) return;

      menuToggle.addEventListener('click', () => {
        menuToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
      });

      // Close mobile nav on outside tap
      document.addEventListener('click', (e) => {
        if (navMenu.classList.contains('active') &&
            !navMenu.contains(e.target) &&
            !menuToggle.contains(e.target)) {
          navMenu.classList.remove('active');
          menuToggle.classList.remove('active');
        }
      });

      // Close mobile nav when a link is tapped
      document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
          navMenu.classList.remove('active');
          menuToggle.classList.remove('active');
        });
      });
    })();

    // Header scroll effect
    (function() {
      const header = document.getElementById('header');
      if (!header) return;
      window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 100);
      });
    })();
  </script>
