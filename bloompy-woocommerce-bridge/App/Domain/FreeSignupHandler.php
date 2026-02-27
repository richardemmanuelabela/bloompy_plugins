<?php
namespace BloompyAddon\WooCommerceBridge\Domain;

use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\Helpers\Helper;

/**
 * Handles free signup functionality for creating tenants without WooCommerce purchase.
 */
class FreeSignupHandler
{
    public function registerHooks(): void
    {
        add_action('template_redirect', [$this, 'handleSignup']);
    }

    public function handleSignup(): void
    {
        if (empty($_POST['bloompy_free_signup'])) {
            return;
        }

        if (
            empty($_POST['bloompy_nonce']) ||
            !wp_verify_nonce($_POST['bloompy_nonce'], 'bloompy_free_signup_action')
        ) {
            self::addError('Security check failed.');
            return;
        }

        if (!empty($_POST['website'])) {
            self::addError('Spam detected.');
            return;
        }

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($email) || empty($first_name) || empty($last_name)) {
            self::addError('Vul alle vereiste velden in.');
            return;
        }

        if (email_exists($email)) {
            self::addError('Er bestaat al een account met dit e-mailadres.');
            return;
        }

        $password = wp_generate_password();
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            self::addError('Account kon niet worden aangemaakt.');
            return;
        }

        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
        ]);

        $user = get_userdata($user_id);
        $user->add_role('booknetic_saas_tenant');

        $plan = Plan::where('monthly_price', 0)->fetch();
        if (!$plan) {
            self::addError('Gratis plan niet beschikbaar.');
            return;
        }

        $tenant_id = Tenant::insert([
            'user_id' => $user_id,
            'full_name' => $first_name . ' ' . $last_name,
            'email' => $email,
            'domain' => sanitize_title($first_name . '-' . $last_name),
            'plan_id' => $plan['id'],
            'expires_in' => Date::dateSQL('+' . Helper::getOption('trial_period', 30) . ' days'),
            'inserted_at' => Date::dateTimeSQL(),
            'verified_at' => Date::dateTimeSQL(),
        ]);

        if (method_exists(Tenant::class, 'createInitialData')) {
            Tenant::createInitialData($tenant_id);
        }

        do_action('bkntcsaas_tenant_sign_up_confirm', $tenant_id);
        do_action('bloompy_tenant_created_from_free_signup', $tenant_id, $user_id);

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        delete_transient(self::getTransientKey());

        wp_safe_redirect(admin_url('admin.php?page=bloompy'));
        exit;
    }

    private static function addError(string $msg): void
    {
        $key = self::getTransientKey();
        $errors = get_transient($key) ?: [];
        $errors[] = $msg;
        set_transient($key, $errors, 60); // 1 minute
    }

    public static function getErrors(): array
    {
        $key = self::getTransientKey();
        $errors = get_transient($key) ?: [];
        delete_transient($key);
        return $errors;
    }

    private static function getTransientKey(): string
    {
        return 'bloompy_signup_errors_' . md5($_SERVER['REMOTE_ADDR'] ?? 'cli');
    }
} 