(() => {
  const getReactionKey = (postId, reaction) =>
    `dc-reaction-${postId}-${reaction}`;

  const createBurst = (x, y, emoji) => {
    const count = 8;
    for (let i = 0; i < count; i++) {
      const el = document.createElement('div');
      el.className = 'dc-particle';
      el.textContent = emoji;

      // Random direction and rotation
      const tx = (Math.random() - 0.5) * 200;
      const ty = (Math.random() - 0.5) * 200 - 50;
      const tr = (Math.random() - 0.5) * 45;

      el.style.left = `${x}px`;
      el.style.top = `${y}px`;
      el.style.setProperty('--tw-tx', `${tx}px`);
      el.style.setProperty('--tw-ty', `${ty}px`);
      el.style.setProperty('--tw-tr', `${tr}deg`);

      document.body.appendChild(el);
      el.addEventListener('animationend', () => el.remove());
    }
  };

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
            
            // Trigger burst effect
            const rect = button.getBoundingClientRect();
            const emoji = button.textContent.split(' ')[0] || '❤️';
            createBurst(rect.left + rect.width / 2, rect.top + rect.height / 2, emoji);

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

        if (platform === 'facebook') {
          const shareUrlParam = encodeURIComponent(shareUrl);
          const quoteParam = encodeURIComponent(shareText);
          const fbUrl = `https://www.facebook.com/sharer/sharer.php?u=${shareUrlParam}&quote=${quoteParam}`;
          window.open(fbUrl, '_blank', 'noopener,noreferrer');
          return;
        }

        if (platform === 'card') {
            const originalText = button.textContent;
            button.textContent = 'Generating...';
            
            try {
                // Find pet data
                const article = document.querySelector('article.type-pets');
                if (!article) throw new Error('No pet found');
                
                const title = document.querySelector('.dc-pet-title')?.textContent || 'Damn Cute Pet';
                const imgElement = document.querySelector('.dc-hero-media img');
                const reactions = container.querySelector('[data-reaction-count="total"]')?.textContent || 
                                  container.querySelector('[data-reaction-count="heart"]')?.textContent || 'Lots of';
                
                if (!imgElement) throw new Error('No image found');

                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = 1080;
                canvas.height = 1920; // Story format

                // Background
                ctx.fillStyle = '#121212';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Image
                const img = new Image();
                img.crossOrigin = 'Anonymous';
                img.src = imgElement.src;
                
                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                });

                // Draw Image (Cover)
                const aspect = img.width / img.height;
                let drawWidth = canvas.width;
                let drawHeight = canvas.width / aspect;
                let drawY = (canvas.height - drawHeight) / 2;
                
                if (drawHeight < canvas.height * 0.6) {
                     drawHeight = canvas.height * 0.6;
                     drawWidth = drawHeight * aspect;
                     drawY = (canvas.height - drawHeight) / 2;
                }

                ctx.drawImage(img, (canvas.width - drawWidth) / 2, 200, drawWidth, drawHeight);

                // Overlay Gradient
                const grad = ctx.createLinearGradient(0, canvas.height - 600, 0, canvas.height);
                grad.addColorStop(0, 'transparent');
                grad.addColorStop(1, '#121212');
                ctx.fillStyle = grad;
                ctx.fillRect(0, canvas.height - 600, canvas.width, 600);

                // Text
                ctx.textAlign = 'center';
                ctx.fillStyle = '#ff8a6b'; // Accent
                ctx.font = 'bold 80px sans-serif';
                ctx.fillText(title.toUpperCase(), canvas.width / 2, canvas.height - 400);

                ctx.fillStyle = '#f7f6f2';
                ctx.font = '40px sans-serif';
                ctx.fillText(`${reactions} Reactions • damncute.com`, canvas.width / 2, canvas.height - 300);

                // Download
                const link = document.createElement('a');
                link.download = `damncute-${title.replace(/\s+/g, '-').toLowerCase()}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                button.textContent = 'Downloaded!';
            } catch (e) {
                console.error(e);
                button.textContent = 'Failed';
            }
            
            setTimeout(() => { button.textContent = originalText; }, 2000);
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

  const initInfiniteScroll = () => {
    const query = document.querySelector('.dc-query');
    const loader = document.querySelector('.dc-loader');
    const pagination = document.querySelector('.dc-pagination');
    
    if (!query || !loader) {
      return;
    }

    // Hide standard pagination if JS is running
    if (pagination) {
      pagination.style.display = 'none';
    }

    let page = 1;
    let loading = false;
    let finished = false;

    // Get filter params from URL
    const urlParams = new URLSearchParams(window.location.search);
    const species = urlParams.get('species') || '';
    const vibe = urlParams.get('vibe') || '';

    const observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && !loading && !finished) {
        loading = true;
        loader.classList.add('is-visible');
        page++;

        const apiUrl = `${window.damncuteData.restUrl}/page/${page}?species=${species}&vibe=${vibe}`;

        fetch(apiUrl)
          .then(res => res.json())
          .then(data => {
            if (data.html) {
              const temp = document.createElement('div');
              temp.innerHTML = data.html;
              
              // Find the grid container (first div inside post-template usually)
              // But WP Query block structure is tricky. We look for the parent of existing cards.
              const grid = query.querySelector('.dc-grid') || query.querySelector('.wp-block-post-template');
              if (grid) {
                while (temp.firstChild) {
                  grid.appendChild(temp.firstChild);
                }
              }
            }

            if (!data.has_next) {
              finished = true;
              loader.style.display = 'none';
            }
          })
          .catch(() => {
            finished = true; // Stop trying on error
          })
          .finally(() => {
            loading = false;
            loader.classList.remove('is-visible');
          });
      }
    }, { rootMargin: '200px' });

    observer.observe(loader);
  };

  document.addEventListener('DOMContentLoaded', () => {
    initReactions();
    initShare();
    initNav();
    initFloatingCta();
    initInfiniteScroll();
  });
})();
