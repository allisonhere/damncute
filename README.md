# Damn Cute WordPress Theme

## Architecture Overview
- Block theme powered by `theme.json` and FSE templates.
- Custom content model in `functions.php` for Pets + taxonomies.
- Minimal JS (`assets/js/theme.js`) for share/like UI only.
- Visual system driven by CSS variables + `clamp()` and modern layout primitives.

## Folder Structure
- `theme.json`: design tokens, global styles, block defaults.
- `functions.php`: CPTs, taxonomies, meta, shortcodes, submit handler.
- `templates/`: front page, single pet, archive, submit page.
- `parts/`: header + footer.
- `patterns/`: hero grid, trending, pet of day, submit CTA.
- `assets/css/theme.css`: component + layout styling.
- `assets/js/theme.js`: like/share UI behavior.

## CSS Architecture
- Global tokens in `:root` and dark mode overrides in `prefers-color-scheme`.
- Component classes prefixed with `.dc-` for clear targeting.
- Layout primitives: grid, flex, aspect-ratio, `clamp()` sizes.
- Motion kept to a single `dc-fade-up` entrance animation.

## Extension Points
- Add vote backend: hook into `dc-like` button via AJAX and store `vote_count`.
- Infinite scroll: replace the archive query with a custom block or JS fetch.
- Community features: add `comment` support or a custom “Reacts” taxonomy.
- Font upgrade: drop WOFF2 into `assets/fonts/` and update `theme.json` + CSS.
- Trending section currently sorts by date; swap to `vote_count` once voting is live.

## Submit Form
- The submit form uses `[damncute_pet_submit_form]` and renders a Forminator form (ID set in Settings → General).
- Submissions create `pets` posts as `pending` and map fields by Forminator element IDs (text-1, textarea-1, text-2, text-3, select-1..4, upload-1).

## Setup Notes
- Create a page with slug `submit` to use `templates/page-submit.html`.
- Default CPT archive is `/pets`.
