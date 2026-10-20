<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'FAQs';

$faqs = [
    ['How do I place an order?', 'Browse our Bangles, Bracelets, Earrings, Pendants, and Rings, add the pieces you like to your cart, then go to checkout and fill in your delivery details. No account needed.'],
    ['What payment methods do you accept?', 'Currently we accept Cash on Delivery (COD) — you pay in cash when your order arrives at your doorstep. Online payment options are coming soon.'],
    ['How long does delivery take?', 'Most orders are delivered within 3–5 working days, depending on your city. See our Shipping Details page for more.'],
    ['Can I exchange or return a product?', 'Yes — see our Refund & Exchange Policy page for the full details on exchanges.'],
    ['How do I know my bangle size?', 'Check our Bangle Size Guide for step-by-step instructions on measuring your wrist at home.'],
    ['How can I track my order?', 'Once your order is placed, we\'ll contact you on the phone number you provide with updates. For any questions in the meantime, message us on WhatsApp.'],
    ['Do you deliver across Pakistan?', 'Yes, we deliver nationwide.'],
    ['How can I contact you?', 'The fastest way is WhatsApp — see our Contact Us page for the link, phone number and email.'],
];

require __DIR__ . '/includes/header.php';
?>

<div class="policy-page">
  <h1>Frequently Asked Questions</h1>
  <p class="updated">Everything you need to know before you order.</p>

  <?php foreach ($faqs as $i => $faq): ?>
    <details class="faq-item" <?= $i === 0 ? 'open' : '' ?>>
      <summary><?= e($faq[0]) ?></summary>
      <p><?= e($faq[1]) ?></p>
    </details>
  <?php endforeach; ?>

  <p style="margin-top:2.5em;">Still have a question? <a href="contact">Get in touch</a> — we're happy to help.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
