# APT-Vault
ICT 2213Y group project-Password Manager

To run locally:
1. Download the zip from the main branch and then
copy the APT-Vault-Main folder into your xampp\htdocs\.

2. Generate a unique encryption key.
In a command prompt with , run:

  cd C:\xampp\htdocs\APT-Vault-Main
  
  C:\xampp\php\php.exe scripts\generate-key.php

copy the key and paste in the .env file after APP_KEY=

Finally with Apache and MySql running, run http://localhost/APT-Vault-Main/public/
