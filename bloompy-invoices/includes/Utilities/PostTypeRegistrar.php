<?php

declare(strict_types=1);

namespace Bloompy\Invoices\Utilities;

use Bloompy\Invoices\Constants\InvoiceConstants;

/**
 * Handles registration of custom post types for the invoice system
 */
class PostTypeRegistrar
{
    /**
     * Register the bloompy_invoice custom post type
     * 
     * @return void
     */
    public static function register(): void
    {
        $args = [
            'labels' => [
                'name' => __('Invoices', 'bloompy-invoices'),
                'singular_name' => __('Invoice', 'bloompy-invoices'),
                'menu_name' => __('Invoices', 'bloompy-invoices'),
                'add_new' => __('Add New', 'bloompy-invoices'),
                'add_new_item' => __('Add New Invoice', 'bloompy-invoices'),
                'edit_item' => __('Edit Invoice', 'bloompy-invoices'),
                'new_item' => __('New Invoice', 'bloompy-invoices'),
                'view_item' => __('View Invoice', 'bloompy-invoices'),
                'search_items' => __('Search Invoices', 'bloompy-invoices'),
                'not_found' => __('No invoices found', 'bloompy-invoices'),
                'not_found_in_trash' => __('No invoices found in trash', 'bloompy-invoices'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => ['title', 'custom-fields'],
            'show_in_rest' => false,
        ];

        register_post_type(InvoiceConstants::POST_TYPE, $args);
    }
}


