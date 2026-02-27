<?php
namespace BloompyAddon\WooCommerceBridge;

use BloompyAddon\WooCommerceBridge\Domain\TenantPlanAssigner;
use BloompyAddon\WooCommerceBridge\Domain\ProductPlanMetaBox;
use BloompyAddon\WooCommerceBridge\Domain\CheckoutFlowController;
use BloompyAddon\WooCommerceBridge\Domain\ShopRedirector;
use BloompyAddon\WooCommerceBridge\Domain\UpgradeFlowController;
use BloompyAddon\WooCommerceBridge\Domain\FreeSignupHandler;
use BloompyAddon\WooCommerceBridge\Domain\NotificationService;
use BloompyAddon\WooCommerceBridge\Domain\EmailNotificationChannel;
use BloompyAddon\WooCommerceBridge\Domain\ShortcodeManager;

class HookRegistrar
{
    private TenantPlanAssigner $tenantPlanAssigner;
    private ProductPlanMetaBox $productPlanMetaBox;
    private CheckoutFlowController $checkoutFlowController;
    private ShopRedirector $shopRedirector;
    private UpgradeFlowController $upgradeFlowController;
    private FreeSignupHandler $freeSignupHandler;
    private NotificationService $notificationService;

    public function __construct()
    {
        // Initialize notification service with email channel
        $emailChannel = new EmailNotificationChannel();
        $this->notificationService = new NotificationService($emailChannel);
        
        // Make notification service globally available
        global $bloompy_woocommerce_bridge_notification_service;
        $bloompy_woocommerce_bridge_notification_service = $this->notificationService;

        // Initialize domain services
        $this->tenantPlanAssigner = new TenantPlanAssigner();
        $this->productPlanMetaBox = new ProductPlanMetaBox();
        $this->checkoutFlowController = new CheckoutFlowController();
        $this->shopRedirector = new ShopRedirector();
        $this->upgradeFlowController = new UpgradeFlowController();
        $this->freeSignupHandler = new FreeSignupHandler();
    }

    public function registerHooks(): void
    {
        $this->tenantPlanAssigner->registerHooks();
        $this->productPlanMetaBox->registerHooks();
        $this->checkoutFlowController->registerHooks();
        $this->shopRedirector->registerHooks();
        $this->upgradeFlowController->registerHooks();
        $this->freeSignupHandler->registerHooks();
        
        // Register all shortcodes
        ShortcodeManager::registerAll();
    }
} 