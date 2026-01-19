# Mollie Connect for Booknetic

A WordPress plugin that integrates Mollie Connect (OAuth2) payment gateway with Booknetic for split payments, recurring subscriptions, and automated payment processing.

## Description

Mollie Connect for Booknetic enables tenants to accept payments directly through their own Mollie accounts using the Mollie Connect API. This plugin provides:

- **Split Payments**: Platform can take a fee while tenants receive the rest directly
- **OAuth2 Integration**: Secure connection to tenant's Mollie account
- **Recurring Subscriptions**: Automatic subscription creation for recurring appointments
- **Webhook Support**: Real-time payment status updates via webhooks
- **Multi-tenant Support**: Each tenant connects their own Mollie account
- **Test Mode**: Full support for Mollie test mode

## Features

### Payment Processing
- **Split Payments**: Platform fee support with automatic split to tenant account
- **Multiple Payment Methods**: Support for all Mollie payment methods (iDEAL, credit card, PayPal, etc.)
- **Payment Links**: Generate payment links for existing appointments
- **Order Support**: Full support for WooCommerce Orders API
- **Payment Status Tracking**: Real-time payment status updates

### Subscription Management
- **Automatic Subscriptions**: Create Mollie subscriptions for recurring appointments
- **Mandate Management**: Handle customer mandates for recurring payments
- **Subscription Webhooks**: Process subscription payment status via webhooks
- **Interval Mapping**: Map service repeat types to Mollie subscription intervals

### Tenant Management
- **OAuth2 Connection**: Secure OAuth2 flow for connecting tenant Mollie accounts
- **Account Onboarding**: Check and display onboarding status
- **Profile Management**: Retrieve and display tenant Mollie profile information
- **Test Mode**: Per-tenant test mode configuration

### Security & Compliance
- **OAuth2 Security**: Secure token-based authentication
- **CSRF Protection**: State validation for OAuth callbacks
- **Permission Scopes**: Granular API permission management
- **Error Handling**: Comprehensive error handling and logging

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Booknetic Core plugin
- Booknetic SaaS (for multi-tenant functionality)
- Mollie Connect account (Client ID and Client Secret)

## Installation

1. Upload the `booknetic-mollie-connect` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Booknetic** > **Settings** > **Payment Gateways** > **Mollie Connect**
4. Configure your Mollie Connect credentials

## Configuration

### Initial Setup

#### For SaaS Platform (Multi-tenant)

1. Navigate to **Booknetic SaaS** > **Settings** > **Payment Split Payments Settings** > **Mollie Connect**
2. Enter your **Client ID** and **Client Secret** from your Mollie Connect app
3. Set **Platform Fee** (fixed amount or percentage)
4. Select **Fee Type** (price or percent)
5. Save settings

#### For Individual Tenants

1. Navigate to **Booknetic** > **Settings** > **Payment Gateways** > **Mollie Connect**
2. Click **"Connect with Mollie"** button
3. Authorize the connection in the popup window
4. Complete Mollie onboarding if required
5. Configure test mode if needed

### Settings Options

#### Platform Settings (SaaS)
- **Client ID**: Your Mollie Connect application Client ID
- **Client Secret**: Your Mollie Connect application Client Secret
- **Platform Fee**: Fee amount (fixed or percentage)
- **Fee Type**: Choose between fixed amount or percentage
- **Terms Page**: URL to terms and conditions page

#### Tenant Settings
- **Test Mode**: Enable/disable test mode for this tenant
- **Connection Status**: Display connection and onboarding status
- **Organization Info**: View connected Mollie organization details

### Mollie Connect App Setup

1. Log in to your Mollie Dashboard
2. Go to **Developers** > **Apps**
3. Create a new app
4. Set redirect URI to: `https://yoursite.com/?bkntc_mollie_connect=callback`
5. Copy the Client ID and Client Secret
6. Configure required scopes:
   - `organizations.read`
   - `payments.read`
   - `payments.write`
   - `customers.read`
   - `customers.write`
   - `orders.read`
   - `orders.write`
   - `profiles.read`
   - `onboarding.read`
   - `invoices.read`
   - `subscriptions.read`
   - `subscriptions.write`
   - `mandates.read`
   - `mandates.write`

## How It Works

### Payment Flow

1. **Customer Books Appointment**: Customer completes booking in Booknetic
2. **Payment Creation**: Plugin creates a payment in Mollie via tenant's connected account
3. **Customer Redirect**: Customer is redirected to Mollie payment page
4. **Payment Processing**: Customer completes payment on Mollie
5. **Webhook Notification**: Mollie sends webhook to your site
6. **Status Update**: Plugin updates appointment payment status
7. **Confirmation**: Customer is redirected back with confirmation

### Split Payment Flow

1. **Platform Fee Calculated**: Fee is calculated based on configured percentage or fixed amount
2. **Payment Created**: Payment is created with application fee
3. **Automatic Split**: 
   - Platform receives the application fee
   - Tenant receives the remaining amount directly to their Mollie account
4. **No Manual Transfer**: All handled automatically by Mollie

### Subscription Flow

1. **Recurring Service Detected**: Plugin detects service with recurring payment enabled
2. **First Payment**: Customer completes first payment with mandate
3. **Mandate Created**: Mollie creates customer mandate for recurring payments
4. **Subscription Created**: Plugin creates Mollie subscription for future payments
5. **Webhook Processing**: Subscription payments are processed via webhooks
6. **Automatic Renewal**: Future appointments are automatically paid via subscription

### Webhook Processing

1. **Mollie Sends Webhook**: When payment status changes, Mollie sends POST request
2. **Webhook URL**: `https://yoursite.com/?bkntc_mollie_subscription_webhook=1&tenant_id={tenant_id}`
3. **Payment Verification**: Plugin verifies payment with Mollie API
4. **Status Update**: Updates appointment payment status
5. **Subscription Processing**: If subscription payment, processes recurring subscription

## Webhook Configuration

### Setting Up Webhooks

Webhooks are automatically configured when creating payments and subscriptions. The webhook URL format is:

```
https://yoursite.com/?bkntc_mollie_subscription_webhook=1&tenant_id={tenant_id}
```

### Webhook Security

- Webhooks are validated against Mollie API
- Payment IDs are verified before processing
- Tenant ID is required and validated
- Only paid/authorized payments trigger actions

## Recurring Subscriptions

### Enabling Recurring Payments

1. Navigate to **Booknetic** > **Services** > Edit a service
2. Enable **"Automatic Recurring Payment"** option
3. Configure repeat type (daily, weekly, monthly, yearly)
4. Save service

### Subscription Creation

When a customer books a recurring service:

1. First payment is processed normally
2. Customer mandate is created for recurring payments
3. Subscription is created in Mollie for future payments
4. Subscription webhook URL is configured automatically
5. Future payments are processed automatically

### Subscription Intervals

The plugin maps service repeat types to Mollie subscription intervals:

- **Daily/Day**: `1 day`
- **Weekly/Week**: `1 week`
- **Monthly** (default): `1 month`
- **Annual/Yearly/Year**: `1 year`

## API Permissions

The plugin requires the following Mollie API permissions:

- **organizations.read**: Read organization information
- **payments.read**: Read payment information
- **payments.write**: Create and update payments
- **customers.read**: Read customer information
- **customers.write**: Create and update customers
- **orders.read**: Read order information
- **orders.write**: Create and update orders
- **profiles.read**: Read profile information
- **onboarding.read**: Check onboarding status
- **invoices.read**: Read invoice information
- **subscriptions.read**: Read subscription information
- **subscriptions.write**: Create and manage subscriptions
- **mandates.read**: Read customer mandates
- **mandates.write**: Create customer mandates

## Technical Details

### File Structure

```
booknetic-mollie-connect/
├── App/
│   ├── Backend/
│   │   ├── Ajax.php                    # Backend AJAX handlers
│   │   └── view/
│   │       ├── connect/                # Connection views
│   │       └── modal/                  # Modal views
│   ├── Handler/
│   │   ├── MollieRegisterHandler.php  # Registration handler
│   │   └── MollieSetupHandler.php     # Setup handler
│   ├── Helpers/
│   │   └── MollieConnectHelper.php    # Helper functions
│   ├── Integration/
│   │   └── MollieConnect.php          # Integration logic
│   ├── Listener.php                    # Event listeners
│   ├── Mollie.php                      # Legacy Mollie class
│   ├── MollieAddon.php                 # Main addon class
│   └── MollieConnectGateway.php       # Payment gateway implementation
├── assets/
│   ├── backend/                        # Admin assets
│   └── frontend/                       # Frontend assets
├── vendor/                             # Composer dependencies
│   ├── mollie/                         # Mollie API libraries
│   ├── league/oauth2-client/          # OAuth2 client
│   └── guzzlehttp/                     # HTTP client
├── init.php                            # Plugin initialization
└── composer.json                       # Composer configuration
```

### Namespace

The plugin uses the namespace: `BookneticAddon\Bloompy\Mollie`

### Key Classes

- **MollieAddon**: Main addon class extending `AddonLoader`
- **MollieConnectGateway**: Payment gateway implementation extending `PaymentGatewayService`
- **MollieConnectHelper**: Helper class for OAuth and API operations
- **Listener**: Event listeners for payment callbacks and webhooks
- **MollieConnect**: Integration handler for tenant onboarding

### Dependencies

- **mollie/mollie-api-php**: Official Mollie API client
- **mollie/oauth2-mollie-php**: Mollie OAuth2 provider
- **league/oauth2-client**: OAuth2 client library
- **guzzlehttp/guzzle**: HTTP client for API requests

## Payment Methods

The plugin supports all Mollie payment methods:

- iDEAL
- Credit Card (Visa, Mastercard, Amex)
- PayPal
- Bancontact
- SEPA Direct Debit
- SOFORT
- KBC/CBC Payment Button
- Belfius Direct Net
- EPS
- Giropay
- Przelewy24
- Apple Pay
- And more...

## Error Handling

### Common Errors

#### "Missing customers.read permission"
- **Cause**: Tenant connected before this permission was added
- **Solution**: Tenant needs to re-authorize the Mollie connection

#### "Missing subscriptions.write permission"
- **Cause**: Subscription creation requires this permission
- **Solution**: Tenant needs to re-authorize with updated scopes

#### "Payment not found"
- **Cause**: Payment ID doesn't exist or wrong test/live mode
- **Solution**: Check payment mode matches tenant settings

#### "Customer object is null"
- **Cause**: Cannot retrieve customer from Mollie API
- **Solution**: Check API permissions and customer ID

### Logging

The plugin logs important events to WordPress debug log:

- Payment creation and updates
- Subscription creation
- Webhook processing
- Error messages
- API responses

Enable WordPress debugging to view logs:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Security

### OAuth2 Security
- State parameter validation prevents CSRF attacks
- Secure token storage
- Automatic token refresh

### Payment Security
- All payments are processed through Mollie's secure infrastructure
- No credit card data is stored on your server
- PCI DSS compliance handled by Mollie

### Webhook Security
- Webhook URLs include tenant ID for isolation
- Payment verification before processing
- Status validation to prevent duplicate processing

## Testing

### Test Mode

1. Enable test mode in tenant settings
2. Use Mollie test API keys
3. Test payments with Mollie test cards:
   - **Success**: `4111111111111111`
   - **Failure**: `4000000000000002`
   - **3D Secure**: `4000000000003220`

### Test Scenarios

- One-time payments
- Recurring subscriptions
- Payment failures
- Webhook processing
- Plan upgrades/downgrades

## Troubleshooting

### Payment Not Processing

- Verify Mollie connection is active
- Check onboarding status is complete
- Ensure payment method is enabled in Mollie dashboard
- Verify webhook URL is accessible
- Check WordPress debug log for errors

### Subscription Not Creating

- Verify service has recurring payment enabled
- Check customer mandate was created
- Ensure subscriptions.write permission is granted
- Review subscription creation logs

### Webhook Not Working

- Verify webhook URL is publicly accessible
- Check Mollie dashboard for webhook delivery status
- Ensure tenant_id parameter is included
- Verify payment status before processing

### Connection Issues

- Verify Client ID and Client Secret are correct
- Check redirect URI matches Mollie app settings
- Ensure all required scopes are granted
- Clear browser cache and try reconnecting

## Development

### Requirements

- PHP 7.4+
- Composer
- Mollie API access

### Setup

1. Clone the repository
2. Run `composer install` to install dependencies
3. Configure Mollie Connect app
4. Activate the plugin

### Code Style

The plugin follows:
- PSR-4 autoloading standards
- WordPress coding standards
- SOLID principles

## Support

For support, please contact:
- **Author**: Levie Company
- **Website**: https://www.simonelevie.nl
- **License**: Commercial

## Changelog

### Version 1.0
- Initial release
- OAuth2 Mollie Connect integration
- Split payment support
- Recurring subscription support
- Webhook processing
- Multi-tenant support
- Test mode support
- Payment link generation
- Order API support

## License

This is a commercial plugin. All rights reserved.

## Credits

Developed by Levie Company for Bloompy/Booknetic platform integration with Mollie.
