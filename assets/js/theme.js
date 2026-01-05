(() => {
  const getReactionKey = (postId, reaction) =>
    `dc-reaction-${postId}-${reaction}`;

  const initReactions = () => {
    const groups = document.querySelectorAll('[data-reaction-group]');
    if (!groups.length) {
      return;
    }

    const apiRoot = window.damncuteData?.restUrl || '';
    const apiNonce = window.damncuteData?.nonce || '';
    if (!apiRoot) {
      return;
    }

    groups.forEach((group) => {
      const postId = group.dataset.postId;
      if (!postId) {
        return;
      }

      group.querySelectorAll('[data-reaction-button]').forEach((button) => {
        const reaction = button.dataset.reaction;
        if (!reaction) {
          return;
        }

        const stored = localStorage.getItem(getReactionKey(postId, reaction));
        if (stored === '1') {
          button.classList.add('is-active');
          button.setAttribute('aria-pressed', 'true');
        }

        button.addEventListener('click', async () => {
          if (button.classList.contains('is-active')) {
            return;
          }

          try {
            const response = await fetch(`${apiRoot}/reaction/${postId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': apiNonce,
              },
              body: JSON.stringify({ reaction }),
            });

            if (!response.ok) {
              return;
            }

            const data = await response.json();
            const counts = data?.counts || {};
            Object.keys(counts).forEach((key) => {
              const countEl = group.querySelector(
                `[data-reaction-count="${key}"]`
              );
              if (countEl) {
                countEl.textContent = counts[key];
              }
            });

            button.classList.add('is-active');
            button.setAttribute('aria-pressed', 'true');
            localStorage.setItem(getReactionKey(postId, reaction), '1');
          } catch (error) {
            // No-op: keep UI stable if request fails.
          }
        });
      });
    });
  };

  const initShare = () => {
    const buttons = document.querySelectorAll('[data-share-platform]');
    if (!buttons.length) {
      return;
    }

    const updateLabel = (button, label) => {
      const original = button.dataset.originalLabel || button.textContent;
      button.dataset.originalLabel = original;
      button.textContent = label;
      window.setTimeout(() => {
        button.textContent = original;
      }, 1600);
    };

    buttons.forEach((button) => {
      button.addEventListener('click', async () => {
        const platform = button.dataset.sharePlatform;
        const container = button.closest('.dc-social');
        const shareText =
          container?.dataset.shareText || document.title || 'Damn Cute';
        const shareUrl = container?.dataset.shareUrl || window.location.href;
        const copyText = `${shareText} ${shareUrl}`;

        if (platform === 'x') {
          const intentUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(
            shareText
          )}&url=${encodeURIComponent(shareUrl)}`;
          window.open(intentUrl, '_blank', 'noopener,noreferrer');
          return;
        }

        try {
          if (navigator.clipboard) {
            await navigator.clipboard.writeText(copyText);
            updateLabel(button, 'Copied');
          }
        } catch (error) {
          updateLabel(button, 'Copy failed');
        }
      });
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
    initReactions();
    initShare();
    initNav();
    initFloatingCta();
  });
})();
