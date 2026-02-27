<?php
namespace BloompyAddon\WooCommerceBridge\Domain\Interfaces;

interface NotificationChannelInterface
{
    public function send(string $subject, string $message): void;
} 