# Bloompy Invoices for Booknetic

A professional invoicing addon for Booknetic that automatically creates invoices for appointments and integrates with the Bloompy tenant forms system.

## Features

- **Automatic Invoice Creation**: Invoices are automatically generated when appointments are created in Booknetic
- **Company Information Integration**: Pulls company info from the Bloompy Tenant Forms addon
- **Professional Invoice Layout**: Clean, modern invoice design with PDF generation
- **Admin Management**: Full admin interface for viewing, creating, and managing invoices
- **Email Workflow Integration**: Shortcodes for including invoice links in Booknetic email workflows
- **Public Invoice Viewing**: Secure public URLs for customers to view and download invoices
- **Multi-tenant Support**: Full support for Booknetic SaaS with tenant isolation
- **Flexible Architecture**: Easy to extend for other sources like WooCommerce

## Installation

1. Upload the `bloompy-invoices` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically integrate with Booknetic

## Requirements

- WordPress 5.0+
- Booknetic Pro
- PHP 7.4+
- Booknetic Invoices plugin (for PDF generation dependencies)

## Usage

### Automatic Invoice Creation

Invoices are automatically created when:
- A new appointment is booked in Booknetic
- The plugin will extract company information from Bloompy Tenant Forms if available

### Admin Interface

Access the invoices through the Booknetic admin panel:
- View all invoices for your tenant
- Create manual invoices
- Download invoices as PDF
- Update invoice status
- View statistics

### Email Workflow Integration

Use these shortcodes in your Booknetic email workflows:

- `{invoice_link}` - Direct link to view the invoice
- `{invoice_number}` - Invoice number
- `{invoice_amount}` - Invoice total amount
- `{invoice_date}` - Invoice creation date

### Public Invoice Access

Customers can access their invoices through secure URLs:
- `yoursite.com/invoice/2025-0001?token=secure_token`
- Add `&download=pdf` to download directly as PDF

## Configuration

The plugin integrates automatically with:
- **Booknetic**: For appointment data and admin interface
- **Bloompy Tenant Forms**: For company information
- **Booknetic Workflows**: For email integration

## Invoice Fields

Each invoice contains:
- Invoice number (auto-generated, format: YEAR-XXXX)
- Customer information (name, email, phone)
- Service details (name, price, duration)
- Company information (from tenant forms)
- Appointment date and time
- Subtotal, tax, and total amounts
- Status (pending, paid, cancelled)
- Notes

## Privacy & Security

- Invoices are tenant-isolated (multi-tenant safe)
- Customer access requires secure token verification
- Only admins, tenant admins, and the customer can view invoices
- PDF generation uses server-side rendering for security

## Customization

The plugin is built with extensibility in mind:

### Adding New Invoice Sources

```php
// Example: Creating invoices from other sources
$invoiceData = [
    'invoice_number' => Invoice::generateInvoiceNumber(),
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
    'service_name' => 'Custom Service',
    'service_price' => 100.00,
    'total_amount' => 100.00,
    'source' => 'custom_source'
];

$invoiceId = Invoice::create($invoiceData);
```

### Custom Invoice Templates

Modify `templates/invoice-view.php` for custom invoice layouts.

### Additional Shortcodes

Register new shortcodes in the `Listener::registerShortCodes()` method.

## Hooks & Filters

### Actions

- `bloompy_invoice_created` - Fired when an invoice is created
- `bloompy_invoice_status_changed` - Fired when invoice status changes

### Filters

- `bloompy_invoice_data` - Filter invoice data before creation
- `bloompy_invoice_pdf_html` - Filter PDF HTML before generation

## Development

### File Structure

```
bloompy-invoices/
├── init.php                       # Main plugin file
├── includes/
│   ├── autoloader.php            # PSR-4 autoloader
│   ├── InvoicesAddon.php         # Main addon class
│   ├── Listener.php              # Event handlers
│   ├── Models/
│   │   └── Invoice.php           # Invoice model
│   ├── Backend/
│   │   ├── Controller.php        # Admin controller
│   │   ├── Ajax.php              # AJAX handler
│   │   └── view/
│   │       └── invoices.php      # Admin interface
│   ├── Frontend/
│   │   └── InvoiceViewer.php     # Public invoice viewer
│   └── Services/
│       └── PDFService.php        # PDF generation
├── templates/
│   └── invoice-view.php          # Invoice template
└── uninstall.php                 # Cleanup script
```

### Database Schema

The plugin uses WordPress custom post types (`bloompy_invoice`) with meta fields:

- `tenant_id` - Tenant isolation
- `invoice_number` - Unique invoice number
- `appointment_id` - Link to Booknetic appointment
- `customer_*` - Customer information
- `service_*` - Service details
- `company_*` - Company information
- `*_amount` - Financial data
- `status` - Invoice status
- `source` - Creation source

## Troubleshooting

### PDF Generation Issues

1. Ensure the `booknetic-invoices` plugin is installed (for mPDF dependencies)
2. Check PHP memory limit (minimum 128MB recommended)
3. Verify write permissions for `/wp-content/uploads/bloompy-invoices/`

### Rewrite Rules

If invoice URLs return 404:
1. Go to Settings > Permalinks
2. Click "Save Changes" to flush rewrite rules

### Missing Company Info

1. Ensure `bloompy-tenant-forms` plugin is active
2. Check that company info is saved in tenant forms
3. Verify form data is stored correctly in appointment meta

## Changelog

### 1.0.0
- Initial release
- Automatic invoice creation from Booknetic appointments
- Admin interface with statistics
- PDF generation
- Email workflow integration
- Public invoice viewing
- Multi-tenant support

## Support

For support, please contact the Bloompy development team or create an issue in the project repository.

## License

This plugin is licensed under GPL v2 or later. 