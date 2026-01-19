# Tenant Info by Bloompy

A WordPress plugin for Booknetic that enables tenants to customize their booking page information and footer content.

## Description

Tenant Info by Bloompy allows tenants in a multi-tenant Booknetic installation to:

- Set a custom company name displayed at the top of the booking page
- Configure custom footer content with up to three columns using a rich text editor
- Set Privacy Policy and Terms & Conditions URLs
- Use shortcodes in email templates and notifications to display tenant-specific information

This plugin is essential for multi-tenant SaaS installations where each tenant needs to brand their booking page with their own company information and legal links.

## Features

- **Company Information**: Set a company name that appears on the booking page
- **Custom Footer**: Configure up to three columns of footer content with rich text editing (Summernote editor)
- **Legal URLs**: Set custom Privacy Policy and Terms & Conditions URLs for each tenant
- **Shortcode Support**: Use shortcodes in email templates to display tenant-specific information
- **Template Integration**: Seamlessly integrates with Booknetic's template system
- **Multi-tenant Support**: Each tenant can have their own custom information
- **Rich Text Editor**: Summernote WYSIWYG editor for footer content

## Requirements

- WordPress 5.0 or higher
- PHP 5.6 or higher
- Booknetic Core plugin
- Booknetic SaaS (for multi-tenant functionality)

## Installation

1. Upload the `bloompy-tenant-info` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Booknetic** > **Settings** > **Booking Page Info** to configure tenant information

## Configuration

### Accessing Settings

1. Log in to your WordPress admin panel
2. Navigate to **Booknetic** > **Settings** > **Booking Page Info**
3. Configure the following settings:

### Settings Options

#### Company Name
Enter the company name that will be displayed at the top of the booking page. This is typically used in the booking page header or title area.

**Example:** "Acme Booking Services"

#### Privacy Policy URL
Set the URL to your Privacy Policy page. This can be used in shortcodes and footer content. Must be a valid URL format.

**Example:** `https://example.com/privacy-policy`

#### Terms & Conditions URL
Set the URL to your Terms & Conditions page. This can be used in shortcodes and footer content. Must be a valid URL format.

**Example:** `https://example.com/terms-and-conditions`

#### Footer Columns
Configure up to three columns of footer content:

- **Footer First Column**: Rich text editor for the first footer column
- **Footer Second Column**: Rich text editor for the second footer column
- **Footer Third Column**: Rich text editor for the third footer column

Each footer column supports:
- HTML content
- Text formatting (bold, italic, underline)
- Links
- Lists (ordered and unordered)
- Images
- Custom HTML

The footer columns are typically displayed side-by-side at the bottom of the booking page.

## Shortcodes

The plugin provides the following shortcodes that can be used in email templates, SMS notifications, and other Booknetic templates:

### `{bloompy_tenant_company_name}`
Displays the tenant's company name.

**Usage Example:**
```
Welcome to {bloompy_tenant_company_name}!

We're excited to have you book with us.
```

**Output:**
```
Welcome to Acme Booking Services!

We're excited to have you book with us.
```

### `{bloompy_tenant_footer_text}`
Displays the tenant's footer text (first column content).

**Usage Example:**
```
{bloompy_tenant_footer_text}
```

### `{bloompy_tenant_privacy_policy_url}`
Displays the tenant's Privacy Policy URL. Falls back to `https://bloompy.nl/privacybeleid/` if not set.

**Usage Example:**
```
<a href="{bloompy_tenant_privacy_policy_url}">View our Privacy Policy</a>
```

**Output:**
```html
<a href="https://example.com/privacy-policy">View our Privacy Policy</a>
```

### `{bloompy_tenant_terms_conditions_url}`
Displays the tenant's Terms & Conditions URL. Falls back to `https://bloompy.nl/algemene-voorwaarden/` if not set.

**Usage Example:**
```
<a href="{bloompy_tenant_terms_conditions_url}">Terms & Conditions</a>
```

**Output:**
```html
<a href="https://example.com/terms-and-conditions">Terms & Conditions</a>
```

## Template Integration

The plugin integrates with Booknetic's template system, allowing you to use tenant-specific information in:

- **Email Templates**: Confirmation emails, reminder emails, cancellation emails
- **SMS Notifications**: Text message notifications
- **PDF Documents**: Invoices, receipts, appointment confirmations
- **Booking Confirmations**: On-screen confirmation messages
- **Reminder Notifications**: Appointment reminders

### Using in Email Templates

1. Navigate to **Booknetic** > **Settings** > **Email Notifications**
2. Edit any email template
3. Use the shortcodes anywhere in the template:

```
Dear Customer,

Thank you for booking with {bloompy_tenant_company_name}.

Please review our <a href="{bloompy_tenant_privacy_policy_url}">Privacy Policy</a> 
and <a href="{bloompy_tenant_terms_conditions_url}">Terms & Conditions</a>.

Best regards,
{bloompy_tenant_company_name} Team
```

## Technical Details

### File Structure

```
bloompy-tenant-info/
├── App/
│   ├── Backend/
│   │   ├── Ajax.php              # AJAX handlers for settings
│   │   ├── Controller.php        # Backend controller
│   │   └── view/
│   │       ├── index.php         # Main settings view
│   │       └── tenant_info_settings.php  # Settings form
│   ├── Frontend/
│   │   └── Ajax.php              # Frontend AJAX handlers
│   ├── BloompyTenantsAddon.php   # Main addon class
│   └── Listener.php              # Event listeners and shortcode handlers
├── assets/
│   ├── backend/
│   │   ├── css/
│   │   │   └── edit.css          # Settings page styles
│   │   └── js/
│   │       └── edit.js           # Settings page JavaScript
│   └── frontend/
│       └── js/
│           └── tenant-info.js    # Frontend JavaScript
├── languages/                     # Translation files
├── vendor/                        # Composer dependencies
├── init.php                       # Plugin initialization
└── composer.json                  # Composer configuration
```

### Namespace

The plugin uses the namespace: `BookneticAddon\BloompyTenants`

### Dependencies

- Booknetic Core
- Booknetic SaaS (for multi-tenant functionality)
- Composer autoloader
- Summernote (rich text editor, provided by Booknetic)

### Data Storage

Tenant information is stored in the Booknetic SaaS tenant data system:
- Company name: Stored as tenant metadata
- Footer columns: Stored as tenant metadata (JSON format)
- URLs: Stored as tenant metadata

## Development

### Requirements

- PHP 5.6+
- Composer

### Setup

1. Clone the repository
2. Run `composer install` to install dependencies
3. Activate the plugin in WordPress

### Code Style

The plugin follows:
- PSR-4 autoloading standards
- WordPress coding standards
- Namespace: `BookneticAddon\BloompyTenants`

### Key Classes

- **BloompyTenantsAddon**: Main addon class extending `AddonLoader`
- **Listener**: Handles shortcode registration and replacement
- **Backend\Ajax**: Handles AJAX requests for saving settings
- **Backend\Controller**: Backend controller for settings page

## Use Cases

### Multi-tenant SaaS Platform
Each tenant can brand their booking page with:
- Their company name
- Custom footer with contact information, links, and branding
- Legal compliance links (Privacy Policy, Terms & Conditions)

### White-label Solution
Perfect for agencies or platforms offering booking services to multiple clients, where each client needs their own branding.

### Compliance
Ensure each tenant can provide their own legal documents and policies, meeting GDPR and other regulatory requirements.

## Troubleshooting

### Settings Not Saving
- Ensure you have the correct permissions (`bloompy_tenants` capability)
- Check browser console for JavaScript errors
- Verify AJAX requests are completing successfully

### Shortcodes Not Working
- Ensure the shortcode is correctly formatted with curly braces: `{bloompy_tenant_company_name}`
- Check that the tenant has set the company name or other required data
- Verify the template system is properly initialized

### Footer Not Displaying
- Check that footer content has been saved in the settings
- Verify the booking page template includes the footer output
- Check for CSS conflicts that might be hiding the footer

## Support

For support, please contact:
- **Author**: Levie Company
- **Website**: https://simonelevie.nl/
- **License**: Commercial

## Changelog

### Version 1.0.0
- Initial release
- Company name configuration
- Footer content editor (3 columns) with rich text support
- Privacy Policy and Terms & Conditions URL settings
- Shortcode support for templates
- Template field integration
- Summernote rich text editor integration

## License

This is a commercial plugin. All rights reserved.

## Credits

Developed by Levie Company for Bloompy/Booknetic platform.
