<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'Refund & Exchange Policy';
require __DIR__ . '/includes/header.php';
?>

<div class="policy-page">
  <h1>Refund &amp; Exchange Policy</h1>
  <p class="updated">Last updated: <?= date('F Y') ?></p>

  <h2>Exchanges</h2>
  <p>If your item arrives damaged, defective, or different from what you ordered, we're happy to arrange an exchange. Please contact us within <strong>3 days</strong> of receiving your order via <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener">WhatsApp</a> or <a href="mailto:<?= e(SITE_EMAIL) ?>">email</a>, along with your order number and a photo of the item.</p>

  <h2>Conditions for Exchange</h2>
  <ul>
    <li>The item must be unused, unworn, and in its original condition</li>
    <li>Request must be made within 3 days of delivery</li>
    <li>Proof of purchase (order number) is required</li>
  </ul>

  <h2>Non-Exchangeable Items</h2>
  <p>Earrings cannot be exchanged once delivered, for hygiene reasons, unless the item received is damaged or incorrect.</p>

  <h2>Refunds</h2>
  <p>As orders are paid via Cash on Delivery, refunds are generally handled as an exchange or store credit. If a suitable replacement isn't available, we'll work with you directly on a fair resolution.</p>

  <h2>How to Start an Exchange</h2>
  <ol>
    <li>Message us on <a href="<?= e(whatsapp_link('Hi, I would like to request an exchange for my order.')) ?>" target="_blank" rel="noopener">WhatsApp</a> with your order number</li>
    <li>Share a photo of the item and describe the issue</li>
    <li>We'll confirm the next steps, including pickup or return shipping if needed</li>
  </ol>

  <h2>Questions?</h2>
  <p>Reach out through our <a href="/contact">Contact Us</a> page — we're happy to help sort things out.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
