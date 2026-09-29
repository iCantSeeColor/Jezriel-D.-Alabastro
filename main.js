(function () {
  'use strict';

  /* iyaha e mark that JavaScript is available  ani nga code(used by .reveal animations) */
  document.body.classList.add('js');

  /* ------------------------------------------------------
     1. MOBILE NAVIGATION
     ------------------------------------------------------ */
  function initMobileNav() {
    var toggle  = document.getElementById('navToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('navOverlay');

    if (!toggle || !sidebar) return;

    function openNav() {
      sidebar.classList.add('open');
      toggle.classList.add('open');
      toggle.setAttribute('aria-expanded', 'true');
      if (overlay) overlay.classList.add('show');
    }

    function closeNav() {
      sidebar.classList.remove('open');
      toggle.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      if (overlay) overlay.classList.remove('show');
    }

    toggle.addEventListener('click', function () {
      if (sidebar.classList.contains('open')) {
        closeNav();
      } else {
        openNav();
      }
    });

    if (overlay) overlay.addEventListener('click', closeNav);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeNav();
    });

    sidebar.querySelectorAll('.nav-link').forEach(function (link) {
      link.addEventListener('click', closeNav);
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 860) closeNav();
    });
  }

  /* switch mode dri */
  function initTheme() {
    var button = document.getElementById('themeToggle');
    var stored = null;

    try {
      stored = localStorage.getItem('portfolio-theme');
    } catch (err) {
      stored = null;
    }

    var prefersDark = window.matchMedia &&
                      window.matchMedia('(prefers-color-scheme: dark)').matches;

    var theme = stored || (prefersDark ? 'dark' : 'light');
    applyTheme(theme);

    function applyTheme(mode) {
      var isDark = mode === 'dark';
      document.body.classList.toggle('dark', isDark);

      if (button) {
        button.innerHTML = isDark
          ? '<span>☀️</span> Light mode'
          : '<span>🌙</span> Dark mode';
        button.setAttribute(
          'aria-label',
          'Switch to ' + (isDark ? 'light' : 'dark') + ' mode'
        );
      }
    }

    if (button) {
      button.addEventListener('click', function () {
        var next = document.body.classList.contains('dark') ? 'light' : 'dark';
        applyTheme(next);
        try {
          localStorage.setItem('portfolio-theme', next);
        } catch (err) { /* storage unavailable — ignore */ }
      });
    }
  }

  /* sa active navigation ni nga js */
  function initActiveNav() {
    var links = document.querySelectorAll('.nav-link');
    if (!links.length) return;

    var current = window.location.pathname.split('/').pop();
    if (!current || current === '') current = 'index.html';

    links.forEach(function (link) {
      var href = link.getAttribute('href');
      if (href === current) {
        link.classList.add('active');
        link.setAttribute('aria-current', 'page');
      }
    });
  }

  /* scroll to up or down nga js ni sya */
  function initScrollTop() {
    var button = document.getElementById('toTop');
    if (!button) return;

    window.addEventListener('scroll', function () {
      button.classList.toggle('show', window.scrollY > 350);
    }, { passive: true });

    button.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* contact form nga checking or validation*/
  function initContactForm() {
    var form = document.getElementById('contactForm');
    if (!form) return;

    var status = document.getElementById('formStatus');

    var validators = {
      name: function (value) {
        var v = value.trim();
        if (!v) return 'Please enter your full name.';
        if (v.length < 2) return 'Name must be at least 2 characters long.';
        if (!/^[A-Za-zÀ-ÿ.'\- ]+$/.test(v)) return 'Name should contain letters only.';
        return '';
      },
      email: function (value) {
        var v = value.trim();
        if (!v) return 'Please enter your email address.';
        if (!/^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/.test(v)) {
          return 'Please enter a valid email (example: name@email.com).';
        }
        return '';
      },
      subject: function (value) {
        var v = value.trim();
        if (!v) return 'Please enter a subject.';
        if (v.length < 3) return 'Subject must be at least 3 characters long.';
        return '';
      },
      message: function (value) {
        var v = value.trim();
        if (!v) return 'Please write your message.';
        if (v.length < 10) return 'Message must be at least 10 characters long.';
        return '';
      }
    };

    function validateField(fieldName) {
      var input = form.elements[fieldName];
      if (!input) return true;

      var group = input.closest('.form-group');
      var errorEl = group.querySelector('.error-msg');
      var message = validators[fieldName](input.value);

      if (message) {
        group.classList.add('error');
        group.classList.remove('success');
        if (errorEl) errorEl.textContent = message;
        return false;
      }

      group.classList.remove('error');
      group.classList.add('success');
      if (errorEl) errorEl.textContent = '';
      return true;
    }

    Object.keys(validators).forEach(function (fieldName) {
      var input = form.elements[fieldName];
      if (!input) return;

      input.addEventListener('blur', function () {
        validateField(fieldName);
      });

      input.addEventListener('input', function () {
        var group = input.closest('.form-group');
        if (group && group.classList.contains('error')) {
          validateField(fieldName);
        }
      });
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      var isValid = true;
      var firstInvalid = null;

      Object.keys(validators).forEach(function (fieldName) {
        if (!validateField(fieldName)) {
          isValid = false;
          if (!firstInvalid) firstInvalid = form.elements[fieldName];
        }
      });

      if (!isValid) {
        if (status) {
          status.textContent = '⚠ Please fix the highlighted fields and try again.';
          status.className = 'form-status error';
        }
        if (firstInvalid) firstInvalid.focus();
        return;
      }

      var senderName = form.elements.name.value.trim();

      if (status) {
        status.textContent = '✓ Thank you, ' + senderName +
          '! Your message has been validated successfully. I will get back to you soon.';
        status.className = 'form-status success';
      }

      form.reset();

      form.querySelectorAll('.form-group').forEach(function (group) {
        group.classList.remove('success', 'error');
      });
    });
  }

  /* accordion ni sya nga java script */
  function initAccordion() {
    var items = document.querySelectorAll('.accordion-item');
    if (!items.length) return;

    items.forEach(function (item) {
      var header = item.querySelector('.accordion-header');
      var panel  = item.querySelector('.accordion-panel');
      if (!header || !panel) return;

      header.addEventListener('click', function () {
        var isOpen = item.classList.contains('open');

        /* Close every other item */
        items.forEach(function (other) {
          if (other === item) return;
          other.classList.remove('open');
          var otherPanel = other.querySelector('.accordion-panel');
          var otherHeader = other.querySelector('.accordion-header');
          if (otherPanel) otherPanel.style.maxHeight = null;
          if (otherHeader) otherHeader.setAttribute('aria-expanded', 'false');
        });

        if (isOpen) {
          item.classList.remove('open');
          panel.style.maxHeight = null;
          header.setAttribute('aria-expanded', 'false');
        } else {
          item.classList.add('open');
          panel.style.maxHeight = panel.scrollHeight + 'px';
          header.setAttribute('aria-expanded', 'true');
        }
      });
    });

    /* Keep open panels sized correctly on window resize */
    window.addEventListener('resize', function () {
      items.forEach(function (item) {
        if (item.classList.contains('open')) {
          var panel = item.querySelector('.accordion-panel');
          if (panel) panel.style.maxHeight = panel.scrollHeight + 'px';
        }
      });
    });
  }

  /* skill bars  */
  function initSkillBars() {
    var bars = document.querySelectorAll('.skill-bar span');
    if (!bars.length) return;

    if (!('IntersectionObserver' in window)) {
      bars.forEach(function (bar) {
        bar.style.width = (bar.dataset.level || 0) + '%';
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var bar = entry.target;
        bar.style.width = (bar.dataset.level || 0) + '%';
        observer.unobserve(bar);
      });
    }, { threshold: 0.25 });

    bars.forEach(function (bar) { observer.observe(bar); });
  }

  /* filtering ni sa project */
  function initProjectFilter() {
    var buttons = document.querySelectorAll('.filter-btn');
    var cards   = document.querySelectorAll('.project-card');
    if (!buttons.length || !cards.length) return;

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        var filter = button.dataset.filter;

        buttons.forEach(function (b) { b.classList.remove('active'); });
        button.classList.add('active');

        cards.forEach(function (card) {
          var match = filter === 'all' || card.dataset.category === filter;
          card.style.display = match ? '' : 'none';
        });
      });
    });
  }

  /* typing efffect ni sya */
  function initTyping() {
    var target = document.getElementById('typed');
    if (!target) return;

    var words = [
      'BSINT Student',
      'Java Programmer',
      'C++ Learner',
      'Digital Animator',
      'Chess Enthusiast'
    ];

    var wordIndex = 0;
    var charIndex = 0;
    var deleting = false;

    function tick() {
      var word = words[wordIndex];
      charIndex += deleting ? -1 : 1;
      target.textContent = word.slice(0, charIndex);

      var delay = deleting ? 55 : 110;

      if (!deleting && charIndex === word.length) {
        deleting = true;
        delay = 1500;
      } else if (deleting && charIndex === 0) {
        deleting = false;
        wordIndex = (wordIndex + 1) % words.length;
        delay = 420;
      }

      window.setTimeout(tick, delay);
    }

    tick();
  }

  /* animation ni sya sa scroll reveal */
  function initReveal() {
    var elements = document.querySelectorAll('.reveal');
    if (!elements.length) return;

    if (!('IntersectionObserver' in window)) {
      elements.forEach(function (el) { el.classList.add('visible'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    elements.forEach(function (el) { observer.observe(el); });
  }

  /* para ma handle ang sa mga image placeholder*/
  function initImagePlaceholders() {
    var images = document.querySelectorAll('.img-slot img');

    images.forEach(function (img) {
      var slot = img.closest('.img-slot');
      if (!slot) return;

      img.addEventListener('load', function () {
        if (img.naturalWidth > 0) slot.classList.add('loaded');
      });

      img.addEventListener('error', function () {
        img.style.display = 'none';
        slot.classList.remove('loaded');
      });

      /* Image already cached / finished loading */
      if (img.complete && img.naturalWidth > 0) {
        slot.classList.add('loaded');
      }
    });
  }

  /* para mo gana ang print */
  function initPrintResume() {
    var button = document.getElementById('printResume');
    if (!button) return;

    button.addEventListener('click', function () {
      window.print();
    });
  }

  /* footer */
  function initYear() {
    var year = new Date().getFullYear();
    document.querySelectorAll('.year').forEach(function (el) {
      el.textContent = year;
    });
  }

  /* kini is iyaha gina initialise tanan */
  function init() {
    initMobileNav();
    initTheme();
    initActiveNav();
    initScrollTop();
    initContactForm();
    initAccordion();
    initSkillBars();
    initProjectFilter();
    initTyping();
    initReveal();
    initImagePlaceholders();
    initPrintResume();
    initYear();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();