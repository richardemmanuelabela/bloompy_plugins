# Bloompy WooCommerce Bridge

A modern, extensible WordPress plugin that seamlessly integrates WooCommerce with Booknetic SaaS for automated plan assignment, upgrades, and subscription management.

## Description

Bloompy WooCommerce Bridge connects your WooCommerce store with Booknetic SaaS, enabling automatic plan assignment when customers purchase products or subscriptions. The plugin handles the entire lifecycle of plan management, from initial purchase to upgrades, downgrades, and renewals.

## Features

### Core Functionality

- **Automatic Plan Assignment**: Automatically assigns Booknetic plans to tenants based on WooCommerce orders and subscriptions
- **Product-Plan Association**: Associate Booknetic plans with WooCommerce products via admin meta box
- **Subscription Management**: Full support for WooCommerce Subscriptions with automatic plan assignment on activation, renewal, and cancellation
- **Plan Upgrades/Downgrades**: Seamless plan switching when customers upgrade or downgrade subscriptions
- **Customized Checkout Flow**: Optimized cart and checkout experience for SaaS plans
- **Post-Purchase Redirects**: Automatic redirects to custom thank you page after successful purchase
- **Free Signup Handler**: Create tenants without WooCommerce purchase for free trials or manual onboarding
- **Plan Upgrade UI**: Admin interface for viewing and managing plan upgrades
- **Variable Product Support**: Full support for WooCommerce variable products and variations

### User Experience

- **Single-Item Cart**: Ensures only one product is in the cart at a time
- **Direct Checkout**: Redirects to checkout immediately after adding to cart
- **Custom Thank You Page**: Branded thank you page with order details
- **Shop Page Redirect**: Redirects shop page to home for cleaner UX
- **Auto-Complete Orders**: Automatically completes orders for virtual/subscription products

### Architecture

- **SOLID Principles**: Built with modern OOP and SOLID principles
- **Extensible Design**: Easy to extend with new notification channels and features
- **Clean Separation**: Clear separation of concerns with domain services
- **Interface-Based**: Uses interfaces for extensibility (NotificationChannelInterface, ShortcodeInterface)

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher
- Booknetic SaaS plugin
- WooCommerce Subscriptions (for subscription features)

## Installation

1. Upload the `bloompy-woocommerce-bridge` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce and Booknetic SaaS are installed and activated
4. Configure WooCommerce products with Booknetic plans (see Configuration section)

## Configuration

### Associating Plans with Products

1. Navigate to **Products** > **All Products** in WordPress admin
2. Edit a product (or create a new one)
3. Look for the **"Bloompy Plan"** meta box in the sidebar
4. Select the appropriate Booknetic plan from the dropdown
5. Save the product

**Note:** For variable products, the plan association is automatically applied to all variations.

### Setting Up Products

#### Simple Products
- Create a WooCommerce product
- Set it as a subscription product (if using WooCommerce Subscriptions)
- Associate it with a Booknetic plan using the meta box
- Set the price and other product details

#### Variable Products
- Create a variable product
- Add variations (e.g., Monthly/Yearly, Basic/Pro)
- Associate the parent product with a Booknetic plan (applies to all variations)
- Set prices for each variation

### Plan Upgrade Settings

1. Navigate to **Booknetic** > **Plan Upgrades** in the admin menu
2. View your current plan and available upgrades
3. See plan limits and current usage
4. Upgrade directly from the interface

## Usage

### Shortcodes

The plugin provides several shortcodes for different use cases:

#### `[bloompy_buy_now]`
Displays a "Buy Now" button that adds a product to cart and redirects to checkout.

**Attributes:**
- `product_id` (required): WooCommerce product ID
- `variation_id` (optional): Variation ID for variable products
- `text` (optional): Button text (default: "Buy Now")

**Example:**
```
[bloompy_buy_now product_id="123" text="Subscribe Now"]
```

#### `[bloompy_free_signup]`
Displays a free signup form for creating tenants without purchase.

**Example:**
```
[bloompy_free_signup]
```

**Form Fields:**
- `first_name` (required)
- `last_name` (required)
- `email` (required)
- `bloompy_free_signup` (hidden field)
- `bloompy_nonce` (security nonce)

#### `[bloompy_thank_you_page]`
Displays the thank you page content after successful purchase.

**Example:**
```
[bloompy_thank_you_page]
```

#### `[bloompy_upgrade_plans]`
Displays plan upgrade options (redirects to admin interface).

**Example:**
```
[bloompy_upgrade_plans]
```

### Free Signup Form

Create a custom form with the following fields:

```html
<form method="post" action="">
    <input type="hidden" name="bloompy_free_signup" value="1">
    <?php wp_nonce_field('bloompy_free_signup', 'bloompy_nonce'); ?>
    
    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="last_name" placeholder="Last Name" required>
    <input type="email" name="email" placeholder="Email" required>
    
    <button type="submit">Sign Up</button>
</form>
```

## How It Works

### Plan Assignment Flow

1. **Customer Purchases Product**: Customer adds product to cart and completes checkout
2. **Order Processing**: WooCommerce processes the order
3. **Plan Detection**: Plugin detects the associated Booknetic plan from product meta
4. **Tenant Creation/Update**: 
   - If new customer: Creates tenant account
   - If existing customer: Updates tenant plan
5. **Plan Activation**: Assigns the plan to the tenant
6. **Redirect**: Redirects to custom thank you page

### Subscription Flow

1. **Subscription Created**: When subscription is activated
2. **Plan Assignment**: Plan is assigned to tenant
3. **Renewals**: On each renewal, plan is re-assigned (maintains active status)
4. **Cancellation**: When subscription is cancelled/expired, plan is removed
5. **Upgrades/Downgrades**: When subscription is switched, new plan is assigned

### Order Status Handling

- **Virtual Products**: Orders with only virtual products are auto-completed
- **Subscriptions**: Subscription orders skip "processing" status
- **Mixed Orders**: Orders with physical products follow normal WooCommerce flow

## Technical Details

### File Structure

```
bloompy-woocommerce-bridge/
├── App/
│   ├── Backend/
│   │   ├── Controller.php           # Base controller
│   │   ├── PlanUpgradesController.php  # Plan upgrades admin interface
│   │   └── view/                    # Admin views
│   ├── Domain/
│   │   ├── CheckoutFlowController.php  # Cart/checkout customizations
│   │   ├── EmailNotificationChannel.php  # Email notifications
│   │   ├── FreeSignupHandler.php    # Free signup processing
│   │   ├── NotificationService.php  # Notification system
│   │   ├── ProductPlanMetaBox.php   # Product meta box
│   │   ├── ShopRedirector.php       # Redirect handling
│   │   ├── TenantPlanAssigner.php   # Plan assignment logic
│   │   ├── UpgradeFlowController.php # Upgrade UI
│   │   ├── Interfaces/              # Interface definitions
│   │   └── Shortcodes/              # Shortcode implementations
│   ├── HookRegistrar.php            # Centralized hook registration
│   └── WooCommerceBridgeAddon.php  # Main addon class
├── assets/
│   ├── backend/                     # Admin assets
│   └── frontend/                    # Frontend assets
├── languages/                       # Translation files
├── autoload.php                     # Autoloader
└── init.php                         # Plugin initialization
```

### Namespace

The plugin uses the namespace: `BloompyAddon\WooCommerceBridge`

### Key Classes

#### Domain Services

- **TenantPlanAssigner**: Handles all plan assignment, upgrade, and downgrade logic
- **ProductPlanMetaBox**: Manages WooCommerce product meta box for plan association
- **CheckoutFlowController**: Controls cart and checkout customizations
- **ShopRedirector**: Handles shop page and post-purchase redirects
- **UpgradeFlowController**: Manages plan upgrade UI and logic
- **FreeSignupHandler**: Processes free signup forms
- **NotificationService**: Extensible notification system
- **ShortcodeManager**: Manages all shortcode registrations

#### Interfaces

- **NotificationChannelInterface**: For creating custom notification channels
- **ShortcodeInterface**: For creating custom shortcodes

### WooCommerce Hooks Used

- `woocommerce_order_status_completed`
- `woocommerce_subscription_status_active`
- `woocommerce_subscription_renewal_payment_complete`
- `woocommerce_subscription_status_cancelled`
- `woocommerce_subscription_status_expired`
- `woocommerce_subscription_status_on-hold`
- `woocommerce_subscription_switch_completed`
- `woocommerce_add_to_cart_redirect`
- `woocommerce_add_to_cart_validation`
- `woocommerce_payment_complete`
- `woocommerce_payment_successful_result`
- `woocommerce_checkout_order_received_url`

## Extending the Plugin

### Adding New Notification Channels

1. Create a new class implementing `NotificationChannelInterface`:

```php
<?php
namespace YourNamespace;

use BloompyAddon\WooCommerceBridge\Domain\Interfaces\NotificationChannelInterface;

class CustomNotificationChannel implements NotificationChannelInterface
{
    public function send(string $message, array $data = []): bool
    {
        // Your notification logic
        return true;
    }
}
```

2. Register it in `HookRegistrar`:

```php
$customChannel = new CustomNotificationChannel();
$notificationService = new NotificationService($customChannel);
```

### Adding New Shortcodes

1. Create a new class implementing `ShortcodeInterface`:

```php
<?php
namespace YourNamespace;

use BloompyAddon\WooCommerceBridge\Domain\Interfaces\ShortcodeInterface;

class CustomShortcode implements ShortcodeInterface
{
    public static function register(): void
    {
        add_shortcode('custom_shortcode', [self::class, 'render']);
    }
    
    public static function render($atts): string
    {
        // Your shortcode logic
        return 'Output';
    }
}
```

2. Register it in `ShortcodeManager`:

```php
protected static array $shortcodes = [
    // ... existing shortcodes
    CustomShortcode::class,
];
```

## Troubleshooting

### Plan Not Assigning

- Verify the product has a plan associated in the meta box
- Check that the order status is "completed" or subscription is "active"
- Review WordPress debug log for errors
- Ensure tenant exists or can be created

### Subscription Not Working

- Verify WooCommerce Subscriptions is installed and activated
- Check subscription status in WooCommerce
- Ensure subscription product is properly configured
- Check that plan is associated with the subscription product

### Redirect Issues

- Verify the thank you page exists at `/thank-you-for-your-purchase/`
- Check that the shortcode `[bloompy_thank_you_page]` is on the page
- Clear WordPress and browser cache
- Check for conflicting redirect plugins

### Cart/Checkout Issues

- Verify WooCommerce is properly configured
- Check for JavaScript errors in browser console
- Ensure payment gateway is working correctly
- Review WooCommerce logs for errors

## Security

- All form submissions are validated and sanitized
- Nonces are used for all form submissions
- User capabilities are checked before allowing actions
- SQL queries use prepared statements
- Input validation on all user data

## Performance

- Tenant data is cached to reduce database queries
- Processed orders are tracked to prevent duplicate processing
- Efficient query patterns for plan and product lookups
- Minimal hook overhead

## Support

For support, please contact:
- **Author**: Bloompy
- **Website**: https://www.bloompy.nl
- **License**: Commercial

## Changelog

### Version 1.0.0
- Initial release
- Automatic plan assignment from WooCommerce orders
- Subscription support with automatic plan management
- Product-plan association via meta box
- Plan upgrade/downgrade handling
- Custom checkout flow
- Free signup functionality
- Plan upgrades admin interface
- Shortcode support
- Extensible notification system
- Variable product support
- Custom thank you page
- Shop page redirect

## License

This is a commercial plugin. All rights reserved.

## Credits

Developed by Bloompy for Booknetic SaaS platform integration.
