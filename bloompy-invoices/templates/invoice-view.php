<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo sprintf(__('Invoice #%s', 'bloompy-invoices'), esc_html($invoice['invoice_number'])); ?></title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .invoice-actions {
            background: #007cba;
            color: white;
            padding: 15px 30px;
            text-align: right;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-left: 10px;
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
        }
        
        .invoice-content {
            padding: 40px;
        }
        
        .invoice-header {
            border-bottom: 2px solid #007cba;
            padding-bottom: 30px;
            margin-bottom: 40px;
        }
        
        .invoice-title {
            font-size: 36px;
            font-weight: bold;
            color: #007cba;
            margin-bottom: 10px;
        }
        
        .invoice-number {
            font-size: 18px;
            color: #666;
        }
        
        .company-info {
            float: right;
            text-align: right;
            max-width: 300px;
        }
        
        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #007cba;
        }
        
        .company-address {
            color: #666;
            font-size: 14px;
        }
        
        .invoice-details {
            clear: both;
            display: flex;
            justify-content: space-between;
            margin: 40px 0;
            gap: 40px;
        }
        
        .bill-to, .invoice-info {
            flex: 1;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #007cba;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .detail-item {
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .detail-label {
            font-weight: bold;
            color: #333;
        }
        
        .detail-value {
            color: #666;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 40px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .invoice-table th {
            background: linear-gradient(135deg, #007cba, #005a87);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .invoice-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .invoice-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .invoice-table tr:hover {
            background-color: #f0f8ff;
        }
        
        .text-right {
            text-align: right;
        }
        
        .service-description {
            font-weight: bold;
            color: #333;
        }
        
        .service-details {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .total-section {
            float: right;
            width: 300px;
            margin-top: 30px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .total-label {
            font-weight: 600;
            color: #333;
        }
        
        .total-value {
            font-weight: bold;
            color: #007cba;
        }
        
        .grand-total {
            border-top: 2px solid #007cba;
            margin-top: 15px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .grand-total .total-label,
        .grand-total .total-value {
            color: #007cba;
            font-size: 18px;
        }
        
        .notes {
            clear: both;
            margin-top: 50px;
            padding: 20px;
            background: #f0f8ff;
            border-left: 4px solid #007cba;
            border-radius: 0 8px 8px 0;
        }
        
        .notes-title {
            font-weight: bold;
            color: #007cba;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .notes-content {
            color: #666;
            line-height: 1.6;
        }
        
        .invoice-footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
            border: 1px solid #a3d977;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .invoice-content {
                padding: 20px;
            }
            
            .invoice-details {
                flex-direction: column;
                gap: 20px;
            }
            
            .company-info {
                float: none;
                text-align: left;
                max-width: none;
                margin-bottom: 20px;
            }
            
            .total-section {
                float: none;
                width: 100%;
                margin-top: 20px;
            }
            
            .invoice-table {
                font-size: 14px;
            }
            
            .invoice-table th,
            .invoice-table td {
                padding: 10px 8px;
            }
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .invoice-actions {
                display: none;
            }
            
            .invoice-content {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Action Buttons -->
        <div class="invoice-actions">
            <a href="<?php echo esc_url(add_query_arg('download', 'pdf')); ?>" class="btn">
                📥 Download PDF
            </a>
            <a href="javascript:window.print()" class="btn">
                🖨️ Print
            </a>
        </div>
        
        <div class="invoice-content">
            <!-- Header -->
            <div class="invoice-header">
                <?php if (!empty($invoice['company_name']) || !empty($invoice['company_address'])): ?>
                    <div class="company-info">
                        <?php if (!empty($invoice['company_name'])): ?>
                            <div class="company-name"><?php echo esc_html($invoice['company_name']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($invoice['company_address'])): ?>
                            <div class="company-address"><?php echo esc_html($invoice['company_address']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($invoice['company_city'])): ?>
                            <div class="company-address"><?php echo esc_html($invoice['company_city']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <h1 class="invoice-title">FACTUUR</h1>
                <p class="invoice-number">Factuur #<?php echo esc_html($invoice['invoice_number']); ?></p>
            </div>

            <!-- Invoice Details -->
            <div class="invoice-details">
                <div class="bill-to">
                    <div class="section-title">Factuur aan</div>
                    <div class="detail-item">
                        <div class="detail-value"><strong><?php echo esc_html($invoice['customer_name']); ?></strong></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-value"><?php echo esc_html($invoice['customer_email']); ?></div>
                    </div>
                    <?php if (!empty($invoice['customer_phone'])): ?>
                        <div class="detail-item">
                            <div class="detail-value"><?php echo esc_html($invoice['customer_phone']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="invoice-info">
                    <div class="section-title">Factuur informatie</div>
                    <div class="detail-item">
                        <span class="detail-label">Factuurdatum:</span>
                        <span class="detail-value"><?php echo date('d-m-Y', strtotime($invoice['invoice_date'])); ?></span>
                    </div>
                    <?php if (!empty($invoice['appointment_date'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Afspraak datum:</span>
                            <span class="detail-value"><?php echo date('d-m-Y H:i', strtotime($invoice['appointment_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge status-<?php echo esc_attr(strtolower($invoice['status'])); ?>">
                            <?php echo esc_html(ucfirst($invoice['status'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Beschrijving</th>
                        <th class="text-right">Prijs</th>
                        <th class="text-right">Aantal</th>
                        <th class="text-right">Totaal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="service-description"><?php echo esc_html($invoice['service_name']); ?></div>
                            <?php if (!empty($invoice['service_duration'])): ?>
                                <div class="service-details">Duur: <?php echo esc_html($invoice['service_duration']); ?> minuten</div>
                            <?php endif; ?>
                            <?php if (!empty($invoice['appointment_date'])): ?>
                                <div class="service-details">Afspraak: <?php echo date('d-m-Y H:i', strtotime($invoice['appointment_date'])); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?php echo Helper::price($invoice['service_price']); ?></td>
                        <td class="text-right">1</td>
                        <td class="text-right"><?php echo Helper::price($invoice['service_price']); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="total-section">
                <div class="total-row">
                    <span class="total-label">Subtotaal:</span>
                    <span class="total-value"><?php echo Helper::price($invoice['subtotal']); ?></span>
                </div>
                
                <?php if (!empty($invoice['tax_amount']) && $invoice['tax_amount'] > 0): ?>
                    <div class="total-row">
                        <span class="total-label">BTW (21%):</span>
                        <span class="total-value"><?php echo Helper::price($invoice['tax_amount']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="total-row grand-total">
                    <span class="total-label">Totaal:</span>
                    <span class="total-value"><?php echo Helper::price($invoice['total_amount']); ?></span>
                </div>
            </div>

            <!-- Notes -->
            <?php if (!empty($invoice['notes'])): ?>
                <div class="notes">
                    <div class="notes-title">Opmerkingen</div>
                    <div class="notes-content"><?php echo nl2br(esc_html($invoice['notes'])); ?></div>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="invoice-footer">
                <p>Gegenereerd op <?php echo date('d-m-Y H:i'); ?> | Powered by Bloompy Invoices</p>
                <p>Deze factuur is automatisch gegenereerd en geldig zonder handtekening.</p>
            </div>
        </div>
    </div>
</body>
</html> 