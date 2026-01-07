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
            button.textContent = 'Designing...';
            
            try {
                // Find pet data - relax selectors for block themes
                const title = document.querySelector('.dc-pet-title')?.textContent || document.querySelector('h1')?.textContent || 'Damn Cute Pet';
                const imgElement = document.querySelector('.dc-hero-media img') || document.querySelector('.wp-block-post-featured-image img');
                
                // Get reactions from the button container itself if possible
                const reactions = container.querySelector('[data-reaction-count="total"]')?.textContent || 
                                  container.querySelector('[data-reaction-count="heart"]')?.textContent || 'Lots of';
                
                if (!imgElement) throw new Error('No image found');

                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = 1080;
                canvas.height = 2100; // Increased height from 1920 to fit text comfortably

                // Background
                ctx.fillStyle = '#121212';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Image
                const imageId = container.dataset.imageId;
                let src = imgElement.src;
                
                if (imageId && window.damncuteData?.restUrl) {
                    src = `${window.damncuteData.restUrl}/proxy-image?id=${imageId}`;
                }
                
                // Fetch blob first to handle errors explicitly
                let blob;
                try {
                    const response = await fetch(src);
                    if (!response.ok) throw new Error(`Network error: ${response.status}`);
                    blob = await response.blob();
                } catch (err) {
                    // Fallback to original source if proxy fails
                    console.warn('Proxy failed, trying direct', err);
                    const response = await fetch(imgElement.src);
                    if (!response.ok) throw new Error('Direct load failed');
                    blob = await response.blob();
                }

                const img = new Image();
                const objectUrl = URL.createObjectURL(blob);
                img.src = objectUrl;
                
                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                });

                // Draw Image (Cover)

                // Draw Image (Cover Top 75%)
                const footerHeight = 550; // Dedicated space for text
                const imageHeight = canvas.height - footerHeight;
                
                const scale = Math.max(canvas.width / img.width, imageHeight / img.height);
                const drawW = img.width * scale;
                const drawH = img.height * scale;
                
                // Align image to top
                const x = (canvas.width - drawW) / 2;
                const y = 0; 
                
                ctx.drawImage(img, x, y, drawW, drawH);

                // Draw Footer (Solid Black)
                ctx.fillStyle = '#121212';
                ctx.fillRect(0, imageHeight, canvas.width, footerHeight);
                
                // Add a subtle accent border between image and footer
                ctx.fillStyle = '#ff8a6b';
                ctx.fillRect(0, imageHeight, canvas.width, 4);

                // Text
                ctx.textAlign = 'center';
                ctx.fillStyle = '#ff8a6b'; // Accent
                ctx.font = 'bold 72px sans-serif';
                ctx.fillText(title.toUpperCase(), canvas.width / 2, imageHeight + 200);

                ctx.fillStyle = '#f7f6f2';
                ctx.font = '42px sans-serif';
                ctx.fillText(`${reactions} Reactions • damncute.com`, canvas.width / 2, imageHeight + 320);

                // Download
                const link = document.createElement('a');
                link.download = `damncute-poster-${title.replace(/\s+/g, '-').toLowerCase()}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                button.textContent = 'Ready!';
            } catch (e) {
                console.error(e);
                button.textContent = 'Retry?';
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

  const initFilterBar = () => {
    const bar = document.querySelector('.dc-filter-bar');
    if (!bar) return;

    const chips = bar.querySelectorAll('.dc-chip');
    const query = document.querySelector('.dc-query');
    const grid = query?.querySelector('.dc-grid') || query?.querySelector('.wp-block-post-template');
    
    if (!grid) return;

    const handleFilter = (e) => {
      const btn = e.currentTarget;
      
      // Update UI
      chips.forEach(c => c.classList.remove('is-active'));
      btn.classList.add('is-active');

      // Update State
      const term = btn.dataset.filterTerm || '';
      const tax = btn.dataset.filterTax || '';
      const url = new URL(window.location);
      
      url.searchParams.delete('species');
      url.searchParams.delete('vibe');
      
      if (term) {
        url.searchParams.set(tax, term);
      }
      
      window.history.pushState({}, '', url);

      // Reset Grid
      grid.innerHTML = ''; // Clear items
      
      // Re-trigger infinite scroll
      // We do this by scrolling to top (optional) or just letting the observer fire
      // But since the grid is empty, the loader will be visible immediately.
      
      // Force reload by removing and re-adding infinite scroll logic?
      // Better: Reload page? No, that's not "App-like".
      // Best: Manually trigger the fetch logic or expose a reset method.
      // For simplicity in this architecture, we will simply reload the page for now
      // as it guarantees 100% compatibility with the complex WP Query state.
      // OR, we can just call window.location.reload() which is fast enough for v1.
      
      // Actually, let's do it properly:
      // We will rely on initInfiniteScroll to handle the "empty grid" state? 
      // initInfiniteScroll runs once. We need to reset its internal state.
      
      // Quickest win for V1 without refactoring the whole scroller:
      window.location.reload(); 
    };

    chips.forEach(chip => {
      chip.addEventListener('click', handleFilter);
    });
    
    // Set active state from URL
    const params = new URLSearchParams(window.location.search);
    const activeTerm = params.get('species') || params.get('vibe');
    if (activeTerm) {
        chips.forEach(c => c.classList.remove('is-active'));
        const match = bar.querySelector(`[data-filter-term="${activeTerm}"]`);
        if (match) match.classList.add('is-active');
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    initReactions();
    initShare();
    initNav();
    initFloatingCta();
    initInfiniteScroll();
    initFilterBar();
  });
})();
