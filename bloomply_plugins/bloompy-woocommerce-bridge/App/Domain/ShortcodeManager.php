<?php
namespace BloompyAddon\WooCommerceBridge\Domain;

use BloompyAddon\WooCommerceBridge\Domain\Interfaces\ShortcodeInterface;
use BloompyAddon\WooCommerceBridge\Domain\Shortcodes\BuyNowShortcode;
use BloompyAddon\WooCommerceBridge\Domain\Shortcodes\FreeSignupShortcode;
use BloompyAddon\WooCommerceBridge\Domain\Shortcodes\ThankYouPageShortcode;

class ShortcodeManager
{
    /**
     * @var class-string<ShortcodeInterface>[]
     */
    protected static array $shortcodes = [
        BuyNowShortcode::class,
        FreeSignupShortcode::class,
        ThankYouPageShortcode::class,
        // Add other shortcode classes here
    ];

    public static function registerAll(): void
    {
        foreach (self::$shortcodes as $shortcodeClass) {
            if (class_exists($shortcodeClass) && is_subclass_of($shortcodeClass, ShortcodeInterface::class)) {
                $shortcodeClass::register();
            }
        }
    }
} 