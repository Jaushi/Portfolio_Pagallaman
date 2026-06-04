# WebDev-Pagallaman

Local portfolio site with a PHP-powered contact endpoint.

## Run locally

Use the portable PHP build that was added to `php-portable/`:

```powershell
.\php-portable\php.exe -S localhost:8000 router.php
```

Open:

- `http://localhost:8000/index.html`
- `http://localhost:8000/project-experience.html`
- `http://localhost:8000/admin` for the owner-only message viewer

## Contact form data

Submitted messages are saved outside the web root in `../WebDev-Pagallaman-private/messages.jsonl` and also logged by PHP on the server side.

## Owner login

The protected admin viewer uses HTTP basic auth. The password lives in the ignored local file `server/admin-config.local.php`:

- Username: `owner`
- Password: set in `server/admin-config.local.php`