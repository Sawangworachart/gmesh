# Restructure Map

This project now uses a transitional module-based structure.

## Root wrappers

Legacy entry files at the project root remain in place and now `require` the real files under `app/`.

## Main locations

- `app/config`
- `app/bootstrap`
- `app/shared/partials`
- `app/modules/auth`
- `app/modules/dashboard`
- `app/modules/customers`
- `app/modules/users`
- `app/modules/pm`
- `app/modules/products`
- `app/modules/service`
- `app/modules/legacy`
- `public/assets`
- `storage/uploads`

## Notes

- Original runtime uploads remain under `uploads/` for compatibility.
- New organized asset copies now also exist under `public/assets/`.
- Files that looked experimental or duplicated were moved under `app/modules/legacy/`.
- `pm_project_extra_modals.php` was replaced with a placeholder partial because the original file was not present in the repository.
- `save_detail.php` was added as a compatibility endpoint and currently redirects back to the detail page until the original save logic is restored.
