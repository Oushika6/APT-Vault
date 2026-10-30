<?php
require_once __DIR__ . '/../src/bootstrap.php';
render_header('Terms and Conditions');
?>
<main class="container main-content contact-page">
<p class="eyebrow">LEGAL</p>
<h1>Terms and Conditions</h1>
<p>Last updated: <?= h(date('F Y')) ?></p>
<div class="panel contact-panel">

<h2>1. About APT Vault</h2>
<p>APT Vault is an educational prototype created by students of the University of Mauritius for the module ICT2213Y Web Technologies and Security. It lets you store passwords and private notes in a personal vault. By creating an account or using this website, you agree to these terms.</p>

<h2>2. Educational use only</h2>
<p>This website is a coursework project. It is not a production password manager. Please use fictional demonstration data only. Do not store real passwords, banking details or sensitive personal information.</p>

<h2>3. Your account</h2>
<ul>
<li>You must provide accurate information when you register.</li>
<li>You are responsible for keeping your account password confidential.</li>
<li>You must tell us if you think someone else has used your account.</li>
<li>One person should not use another person's account.</li>
</ul>

<h2>4. Acceptable use</h2>
<p>You agree not to:</p>
<ul>
<li>try to access another user's vault, notes or account;</li>
<li>attempt to disrupt, overload or break the website;</li>
<li>upload harmful code or use the service for illegal activity;</li>
<li>misuse the contact form to send spam or abusive messages.</li>
</ul>

<h2>5. Your data and security</h2>
<p>Account passwords are stored as one way hashes. Vault passwords and secure notes are encrypted before they are saved. However, this is server managed encryption, not zero knowledge encryption, so the application server holds the encryption key. Some details such as titles, usernames, website addresses and category names are not encrypted. See our Privacy Policy for more information.</p>

<h2>6. Suspension of accounts</h2>
<p>Administrators may suspend an account that breaks these terms or threatens the safety of the system. Administrators can view account and support information but cannot read your stored vault secrets through the website.</p>

<h2>7. No warranty</h2>
<p>The service is provided as is, without any guarantee of availability, accuracy or security. The project team and the University of Mauritius are not responsible for any loss of data or damage that results from using this prototype.</p>

<h2>8. Changes to these terms</h2>
<p>We may update these terms as the project develops. Continued use of the website after a change means you accept the updated terms.</p>

<h2>9. Contact</h2>
<p>If you have questions about these terms, please use our <a href="contact.php">contact page</a>. You can also read our <a href="privacy.php">Privacy Policy</a>.</p>

</div>
</main>
<?php render_footer(); ?>
