<?php
require_once __DIR__ . '/../src/bootstrap.php';
render_header('Privacy Policy');
?>
<main class="container main-content contact-page">
<p class="eyebrow">LEGAL</p>
<h1>Privacy Policy</h1>
<p>Last updated: <?= h(date('F Y')) ?></p>
<div class="panel contact-panel">

<h2>1. Introduction</h2>
<p>APT Vault is an educational prototype built for the module ICT2213Y Web Technologies and Security at the University of Mauritius. This policy explains what information the website collects, how it is used and how it is protected.</p>

<h2>2. Information we collect</h2>
<ul>
<li><strong>Account details:</strong> your full name, email address and a hashed version of your account password.</li>
<li><strong>Vault content:</strong> the passwords and secure notes you save, along with titles, usernames, website addresses and categories.</li>
<li><strong>Support messages:</strong> your name, email, subject and message when you use the contact form.</li>
<li><strong>Security logs:</strong> records of actions such as logins, to help detect misuse.</li>
</ul>

<h2>3. How we use your information</h2>
<ul>
<li>To create and manage your account and let you sign in.</li>
<li>To store and show your vault items only to you.</li>
<li>To answer your support requests.</li>
<li>To keep the website secure and investigate misuse.</li>
</ul>
<p>We do not sell your information and we do not share it with advertisers.</p>

<h2>4. How we protect your information</h2>
<ul>
<li>Account passwords are stored as one way hashes and never in plain text.</li>
<li>Vault passwords and secure note content are encrypted with AES 256 GCM before they are stored.</li>
<li>Database queries use prepared statements, and forms use CSRF tokens.</li>
<li>Every vault item is linked to its owner, so other users cannot open it.</li>
</ul>
<p><strong>Important limitation:</strong> the encryption key is held by the application server, so this is not zero knowledge encryption. Titles, usernames, website addresses and category names are stored without encryption. For this reason, please use fictional data only.</p>

<h2>5. Cookies and sessions</h2>
<p>The website uses a session cookie to keep you signed in. It is marked HttpOnly and is removed when you log out or close your session. We do not use advertising or tracking cookies.</p>

<h2>6. Who can see your data</h2>
<p>Administrators can see account and support information and security logs. The website does not give administrators a way to view your stored passwords or note contents.</p>

<h2>7. Keeping and deleting data</h2>
<p>Your data is kept while your account exists, or until the project database is reset at the end of the coursework. You can delete your own vault entries, notes and categories at any time. To ask for your account to be removed, please <a href="contact.php">contact us</a>.</p>

<h2>8. Your rights</h2>
<p>You may ask to see, correct or delete the personal information we hold about you. Use the contact page to make a request.</p>

<h2>9. Changes to this policy</h2>
<p>We may update this policy as the project develops. The date at the top shows when it was last changed.</p>

<h2>10. Contact</h2>
<p>For any privacy question, use our <a href="contact.php">contact page</a>. You can also read our <a href="terms.php">Terms and Conditions</a>.</p>

</div>
</main>
<?php render_footer(); ?>
