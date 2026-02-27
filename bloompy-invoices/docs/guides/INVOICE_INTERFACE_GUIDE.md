# Bloompy Invoice Interface System

This document explains the new flexible invoice interface system that has been implemented to replace the tightly coupled invoice implementation.

## Overview

The new system provides a clean, extensible architecture for handling different types of invoices:

1. **Customer Invoices** - For Booknetic appointments (existing functionality)
2. **WooCommerce Invoices** - For WooCommerce orders (new functionality)
3. **Future Invoice Types** - Easy to add new types like Moneybird, custom integrations, etc.

## Architecture

### Core Components

1. **InvoiceInterface** - Defines the contract for all invoice implementations
2. **AbstractInvoice** - Provides common functionality shared across invoice types
3. **InvoiceFactory** - Factory pattern for creating and managing invoice instances
4. **InvoiceService** - Facade service that provides a clean API for invoice operations

### Directory Structure

```
includes/
├── Interfaces/
│   └── InvoiceInterface.php          # Contract for invoice implementations
├── Abstract/
│   └── AbstractInvoice.php           # Base class with common functionality
├── Types/
│   ├── CustomerInvoice.php           # Booknetic appointment invoices
│   └── WooCommerceInvoice.php        # WooCommerce order invoices
├── Factories/
│   └── InvoiceFactory.php            # Factory for creating invoice instances
├── Services/
│   └── InvoiceService.php            # Main service facade
└── Hooks/
    └── WooCommerceHooks.php          # WooCommerce integration hooks
```

## Usage Examples

### Creating an Invoice

```php
use Bloompy\Invoices\Services\InvoiceService;

// Create a customer invoice (auto-detects type from data)
$invoiceId = InvoiceService::create([
    'invoice_number' => '2025-0001',
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
    'service_name' => 'Consultation',
    'total_amount' => 100.00,
    'source' => 'booknetic'
]);

// Create a WooCommerce invoice
$invoiceId = InvoiceService::create([
    'invoice_number' => 'WC-2025-0001',
    'order_id' => 12345,
    'customer_email' => 'customer@example.com',
    'product_name' => 'Premium Plan',
    'total_amount' => 299.00,
    'source' => 'woocommerce'
], 'woocommerce');
```

### Creating Invoice from WooCommerce Order

```php
use Bloompy\Invoices\Services\InvoiceService;

$order = wc_get_order($order_id);
$invoiceId = InvoiceService::createFromWooCommerceOrder($order);
```

### Getting Invoices

```php
// Get all invoices for tenant
$invoices = InvoiceService::getForTenant($tenantId, 20, 0, 'search term');

// Get invoices by type
$customerInvoices = InvoiceService::getForTenant($tenantId, 20, 0, '', 'customer');
$woocommerceInvoices = InvoiceService::getForTenant($tenantId, 20, 0, '', 'woocommerce');

// Get specific invoice
$invoice = InvoiceService::get($invoiceId);
```

### Using the Factory Directly

```php
use Bloompy\Invoices\Factories\InvoiceFactory;

// Create specific invoice type
$customerInvoice = InvoiceFactory::create('customer');
$woocommerceInvoice = InvoiceFactory::create('woocommerce');

// Auto-detect type from context
$invoice = InvoiceFactory::createForContext([
    'order_id' => 12345,
    'source' => 'woocommerce'
]);

// Get all available types
$types = InvoiceFactory::getAvailableTypes(); // ['customer', 'woocommerce']
```

## Adding New Invoice Types

### 1. Create the Invoice Class

```php
<?php
namespace Bloompy\Invoices\Types;

use Bloompy\Invoices\Abstract\AbstractInvoice;
use Bloompy\Invoices\Interfaces\InvoiceInterface;

class MoneybirdInvoice extends AbstractInvoice implements InvoiceInterface
{
    public function getType(): string
    {
        return 'moneybird';
    }

    public function getSource(): string
    {
        return 'moneybird';
    }

    public function getSearchFields(): array
    {
        return ['invoice_number', 'customer_name', 'customer_email'];
    }

    public function getDisplayColumns(): array
    {
        return [
            'invoice_number' => 'Invoice #',
            'customer' => function($row) {
                return '<strong>' . $row['customer_name'] . '</strong>';
            },
            // ... other columns
        ];
    }

    // Implement other required methods...
}
```

### 2. Register the Type

```php
use Bloompy\Invoices\Factories\InvoiceFactory;

// Register the new type
InvoiceFactory::registerType('moneybird', MoneybirdInvoice::class);
```

### 3. Use the New Type

```php
// Create moneybird invoice
$invoiceId = InvoiceService::create([
    'invoice_number' => 'MB-2025-0001',
    'customer_name' => 'John Doe',
    'total_amount' => 150.00,
    'source' => 'moneybird'
], 'moneybird');
```

## WooCommerce Integration

### Automatic Invoice Creation

The system automatically creates invoices when WooCommerce orders are completed:

```php
use Bloompy\Invoices\Hooks\WooCommerceHooks;

// Register hooks (usually done in plugin initialization)
WooCommerceHooks::registerHooks();
```

### Manual Invoice Creation

```php
use Bloompy\Invoices\Hooks\WooCommerceHooks;

// Create invoice for specific order
$invoiceId = WooCommerceHooks::manualCreateInvoice($order_id);

// Check if order has invoice
$hasInvoice = WooCommerceHooks::orderHasInvoice($order_id);

// Get invoice for order
$invoice = WooCommerceHooks::getInvoiceForOrder($order_id);
```

## Backend Integration

### Using the New Controller

```php
use Bloompy\Invoices\Backend\NewController;

// Show all invoices (auto-detects type)
$controller = new NewController();
$controller->index();

// Show specific invoice type
$controller->woocommerce(); // WooCommerce invoices
$controller->customer();    // Customer invoices
```

### DataTable Integration

The new system provides DataTable query adapters for each invoice type:

```php
// Get DataTable query for specific type
$query = InvoiceService::getDataTableQuery('woocommerce', $tenantId);

// Use with Booknetic DataTableUI
$dataTable = new DataTableUI($query);
```

## Migration from Old System

### Replacing Old Invoice Model Calls

```php
// Old way
$invoiceId = Invoice::create($data);
$invoice = Invoice::get($invoiceId);

// New way
$invoiceId = InvoiceService::create($data);
$invoice = InvoiceService::get($invoiceId);
```

### Updating Listener Class

```php
// Old way in Listener::createInvoiceForAppointment
$invoiceId = Invoice::create($invoiceData);

// New way
$invoiceId = InvoiceService::create($invoiceData);
```

## Benefits

1. **Extensibility** - Easy to add new invoice types without modifying existing code
2. **Separation of Concerns** - Each invoice type handles its own logic
3. **Clean API** - InvoiceService provides a simple, consistent interface
4. **Type Safety** - Interface ensures all implementations follow the same contract
5. **Backward Compatibility** - Existing functionality continues to work
6. **Future-Proof** - Ready for Moneybird, custom integrations, etc.

## Configuration

### Available Invoice Types

```php
$availableTypes = InvoiceService::getAvailableTypes();
// ['customer', 'woocommerce']

$supportedTypes = InvoiceService::getSupportedTypes();
// ['customer'] (if WooCommerce not available)
// ['customer', 'woocommerce'] (if WooCommerce is available)
```

### Feature Detection

```php
use Bloompy\Invoices\Factories\InvoiceFactory;

$woocommerceAvailable = InvoiceFactory::isFeatureAvailable('woocommerce');
$moneybirdAvailable = InvoiceFactory::isFeatureAvailable('moneybird');
```

## Error Handling

The system includes comprehensive error handling and logging:

```php
try {
    $invoiceId = InvoiceService::create($data);
    if (!$invoiceId) {
        error_log('Failed to create invoice');
    }
} catch (\Exception $e) {
    error_log('Invoice creation error: ' . $e->getMessage());
}
```

## Best Practices

1. **Always use InvoiceService** for invoice operations unless you need type-specific functionality
2. **Let the system auto-detect invoice types** when possible
3. **Use the factory pattern** when you need specific invoice type instances
4. **Implement proper error handling** in your integration code
5. **Test new invoice types thoroughly** before registering them
6. **Follow the interface contract** when creating new invoice types

This new system provides a solid foundation for the current invoice functionality while making it easy to extend with new invoice types in the future.

