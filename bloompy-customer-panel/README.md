# Bloompy Customer Panel

A WordPress plugin for Booknetic that provides customers with a self-service portal to manage their appointments, view booking history, and update their profile information.

## Description

Bloompy Customer Panel enables customers to access a personalized dashboard where they can:

- View and manage their appointments
- Reschedule or cancel appointments (based on configured restrictions)
- Update their profile information
- Change their password
- View appointment history
- Access invoices and payment information

The plugin integrates seamlessly with Booknetic's booking system and provides a user-friendly interface for customer self-service.

## Features

### Appointment Management
- **View Appointments**: Customers can see all their upcoming and past appointments
- **Reschedule Appointments**: Reschedule appointments within allowed time restrictions
- **Cancel Appointments**: Cancel appointments based on configured rules
- **Status Changes**: Change appointment status (if allowed by admin settings)

### Profile Management
- **Update Profile**: Edit personal information including name, email, phone, birthdate, and gender
- **Change Password**: Secure password change functionality
- **Phone Number Formatting**: International phone number input with country code selection

### Settings & Configuration
- **Time Restrictions**: Configure minimum time before appointment to allow changes
- **Status Restrictions**: Control which appointment statuses can be modified
- **Reschedule Permissions**: Enable/disable rescheduling functionality
- **Customizable UI**: Modern, responsive design that works on all devices

### Integrations
- **Divi Builder**: Native Divi extension for easy page building
- **Gutenberg Blocks**: WordPress block editor support
- **Shortcode Support**: Use `[bloompy-customer-panel]` shortcode anywhere
- **Template Integration**: Shortcodes available for email templates

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Booknetic Core plugin
- Booknetic SaaS (for multi-tenant functionality)

## Installation

1. Upload the `bloompy-customer-panel` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Booknetic** > **Settings** > **Customer Panel** to configure settings
4. Create a page and add the `[bloompy-customer-panel]` shortcode

## Configuration

### Accessing Settings

1. Log in to your WordPress admin panel
2. Navigate to **Booknetic** > **Settings** > **Customer Panel** (or **Booknetic SaaS** > **Settings** > **Customer Panel** for SaaS version)
3. Configure the following options:

### Settings Options

#### Enable Customer Panel
Toggle to enable or disable the customer panel functionality.

#### Allow Reschedule
Enable or disable the ability for customers to reschedule appointments.

#### Reschedule Allowed Status
Select which appointment statuses allow rescheduling (comma-separated list).

#### Allowed Status Changes
Configure which statuses customers can change their appointments to.

#### Time Restriction
Set the minimum number of minutes before an appointment start time that changes are allowed. For example, if set to 5, customers cannot reschedule or cancel appointments within 5 minutes of the start time.

### Creating the Customer Panel Page

1. Create a new WordPress page
2. Add the shortcode: `[bloompy-customer-panel]`
3. Publish the page
4. In Booknetic settings, set the "Customer Panel Page ID" to the page you just created

## Usage

### Shortcode

The main shortcode to display the customer panel:

```
[bloompy-customer-panel]
```

### Email Template Shortcodes

The following shortcodes are available for use in email templates and notifications:

#### `{customer_panel_url}`
Displays the URL to the customer panel page.

**Usage Example:**
```
Access your appointments at: {customer_panel_url}
```

#### `{customer_panel_restriction_time}`
Displays the configured time restriction (in minutes) for making changes to appointments.

**Usage Example:**
```
You can reschedule appointments up to {customer_panel_restriction_time} minutes before the appointment time.
```

### Gutenberg Block

If using the WordPress block editor, you can add the "Booknetic Customer Panel" block to any page or post.

### Divi Builder

If using Divi Builder, you can add the "Booknetic Customer Panel" module to your page.

## Customer Features

### Dashboard
- Overview of upcoming appointments
- Quick access to profile settings
- Recent activity summary

### Appointments
- **List View**: See all appointments in a table format
- **Filter Options**: Filter by date, status, or service
- **Actions**: Reschedule, cancel, or change status (based on permissions)

### Profile
- **Personal Information**: Update name, surname, email, phone, birthdate, gender
- **Password Management**: Change password securely
- **Phone Formatting**: International phone number formatting with country flags

### Restrictions

The following restrictions apply to customer actions:

1. **Time Restriction**: Customers cannot modify appointments within the configured time limit before the appointment start time
2. **Status Restriction**: Only appointments with allowed statuses can be rescheduled
3. **Past Appointments**: Customers cannot modify appointments that have already started or passed
4. **Permission-Based**: All actions are subject to admin-configured permissions

## Technical Details

### File Structure

```
bloompy-customer-panel/
├── includes/
│   ├── Backend/
│   │   ├── Ajax.php              # Backend AJAX handlers
│   │   └── view/                 # Settings views
│   ├── Frontend/
│   │   ├── Ajax.php              # Frontend AJAX handlers
│   │   ├── Controller.php        # Frontend controller
│   │   └── view/                 # Frontend views
│   ├── Integrations/
│   │   └── Divi/                 # Divi Builder integration
│   ├── CustomerPanelAddon.php    # Main addon class
│   ├── CustomerPanelHelper.php   # Helper functions
│   └── Listener.php              # Event listeners
├── assets/
│   ├── backend/                  # Backend assets (CSS, JS, icons)
│   └── frontend/                 # Frontend assets (CSS, JS, images)
├── languages/                     # Translation files
├── vendor/                        # Composer dependencies
├── init.php                       # Plugin initialization
└── composer.json                  # Composer configuration
```

### Namespace

The plugin uses the namespace: `Bloompy\CustomerPanel`

### Dependencies

- Booknetic Core
- Booknetic SaaS (for multi-tenant functionality)
- Composer autoloader
- jQuery
- intlTelInput (for phone number formatting)
- Bootstrap (for styling)
- Select2 (for dropdowns)
- Flatpickr (for date pickers)

### JavaScript Libraries

The plugin uses the following frontend libraries:
- **jQuery**: DOM manipulation and AJAX
- **Bootstrap**: UI framework
- **Select2**: Enhanced select dropdowns
- **Flatpickr**: Date and time picker
- **intlTelInput**: International phone number input with country flags

## Security

- All customer actions require user authentication
- Customers can only access their own appointments and data
- Time and status restrictions prevent unauthorized modifications
- Password changes require current password verification
- All AJAX requests are validated and sanitized

## Customization

### Styling
The plugin includes customizable CSS files:
- `assets/frontend/css/custom.css` - Main custom styles
- `assets/frontend/css/override.css` - Style overrides
- `assets/frontend/css/booknetic-cp.css` - Core panel styles

### Hooks and Filters
The plugin provides various WordPress hooks for customization:
- `bkntc_after_customer_panel_shortcode` - Action fired after shortcode rendering
- `bloompy_cp_appointment_list_body` - Filter for appointment list content

## Troubleshooting

### Customer Panel Not Displaying
- Ensure the plugin is activated
- Check that "Customer Panel" is enabled in settings
- Verify the shortcode is correctly placed on the page
- Check that the user is logged in and has a customer profile

### Reschedule Not Working
- Verify "Allow Reschedule" is enabled in settings
- Check that the appointment status is in the allowed list
- Ensure the time restriction hasn't been exceeded
- Verify the appointment hasn't already started

### Phone Number Formatting Issues
- Ensure intlTelInput library is loading correctly
- Check browser console for JavaScript errors
- Verify the phone input field exists on the page

## Development

### Requirements

- PHP 7.4+
- Composer
- Node.js (for Divi extension development)

### Setup

1. Clone the repository
2. Run `composer install` to install dependencies
3. For Divi extension development, navigate to `includes/Integrations/Divi/` and run `npm install`
4. Activate the plugin in WordPress

### Code Style

The plugin follows:
- PSR-4 autoloading standards
- WordPress coding standards
- Namespace: `Bloompy\CustomerPanel`

## Support

For support, please contact:
- **Author**: Levie Company
- **Website**: https://simonelevie.nl/
- **License**: Commercial

## Changelog

### Version 1.0.0
- Initial release
- Customer dashboard
- Appointment management (view, reschedule, cancel)
- Profile management
- Password change functionality
- Time and status restrictions
- Divi Builder integration
- Gutenberg block support
- Shortcode support
- Email template shortcodes
- International phone number formatting
- Responsive design

## License

This is a commercial plugin. All rights reserved.

## Credits

Developed by Levie Company for Bloompy/Booknetic platform.
