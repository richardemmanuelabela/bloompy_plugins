<?php

namespace Bloompy\Invoices\Services;

use Bloompy\Invoices\PDF\InvoicePDFTemplateFactory;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticSaaS\Models\Tenant;
use TCPDF;

/**
 * PDF generation service for invoices
 */
class PDFService
{
    /**
     * Generate PDF content for invoice
     * 
     * Uses template pattern to delegate HTML generation to invoice-specific templates
     */
    public function generateInvoicePDFContent($invoice)
    {
        // Get the appropriate template for this invoice type
        $template = InvoicePDFTemplateFactory::create($invoice);
        
        // Generate HTML using the template
        $html = $template->getHTML($invoice);
        
        try {
            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Bloompy Invoices');
            $pdf->SetAuthor($invoice['company_name'] ?: 'Bloompy');
            $pdf->SetTitle($template->getTitle($invoice));

            $pdf->SetSubject('Invoice');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 25);
            
            // Set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', '', 10);
            
            // Output the HTML content
            $pdf->writeHTML($html, true, false, true, false, '');
            
            // Return PDF as string
            return $pdf->Output('', 'S');
            
        } catch (\Exception $e) {
            error_log('TCPDF error: ' . $e->getMessage());
            throw new \Exception('Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate and save PDF file for invoice
     */
    public function generateInvoicePDF($invoice)
    {
        $uploadDir = wp_upload_dir();
        $invoiceDir = $uploadDir['basedir'] . '/bloompy-invoices';
        
        // Create directory if it doesn't exist
        if (!file_exists($invoiceDir)) {
            wp_mkdir_p($invoiceDir);
        }

        // Get the appropriate template for this invoice type
        $template = InvoicePDFTemplateFactory::create($invoice);
        $filename = $template->getFilename($invoice);

        $filepath = $invoiceDir . '/' . $filename;

        // Generate PDF content
        $pdfContent = $this->generateInvoicePDFContent($invoice);

        
        // Save to file
        if (file_put_contents($filepath, $pdfContent) !== false) {
            return $filepath;
        }
        
        return false;
    }

    /**
     * Generate HTML for invoice using Bloompy's table-based template structure
     */
    public function generateInvoiceHTML($invoice)
    {
        // Get tenant data for company information
        $tenantId = $invoice['tenant_id'] ?? (class_exists('BookneticApp\\Providers\\Core\\Permission') ? \BookneticApp\Providers\Core\Permission::tenantId() : 0);
        $companyData = $this->getCompanyData($invoice, $tenantId);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta http-equiv="x-ua-compatible" content="ie=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Invoice #<?php echo esc_html($invoice['invoice_number']); ?></title>
            <style type="text/css">
                /**
                 * Google webfonts. Recommended to include the .woff version for cross-client compatibility.
                 */
                @media screen {
                    @font-face {
                        font-family: 'MaBryPro - Medium';
                        font-style: normal;
                        font-weight: 400;
                        src: local('MaBryPro - Medium'), local('MaBryPro-Medium'), url(https://bloompy.nl/fonts/MabryPro-Medium.otf) format('otf');
                    }
                    @font-face {
                        font-family: 'MaBryPro - Medium';
                        font-style: normal;
                        font-weight: 700;
                        src: local('MaBryPro - Medium'), local('MaBryPro-Medium'), url(https://bloompy.nl/fonts/MabryPro-Medium.otf) format('otf');
                    }
                }
                /**
                 * Avoid browser level font resizing.
                 * 1. Windows Mobile
                 * 2. iOS / OSX
                 */
                body,
                table,
                td,
                a {
                    -ms-text-size-adjust: 100%; /* 1 */
                    -webkit-text-size-adjust: 100%; /* 2 */
                }
                /**
                 * Remove extra space added to tables and cells in Outlook.
                 */
                table,
                td {
                    mso-table-rspace: 0pt;
                    mso-table-lspace: 0pt;
                }
                /**
                 * Better fluid images in Internet Explorer.
                 */
                img {
                    -ms-interpolation-mode: bicubic;
                }
                /**
                 * Remove blue links for iOS devices.
                 */
                a[x-apple-data-detectors] {
                    font-family: inherit !important;
                    font-size: inherit !important;
                    font-weight: inherit !important;
                    line-height: inherit !important;
                    color: inherit !important;
                    text-decoration: none !important;
                }
                /**
                 * Fix centering issues in Android 4.4.
                 */
                div[style*="margin: 16px 0;"] {
                    margin: 0 !important;
                }
                body {
                    width: 100% !important;
                    height: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }
                /**
                 * Collapse table borders to avoid space between cells.
                 */
                table {
                    border-collapse: collapse !important;
                    font-family: 'MaBryPro - Medium' !important;
                }
                a {
                    color: #1a82e2;
                }
                img {
                    height: auto;
                    line-height: 100%;
                    text-decoration: none;
                    border: 0;
                    outline: none;
                }
                .invoice-table, .invoice-table td {
                    border:0 !important;
                }
                .invoice-table td {
                    padding:8px !important;
                }
                td {
                    font-family: 'MaBryPro - Medium';
                    line-height: 1.4em !important;
                    font-size: 26px !important;
                }
                th {
                    font-weight: bold;
                    font-weight:bold;
                    font-size: 14px !important;
                    text-align: left !important;
                }
                thead tr {
                    border-bottom:1px #000 solid;
                }
                table {
                    width:100%;
                }
                .appoint_status {
                    font-size: 24px;
                }
                .width_border_bottom {
                    border-bottom: 5px solid #EEEEEE;
                }
                .company_logo img{
                    width:100px;
                }
                .heading {
                    font-size: 26px !important;
                }
                .border-bottom {
                    border:2px solid #000;
                }
            </style>
        </head>
        <body>
            <!-- start body -->
            <table border="0" cellpadding="0" cellspacing="0" width="100%" >
                <!-- start copy block -->
                <tbody><tr>
                    <td align="left">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody><tr>
                                <td class="main_td">
                                    <?php if (!empty($companyData['logo'])): ?>
                                        <div class="company_logo" >
                                            <img src="<?php echo esc_url($companyData['logo']); ?>" alt="Company Logo" style="max-width: 150px; max-height: 80px;">
                                        </div>
                                    <p></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr class="main_tr">
                                <td class="main_td"><table>
                                        <tbody>
                                        <tr>
                                            <td style="width:70%;" valign="top"><table border="0" cellpadding="0" cellspacing="0"><tbody>
                                                    <tr>
                                                        <td style="color:#000000"><strong><?php echo esc_html($companyData['customer_company_name']); ?></strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="color:#000000"><?php echo esc_html($invoice['customer_name']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><?php echo esc_html($companyData['customer_address']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><?php echo esc_html($companyData['customer_zipcode']." ". $companyData['customer_city']); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><?php echo esc_html($companyData['customer_country']); ?></td>
                                                    </tr>
													<?php if (!empty($companyData['customer_phone'])): ?>
                                                        <tr>
                                                            <td>Tel: <?php echo esc_html($companyData['customer_phone']); ?></td>
                                                        </tr>
													<?php endif; ?>
													<?php if (!empty($companyData['customer_iban'])): ?>
                                                        <tr>
                                                            <td>IBAN: <?php echo esc_html($companyData['customer_iban']); ?></td>
                                                        </tr>
													<?php endif; ?>
													<?php if (!empty($companyData['customer_kvk_number'])): ?>
                                                        <tr>
                                                            <td>KVK: <?php echo esc_html($companyData['customer_kvk_number']); ?></td>
                                                        </tr>
													<?php endif; ?>
													<?php if (!empty($companyData['customer_btw_number'])): ?>
                                                        <tr>
                                                            <td>BTW: <?php echo esc_html($companyData['customer_btw_number']); ?></td>
                                                        </tr>
													<?php endif; ?>
                                                    <tr>
                                                        <td>Factuurdatum: <?php echo date('d-m-Y', strtotime($invoice['invoice_date'])); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Factuurnummer: <?php echo esc_html($invoice['invoice_number']); ?>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                            <td style="width:30%;">
                                                <table border="0" cellpadding="0" cellspacing="0" align="right">
                                                    <tbody>
                                                    <tr>
                                                        <td style="color:#000000">
                                                            <strong><?php echo esc_html($companyData['company_name']); ?></strong>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo esc_html($companyData['address']); ?></strong>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <?php echo esc_html($companyData['zipcode']); ?> <?php echo esc_html($companyData['city']); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <?php echo esc_html($companyData['country']); ?>
                                                        </td>
                                                    </tr>
                                                    <?php if (!empty($companyData['phone'])): ?>
                                                    <tr>
                                                        <td>
                                                            Tel: <?php echo esc_html($companyData['phone']); ?>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    <?php if (!empty($companyData['iban'])): ?>
                                                    <tr>
                                                        <td>
                                                            IBAN: <?php echo esc_html($companyData['iban']); ?>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    <?php if (!empty($companyData['kvk_number'])): ?>
                                                    <tr>
                                                        <td>
                                                            KVK: <?php echo esc_html($companyData['kvk_number']); ?>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    <?php if (!empty($companyData['btw_number'])): ?>
                                                    <tr>
                                                        <td>
                                                            BTW: <?php echo esc_html($companyData['btw_number']); ?>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <!-- Spacer -->
                                </td>
                            </tr>
                            <tr class="main_tr">
                                <td class="main_td"><table>
                                        <tbody><tr>
                                            <td class="appoint_status"><strong style="color:#357e22; "><?php echo $invoice['status'] === 'paid' ? 'Reeds betaald' : 'In behandeling'; ?></strong>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td><table>
                                        <tbody><tr>
                                            <td style="width:60%;" valign="top"><strong>Kenmerk:</strong> <?php echo esc_html($invoice['service_name']); ?>
                                            </td>
                                            <td style="width:40%;"><table>
                                                    <tbody><tr>
                                                        <td><table>
                                                                <tbody>
                                                                <tr>
                                                                    <td><strong>Vervaldatum:</strong>
                                                                    </td>
                                                                    <td><?php
                                                                        $dueDate = $invoice['due_date'] ?? $invoice['appointment_date'];
                                                                        echo $dueDate ? date('d-m-Y', strtotime($dueDate)) : 'N/A'; 
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr class="main_tr"><td>&nbsp;</td></tr>
                            <tr class="main_tr">
                                <td class="main_td"><table class="purchased_table">
                                        <thead>
                                        <tr>
                                            <th></th>
                                            <th width="25%">Omschrijving</th>
                                            <th>Bedrag</th>
                                            <th>Totaal</th>
                                            <th width="15%">BTW</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr style="height:2px !important;">
                                            <td colspan="5" height="1" style="height:2px !important;"><div style="height:2px; border-bottom:1px solid #000;"></div></td>
                                        </tr>
                                        <tr>
                                            <td><p><?php echo esc_html($invoice['number_of_appointments']); ?>x</p></td>
                                            <td><?php echo esc_html($invoice['service_name']); ?></td>
                                            <td><?php echo Helper::price($invoice['service_price']); ?></td>
                                            <td><?php echo Helper::price($invoice['service_price']); ?></td>
                                            <td><?php echo Helper::price($invoice['tax_amount']); ?></td>
                                        </tr>
                                        
                                        <!-- Service Extras -->
                                        <?php if (!empty($invoice['service_extras']) && is_array($invoice['service_extras'])): ?>
                                            <?php foreach ($invoice['service_extras'] as $extra): ?>
                                                <tr>
                                                    <td><p><?php echo intval($extra['quantity'] ?? 1); ?>x</p></td>
                                                    <td>Service Extra</td>
                                                    <td><?php echo Helper::price($extra['price']); ?></td>
                                                    <td><?php echo Helper::price($extra['price'] * intval($extra['quantity'] ?? 1)); ?></td>
                                                    <td></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <tr>
                                            <td colspan="5" height="1">
                                                <div style="height:2px; border-bottom:1px solid #000;"></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td style="height: 20px;"><strong>Subtotaal </strong></td>
                                            <td><strong><?php echo Helper::price($invoice['subtotal']); ?></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td><strong>BTW </strong></td>
                                            <td><strong><?php echo Helper::price($invoice['tax_amount']); ?></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding:1px"></td>
                                            <td colspan="3" ><div style="height:2px; border-bottom:1px solid #000;"></div></td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td><strong>Totaal </strong></td>
                                            <td><strong><?php echo Helper::price($invoice['total_amount']); ?></strong></td>
                                            <td></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <!-- end copy block -->
                </tbody>
            </table>
            <!-- end body -->
            <p></p>
            <p></p>
            <!-- Footer -->
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                <?php if (!empty($companyData['footer_text'])): ?>
                    <p style="margin-bottom: 13px; font-size: 13px; color: #666;"><?php echo nl2br(esc_html($companyData['footer_text'])); ?></p>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        
        return ob_get_clean();
    }
	public function generateSaasInvoiceHTML($invoice)
	{
		// Get tenant data for company information
		$companyData = $this->getSaasCompanyData($invoice);
		ob_start();
		?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta http-equiv="x-ua-compatible" content="ie=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Invoice #<?php echo esc_html($invoice['invoice_number']); ?></title>
            <style type="text/css">
                /**
                 * Google webfonts. Recommended to include the .woff version for cross-client compatibility.
                 */
                @media screen {
                    @font-face {
                        font-family: 'MaBryPro - Medium';
                        font-style: normal;
                        font-weight: 400;
                        src: local('MaBryPro - Medium'), local('MaBryPro-Medium'), url(https://bloompy.nl/fonts/MabryPro-Medium.otf) format('otf');
                    }
                    @font-face {
                        font-family: 'MaBryPro - Medium';
                        font-style: normal;
                        font-weight: 700;
                        src: local('MaBryPro - Medium'), local('MaBryPro-Medium'), url(https://bloompy.nl/fonts/MabryPro-Medium.otf) format('otf');
                    }
                }
                /**
                 * Avoid browser level font resizing.
                 * 1. Windows Mobile
                 * 2. iOS / OSX
                 */
                body,
                table,
                td,
                a {
                    -ms-text-size-adjust: 100%; /* 1 */
                    -webkit-text-size-adjust: 100%; /* 2 */
                }
                /**
                 * Remove extra space added to tables and cells in Outlook.
                 */
                table,
                td {
                    mso-table-rspace: 0pt;
                    mso-table-lspace: 0pt;
                }
                /**
                 * Better fluid images in Internet Explorer.
                 */
                img {
                    -ms-interpolation-mode: bicubic;
                }
                /**
                 * Remove blue links for iOS devices.
                 */
                a[x-apple-data-detectors] {
                    font-family: inherit !important;
                    font-size: inherit !important;
                    font-weight: inherit !important;
                    line-height: inherit !important;
                    color: inherit !important;
                    text-decoration: none !important;
                }
                /**
                 * Fix centering issues in Android 4.4.
                 */
                div[style*="margin: 16px 0;"] {
                    margin: 0 !important;
                }
                body {
                    width: 100% !important;
                    height: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }
                /**
                 * Collapse table borders to avoid space between cells.
                 */
                table {
                    border-collapse: collapse !important;
                    font-family: 'MaBryPro - Medium' !important;
                }
                a {
                    color: #1a82e2;
                }
                img {
                    height: auto;
                    line-height: 100%;
                    text-decoration: none;
                    border: 0;
                    outline: none;
                }
                .invoice-table, .invoice-table td {
                    border:0 !important;
                }
                .invoice-table td {
                    padding:8px !important;
                }
                td {
                    font-family: 'MaBryPro - Medium';
                    line-height: 1.4em !important;
                    font-size: 26px !important;
                }
                th {
                    font-weight: bold;
                    font-weight:bold;
                    font-size: 14px !important;
                    text-align: left !important;
                }
                thead tr {
                    border-bottom:1px #000 solid;
                }
                table {
                    width:100%;
                }
                .appoint_status {
                    font-size: 24px;
                }
                .width_border_bottom {
                    border-bottom: 5px solid #EEEEEE;
                }
                .company_logo img{
                    width:100px;
                }
                .heading {
                    font-size: 26px !important;
                }
                .border-bottom {
                    border:2px solid #000;
                }
            </style>
        </head>
        <body>
        <!-- start body -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" >
            <!-- start copy block -->
            <tbody><tr>
                <td align="left">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tbody><tr>
                            <td class="main_td">
								<?php if (!empty($companyData['logo'])): ?>
                                    <div class="company_logo" >
                                        <img src="<?php echo esc_url($companyData['logo']); ?>" alt="Company Logo" style="max-width: 150px; max-height: 80px;">
                                    </div>
                                    <p></p>
								<?php endif; ?>
                            </td>
                        </tr>
                        <tr class="main_tr">
                            <td class="main_td"><table>
                                    <tbody>
                                    <tr>
                                        <td style="width:70%;" valign="top"><table border="0" cellpadding="0" cellspacing="0"><tbody>
                                                <tr>
                                                    <td style="color:#000000"><strong><?php echo esc_html($companyData['tenant_company_name']); ?></strong></td>
                                                </tr>
                                                <?php if(isset($invoice['tenant_name'])):?>
                                                <tr>
                                                    <td style="color:#000000"><?php echo esc_html($invoice['tenant_name']); ?></td>
                                                </tr>
                                                <?php endif; ?>
                                                <tr>
                                                    <td><?php echo esc_html($companyData['tenant_address']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo esc_html($companyData['tenant_zipcode']." ". $companyData['tenant_city']); ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo esc_html($companyData['tenant_country']); ?></td>
                                                </tr>
												<?php if (!empty($companyData['tenant_phone'])): ?>
                                                    <tr>
                                                        <td>Tel: <?php echo esc_html($companyData['tenant_phone']); ?></td>
                                                    </tr>
												<?php endif; ?>
<!--												--><?php //if (!empty($companyData['customer_iban'])): ?>
<!--                                                    <tr>-->
<!--                                                        <td>IBAN: --><?php //echo esc_html($companyData['customer_iban']); ?><!--</td>-->
<!--                                                    </tr>-->
<!--												--><?php //endif; ?>
<!--												--><?php //if (!empty($companyData['customer_kvk_number'])): ?>
<!--                                                    <tr>-->
<!--                                                        <td>KVK: --><?php //echo esc_html($companyData['customer_kvk_number']); ?><!--</td>-->
<!--                                                    </tr>-->
<!--												--><?php //endif; ?>
<!--												--><?php //if (!empty($companyData['customer_btw_number'])): ?>
<!--                                                    <tr>-->
<!--                                                        <td>BTW: --><?php //echo esc_html($companyData['customer_btw_number']); ?><!--</td>-->
<!--                                                    </tr>-->
<!--												--><?php //endif; ?>
                                                <tr>
                                                    <td>Factuurdatum: <?php echo date('d-m-Y', strtotime($invoice['invoice_date'])); ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Factuurnummer: <?php echo esc_html($invoice['invoice_number']); ?>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td style="width:30%;">
                                            <table border="0" cellpadding="0" cellspacing="0" align="right">
                                                <tbody>
                                                <tr>
                                                    <td style="color:#000000">
                                                        <strong><?php echo esc_html($companyData['company_name']); ?></strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo esc_html($companyData['address']); ?></strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
														<?php echo esc_html($companyData['zipcode']); ?> <?php echo esc_html($companyData['city']); ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
														<?php echo esc_html($companyData['country']); ?>
                                                    </td>
                                                </tr>
												<?php if (!empty($companyData['phone'])): ?>
                                                    <tr>
                                                        <td>
                                                            Tel: <?php echo esc_html($companyData['phone']); ?>
                                                        </td>
                                                    </tr>
												<?php endif; ?>
												<?php if (!empty($companyData['iban'])): ?>
                                                    <tr>
                                                        <td>
                                                            IBAN: <?php echo esc_html($companyData['iban']); ?>
                                                        </td>
                                                    </tr>
												<?php endif; ?>
												<?php if (!empty($companyData['kvk_number'])): ?>
                                                    <tr>
                                                        <td>
                                                            KVK: <?php echo esc_html($companyData['kvk_number']); ?>
                                                        </td>
                                                    </tr>
												<?php endif; ?>
												<?php if (!empty($companyData['btw_number'])): ?>
                                                    <tr>
                                                        <td>
                                                            BTW: <?php echo esc_html($companyData['btw_number']); ?>
                                                        </td>
                                                    </tr>
												<?php endif; ?>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <!-- Spacer -->
                            </td>
                        </tr>
                        <tr class="main_tr">
                            <td class="main_td"><table>
                                    <tbody><tr>
                                        <td class="appoint_status"><strong style="color:#357e22; "><?php echo $invoice['status'] === 'completed' ? 'Reeds betaald' : 'In behandeling'; ?></strong>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td><table>
                                    <tbody>                                    <tr>
                                        <td style="width:60%;" valign="top"><strong>Kenmerk:</strong> <?php echo esc_html($invoice['package_name'] ?? $invoice['product_name'] ?? 'N/A'); ?>
                                        </td>
                                        <td style="width:40%;"><table>
                                                <tbody><tr>
                                                    <td><table>
                                                            <tbody>
                                                            <tr>
                                                                <td><strong>Vervaldatum:</strong>
                                                                </td>
                                                                <td><?php
																	$dueDate = $invoice['invoice_date'] ?? $invoice['invoice_date'];
																	echo $dueDate ? date('d-m-Y', strtotime($dueDate)) : 'N/A';
																	?>
                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="main_tr"><td>&nbsp;</td></tr>
                        <tr class="main_tr">
                            <td class="main_td"><table class="purchased_table">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th width="25%">Omschrijving</th>
                                        <th>Bedrag</th>
                                        <th>Totaal</th>
                                        <th width="15%">BTW</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr style="height:2px !important;">
                                        <td colspan="5" height="1" style="height:2px !important;"><div style="height:2px; border-bottom:1px solid #000;"></div></td>
                                    </tr>
                                    <tr>
                                        <td><p><?php echo ($invoice['quantity'] ?? 1); ?>x</p></td>
                                        <td><?php echo esc_html($invoice['package_name'] ?? $invoice['product_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo Helper::price($invoice['package_price'] ?? $invoice['unit_price'] ?? 0); ?></td>
                                        <td><?php echo Helper::price($invoice['package_price'] ?? $invoice['subtotal'] ?? 0); ?></td>
                                        <td><?php echo Helper::price($invoice['tax_amount'] ?? 0); ?></td>
                                    </tr>



                                    <tr>
                                        <td colspan="5" height="1">
                                            <div style="height:2px; border-bottom:1px solid #000;"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td style="height: 20px;"><strong>Subtotaal </strong></td>
                                        <td><strong><?php echo Helper::price($invoice['subtotal']); ?></strong></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td><strong>BTW </strong></td>
                                        <td><strong><?php echo Helper::price($invoice['tax_amount']); ?></strong></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="padding:1px"></td>
                                        <td colspan="3" ><div style="height:2px; border-bottom:1px solid #000;"></div></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td><strong>Totaal </strong></td>
                                        <td><strong><?php echo Helper::price($invoice['total_amount']); ?></strong></td>
                                        <td></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <!-- end copy block -->
            </tbody>
        </table>
        <!-- end body -->
        <p></p>
        <p></p>
        <!-- Footer -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
			<?php if (!empty($companyData['footer_text'])): ?>
                <p style="margin-bottom: 13px; font-size: 13px; color: #666;"><?php echo nl2br(esc_html($companyData['footer_text'])); ?></p>
			<?php endif; ?>
        </div>
        </body>
        </html>
		<?php

		return ob_get_clean();
	}

    /**
     * Get company data from invoice data
     */
    private function getCompanyData($invoice = null, $tenantId = null)
    {
        if (!$invoice) {
            return $this->getEmptyCompanyData();
        }

        return [
			'customer_company_name' => $invoice['customer_company_name'] ?? '',
			'customer_address' => $invoice['customer_company_address'] ?? '',
			'customer_zipcode' => $invoice['customer_company_zipcode'] ?? '',
			'customer_city' => $invoice['customer_company_city'] ?? '',
			'customer_country' => $invoice['customer_company_country'] ?? '',
			'customer_iban' => $invoice['customer_company_iban'] ?? '',
			'customer_phone' => $invoice['customer_phone'] ?? '',
			'customer_kvk_number' => $invoice['customer_company_kvk_number'] ?? '',
			'customer_btw_number' => $invoice['customer_company_btw_number'] ?? '',
            'company_name' => $invoice['company_name'] ?? '',
            'address' => $invoice['company_address'] ?? '',
            'zipcode' => $invoice['company_zipcode'] ?? '',
            'city' => $invoice['company_city'] ?? '',
            'country' => $invoice['company_country'] ?? '',
            'iban' => $invoice['company_iban'] ?? '',
            'phone' => $invoice['company_phone'] ?? '',
            'kvk_number' => $invoice['company_kvk_number'] ?? '',
            'btw_number' => $invoice['company_btw_number'] ?? '',
            'footer_text' => $invoice['company_footer_text'] ?? '',
            'logo' => $invoice['company_logo'] ?? ''
        ];
    }

	/**
	 * @param $invoice
	 * @return array|string[]
     * Get Saas company data.
	 */
    private function getSaasCompanyData($invoice = null) {
		if (!$invoice) {
			return $this->getSaasEmptyCompanyData();
		}
        $billingAddress = json_decode($invoice['billing_address']);
		return [
			'tenant_company_name' => $invoice['customer_name'] ?? '',
			'tenant_address' => $billingAddress->address_1 ?? '',
			'tenant_zipcode' => $billingAddress->postcode ?? '',
			'tenant_city' => $billingAddress->city  ?? '',
			'tenant_country' => $billingAddress->country ?? '',
			'tenant_phone' => $invoice['customer_phone'] ?? '',
			'company_name' => $invoice['company_name'] ?? '',
			'address' => $invoice['company_address'] ?? '',
			'zipcode' => $invoice['company_zipcode'] ?? '',
			'city' => $invoice['company_city'] ?? '',
			'country' => $invoice['company_country'] ?? '',
			'iban' => $invoice['company_iban'] ?? '',
			'phone' => $invoice['company_phone'] ?? '',
			'kvk_number' => $invoice['company_kvk_number'] ?? '',
			'btw_number' => $invoice['company_btw_number'] ?? '',
			'footer_text' => $invoice['company_footer_text'] ?? '',
			'logo' => $invoice['company_logo'] ?? ''
		];
	}

	/**
	 * Get Saas empty company data structure
	 */
	private function getSaasEmptyCompanyData()
	{
		return [
			'tenant_company_name' => '',
			'tenant_address' => '',
			'tenant_zipcode' => '',
			'tenant_city' => '',
			'tenant_country' => '',
			'tenant_phone' => '',
			'company_name' => '',
			'address' => '',
			'zipcode' => '',
			'city' => '',
			'country' => '',
			'iban' => '',
			'phone' => '',
			'kvk_number' => '',
			'btw_number' => '',
			'footer_text' => '',
			'logo' => ''
		];
	}

    /**
     * Get empty company data structure
     */
    private function getEmptyCompanyData()
    {
        return [
			'customer_company_name' => '',
			'customer_address' => '',
			'customer_zipcode' => '',
			'customer_city' => '',
			'customer_country' => '',
			'customer_iban' => '',
			'customer_phone' => '',
			'customer_kvk_number' => '',
			'customer_btw_number' => '',
            'company_name' => '',
            'address' => '',
            'zipcode' => '',
            'city' => '',
            'country' => '',
            'iban' => '',
            'phone' => '',
            'kvk_number' => '',
            'btw_number' => '',
            'footer_text' => '',
            'logo' => ''
        ];
    }
} 