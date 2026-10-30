APT Vault - Member 3 files
===========================

Files included in public/:
- passwords.php
- password-form.php
- password-delete.php
- password-reveal.php

INSTALLATION
1. Extract this ZIP.
2. Copy the four PHP files from its public/ folder into the public/ folder
   of your existing APT-Vault project.
3. Do not copy them into the repository root.
4. Commit them on your own feature branch and open a pull request.

IMPORTANT DEPENDENCIES
These files are taken from the supplied Week 10 APT-Vault project and rely
on the existing shared project files, especially:
- src/bootstrap.php
- the database connection and helper functions loaded by bootstrap.php
- the password_entries and categories database tables
- the existing CSS/JavaScript assets and layout helpers

Do not replace the shared src/, database/, CSS, or JavaScript files with
this ZIP. Coordinate with the group before changing shared files.

PHP syntax check: all four files passed `php -l`.
This confirms syntax only; test add/edit/delete/reveal/copy with the
group's database and full project before submission.
