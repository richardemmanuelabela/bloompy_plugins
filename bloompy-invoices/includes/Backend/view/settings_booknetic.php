<?php

defined( 'ABSPATH' ) or exit;

use BookneticApp\Providers\Helpers\Helper;
use function Bloompy\Invoices\bkntc__;




?>

<div class="actions_panel clearfix">
    <div class="float-right">
        <button type="button" class="btn btn-lg btn-info mr-2" id="preview_invoice_btn">
            <i class="fa fa-eye pr-2"></i> <?php echo bkntc__('Preview Invoice'); ?>
        </button>
        <button type="button" class="btn btn-lg btn-success" id="save_settings_btn">
            <i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES'); ?>
        </button>
    </div>
</div>

<!-- Company Details Section -->
<div class="settings-light-portlet mb-4">
    <div class="ms-title">
        <?php echo bkntc__('Company Details'); ?>
    </div>
    <div class="ms-content">

        <form class="position-relative">
            <!-- Company Logo -->
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="company_logo"><?php echo bkntc__('Company Logo'); ?></label>
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="company_logo" 
                               name="company_logo"
                               value="<?php echo htmlspecialchars($settings['company_logo'] ?? ''); ?>"
                               placeholder="<?php echo bkntc__('Logo URL or upload new image'); ?>"
                               readonly>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" id="upload_logo_btn">
                                <i class="fa fa-upload"></i> <?php echo bkntc__('Upload'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="remove_logo_btn" style="display: none;">
                                <i class="fa fa-trash"></i> <?php echo bkntc__('Remove'); ?>
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        <?php echo bkntc__('Upload JPEG, PNG, GIF, or BMP image (max 2MB). This logo will appear on your invoices and sync with Booknetic.'); ?>
                    </small>
                    <div id="logo_preview" class="mt-2" style="display: none;">
                        <img src="" alt="<?php echo bkntc__('Logo Preview'); ?>" class="img-thumbnail" style="max-height: 100px;">
                    </div>
                    <input type="file" id="logo_file_input" accept="image/jpeg,image/jpg,image/png,image/gif,image/bmp" style="display: none;">
                </div>
            </div>

            <!-- Company Basic Information -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="company_name"><?php echo bkntc__('Company Name'); ?></label>
                    <input type="text" 
                           class="form-control" 
                           id="company_name" 
                           name="company_name"
                           value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('Your Company Name'); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label for="company_phone"><?php echo bkntc__('Phone'); ?></label>
                    <input type="text" 
                           class="form-control" 
                           id="company_phone" 
                           name="company_phone"
                           value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('+31 123 456 789'); ?>">
                </div>
            </div>

            <!-- Company Address -->
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="company_address"><?php echo bkntc__('Company Address'); ?></label>
                    <input type="text" 
                           class="form-control" 
                           id="company_address" 
                           name="company_address"
                           value="<?php echo htmlspecialchars($settings['company_address'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('Street Address'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="company_zipcode"><?php echo bkntc__('Zip Code'); ?></label>
                    <input type="text" 
                           class="form-control" 
                           id="company_zipcode" 
                           name="company_zipcode"
                           value="<?php echo htmlspecialchars($settings['company_zipcode'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('1234 AB'); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="company_city"><?php echo bkntc__('City'); ?></label>
                    <input type="text" 
                           class="form-control" 
                           id="company_city" 
                           name="company_city"
                           value="<?php echo htmlspecialchars($settings['company_city'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('City Name'); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="company_country"><?php echo bkntc__('Country'); ?></label>
                    <input type="text" 
                           class="form-control" 
                           id="company_country" 
                           name="company_country"
                           value="<?php echo htmlspecialchars($settings['company_country'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('Country'); ?>">
                </div>
            </div>
			<?php if( \BookneticApp\Providers\Core\Capabilities::tenantCan( 'upload_logo_to_booking_panel' ) ):?>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <div class="form-control-checkbox">
                            <label for="input_display_logo_on_booking_panel"><?php echo bkntc__('Display a company logo on the  Booking panel')?>:</label>
                            <div class="fs_onoffswitch">
                                <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_display_logo_on_booking_panel"<?php echo Helper::getOption('display_logo_on_booking_panel', 'off')=='on'?' checked':''?>>
                                <label class="fs_onoffswitch-label" for="input_display_logo_on_booking_panel"></label>
                            </div>
                        </div>
                    </div>
                </div>
			<?php endif;?>
        </form>
    </div>
</div>

<!-- Invoice Settings Section -->
<div class="settings-light-portlet">
    <div class="ms-title">
        <?php echo bkntc__('Invoice Settings'); ?>
    </div>
    <div class="ms-content">

        <form class="position-relative">
            <!-- Invoice Numbering Settings -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="invoice_starting_number"><?php echo bkntc__('Starting Invoice Number'); ?></label>
                    <input type="text" 
                           class="form-control" 
                           id="invoice_starting_number" 
                           name="invoice_starting_number"
                           value="<?php echo htmlspecialchars($settings['invoice_starting_number'] ?? ''); ?>"
                           placeholder="000001"
                           <?php echo $can_set_starting_number ? '' : 'disabled'; ?>>
                    <?php if ($can_set_starting_number): ?>
                        <small class="form-text text-muted">
                            <?php echo bkntc__('This can only be set once per year and cannot be changed after invoices are created.'); ?>
                        </small>
                    <?php else: ?>
                        <small class="form-text text-warning">
                            <?php echo bkntc__('Starting number cannot be changed after invoices are created.'); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="company_iban"><?php echo bkntc__('IBAN'); ?></label>
                    <input type="text"
                           class="form-control"
                           id="company_iban"
                           name="company_iban"
                           value="<?php echo htmlspecialchars($settings['company_iban'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('NL91 ABNA 0417 1643 00'); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="company_kvk_number"><?php echo bkntc__('KVK Number'); ?></label>
                    <input type="text"
                           class="form-control"
                           id="company_kvk_number"
                           name="company_kvk_number"
                           value="<?php echo htmlspecialchars($settings['company_kvk_number'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('12345678'); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="company_btw_number"><?php echo bkntc__('BTW Number'); ?></label>
                    <input type="text"
                           class="form-control"
                           id="company_btw_number"
                           name="company_btw_number"
                           value="<?php echo htmlspecialchars($settings['company_btw_number'] ?? ''); ?>"
                           placeholder="<?php echo bkntc__('NL123456789B01'); ?>">
                </div>
            </div>

            <!-- Footer Text -->
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="company_footer_text"><?php echo bkntc__('Invoice Footer Text'); ?></label>
                    <textarea class="form-control" 
                              id="company_footer_text" 
                              name="company_footer_text" 
                              rows="4"
                              placeholder="<?php echo bkntc__('Additional information to display at the bottom of invoices...'); ?>"><?php echo htmlspecialchars($settings['company_footer_text'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">
                        <?php echo bkntc__('This text will appear at the bottom of all invoices.'); ?>
                    </small>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Logo upload functionality
    var currentLogoUrl = '<?php echo htmlspecialchars($settings['company_logo'] ?? ''); ?>';
    
    // Show logo preview if logo exists
    if (currentLogoUrl) {
        $('#logo_preview img').attr('src', currentLogoUrl);
        $('#logo_preview').show();
        $('#remove_logo_btn').show();
    }
    
    // Upload logo button click
    $('#upload_logo_btn').on('click', function() {
        $('#logo_file_input').click();
    });
    
    // File input change
    $('#logo_file_input').on('change', function() {
        var file = this.files[0];
        if (file) {
            // Validate file size
            if (file.size > 2 * 1024 * 1024) {
                booknetic.helpers.notify('<?php echo bkntc__('File size must be less than 2MB'); ?>', 'error');
                return;
            }
            
            // Validate file type
            var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'];
            if (!allowedTypes.includes(file.type)) {
                booknetic.toast('<?php echo bkntc__('Only JPEG, PNG, GIF, and BMP images are allowed'); ?>', 'unsuccess');
                return;
            }
            
            // Upload file using Booknetic AJAX
            var formData = new FormData();
            formData.append('bloompy_invoice_settings_upload_logo', '1');
            formData.append('logo', file);
            
            booknetic.ajax('bloompy_invoice_settings.bloompy_invoice_settings_upload_logo', formData, function(response) {
                if (response.status === 'ok') {
                    var logoUrl = response.logo_url;
                    $('#company_logo').val(logoUrl);
                    $('#logo_preview img').attr('src', logoUrl);
                    $('#logo_preview').show();
                    $('#remove_logo_btn').show();
                    currentLogoUrl = logoUrl;
                    booknetic.toast('<?php echo bkntc__('Logo uploaded successfully!'); ?>', 'success');
                } else {
                    booknetic.toast(response.error_msg || '<?php echo bkntc__('Error uploading logo. Please try again.'); ?>', 'unsuccess');
                }
            });
        }
    });
    
    // Remove logo button click
    $('#remove_logo_btn').on('click', function() {
        $('#company_logo').val('');
        $('#logo_preview').hide();
        $('#remove_logo_btn').hide();
        currentLogoUrl = '';
    });

    // Save settings
    $('#save_settings_btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        
        // Disable button and show loading
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin pr-2"></i> <?php echo bkntc__('Saving...'); ?>');
        var display_logo_on_booking_panel	= $("#input_display_logo_on_booking_panel").is(':checked')?'on':'off';
        // Collect form data
        var formData = new FormData();
        formData.append('bloompy_invoice_settings_save_settings', '1');
        formData.append('invoice_starting_number', $('#invoice_starting_number').val());
        formData.append('company_name', $('#company_name').val());
        formData.append('company_address', $('#company_address').val());
        formData.append('company_zipcode', $('#company_zipcode').val());
        formData.append('company_city', $('#company_city').val());
        formData.append('company_country', $('#company_country').val());
        formData.append('company_phone', $('#company_phone').val());
        formData.append('company_iban', $('#company_iban').val());
        formData.append('company_kvk_number', $('#company_kvk_number').val());
        formData.append('company_btw_number', $('#company_btw_number').val());
        formData.append('company_footer_text', $('#company_footer_text').val());
        formData.append('company_logo', $('#company_logo').val());
        formData.append('display_logo_on_booking_panel', display_logo_on_booking_panel);

        
        booknetic.ajax('bloompy_invoice_settings.bloompy_invoice_settings_save_settings', formData, function(response) {
            if (response.status === 'ok') {
                booknetic.toast('<?php echo bkntc__('Settings saved successfully!'); ?>', 'success');
                
                // If starting number was set, disable the field
                if (formData.get('invoice_starting_number') && formData.get('invoice_starting_number').trim() !== '') {
                    $('#invoice_starting_number').prop('disabled', true);
                    $('#invoice_starting_number').next('.form-text').html('<span class="text-warning"><?php echo bkntc__('Starting number cannot be changed after invoices are created.'); ?></span>');
                }
            } else {
                booknetic.toast(response.error_msg || '<?php echo bkntc__('Error saving settings. Please try again.'); ?>', 'unsuccess');
            }
            
            // Re-enable button on success
            $btn.prop('disabled', false).html(originalText);
        }, function() {
            booknetic.toast('<?php echo bkntc__('Error saving settings. Please try again.'); ?>', 'unsuccess');
            
            // Re-enable button on error
            $btn.prop('disabled', false).html(originalText);
        });
    });

    // Preview Invoice functionality
    $('#preview_invoice_btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        
        // Disable button and show loading
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin pr-2"></i> <?php echo bkntc__('Generating Preview...'); ?>');
        
        // Collect current form data
        var formData = new FormData();
        formData.append('bloompy_invoice_settings_preview_invoice', '1');
        formData.append('company_name', $('#company_name').val());
        formData.append('company_phone', $('#company_phone').val());
        formData.append('company_logo', $('#company_logo').val());
        formData.append('company_address', $('#company_address').val());
        formData.append('company_zipcode', $('#company_zipcode').val());
        formData.append('company_city', $('#company_city').val());
        formData.append('company_country', $('#company_country').val());
        formData.append('company_iban', $('#company_iban').val());
        formData.append('company_kvk_number', $('#company_kvk_number').val());
        formData.append('company_btw_number', $('#company_btw_number').val());
        formData.append('company_footer_text', $('#company_footer_text').val());
        
        // Use Booknetic AJAX for preview
        booknetic.ajax('bloompy_invoice_settings.bloompy_invoice_settings_preview_invoice', formData, function(response) {
            if (response.status === 'ok' && response.download_url) {
                // Create a temporary link to download the PDF
                var link = document.createElement('a');
                link.href = response.download_url;
                link.download = response.filename || 'invoice-preview.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                booknetic.toast('<?php echo bkntc__('Preview invoice generated successfully!'); ?>', 'success');
            } else {
                booknetic.toast('<?php echo bkntc__('Error generating preview. Please try again.'); ?>', 'unsuccess');
            }
        }, function() {
            booknetic.toast('<?php echo bkntc__('Error generating preview. Please try again.'); ?>', 'unsuccess');
        });
        
        // Re-enable button after a short delay
        setTimeout(function() {
            $btn.prop('disabled', false).html(originalText);
        }, 1000);
    });
});
</script>
