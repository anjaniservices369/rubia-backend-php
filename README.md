# Rubia Services backend

Start here.

| You are... | Read |
|---|---|
| The owner / a non-technical manager | `BUSINESS_GUIDE.md` - what the system does, rules, money, dashboard, what to do when something breaks |
| A developer new to the project | `DEVELOPER_GUIDE.md` - architecture, states, guards, config, commands, test scenarios |
| Putting a new version on the server | `DEPLOY.md` |
| Looking for where a service lives | `MODULES.md` |
| Working on uploads / images | `MEDIA_STORAGE.md` |
| Updating the Customer or Partner mobile app | `MOBILE_APP_CHANGES.md` |
| Changing the database | `app/database/README.md` |

Layout: `app/` is the Yii2 application (`modules/` one folder per service, `commands/` console jobs, `config/`,
`database/migrations/`). Everything outside `app/` is documentation and release packages.

| Looking for the full list of URLs, states, settings, commands | `REFERENCE.md` (generated - run `php yii docs/reference`) |
| The plan to give every service its own backend, database and apps (only the user app common) | `ARCHITECTURE_TARGET.md` and the empty skeleton in `target-layout/` |
