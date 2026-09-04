<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'Shipping Details';
require __DIR__ . '/includes/header.php';
?>

<div class="policy-page">
  <h1>Shipping Details</h1>
  <p class="updated">Where and how fast we deliver.</p>

  <h2>Delivery Coverage</h2>
  <p>We deliver nationwide across Pakistan, including major cities and most smaller towns via our courier partners.</p>

  <h2>Delivery Time</h2>
  <ul>
    <li>Major cities (Lahore, Karachi, Islamabad, Rawalpindi, Faisalabad, etc.): 2–4 working days</li>
    <li>Other cities and towns: 3–6 working days</li>
  </ul>
  <p>Delivery times may vary slightly during sales, holidays, or due to courier delays outside our control.</p>

  <h2>Shipping Charges</h2>
  <p>Any applicable delivery charges are shown at checkout before you place your order — the total you see is the total you pay.</p>

  <h2>Order Tracking</h2>
  <p>After your order is dispatched, we'll share tracking details with you via the phone number provided at checkout. You can also message us on <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener">WhatsApp</a> anytime for an update.</p>

  <h2>Failed Delivery Attempts</h2>
  <p>Please make sure your address and phone number are correct at checkout. If our courier is unable to reach you after multiple attempts, the order may be returned — in that case we'll contact you to arrange redelivery.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
