<?php
defined('ABSPATH') or die();

use function BookneticAddon\Newsletters\bkntc__;

// Get current service ID from parameters (passed from callback)
$currentServiceId = $parameters['current_service_id'] ?? 0;
?>

<div class="form-row">
    <div class="form-group col-md-12">
        <div class="mt-3 mb-2">
            <a href="<?php echo admin_url('admin.php?page=bloompy&module=newsletters'); ?>"
               target="_blank"
              >
                <?php echo bkntc__('Newsletter Assignment'); ?>
            </a>
            <small class="form-text text-muted d-block mt-1">
                <?php echo bkntc__('Assign MailBlue and MailChimp lists to services for automatic newsletter subscriptions'); ?>
            </small>
        </div>
    </div>
</div>
