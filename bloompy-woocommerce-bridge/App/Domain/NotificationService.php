<?php
namespace BloompyAddon\WooCommerceBridge\Domain;

use BloompyAddon\WooCommerceBridge\Domain\Interfaces\NotificationChannelInterface;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\Plan;

/**
 * Handles notifications for the WooCommerce Bridge plugin.
 */
class NotificationService
{
    private NotificationChannelInterface $channel;

    public function __construct(NotificationChannelInterface $channel)
    {
        $this->channel = $channel;
    }

    public function notifyFailedPlanSwitch(int $user_id, int $plan_id): void
    {
        $user = get_userdata($user_id);
        $plan = Plan::get($plan_id);

        $subject = '[Bloompy WooCommerce Bridge] Failed to assign plan during subscription switch';
        $message = sprintf(
            "Failed to assign plan ID %d (%s) to user ID %d (%s).\n\nTenant may be missing or update failed.",
            $plan_id,
            $plan ? $plan->name : 'Unknown Plan',
            $user_id,
            $user ? $user->user_email : 'Unknown User'
        );

        $this->channel->send($subject, $message);
    }
} 