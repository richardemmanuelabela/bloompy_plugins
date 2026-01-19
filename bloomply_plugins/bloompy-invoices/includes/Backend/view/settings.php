<?php

defined( 'ABSPATH' ) or exit;

use BookneticApp\Providers\Helpers\Helper;
use function Bloompy\Invoices\bkntc__;

/**
 * @var array $parameters
 */

// Extract data from parameters array
$tenant_id = $parameters['tenant_id'] ?? 0;
$settings = $parameters['settings'] ?? [];
$can_set_starting_number = $parameters['can_set_starting_number'] ?? true;


?>

<div id="booknetic_area">
    <div class="m_header clearfix">
        <div class="m_head_title float-left"><?php echo bkntc__('Company & Invoice Settings'); ?></div>
        <div class="m_head_actions float-right">
            <button type="button" class="btn btn-lg btn-info mr-2" id="preview_invoice_btn">
                <i class="fa fa-eye pr-2"></i> <?php echo bkntc__('Preview Invoice'); ?>
            </button>
            <button type="button" class="btn btn-lg btn-success" id="save_settings_btn">
                <i class="fa fa-check pr-2"></i> <?php echo bkntc__('Save Changes'); ?>
            </button>
            <a href="admin.php?page=bloompy&module=bloompy_invoices" class="btn btn-lg btn-outline-secondary">
                <i class="fa fa-arrow-left pr-2"></i> <?php echo bkntc__('Back to Invoices'); ?>
            </a>
        </div>
    </div>

    <div class="fs_separator"></div>

    <div class="m-4">
        <div class="fs_portlet">
            <div class="fs_portlet_title"><?php echo bkntc__('Invoice Settings'); ?></div>
            <div class="fs_portlet_content">
                <!-- Invoice Numbering Settings -->
                <div class="form-row mb-3">
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

                <!-- Company Information -->
                <div class="form-row mb-3">
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

                <!-- Company Logo -->
                <div class="form-row mb-3">
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
                                <button type="button" class="btn btn-outline-secondary" id="upload_logo_btn" style="height:100%;">
                                    <i class="fa fa-upload"></i> <?php echo bkntc__('Upload'); ?>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="remove_logo_btn" style="display: none; height:100%;">
                                    <i class="fa fa-trash"></i> <?php echo bkntc__('Remove'); ?>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            <?php echo bkntc__('Upload JPEG, PNG, GIF, or BMP image (max 2MB). This logo will appear on your invoices.'); ?>
                        </small>
                        <div id="logo_preview" class="mt-2" style="display: none;">
                            <img src="" alt="Logo Preview" class="img-thumbnail" style="max-height: 100px;">
                        </div>
                        <input type="file" id="logo_file_input" accept="image/jpeg,image/jpg,image/png,image/gif,image/bmp" style="display: none;">
                    </div>
                </div>

                <div class="form-row mb-3">
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

                <div class="form-row mb-3">
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

                <!-- Financial Information -->
                <div class="form-row mb-3">
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
                <div class="form-row mb-3">
                    <div class="form-group col-md-12">
                        <label for="company_footer_text"><?php echo bkntc__('Footer Text'); ?></label>
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
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Use WordPress AJAX URL instead of Booknetic ajaxurl
    var wpAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
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
                alert('<?php echo bkntc__('File size must be less than 2MB'); ?>');
                return;
            }
            
            // Validate file type
            var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'];
            if (!allowedTypes.includes(file.type)) {
                alert('<?php echo bkntc__('Only JPEG, PNG, GIF, and BMP images are allowed'); ?>');
                return;
            }
            
            // Upload file
            var formData = new FormData();
            formData.append('action', 'bloompy_invoices_upload_logo');
            formData.append('nonce', '<?php echo wp_create_nonce('bloompy_invoices_settings'); ?>');
            formData.append('logo', file);
            
            $.ajax({
                url: wpAjaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        var logoUrl = response.data.logo_url;
                        console.log(logoUrl);
                        $('#company_logo').val(logoUrl);
                        $('#logo_preview img').attr('src', logoUrl);
                        $('#logo_preview').show();
                        $('#remove_logo_btn').show();
                        currentLogoUrl = logoUrl;
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : '<?php echo bkntc__('Error uploading logo. Please try again.'); ?>';
                        if (typeof booknetic !== 'undefined' && booknetic.helpers) {
                            booknetic.helpers.notify(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    }
                },
                error: function() {
                    var errorMsg = '<?php echo bkntc__('Error uploading logo. Please try again.'); ?>';
                    if (typeof booknetic !== 'undefined' && booknetic.helpers) {
                        booknetic.helpers.notify(errorMsg, 'error');
                    } else {
                        alert(errorMsg);
                    }
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

    $('#save_settings_btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        
        // Disable button and show loading
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin pr-2"></i> <?php echo bkntc__('Saving...'); ?>');
        
        // Collect form data
        var formData = {
            action: 'bloompy_invoices_save_settings',
            nonce: '<?php echo wp_create_nonce('bloompy_invoices_settings'); ?>',
            tenant_id: <?php echo $tenant_id ?: 0; ?>,
            invoice_starting_number: $('#invoice_starting_number').val(),
            company_name: $('#company_name').val(),
            company_address: $('#company_address').val(),
            company_zipcode: $('#company_zipcode').val(),
            company_city: $('#company_city').val(),
            company_country: $('#company_country').val(),
            company_phone: $('#company_phone').val(),
            company_iban: $('#company_iban').val(),
            company_kvk_number: $('#company_kvk_number').val(),
            company_btw_number: $('#company_btw_number').val(),
            company_footer_text: $('#company_footer_text').val(),
            company_logo: $('#company_logo').val()
        };
        
                        // Send AJAX request using WordPress AJAX
            $.post(wpAjaxUrl, formData, function(response) {
            
            if (response.success) {
                // Show success message
                if (typeof booknetic !== 'undefined' && booknetic.helpers) {
                    booknetic.helpers.notify('<?php echo bkntc__('Settings saved successfully!'); ?>', 'success');
                } else {
                    alert('<?php echo bkntc__('Settings saved successfully!'); ?>');
                }
                
                // If starting number was set, disable the field
                if (formData.invoice_starting_number && formData.invoice_starting_number.trim() !== '') {
                    $('#invoice_starting_number').prop('disabled', true);
                    $('#invoice_starting_number').next('.form-text').html('<span class="text-warning"><?php echo bkntc__('Starting number cannot be changed after invoices are created.'); ?></span>');
                }
                            } else {
                    // Show error message
                    var errorMsg = response.data && response.data.message ? response.data.message : '<?php echo bkntc__('Error saving settings. Please try again.'); ?>';
                    if (typeof booknetic !== 'undefined' && booknetic.helpers) {
                        booknetic.helpers.notify(errorMsg, 'error');
                    } else {
                        alert(errorMsg);
                    }
                }
        }).fail(function(xhr, status, error) {
            // Show error message for AJAX failure
            var errorMsg = '<?php echo bkntc__('Error saving settings. Please try again.'); ?>';
            if (typeof booknetic !== 'undefined' && booknetic.helpers) {
                booknetic.helpers.notify(errorMsg, 'error');
            } else {
                alert(errorMsg);
            }
        }).always(function() {
            // Re-enable button
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
        var formData = {
            action: 'bloompy_invoices_preview_invoice',
            company_name: $('#company_name').val(),
            company_phone: $('#company_phone').val(),
            company_logo: $('#company_logo').val(),
            company_address: $('#company_address').val(),
            company_zipcode: $('#company_zipcode').val(),
            company_city: $('#company_city').val(),
            company_country: $('#company_country').val(),
            company_iban: $('#company_iban').val(),
            company_kvk_number: $('#company_kvk_number').val(),
            company_btw_number: $('#company_btw_number').val(),
            company_footer_text: $('#company_footer_text').val(),
            _wpnonce: '<?php echo wp_create_nonce('bloompy_invoices_preview'); ?>'
        };
        
        // Create a form and submit it to trigger direct PDF download
        var form = $('<form>', {
            method: 'POST',
            action: wpAjaxUrl,
            target: '_blank'
        });
        
        // Add all form data as hidden inputs
        $.each(formData, function(key, value) {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: value
            }));
        });
        
        // Append form to body and submit
        form.appendTo('body').submit().remove();
        
        // Show success message immediately
        if (typeof booknetic !== 'undefined' && booknetic.helpers) {
            booknetic.helpers.notify('<?php echo bkntc__('Preview invoice generated successfully!'); ?>', 'success');
        }
        
        // Re-enable button after a short delay
        setTimeout(function() {
            $btn.prop('disabled', false).html(originalText);
        }, 1000);
    });
});
</script>
