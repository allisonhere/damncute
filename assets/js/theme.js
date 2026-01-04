(() => {
  const getKey = () => `dc-like-${window.location.pathname}`;

  const initLikes = () => {
    const buttons = document.querySelectorAll('[data-like-button]');
    if (!buttons.length) {
      return;
    }

    const stored = localStorage.getItem(getKey());
    const hasLiked = stored === '1';

    buttons.forEach((button) => {
      const countEl = button.querySelector('[data-like-count]');
      if (!countEl) {
        return;
      }

      if (hasLiked) {
        button.classList.add('is-active');
        countEl.textContent = '1';
      }

      button.addEventListener('click', () => {
        const active = button.classList.toggle('is-active');
        countEl.textContent = active ? '1' : '0';
        localStorage.setItem(getKey(), active ? '1' : '0');
      });
    });
  };

  const initShare = () => {
    document.addEventListener('click', async (event) => {
      const button = event.target.closest('[data-share-button]');
      if (!button) {
        return;
      }

      const shareData = {
        title: document.title,
        url: window.location.href,
      };

      try {
        if (navigator.share) {
          await navigator.share(shareData);
          return;
        }

        if (navigator.clipboard) {
          await navigator.clipboard.writeText(window.location.href);
          button.textContent = 'Link copied';
          setTimeout(() => {
            button.textContent = 'Share';
          }, 1800);
        }
      } catch (error) {
        button.textContent = 'Share';
      }
    });
  };

  const initNav = () => {
    const toggle = document.querySelector('[data-nav-toggle]');
    const overlay = document.querySelector('[data-nav-overlay]');
    if (!toggle || !overlay) {
      return;
    }

    const setOpen = (isOpen) => {
      document.body.classList.toggle('dc-nav-open', isOpen);
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
      setOpen(!document.body.classList.contains('dc-nav-open'));
    });

    overlay.addEventListener('click', () => {
      setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    });

    document.addEventListener('click', (event) => {
      if (!document.body.classList.contains('dc-nav-open')) {
        return;
      }
      if (event.target.closest('.dc-nav a')) {
        setOpen(false);
      }
    });
  };

  const initFloatingCta = () => {
    const cta = document.querySelector('.dc-floating-cta');
    if (!cta) {
      return;
    }

    if (!cta.querySelector('.dc-floating-cta__label')) {
      const labelText = cta.textContent.trim();
      cta.textContent = '';

      const label = document.createElement('span');
      label.className = 'dc-floating-cta__label';
      label.textContent = labelText;

      const icon = document.createElement('span');
      icon.className = 'dc-floating-cta__icon';
      icon.setAttribute('aria-hidden', 'true');
      icon.textContent = '<<';

      cta.append(label, icon);
    }

    const icon = cta.querySelector('.dc-floating-cta__icon');
    const mq = window.matchMedia('(max-width: 720px)');
    const scrollThreshold = 80;
    let userExpanded = false;

    const setCollapsed = (collapsed) => {
      cta.classList.toggle('is-collapsed', collapsed);
      if (icon) {
        icon.textContent = collapsed ? '<<' : '>>';
      }
      cta.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };

    setCollapsed(mq.matches);

    const handleChange = (event) => {
      if (event.matches) {
        setCollapsed(true);
      } else {
        setCollapsed(false);
      }
    };

    if (typeof mq.addEventListener === 'function') {
      mq.addEventListener('change', handleChange);
    } else {
      mq.addListener(handleChange);
    }

    cta.addEventListener('click', (event) => {
      if (!mq.matches) {
        return;
      }

      const isCollapsed = cta.classList.contains('is-collapsed');
      const clickedIcon = event.target.closest('.dc-floating-cta__icon');

      if (isCollapsed || clickedIcon) {
        event.preventDefault();
        const nextCollapsed = !isCollapsed;
        setCollapsed(nextCollapsed);
        userExpanded = !nextCollapsed;
      }
    });

    const handleScroll = () => {
      if (!mq.matches) {
        return;
      }

      const scrolled = window.scrollY > scrollThreshold;
      if (scrolled && !userExpanded) {
        setCollapsed(true);
        return;
      }
      if (!scrolled) {
        userExpanded = false;
        setCollapsed(false);
      }
    };

    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
  };

  document.addEventListener('DOMContentLoaded', () => {
    initLikes();
    initShare();
    initNav();
    initFloatingCta();
  });
})();
