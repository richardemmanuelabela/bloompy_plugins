<?php

defined( 'ABSPATH' ) or exit;

use BookneticApp\Providers\Helpers\Helper;
use BookneticAddon\BloompyTenants\BloompyTenantsAddon;
use function BookneticAddon\BloompyTenants\bkntc__;

?>

<script src="<?php echo BloompyTenantsAddon::loadAsset('assets/backend/js/edit.js')?>" id="tenant-info-script" data-id="<?php echo (int)$parameters['id']?>"></script>
<link rel="stylesheet" href="<?php echo BloompyTenantsAddon::loadAsset('assets/backend/css/edit.css')?>" type="text/css">
<script src="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.css')?>">
<script src="<?php echo Helper::assets('js/summernote.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('css/summernote.css')?>" type="text/css">

<div class="actions_panel clearfix">
    <button type="button" class="btn btn-lg btn-success float-right" id="save_tenant_info_btn">
        <i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES'); ?>
    </button>
</div>

<div class="settings-light-portlet">
    <div class="ms-title">
        <?php echo bkntc__('Booking Page Info'); ?>
    </div>
    <div class="ms-content">

        <form class="position-relative">
            <input id="tenant_id" type="hidden" value="<?php echo (int)$parameters['id']?>"/>

            <!-- Company Information -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="company_name"><?php echo bkntc__('Company name'); ?></label>
                    <input type="text" 
                           class="form-control" 
                           id="company_name" 
                           name="company_name"
                           value="<?php echo htmlspecialchars($parameters['company_name'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('Company name'); ?>">
                </div>
            </div>

            <!-- Legal URLs -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="privacy_policy_url"><?php echo bkntc__('Privacy Policy URL'); ?></label>
                    <input type="url" 
                           class="form-control" 
                           id="privacy_policy_url" 
                           name="privacy_policy_url"
                           value="<?php echo htmlspecialchars($parameters['info']['privacy_policy_url'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('Privacy Policy URL'); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label for="terms_conditions_url"><?php echo bkntc__('Terms & Conditions URL'); ?></label>
                    <input type="url" 
                           class="form-control" 
                           id="terms_conditions_url" 
                           name="terms_conditions_url"
                           value="<?php echo htmlspecialchars($parameters['info']['terms_conditions_url'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('Terms & Conditions URL'); ?>">
                </div>
            </div>

            <!-- Footer Content -->
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="footer_first_column"><?php echo bkntc__('Footer first column'); ?></label>
                    <textarea name="footer_first_column" 
                              id="footer_first_column" 
                              class="editor_tenant_info" 
                              cols="30" 
                              rows="10"><?php echo htmlspecialchars($parameters['info']['footer_first_column'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="footer_second_column"><?php echo bkntc__('Footer second column'); ?></label>
                    <textarea name="footer_second_column" 
                              class="editor_tenant_info" 
                              id="footer_second_column" 
                              cols="30" 
                              rows="10"><?php echo htmlspecialchars($parameters['info']['footer_second_column'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="footer_third_column"><?php echo bkntc__('Footer third column'); ?></label>
                    <textarea name="footer_third_column" 
                              class="editor_tenant_info" 
                              id="footer_third_column" 
                              cols="30" 
                              rows="10"><?php echo htmlspecialchars($parameters['info']['footer_third_column'] ?? ''); ?></textarea>
                </div>
            </div>

        </form>

    </div>
</div>

<script>
$(document).ready(function() {
    // Save settings
    $('#save_tenant_info_btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        
        // Disable button and show loading
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin pr-2"></i> <?php echo bkntc__('Saving...'); ?>');
        
        var tenant_id = $('#tenant_id').val();
        var tenant_company_name = $('#company_name').val();
        var footer_first_column = $('#footer_first_column').val();
        var footer_second_column = $('#footer_second_column').val();
        var footer_third_column = $('#footer_third_column').val();
        var footer_fourth_column = $('#footer_fourth_column').val();
        var privacy_policy_url = $('#privacy_policy_url').val();
        var terms_conditions_url = $('#terms_conditions_url').val();
        
        booknetic.ajax('bloompy_tenant_info_settings.bloompy_tenant_info_settings_save', {
            tenant_id: tenant_id,
            tenant_company_name: tenant_company_name,
            footer_first_column: footer_first_column,
            footer_second_column: footer_second_column,
            footer_third_column: footer_third_column,
            footer_fourth_column: footer_fourth_column,
            privacy_policy_url: privacy_policy_url,
            terms_conditions_url: terms_conditions_url,
        }, function(response) {
            if (response.status === 'ok') {
                booknetic.toast('<?php echo bkntc__('Settings saved successfully!'); ?>', 'success');
            } else {
                booknetic.toast(response.error_msg || '<?php echo bkntc__('Error saving settings. Please try again.'); ?>', 'unsuccess');
            }
            
            // Re-enable button on success
            $btn.prop('disabled', false).html(originalText);
        }, function() {
            // Re-enable button on error
            $btn.prop('disabled', false).html(originalText);
        });
    });
});
</script>
