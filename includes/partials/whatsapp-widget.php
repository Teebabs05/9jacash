<?php
/**
 * Sitewide floating WhatsApp contact button. Renders nothing if no
 * number is configured. Expects $assetBase to already be set (every
 * layout that includes this already defines it for its own asset tags).
 */
$whatsappNumber = preg_replace('/\D+/', '', (string) get_setting('whatsapp_number', ''));
$whatsappNumber = ltrim($whatsappNumber, '0');

if ($whatsappNumber !== ''):
?>
<a href="https://wa.me/<?= e($whatsappNumber) ?>" target="_blank" rel="noopener" class="whatsapp-float" aria-label="Chat with us on WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>
<?php endif; ?>
