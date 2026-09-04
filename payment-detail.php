<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'Payment Details';
require __DIR__ . '/includes/header.php';
?>

<div class="policy-page">
  <h1>Payment Details</h1>
  <p class="updated">How you can pay for your order.</p>

  <h2>Cash on Delivery (COD)</h2>
  <p>All orders are currently paid for on delivery. Place your order online, and pay in cash to the rider when your package arrives at your doorstep — no advance payment required.</p>

  <h2>Online Payment — Coming Soon</h2>
  <p>We're working on adding online payment options (card, JazzCash, Easypaisa) for a faster checkout. Until then, all orders go through Cash on Delivery.</p>

  <h2>Order Confirmation</h2>
  <p>Once you place an order, you'll see an order confirmation on screen with your order number and total. If anything is unclear, you're welcome to confirm your order with us directly over <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener">WhatsApp</a>.</p>

  <h2>Questions?</h2>
  <p>If you have any questions about payment before placing an order, reach out via our <a href="contact.php">Contact Us</a> page.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
