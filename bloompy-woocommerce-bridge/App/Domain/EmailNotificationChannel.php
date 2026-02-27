<?php
namespace BloompyAddon\WooCommerceBridge\Domain;

use BloompyAddon\WooCommerceBridge\Domain\Interfaces\NotificationChannelInterface;

/**
 * Email notification channel implementation.
 */
class EmailNotificationChannel implements NotificationChannelInterface
{
    public function send(string $subject, string $message): void
    {
        wp_mail(get_option('admin_email'), $subject, $message);
    }
} 