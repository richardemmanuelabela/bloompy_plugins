<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
// Fix tooltip and select2 errors by providing fallbacks
$(document).ready(function() {
    // Add Bootstrap tooltip fallback if not available
    if (typeof $.fn.tooltip === 'undefined') {
        $.fn.tooltip = function() { return this; };
    }

    // Add Select2 fallback if not available
    if (typeof $.fn.select2 === 'undefined') {
        $.fn.select2 = function() { return this; };
    }

    // Initialize any tooltips if Bootstrap is available
    if (typeof $.fn.tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Initialize Select2 on filter selects if available
    // if (typeof $.fn.select2 === 'function') {
    //     setTimeout(function() {
    //         $('.form-control[data-filter-id]').select2({
    //             theme: 'bootstrap',
    //             allowClear: true,
    //             placeholder: function() {
    //                 return $(this).data('placeholder');
    //             }
    //         });
    //     }, 500);
    // }

    // Make DataTableUI create button work with our modal
    setTimeout(function() {
        $('#addBtn').off('click').on('click', function(e) {
            e.preventDefault();
            if (typeof window.bloompy_invoices !== 'undefined') {
                window.bloompy_invoices.createInvoice();
            }
        });

        // Hide the button but keep functionality intact
        $('#addBtn').hide();
    }, 500);

    $("[data-filter-id='2'][placeholder='Date start']").datepicker();
    $("[data-filter-id='3'][placeholder='Date end']").datepicker();
});
</script>
<?php

defined( 'ABSPATH' ) or exit;

use BookneticApp\Providers\Helpers\Helper;
?>

<div id="booknetic_area">
    <div class="m_header clearfix">
        <div class="m_head_actions float-right">
            <!-- Invoice Settings button removed - now available in Booknetic Settings -->
            <button type="button" data-setp="1" class="btn btn-primary btn-lg" id="export-invoices-pdf"  onclick="bloompy_invoices.exportInvoicePDF()"><i class="fa fa-download"></i> Export PDF</button>
            <button type="button" data-setp="1" class="btn btn-primary btn-lg" id="export-invoices-xml"  onclick="bloompy_invoices.exportInvoiceXML()"><i class="fa fa-download"></i> Export XML</button>
            <button type="button" data-setp="1" class="btn btn-primary btn-lg" id="export-invoices-csv"  onclick="bloompy_invoices.exportInvoiceCSV()"><i class="fa fa-download"></i> Export CSV</button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="bkntc-tab-content">
                <div class="bkntc-tab-pane active">
                    <!-- Statistics Cards -->
                    <div class="row mb-4" id="invoice-stats" style="display: none;">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="total-invoices">0</h4>
                                            <p class="card-text"><?php echo bkntc__('Total Invoices'); ?></p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fa fa-file-invoice fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="total-revenue">€0</h4>
                                            <p class="card-text"><?php echo bkntc__('Total Revenue'); ?></p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fa fa-euro-sign fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="pending-invoices">0</h4>
                                            <p class="card-text"><?php echo bkntc__('Pending Invoices'); ?></p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fa fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="this-month-invoices">0</h4>
                                            <p class="card-text"><?php echo bkntc__('This Month'); ?></p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fa fa-calendar fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DataTableUI Table Output -->
                    <?php echo isset($table) ? $table : 'DATATABLEUI_TABLE_PLACEHOLDER'; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Invoice Modal -->
<div class="modal fade" id="create_invoice_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo bkntc__('Create Invoice'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="create_invoice_form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo bkntc__('Customer Email'); ?> *</label>
                                <input type="email" class="form-control" name="customer_email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo bkntc__('Customer Name'); ?> *</label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo bkntc__('Customer Phone'); ?></label>
                                <input type="text" class="form-control" name="customer_phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo bkntc__('Service Name'); ?> *</label>
                                <input type="text" class="form-control" name="service_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo bkntc__('Service Price'); ?></label>
                                <input type="number" step="0.01" class="form-control" name="service_price" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo bkntc__('Tax Amount'); ?></label>
                                <input type="number" step="0.01" class="form-control" name="tax_amount" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo bkntc__('Total Amount'); ?></label>
                                <input type="number" step="0.01" class="form-control" name="total_amount" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo bkntc__('Company Name'); ?></label>
                                <input type="text" class="form-control" name="company_name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo bkntc__('Company Address'); ?></label>
                                <input type="text" class="form-control" name="company_address">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo bkntc__('Company City'); ?></label>
                                <input type="text" class="form-control" name="company_city">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo bkntc__('Notes'); ?></label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo bkntc__('Close'); ?></button>
                <button type="button" class="btn btn-primary" onclick="bloompy_invoices.saveInvoice()"><?php echo bkntc__('Create Invoice'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- View Invoice Modal -->
<div class="modal fade" id="view_invoice_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo bkntc__('Invoice Details'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="invoice_details_content">
                <!-- Invoice details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo bkntc__('Close'); ?></button>
                <button type="button" class="btn btn-primary" id="download_invoice_btn"><?php echo bkntc__('Download PDF'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function($) {

    window.bloompy_invoices = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Auto-calculate total amount
            $(document).on('input', '#create_invoice_form input[name="service_price"], #create_invoice_form input[name="tax_amount"]', function() {
                var servicePrice = parseFloat($('#create_invoice_form input[name="service_price"]').val()) || 0;
                var taxAmount = parseFloat($('#create_invoice_form input[name="tax_amount"]').val()) || 0;
                var totalAmount = servicePrice + taxAmount;
                $('#create_invoice_form input[name="total_amount"]').val(totalAmount.toFixed(2));
            });
        },

        showStats: function() {
            var $statsRow = $('#invoice-stats');
            if ($statsRow.is(':visible')) {
                $statsRow.slideUp();
                return;
            }

            booknetic.ajax('bloompy_invoices.get_stats', {}, function(response) {
                if (response.status === 'ok' || response.status === true) {
                    $('#total-invoices').text(response.total_invoices);
                    $('#total-revenue').text(response.total_revenue);
                    $('#pending-invoices').text(response.pending_invoices);
                    $('#this-month-invoices').text(response.this_month_invoices);
                    $statsRow.slideDown();
                } else {
                    booknetic.toast('Failed to load statistics: ' + (response.data || 'Unknown error'), 'unsuccess');
                }
            });
        },

        createInvoice: function() {
            $('#create_invoice_form')[0].reset();
            $('#create_invoice_modal').modal('show');
        },

        saveInvoice: function() {
            var formData = $('#create_invoice_form').serializeArray();
            var postData = {};

            // Add form data to post data
            $.each(formData, function(i, field) {
                postData[field.name] = field.value;
            });

            booknetic.ajax('bloompy_invoices.create_invoice', postData, function(response) {

                if (response && response.status) {
                    booknetic.toast(response.data || 'Invoice created successfully', 'success');
                    $('#create_invoice_modal').modal('hide');
                    // The table will be reloaded by the DataTableUI
                } else {
                    var errorMsg = response && response.data ? response.data : 'Unknown error occurred';
                    booknetic.toast(errorMsg, 'unsuccess');
                }
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                booknetic.toast('Request failed: ' + error, 'unsuccess');
            });
        },

        viewInvoice: function(id) {
            booknetic.ajax('bloompy_invoices.view_invoice', {
                id: id
            }, function(response) {
                if (response && response.status) {
                    var data = response.data || response;
                    var invoice = data.invoice || data;
                    var html = bloompy_invoices.renderInvoiceDetails(invoice);
                    $('#invoice_details_content').html(html);
                    $('#download_invoice_btn').attr('onclick', 'bloompy_invoices.downloadInvoice(' + id + ')');
                    $('#view_invoice_modal').modal('show');
                } else {
                    var errorMsg = (response && response.data) ? response.data : 'Failed to load invoice';
                    booknetic.toast(errorMsg, 'unsuccess');
                }
            });
        },
        exportInvoicePDF: function(id) {
            let invoice_numbers = [];
            $(".invoice_checkbox_invoice_number:checked").each(function () {
                invoice_numbers.push($(this).val());
            });
            invoice_numbers = JSON.stringify(invoice_numbers);
            booknetic.ajax('bloompy_invoices.exportInvoicePdf', {
                invoice_numbers:invoice_numbers
            }, function(response) {
                if (response && response.status) {
                    var data = response.data || response;
                    if (data.download_url) {
                        // PDF download - create a proper download link
                        var link = document.createElement('a');
                        link.href = data.download_url;
                        link.download = data.filename;
                        link.target = '_blank';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        booknetic.toast('Zipped file generated successfully!', 'success');
                    }  else {
                        booknetic.toast('No download content available', 'unsuccess');
                    }
                } else {
                    var errorMsg = (response && response.data) ? response.data : 'Download failed';
                    booknetic.toast(errorMsg, 'unsuccess');
                }
            });
        },
        exportInvoiceXML: function(id) {
            let invoice_numbers = [];
            $(".invoice_checkbox_invoice_number:checked").each(function () {
                invoice_numbers.push($(this).val());
            });
            invoice_numbers = JSON.stringify(invoice_numbers);
            booknetic.ajax('bloompy_invoices.exportInvoiceXml', {
                invoice_numbers:invoice_numbers
            }, function(response) {
                if (response && response.status) {
                    // Create a Blob from the string
                    const blob = new Blob([response.xml], { type: "application/xml" });
                    const link = document.createElement("a");

                    // Trigger the download
                    link.href = URL.createObjectURL(blob);
                    link.download = response.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                } else {
                    var errorMsg = (response && response.data) ? response.data : 'Download failed';
                    booknetic.toast(errorMsg, 'unsuccess');
                }
            });
        },
        exportInvoiceCSV: function(id) {
            let invoice_numbers = [];
            $(".invoice_checkbox_invoice_number:checked").each(function () {
                invoice_numbers.push($(this).val());
            });
            invoice_numbers = JSON.stringify(invoice_numbers);
            booknetic.ajax('bloompy_invoices.exportInvoiceCsv', {
                invoice_numbers:invoice_numbers
            }, function(response) {
                if (response && response.status) {
                    // Convert response into downloadable file
                    var blob = new Blob([response.csv], { type: "text/csv;charset=utf-8;" });
                    var url = URL.createObjectURL(blob);

                    var a = document.createElement("a");
                    a.href = url;
                    a.download = "invoice.csv";
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                } else {
                    var errorMsg = (response && response.data) ? response.data : 'Download failed';
                    booknetic.toast(errorMsg, 'unsuccess');
                }
            });
        },

        renderInvoiceDetails: function(invoice) {
            // Check if this is a WooCommerce invoice
            var isWooCommerceInvoice = invoice.source == "woocommerce";
            
            if (isWooCommerceInvoice) {
                var name = invoice.tenant_name || invoice.customer_name;
                var email = invoice.tenant_email || invoice.customer_email;
                var phone = invoice.tenant_phone || invoice.customer_phone;
                var invoice_number = invoice.invoice_number;
                var invoice_date = invoice.invoice_date;
                var status = invoice.status;
                // Handle multiple field name variations
                var product_name = invoice.package_name || invoice.product_name || invoice.service_name;
                var product_price = invoice.package_price || invoice.unit_price || invoice.service_price;
                var quantity = invoice.quantity || 1;
                var total_amount = invoice.total_amount;
            } else {
                // Booknetic customer invoice (appointment)
                var name = invoice.customer_name;
                var email = invoice.customer_email;
                var phone = invoice.customer_phone;
                var invoice_number = invoice.invoice_number;
                var invoice_date = invoice.invoice_date;
                var status = invoice.status;
                var product_name = invoice.service_name;
                var product_price = invoice.service_price;
                var quantity = 1;
                var total_amount = invoice.total_amount;
            }


            return `
                <div class="invoice-details-view">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p><strong>Name:</strong> ${name}</p>
                            <p><strong>Email:</strong> ${email}</p>
                            ${phone ? '<p><strong>Phone:</strong> ' + phone + '</p>' : ''}
                        </div>
                        <div class="col-md-6">
                            <h6>Invoice Information</h6>
                            <p><strong>Invoice #:</strong> ${invoice_number}</p>
                            <p><strong>Date:</strong> ${new Date(invoice_date).toLocaleDateString()}</p>
                            <p><strong>Status:</strong> <span class="badge badge-primary">${status}</span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Service Details</h6>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>${product_name}</td>
                                        <td>${product_price}</td>
                                        <td>${quantity}</td>
                                        <td>${total_amount}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ${invoice.company_name ? `
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Company Information</h6>
                                <p><strong>Company:</strong> ${invoice.company_name}</p>
                                ${invoice.company_address ? '<p><strong>Address:</strong> ' + invoice.company_address + '</p>' : ''}
                                ${invoice.company_city ? '<p><strong>City:</strong> ' + invoice.company_city + '</p>' : ''}
                            </div>
                        </div>
                    ` : ''}
                    ${invoice.notes ? `
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Notes</h6>
                                <p>${invoice.notes}</p>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        },

        downloadInvoice: function(id) {
            booknetic.ajax('bloompy_invoices.download_invoice', {
                id: id
            }, function(response) {
                if (response && response.status) {
                    var data = response.data || response;
                    if (data.download_url) {
                        // PDF download - create a proper download link
                        var link = document.createElement('a');
                        link.href = data.download_url;
                        link.download = data.filename || 'invoice-' + data.invoice_number + '.pdf';
                        link.target = '_blank';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        booknetic.toast('PDF generated successfully!', 'success');
                    } else if (data.html_content) {
                        // Fallback HTML preview
                        var newWindow = window.open('', '_blank');
                        newWindow.document.write(data.html_content);
                        newWindow.document.close();

                        if (data.message) {
                            booknetic.toast(data.message, 'warning');
                        }
                    } else {
                        booknetic.toast('No download content available', 'unsuccess');
                    }
                } else {
                    var errorMsg = (response && response.data) ? response.data : 'Download failed';
                    booknetic.toast(errorMsg, 'unsuccess');
                }
            });
        },

        updateStatus: function(id, status) {


            try {
                var ajaxResult = booknetic.ajax('bloompy_invoices.update_status', {
                    id: id,
                    status: status
                }, function(response) {


                    // Check if response has 'ok' status (Booknetic format)
                    if (response && (response.status === 'ok' || response.status === true)) {
                        var message = response.error_msg || response.data || 'Status updated successfully';

                        // Try different notification methods
                        if (typeof booknetic.helpers !== 'undefined' && booknetic.helpers.notify) {
                            booknetic.helpers.notify(message, 'success');
                        } else if (typeof booknetic.toast === 'function') {
                            try {
                                booknetic.toast(message, 'success');
                            } catch (e) {
                                console.error('Toast error:', e);
                                alert(message); // Fallback
                            }
                        } else {
                            alert(message); // Fallback
                        }

                                                    // Force table refresh
                            bloompy_invoices.refreshTable();

                    } else {
                        var errorMsg = (response && response.error_msg) ? response.error_msg :
                                     (response && response.data) ? response.data : 'Failed to update status';

                        if (typeof booknetic.helpers !== 'undefined' && booknetic.helpers.notify) {
                            booknetic.helpers.notify(errorMsg, 'error');
                        } else if (typeof booknetic.toast === 'function') {
                            try {
                                booknetic.toast(errorMsg, 'unsuccess');
                            } catch (e) {
                                console.error('Toast error:', e);
                                alert(errorMsg); // Fallback
                            }
                        } else {
                            alert(errorMsg); // Fallback
                        }
                    }
                });

                // Only add fail handler if ajax returns a promise
                if (ajaxResult && typeof ajaxResult.fail === 'function') {
                    ajaxResult.fail(function(xhr, status, error) {
                        console.error('AJAX Error:', xhr.responseText);
                        var errorMsg = 'Request failed: ' + error;
                        if (typeof booknetic.helpers !== 'undefined' && booknetic.helpers.notify) {
                            booknetic.helpers.notify(errorMsg, 'error');
                        } else if (typeof booknetic.toast === 'function') {
                            try {
                                booknetic.toast(errorMsg, 'unsuccess');
                            } catch (e) {
                                console.error('Toast error:', e);
                                alert(errorMsg); // Fallback
                            }
                        } else {
                            alert(errorMsg); // Fallback
                        }
                    });
                }

            } catch (e) {
                console.error('AJAX call failed:', e);
                var errorMsg = 'Failed to send request: ' + e.message;
                if (typeof booknetic.helpers !== 'undefined' && booknetic.helpers.notify) {
                    booknetic.helpers.notify(errorMsg, 'error');
                } else {
                    alert(errorMsg); // Fallback
                }
            }
        },

        deleteInvoice: function(id) {
            if (confirm('<?php echo \Bloompy\Invoices\bkntc__('Are you sure you want to delete this invoice?'); ?>')) {


                try {
                    var ajaxResult = booknetic.ajax('bloompy_invoices.delete_invoice', {
                        id: id
                    }, function(response) {


                        // Check if response has 'ok' status (Booknetic format)
                        if (response && (response.status === 'ok' || response.status === true)) {
                            var message = response.error_msg || response.data || 'Invoice deleted successfully';

                            if (typeof booknetic.helpers !== 'undefined' && booknetic.helpers.notify) {
                                booknetic.helpers.notify(message, 'success');
                            } else if (typeof booknetic.toast === 'function') {
                                try {
                                    booknetic.toast(message, 'success');
                                } catch (e) {
                                    console.error('Toast error:', e);
                                    alert(message); // Fallback
                                }
                            } else {
                                alert(message); // Fallback
                            }

                            // Force table refresh
                            bloompy_invoices.refreshTable();

                        } else {
                            var errorMsg = (response && response.error_msg) ? response.error_msg :
                                         (response && response.data) ? response.data : 'Failed to delete invoice';
                            console.log('Error message:', errorMsg);

                            if (typeof booknetic.helpers !== 'undefined' && booknetic.helpers.notify) {
                                booknetic.helpers.notify(errorMsg, 'error');
                            } else if (typeof booknetic.toast === 'function') {
                                try {
                                    booknetic.toast(errorMsg, 'unsuccess');
                                } catch (e) {
                                    console.error('Toast error:', e);
                                    alert(errorMsg); // Fallback
                                }
                            } else {
                                alert(errorMsg); // Fallback
                            }
                        }
                    });

                    // Only add fail handler if ajax returns a promise
                    if (ajaxResult && typeof ajaxResult.fail === 'function') {
                        ajaxResult.fail(function(xhr, status, error) {
                            console.error('AJAX Error:', xhr.responseText);
                            var errorMsg = 'Request failed: ' + error;
                            if (typeof booknetic.helpers !== 'undefined' && booknetic.helpers.notify) {
                                booknetic.helpers.notify(errorMsg, 'error');
                            } else if (typeof booknetic.toast === 'function') {
                                try {
                                    booknetic.toast(errorMsg, 'unsuccess');
                                } catch (e) {
                                    console.error('Toast error:', e);
                                    alert(errorMsg); // Fallback
                                }
                            } else {
                                alert(errorMsg); // Fallback
                            }
                        });
                    }

                } catch (e) {
                    console.error('AJAX call failed:', e);
                    var errorMsg = 'Failed to send request: ' + e.message;
                    if (typeof booknetic.helpers !== 'undefined' && booknetic.helpers.notify) {
                        booknetic.helpers.notify(errorMsg, 'error');
                    } else {
                        alert(errorMsg); // Fallback
                    }
                }
            }
        },

        /**
         * Force refresh the invoice table
         */
        refreshTable: function() {
            // Try to find and refresh the DataTableUI table
            if (typeof booknetic !== 'undefined' && booknetic.helpers && booknetic.helpers.reloadTable) {
                // Use Booknetic's table reload if available
                booknetic.helpers.reloadTable();
            } else if (window.location && window.location.reload) {
                // Fallback: reload the page
                setTimeout(function() {
                    window.location.reload();
                }, 1000); // Wait 1 second to show the success message
            }
        },


    };

    $(document).ready(function() {
        bloompy_invoices.init();
        $('.fs_data_table th[data-column~="0"]').html('<input type="checkbox" id="all-invoice" value="1"/> ');
    });
    $(document).on("change", "#all-invoice", function() {
        if ($(this).is(":checked")) {
            // if one is checked → check all
            $(".invoice_checkbox_invoice_number").prop("checked", true);
        } else {
            // if one is unchecked → uncheck all
            $(".invoice_checkbox_invoice_number").prop("checked", false);
        }
    });

})(jQuery);
</script>

<style>
.invoice-details-view h6 {
    color: #007cba;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
    margin-bottom: 15px;
}

.btn-group .dropdown-toggle::after {
    margin-left: 0.5em;
}



.badge {
    font-size: 11px;
}

.card {
    margin-bottom: 1rem;
}

.card-body {
    padding: 1.25rem;
}

#invoice-stats .card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}

/* Hide create invoice button */
#addBtn,
.btn-primary[onclick*="createInvoice"],
button[onclick*="createInvoice"] {
    display: none !important;
}
</style>