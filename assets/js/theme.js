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

    const openShareWindow = (url) => {
      window.open(url, '_blank', 'noopener,noreferrer');
    };

    buttons.forEach((button) => {
      button.addEventListener('click', async () => {
        const platform = button.dataset.sharePlatform;
        const container = button.closest('.dc-social');
        const shareText =
          container?.dataset.shareText || document.title || 'Damn Cute';
        const shareUrl = container?.dataset.shareUrl || window.location.href;
        const copyText = `${shareText} ${shareUrl}`;
        const ua = navigator.userAgent;
        const isAndroid = /Android/i.test(ua);
        const isIOS = /iPhone|iPad|iPod/i.test(ua);
        const isMobile = isAndroid || isIOS;
        let copyLabel = 'Copied Link';

        if (platform === 'x') {
          const webUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(shareUrl)}`;
          if (isMobile) {
             const appUrl = `twitter://post?message=${encodeURIComponent(shareText + ' ' + shareUrl)}`;
             window.location = appUrl;
             setTimeout(() => { window.location = webUrl; }, 1000);
          } else {
             window.open(webUrl, '_blank', 'noopener,noreferrer');
          }
          return;
        }

        if (platform === 'facebook') {
          const shareUrlParam = encodeURIComponent(shareUrl);
          const quoteParam = encodeURIComponent(shareText);
          const webUrl = `https://www.facebook.com/sharer/sharer.php?u=${shareUrlParam}&quote=${quoteParam}`;
          
          if (isMobile) {
             // Facebook scheme varies, but this is the standard intent
             const appUrl = `fb://facewebmodal/f?href=${encodeURIComponent(webUrl)}`;
             window.location = appUrl;
             setTimeout(() => { window.location = webUrl; }, 1000);
          } else {
             window.open(webUrl, '_blank', 'noopener,noreferrer');
          }
          return;
        }

        if (platform === 'card') {
            // ... (existing card logic) ...
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
                
                const loadImage = (source) =>
                    new Promise((resolve, reject) => {
                        const image = new Image();
                        image.crossOrigin = 'anonymous';
                        image.src = source;
                        image.onload = () => resolve(image);
                        image.onerror = (event) => reject(event);
                    });

                let img;
                try {
                    img = await loadImage(src);
                } catch (err) {
                    console.warn('Proxy failed, trying direct', err);
                    const fallbackSrc = imgElement.currentSrc || imgElement.src;
                    img = await loadImage(fallbackSrc);
                }

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

        // Clipboard Copy (Modern + Legacy Fallback)
        try {
          if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(copyText);
            updateLabel(button, copyLabel);
          } else {
            // Legacy fallback for HTTP/Older Browsers
            const textArea = document.createElement("textarea");
            textArea.value = copyText;
            textArea.style.position = "fixed"; // Avoid scrolling to bottom
            textArea.style.opacity = "0";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            
            if (successful) {
                updateLabel(button, copyLabel);
            } else {
                throw new Error('execCommand failed');
            }
          }
        } catch (error) {
          console.error('Copy failed:', error);
          updateLabel(button, 'Copy Failed');
          
          // Last resort: Alert (ugly but functional for debugging)
          // alert('Could not auto-copy. Here is the link:\n' + copyText);
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

  let scrollController = null;

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

    const observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && !loading && !finished) {
        loading = true;
        loader.classList.add('is-visible');
        page++;

        // Get fresh filter params from URL on every fetch
        const urlParams = new URLSearchParams(window.location.search);
        const species = urlParams.get('species') || '';
        const vibe = urlParams.get('vibe') || '';

        const apiUrl = `${window.damncuteData.restUrl}/page/${page}?species=${species}&vibe=${vibe}`;

        fetch(apiUrl)
          .then(res => res.json())
          .then(data => {
            if (data.html) {
              const temp = document.createElement('div');
              temp.innerHTML = data.html;
              
              // Handle smooth image reveal
              temp.querySelectorAll('img').forEach(img => {
                  img.style.opacity = '0';
                  img.style.transition = 'opacity 0.6s ease';
                  img.onload = () => { img.style.opacity = '1'; };
              });

              const grid = query.querySelector('.dc-grid') || query.querySelector('.wp-block-post-template');
              if (grid) {
                while (temp.firstChild) {
                  grid.appendChild(temp.firstChild);
                }
                initReactions(); // Re-bind new items
              }
            }

            if (!data.has_next) {
              finished = true;
              loader.style.display = 'none';
            }
          })
          .catch(() => {
            finished = true;
          })
          .finally(() => {
            loading = false;
            loader.classList.remove('is-visible');
          });
      }
    }, { rootMargin: '400px' });

    observer.observe(loader);

    // Return reset function
    return {
        reset: () => {
            page = 1;
            finished = false;
            loading = false;
            loader.style.display = 'flex';
            loader.classList.remove('is-visible');
        }
    };
  };

  const initFilterBar = () => {
    const bar = document.querySelector('.dc-filter-bar');
    if (!bar) return;

    const chips = bar.querySelectorAll('.dc-chip');
    const query = document.querySelector('.dc-query');
    // Support both block theme templates and classic divs
    const grid = query?.querySelector('.dc-grid') || query?.querySelector('.wp-block-post-template');
    
    if (!grid) return;

    // Helper to fetch and render
    const loadVibe = async (species, vibe) => {
        // Add loading state
        grid.style.opacity = '0.5';
        grid.style.transition = 'opacity 0.2s';
        
        try {
            // Fetch page 1 with new filters
            const apiUrl = `${window.damncuteData.restUrl}/page/1?species=${species}&vibe=${vibe}`;
            const res = await fetch(apiUrl);
            const data = await res.json();
            
            if (data.html) {
                // Clear and replace
                grid.innerHTML = data.html;
                
                // Re-init any interactions on new items (like reactions/share)
                // Note: We might need to expose initReactions/initShare globally or rerun them
                // For now, simpler is better.
                initReactions(); // Re-run to bind events to new elements
                if (scrollController) scrollController.reset();
            } else {
                grid.innerHTML = '<p class="dc-no-results">No pets found for this vibe yet!</p>';
            }
        } catch (err) {
            console.error('Filter fetch failed', err);
        } finally {
            grid.style.opacity = '1';
        }
    };

    const handleFilter = async (e) => {
      const btn = e.currentTarget;
      if (btn.classList.contains('is-active')) return;

      // Update UI
      chips.forEach(c => c.classList.remove('is-active'));
      btn.classList.add('is-active');

      // Update URL state
      const term = btn.dataset.filterTerm || '';
      const tax = btn.dataset.filterTax || 'vibe';
      const url = new URL(window.location);
      
      url.searchParams.delete('vibe');
      if (term) url.searchParams.set('vibe', term);
      
      const currentSpecies = url.searchParams.get('species') || '';
      window.history.pushState({}, '', url);
      
      // Smoothly load new content
      await loadVibe(currentSpecies, term);
      
      // Scroll back to top of feed if we've scrolled down
      const barRect = bar.getBoundingClientRect();
      if (barRect.top < 0) {
          window.scrollTo({
              top: window.scrollY + barRect.top - 100,
              behavior: 'smooth'
          });
      }
    };

    chips.forEach(chip => {
      chip.addEventListener('click', handleFilter);
    });
  };

  const initFilterGroups = () => {
    const groups = document.querySelectorAll('.dc-filters__group[data-filter-group]');
    if (!groups.length) {
      return;
    }

    const normalizePath = (path) => path.replace(/\/+$/, '');
    const currentPath = normalizePath(window.location.pathname);

    groups.forEach((group) => {
      const limit = parseInt(group.dataset.limit || '8', 10);
      const chips = Array.from(group.querySelectorAll('a.dc-chip'));
      const toggle = group.querySelector('[data-filter-toggle]');

      if (!toggle || chips.length <= limit) {
        if (toggle) {
          toggle.remove();
        }
        return;
      }

      let activeChip = null;
      chips.forEach((chip) => {
        try {
          const chipPath = normalizePath(new URL(chip.href).pathname);
          if (chipPath === currentPath) {
            chip.classList.add('is-active');
            activeChip = chip;
          }
        } catch (err) {
          // Ignore malformed URLs.
        }
      });

      const applyState = (expanded) => {
        group.classList.toggle('is-expanded', expanded);
        chips.forEach((chip, index) => {
          const shouldHide = !expanded && index >= limit && chip !== activeChip;
          chip.classList.toggle('is-hidden', shouldHide);
          chip.setAttribute('aria-hidden', shouldHide ? 'true' : 'false');
        });
        toggle.textContent = expanded
          ? toggle.dataset.labelLess || 'Less'
          : toggle.dataset.labelMore || 'More';
      };

      const startExpanded = activeChip && chips.indexOf(activeChip) >= limit;
      applyState(startExpanded);

      toggle.addEventListener('click', () => {
        applyState(!group.classList.contains('is-expanded'));
      });
    });
  };

  const initFeedStateLinks = () => {
    if (document.body.classList.contains('single-pets')) {
      return;
    }

    const isPetLink = (link) => {
      if (!link || !link.href) {
        return false;
      }

      const url = new URL(link.href, window.location.origin);
      return url.origin === window.location.origin && url.pathname.includes('/pets/');
    };

    const getFeedUrl = () => {
      const feedUrl = new URL(window.location.href);
      feedUrl.searchParams.delete('from');
      feedUrl.searchParams.delete('scroll');
      feedUrl.searchParams.set('from', 'feed');
      feedUrl.searchParams.set('scroll', `${Math.round(window.scrollY)}`);
      return feedUrl;
    };

    const decorateLink = (link) => {
      if (!isPetLink(link)) {
        return;
      }

      const feedUrl = getFeedUrl();
      const petUrl = new URL(link.href, window.location.origin);
      const scroll = feedUrl.searchParams.get('scroll');

      petUrl.searchParams.set('from', 'feed');
      if (scroll) {
        petUrl.searchParams.set('scroll', scroll);
      }
      petUrl.searchParams.set('dc_feed', `${feedUrl.pathname}${feedUrl.search}`);

      ['species', 'vibe'].forEach((key) => {
        const value = feedUrl.searchParams.get(key);
        if (value) {
          petUrl.searchParams.set(key, value);
        }
      });

      link.href = petUrl.toString();
    };

    const handleIntent = (event) => {
      const link = event.target.closest('a');
      if (!link || !link.closest('.dc-query')) {
        return;
      }

      decorateLink(link);
    };

    document.addEventListener('pointerdown', handleIntent);
    document.addEventListener('auxclick', handleIntent);
    document.addEventListener('contextmenu', handleIntent);
    document.addEventListener('mouseover', handleIntent);
    document.addEventListener('focusin', handleIntent);
  };

  const initFeedReturnLink = () => {
    if (!document.body.classList.contains('single-pets')) {
      return;
    }

    const wrappers = document.querySelectorAll('.dc-back-to-feed');
    if (!wrappers.length) {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    if (params.get('from') !== 'feed') {
      wrappers.forEach((wrapper) => {
        wrapper.style.display = 'none';
      });
      return;
    }

    const feedParam = params.get('dc_feed') || '';
    const scroll = params.get('scroll') || '';
    let backUrl;

    try {
      backUrl = feedParam
        ? new URL(decodeURIComponent(feedParam), window.location.origin)
        : new URL('/pets/', window.location.origin);
    } catch (error) {
      backUrl = new URL('/pets/', window.location.origin);
    }

    if (scroll) {
      backUrl.searchParams.set('scroll', scroll);
    }
    backUrl.searchParams.set('from', 'feed');

    ['species', 'vibe'].forEach((key) => {
      const value = params.get(key);
      if (value && !backUrl.searchParams.get(key)) {
        backUrl.searchParams.set(key, value);
      }
    });

    wrappers.forEach((wrapper) => {
      const link = wrapper.querySelector('a');
      if (link) {
        link.href = backUrl.toString();
        link.textContent = 'Back to feed';
      }
    });
  };

  const restoreFeedScroll = () => {
    if (document.body.classList.contains('single-pets')) {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    if (params.get('from') !== 'feed') {
      return;
    }

    const target = parseInt(params.get('scroll') || '0', 10);
    if (!target) {
      return;
    }

    let attempts = 0;
    const maxAttempts = 12;

    const cleanup = () => {
      const url = new URL(window.location.href);
      url.searchParams.delete('scroll');
      url.searchParams.delete('from');
      window.history.replaceState({}, '', url.toString());
    };

    const step = () => {
      const maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
      if (target <= maxScroll || attempts >= maxAttempts) {
        window.scrollTo(0, Math.min(target, maxScroll));
        cleanup();
        return;
      }

      attempts += 1;
      window.scrollTo(0, maxScroll);
      window.setTimeout(step, 450);
    };

    window.setTimeout(step, 250);
  };

  const initRelatedLoadMore = () => {
    const btn = document.querySelector('.dc-load-more-related');
    const grid = document.querySelector('#dc-related-grid');
    if (!btn || !grid) return;

    const postId = grid.dataset.postId;

    btn.addEventListener('click', async () => {
      const originalText = btn.textContent;
      btn.textContent = 'Loading...';
      btn.disabled = true;

      const offset = parseInt(btn.dataset.offset, 10);
      const apiUrl = `${window.damncuteData.restUrl}/related/${postId}?offset=${offset}`;

      try {
        const res = await fetch(apiUrl);
        const data = await res.json();

        if (data.html) {
          const temp = document.createElement('div');
          temp.innerHTML = data.html;
          
          // Image fade-in
          temp.querySelectorAll('img').forEach(img => {
              img.style.opacity = '0';
              img.style.transition = 'opacity 0.6s ease';
              img.onload = () => { img.style.opacity = '1'; };
          });

          while (temp.firstChild) {
            grid.appendChild(temp.firstChild);
          }
          
          // Re-bind interactions
          initReactions(); 
          
          btn.dataset.offset = offset + 8; // Increment by batch size
          btn.textContent = originalText;
          btn.disabled = false;

          if (!data.has_next) {
            btn.style.display = 'none';
          }
        } else {
          btn.style.display = 'none';
        }
      } catch (err) {
        console.error(err);
        btn.textContent = 'Error';
      }
    });
  };

  const initCommentToggle = () => {
    const toggle = document.querySelector('[data-toggle-comments]');
    const comments = document.querySelector('.dc-comments');
    if (!toggle || !comments) return;

    toggle.addEventListener('click', () => {
      const isOpen = comments.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen);
      
      if (isOpen) {
        comments.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  };

  const initForminatorFileClear = () => {
    const updateField = (input) => {
      if (!input) return;
      const field = input.closest('.forminator-file-upload');
      if (!field) return;
      const clear = field.querySelector('.forminator-button-delete');
      if (!clear) return;

      const hasFile = !!(input.files && input.files.length);
      field.dataset.hasFile = hasFile ? '1' : '0';
      clear.style.display = hasFile ? 'inline-flex' : 'none';
    };

    document
      .querySelectorAll('.forminator-file-upload input[type="file"]')
      .forEach((input) => updateField(input));

    document.addEventListener('change', (event) => {
      const input =
        event.target instanceof HTMLInputElement
          ? event.target
          : null;
      if (
        input &&
        input.matches('.forminator-file-upload input[type="file"]')
      ) {
        updateField(input);
      }
    });

    document.addEventListener('click', (event) => {
      const button = event.target.closest(
        '.forminator-file-upload .forminator-button-delete'
      );
      if (!button) return;

      const input = button
        .closest('.forminator-file-upload')
        ?.querySelector('input[type="file"]');
      if (!input) return;

      window.setTimeout(() => updateField(input), 0);
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    initReactions();
    initShare();
    initNav();
    initFloatingCta();
    scrollController = initInfiniteScroll();
    initFilterBar();
    initFilterGroups();
    initFeedStateLinks();
    initFeedReturnLink();
    restoreFeedScroll();
    initRelatedLoadMore();
    initCommentToggle();
    initForminatorFileClear();
  });
})();
