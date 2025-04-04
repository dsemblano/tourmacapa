<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; }
.coupon {
  border: 2px dashed #ccc;
  padding: 20px;
  margin: 20px 0;
  text-align: center;
  background: #f9f9f9;
}
.code {
  font-size: 24px;
  font-weight: bold;
  color: #ff0000;
}
</style>
</head>
<body>
<h2><?php _e('Thank You For Your Purchase!', 'woocommerce'); ?></h2>
<p><?php printf(__('As a thank you, here\'s a %d%% off coupon for our restaurant:', 'woocommerce'), $amount); ?></p>

<div class="coupon">
<div class="code"><?php echo esc_html($code); ?></div>
<p><?php _e('Valid for 30 days on restaurant menu items', 'woocommerce'); ?></p>
</div>

<p><?php _e('We hope to see you soon!', 'woocommerce'); ?></p>
</body>
</html>
