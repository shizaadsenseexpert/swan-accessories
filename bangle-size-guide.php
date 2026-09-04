<?php
require_once __DIR__ . '/includes/functions.php';
$basePath = '';
$pageTitle = 'Bangle Size Guide';
require __DIR__ . '/includes/header.php';
?>

<div class="policy-page">
  <h1>Bangle Size Guide</h1>
  <p class="updated">Measure at home in under a minute.</p>

  <h2>Method 1: Measure an Existing Bangle</h2>
  <p>Take a bangle that already fits you well and measure the inner diameter (straight across the inside, not around the edge) in centimetres.</p>

  <h2>Method 2: Measure Your Hand</h2>
  <ol>
    <li>Bring your thumb and little finger together to make your hand as narrow as possible, the way you would to slide a bangle on</li>
    <li>Wrap a soft measuring tape (or a strip of paper/string) around the widest part of your hand</li>
    <li>Mark where it overlaps and measure the length in centimetres — if using string, lay it flat against a ruler</li>
    <li>Divide that number by 3.14 (pi) to get your bangle's inner diameter</li>
  </ol>

  <h2>Size Reference</h2>
  <table class="admin-table" style="margin-top:10px;">
    <thead><tr><th>Inner Diameter (cm)</th><th>Approx. Size</th></tr></thead>
    <tbody>
      <tr><td>6.0 cm</td><td>Extra Small</td></tr>
      <tr><td>6.6 cm</td><td>Small</td></tr>
      <tr><td>7.1 cm</td><td>Medium</td></tr>
      <tr><td>7.6 cm</td><td>Large</td></tr>
      <tr><td>8.1 cm</td><td>Extra Large</td></tr>
    </tbody>
  </table>

  <h2>Not Sure Which Size to Order?</h2>
  <p>Send us your measurement over <a href="<?= e(whatsapp_link('Hi, I need help picking my bangle size.')) ?>" target="_blank" rel="noopener">WhatsApp</a> and we'll help you pick the right fit before you order.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
