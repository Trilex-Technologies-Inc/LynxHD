LynxHD Module Development Guide

Overview

Modules are self-contained features stored in modules/<module-name>. The module manager automatically discovers directories containing module.json and bootstrap.php.

1. Create the module structure

modules/example/
  module.json
  bootstrap.php

Optional management screen:

admin/example.php

2. Create module.json

{
  "name": "Example Module",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "A short explanation of the module.",
  "icon": "fa-puzzle-piece",
  "manage_url": "example.php"
}

The directory name may contain lowercase letters, numbers, hyphens, and underscores. icon uses Font Awesome 5. Omit manage_url if the module has no management page.

3. Implement lifecycle functions

For modules/example/bootstrap.php, define:

example_install()
example_uninstall()
example_enable()
example_disable()

Every function must return true on success and false on failure. Install creates required tables and initial settings. Uninstall removes only module-owned data. Enable and disable must preserve data.

Example:

<?php
function example_install()
{
    global $pre;
    return (bool) mysql_query("CREATE TABLE IF NOT EXISTS {$pre}example (...)");
}

function example_uninstall()
{
    global $pre;
    return (bool) mysql_query("DROP TABLE IF EXISTS {$pre}example");
}

function example_enable() { return true; }
function example_disable() { return true; }

4. Protect management pages

Include settings, shared functions, and the module system. Require an authenticated staff user and verify that the module is enabled:

include '../include/settings.php';
include '../include/include.php';
include '../modules/system.php';

if (!hd_module_enabled('example')) {
    header('Location: modules.php');
    exit;
}

5. Security requirements

- Use POST for state-changing actions.
- Add session CSRF tokens and verify with hash_equals().
- Cast numeric identifiers to integers.
- Escape SQL text with hd_module_escape().
- Escape HTML with htmlspecialchars() or field().
- Check permissions on the server.
- Confirm destructive actions and delete only module-owned data.

6. Fresh installations

Add module-owned table definitions to docs/lynxhd.sql if they should exist in a fresh LynxHD installation. Runtime installation must still work through the install lifecycle function.

7. Verification

- Run php -l for each PHP file.
- Validate module.json with a JSON parser.
- Run git diff --check.
- Test install, disable, enable, and uninstall.
- Confirm disable preserves data.
- Confirm uninstall removes only module data.
- Test direct management-page access while disabled and logged out.

The Modules page discovers a valid module automatically. You do not need to edit admin/modules.php.

8. ZIP distribution

Package the complete module directory as a ZIP, keeping the directory as the archive's top-level folder:

  example.zip
    example/
      module.json
      bootstrap.php

Add a lowercase `slug` field to module.json when possible. Administrators can upload the ZIP from the Modules page; LynxHD validates its paths and manifest, places it under modules/<slug>, and runs the normal install and enable lifecycle automatically. Uploads are limited to 10 MB and cannot replace an existing module directory.
