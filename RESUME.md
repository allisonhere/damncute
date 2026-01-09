# DamnCute Theme Resume Notes

Last updated: 2025-03-08

## Current focus
- Breed Manager UX + imports + breed type mapping.
- Forminator conditional breed fields + breed type handling.

## Admin UX changes
- Added a dedicated "Breed Manager" page with tabs: Manual (default) and Import.
- Both tabs show current breeds with columns: Breed, Breed Type, Species.
- Added Danger Zone "Delete All Breeds" with rigid confirmation.

## Import behavior
- Breed import supports CSV rows as `breed,type` or header `name,type`.
- Species chosen in the import form is applied to all rows.
- Breed type per row overrides the dropdown (dropdown is a fallback).
- Type values accepted (case-insensitive):
  - AKC Registered (`akc`)
  - Designer (`designer`)
  - Just Cute (`just-cute`)
  - Purebred (`purebred`)
  - Mixed (`mixed`)

## Breed Manager filtering/sorting
- Client-side filters: search, species, breed type.
- Client-side sorting on Breed / Breed Type / Species.
- JS: `assets/js/breed-manager.js`
- CSS: `assets/css/admin.css`

## Breed type storage
- Stored on breed term meta: `_damncute_breed_type`
- Species mapping stored on breed term meta: `_damncute_breed_species`
- Pet meta for breed type: `breed_type`

## Forminator setup needed
- Add Breed Type select field (options include all 5 types).
- Conditional logic: show Breed Type only when Species is Dog or Cat.
- Create separate Breed fields per type if filtering is needed:
  - Breed (AKC), Breed (Designer), Breed (Just Cute), Breed (Purebred), Breed (Mixed)
  - Each field shows only for matching Species + Breed Type.
- Provide field IDs so handler can map them.

## Recent file edits
- `functions.php`
  - Breed Manager page
  - Import parsing + term meta updates
  - Breed list table + filters
  - Breed type metabox (species-aware)
  - Delete all breeds action
- `inc/class-pet-submission-handler.php`
  - Breed type mapping for dog/cat submissions
- `assets/js/breed-manager.js`
  - Filter/sort logic
- `assets/css/admin.css`
  - Breed manager filter styling

## Known issues / reminders
- Import previously failed due to nested form; now separate Breed Manager page.
- If Breed Type is included in CSV, dropdown can be ignored.
- If using conditional Breed fields in Forminator, update handler with new field IDs.

## Next steps
- Add Forminator field ID mapping once IDs are known.
- Optionally add "Unmapped only" filter in Breed Manager.
- Optionally add a bulk update tool for existing breed types.
