# Stage 04 - BPMN Modeler

## Goal

This stage adds:

- an embedded `bpmn-js` modeler
- diagram preview in process details
- import BPMN file
- export BPMN XML
- visual edit flow for new versions

## Run

```bash
npm run build
php artisan serve
```

If you prefer a live frontend dev server, use:

```bash
npm run dev
php artisan serve
```

After rebuilding assets, do a hard refresh in the browser so the new JS bundle is loaded.

## Local URLs

Use the exact host/port printed by `php artisan serve`.

If Laravel prints:

```bash
Server running on [http://127.0.0.1:8000]
```

then the main test URLs for this stage are:

- Login: `http://127.0.0.1:8000/login`
- Process library: `http://127.0.0.1:8000/process-definitions`
- Create process family: `http://127.0.0.1:8000/process-definitions/create`
- Example detail page: `http://127.0.0.1:8000/process-definitions/1`

If your local Laravel server is on another port such as `8011`, replace only the port.

## Test

1. Open `http://127.0.0.1:8000/login`
2. Login with `admin@bpms.test` / `password`
3. Open `http://127.0.0.1:8000/process-definitions`
4. Click `Create process family` or open an existing definition detail page
5. Confirm the BPMN canvas appears on the create/version form
6. Move or add elements in the diagram and save
7. Open the saved definition detail page and confirm the preview renders
8. Use `Download BPMN XML` and confirm a `.bpmn` file downloads
9. Use `Import BPMN file` on a create/version form and confirm the canvas updates

## Legacy note

If you created process definitions before this stage had full `BPMN DI` layout metadata, the preview page will stay blank by design because those older XML files have no render coordinates.

To upgrade an older definition:

1. Open `Create next version`
2. Click `Load starter diagram` or import a BPMN exported from a modeler
3. Save the new version

The newly saved version will then render in both the modeler and preview pages.

## Expected result

- Admins can edit BPMN visually instead of only editing raw XML
- The designer form uses a wide canvas so drawing the process graph is practical on desktop
- The saved definition still lands in PostgreSQL
- The definition detail page renders a BPMN preview
- Operators remain blocked from this admin-only area
