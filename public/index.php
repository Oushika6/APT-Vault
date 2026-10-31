<?php
require_once __DIR__ . '/../src/bootstrap.php';
render_header('Secure passwords and notes', '', true);
?>
<main>
  <section class="hero">
    <div class="hero-inner container">
      <div class="project-tag">◈ &nbsp; CYBERSECURITY PROJECT — ICT2213</div>
      <h1>Your Passwords.<br>Your Notes.<br><em>Securely Stored.</em></h1>
      <p>APT Vault helps you securely manage your passwords, credentials and private notes in one protected vault.</p>
      <div class="hero-buttons"><a class="btn btn-primary" href="register.php">→ &nbsp; Get Started — It's Free</a><a class="btn btn-outline" href="#features">Learn More</a></div>
    </div>
    <div class="hero-curve" aria-hidden="true"></div>
  </section>
  <section class="section container" id="features"><p class="eyebrow">BUILT FOR EVERYDAY SECURITY</p><h2>Everything in one secure place</h2>
    <div class="feature-grid">
      <article class="feature-card"><span class="feature-icon">⌑</span><h3>Password Vault</h3><p>Save, search and organize login credentials. Reveal them only when needed.</p></article>
      <article class="feature-card"><span class="feature-icon">▤</span><h3>Secure Notes</h3><p>Protect important notes using authenticated encryption before they reach the database.</p></article>
      <article class="feature-card"><span class="feature-icon">▦</span><h3>Categories</h3><p>Keep your passwords and notes organized in your own private categories.</p></article>
    </div>
  </section>
  <section class="section section-tinted" id="security"><div class="container"><p class="eyebrow">SECURITY FIRST</p><h2>Security decisions you can explain</h2><p>Account passwords are hashed; vault secrets are encrypted with AES-256-GCM before storage. Each database operation checks ownership. Prepared statements and CSRF tokens protect sensitive actions.</p><p class="note-muted">Coursework prototype: application-managed encryption, not a zero-knowledge service. Never store actual personal passwords in this demo.</p></div></section>
  <section class="section container" id="faq"><p class="eyebrow">FAQ</p><h2>Frequently asked questions</h2><details><summary>Do you store my account password?</summary><p>Only a one-way PHP password hash is stored for your account authentication.</p></details><details><summary>Can administrators see my vault?</summary><p>No. Admin pages show account and support information, and do not provide vault secret retrieval endpoints.</p></details><details><summary>Is this production ready?</summary><p>No. APT Vault is an educational prototype; use fictional demonstration data only.</p></details></section>
</main>
<?php render_footer(); ?>
