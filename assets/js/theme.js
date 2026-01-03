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

  const fixSubmitLinks = () => {
    // Find all links that might be pointing to the broken permalink
    const links = document.querySelectorAll('a[href*="submit"]');
    links.forEach(link => {
      try {
        const url = new URL(link.href);
        // If the path ends in /submit or /submit/, fix it
        if (url.pathname.match(/\/submit\/?$/)) {
           // Remove 'submit' from the path
           const newPath = url.pathname.replace(/\/submit\/?$/, '/');
           // Construct the new URL with the query param
           // Preserve existing base path (e.g. /damncute/)
           url.pathname = newPath; 
           url.searchParams.set('dc_route', 'submit');
           link.href = url.toString();
        }
      } catch (e) {
        // Ignore invalid URLs
      }
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    initLikes();
    initShare();
    fixSubmitLinks();
  });
})();
