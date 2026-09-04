<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'Contact Us';
require __DIR__ . '/includes/header.php';
?>

<div class="contact-page">
  <div>
    <h1>Contact Us</h1>
    <p style="color:var(--ink-soft); margin-bottom:30px;">Questions about a product, an existing order, or anything else — reach out and we'll get back to you as soon as we can.</p>

    <div class="contact-card">
      <div class="row">
        <span class="icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.85 9.85 0 0 0 12.04 2zm0 18.13h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.36c0-4.54 3.7-8.24 8.25-8.24a8.2 8.2 0 0 1 5.83 2.42 8.18 8.18 0 0 1 2.41 5.82c0 4.55-3.7 8.22-8.24 8.22z"/></svg>
        </span>
        <div>
          <div style="font-weight:500;">WhatsApp / Phone</div>
          <a href="<?= e(whatsapp_link('Hi! I have a question about a product.')) ?>" target="_blank" rel="noopener"><?= e(SITE_PHONE_DISPLAY) ?></a>
        </div>
      </div>
      <div class="row">
        <span class="icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M2 5.5A2.5 2.5 0 0 1 4.5 3h15A2.5 2.5 0 0 1 22 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-15A2.5 2.5 0 0 1 2 18.5v-13zm2.2.5 7.3 5.47a.8.8 0 0 0 1 0L19.8 6H4.2zM20 8.13l-6.9 5.17a2.8 2.8 0 0 1-3.4 0L4 8.13V18.5c0 .28.22.5.5.5h15a.5.5 0 0 0 .5-.5V8.13z"/></svg>
        </span>
        <div>
          <div style="font-weight:500;">Email</div>
          <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
        </div>
      </div>
      <div class="row">
        <span class="icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
        </span>
        <div>
          <div style="font-weight:500;">Based In</div>
          <div>Pakistan · Nationwide Delivery</div>
        </div>
      </div>
      <div class="row">
        <span class="icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 5v5.41l4.29 4.3-1.41 1.41L11 13V7h2z"/></svg>
        </span>
        <div>
          <div style="font-weight:500;">Response Time</div>
          <div>Usually within a few hours, daily</div>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="contact-card">
      <h3>Fastest way to reach us</h3>
      <p style="color:var(--ink-soft); margin-bottom:20px;">For order questions, sizing help, or anything urgent, WhatsApp is the quickest way to get a reply.</p>
      <a href="<?= e(whatsapp_link('Hi! I have a question about a product.')) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-block">Chat on WhatsApp</a>
      <a href="mailto:<?= e(SITE_EMAIL) ?>" class="btn btn-outline btn-block" style="margin-top:12px;">Email Us</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
