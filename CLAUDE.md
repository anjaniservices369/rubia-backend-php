# Notes for AI assistants working on this repo

- Docs: after changing code run (from app/) php yii docs/annotate --apply, php yii docs/reference, then php yii docs/check. Keep BUSINESS_GUIDE, DEVELOPER_GUIDE and MOBILE_APP_CHANGES current for behaviour changes.
- New classes in libs/platform: regenerate the classmap with php yii service/pack --lib=platform.
- Database changes are numbered SQL pairs in app/database/migrations. Check php yii db/status first and apply only what is pending; never run db/up blindly. Dev database only; never touch production.
- Maps: MapProviders order comes from setting maps_provider_order (Ola, Google, Mapbox, OpenStreetMap). Keys live in Integrations, never in code or git.
- Account deletion: app/components/AccountDeletion.php, endpoints user/delete-account and driver/delete-account, public page /account-deletion, table account_deletion_request.
- Local PHP: D:/xampp/php/php.exe; console runs that call out over https need -d curl.cainfo=<ca bundle>.
- When editing files with scripts, a backslash followed by a letter inside a normal Python string becomes a control character; use raw strings or the Edit tool.
