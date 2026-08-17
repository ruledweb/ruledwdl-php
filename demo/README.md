# WDL PHP Core Layout Engine Demo

This directory contains standalone, zero-dependency examples demonstrating how to use the WDL (Web Definition Language) PHP layout engine.

## Contents

* **[CLI Demo](file:///home/pradeep/cloudflare/workers/wdl/libraries/ruledwdl-php/demo/cli.php)**: A simple terminal-based script compiling a layout from the command line and writing it to a file.
* **[Web Playground](file:///home/pradeep/cloudflare/workers/wdl/libraries/ruledwdl-php/demo/web/index.php)**: An interactive browser-based dashboard. You can edit WDL layers markup, inject dynamic variables, change design/brand style tokens, and see visual previews instantly.

---

## Running the CLI Demo

From the root of the `ruledwdl-php` library folder, run:

```bash
php demo/cli.php
```

This compiles a mock layout structure, outputs status logs, and saves the final HTML code inside the demo directory as `demo/output.html`.

---

## Running the Web Playground

To launch the web editor and live compiler, spin up PHP's built-in web server pointing to the `web/` directory:

```bash
php -S localhost:8000 -t demo/web/
```

Then, open your browser and navigate to:
```
http://localhost:8000
```

### Playground Features:
* **Interactive Code Inputs**: Direct textareas to write WDL layout syntax, page JSON context data, design token properties, and CSS stylesheet definitions.
* **Prebuilt Presets**: One-click chips to load common UI templates (Hero Section, Features Grid, and Interactive Form).
* **Self-Contained Autoloader**: Auto-loads WDL engine modules under [src/](file:///home/pradeep/cloudflare/workers/wdl/libraries/ruledwdl-php/src/) instantly without needing a full `composer install`.
