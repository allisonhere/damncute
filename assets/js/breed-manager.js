(() => {
  const table = document.querySelector('#damncute-breed-table');
  if (!table) {
    return;
  }

  const tbody = table.querySelector('tbody');
  const rows = Array.from(tbody.querySelectorAll('tr'));
  const searchInput = document.querySelector('[data-breed-filter="search"]');
  const speciesSelect = document.querySelector('[data-breed-filter="species"]');
  const typeSelect = document.querySelector('[data-breed-filter="type"]');
  const sortHeaders = table.querySelectorAll('[data-breed-sort]');

  const state = {
    sortKey: 'breed',
    sortDir: 'asc',
    search: '',
    species: '',
    type: '',
  };

  const normalize = (value) => String(value || '').toLowerCase();

  const updateRows = () => {
    const filtered = rows.filter((row) => {
      const breed = normalize(row.dataset.breed);
      const species = normalize(row.dataset.species);
      const type = normalize(row.dataset.type);

      if (state.search && !breed.includes(state.search)) {
        return false;
      }
      if (state.species && !species.split(',').includes(state.species)) {
        return false;
      }
      if (state.type && type !== state.type) {
        return false;
      }
      return true;
    });

    filtered.sort((a, b) => {
      const aVal = normalize(a.dataset[state.sortKey]);
      const bVal = normalize(b.dataset[state.sortKey]);
      if (aVal === bVal) return 0;
      const result = aVal.localeCompare(bVal);
      return state.sortDir === 'asc' ? result : -result;
    });

    rows.forEach((row) => row.remove());
    filtered.forEach((row) => tbody.appendChild(row));
  };

  if (searchInput) {
    searchInput.addEventListener('input', (event) => {
      state.search = normalize(event.target.value);
      updateRows();
    });
  }

  if (speciesSelect) {
    speciesSelect.addEventListener('change', (event) => {
      state.species = normalize(event.target.value);
      updateRows();
    });
  }

  if (typeSelect) {
    typeSelect.addEventListener('change', (event) => {
      state.type = normalize(event.target.value);
      updateRows();
    });
  }

  sortHeaders.forEach((header) => {
    header.addEventListener('click', () => {
      const key = header.dataset.breedSort;
      if (state.sortKey === key) {
        state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
      } else {
        state.sortKey = key;
        state.sortDir = 'asc';
      }

      sortHeaders.forEach((h) => h.classList.remove('is-sorted', 'is-desc'));
      header.classList.add('is-sorted');
      if (state.sortDir === 'desc') {
        header.classList.add('is-desc');
      }

      updateRows();
    });
  });

  updateRows();
})();
