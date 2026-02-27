<?php
namespace BloompyAddon\WooCommerceBridge\Domain\Shortcodes;

use BloompyAddon\WooCommerceBridge\Domain\Interfaces\ShortcodeInterface;
use BloompyAddon\WooCommerceBridge\Domain\FreeSignupHandler;

class FreeSignupShortcode implements ShortcodeInterface
{
    public static function register(): void
    {
        add_shortcode('bloompy_free_signup_form', [self::class, 'render']);
    }

    public static function render($atts): string
    {
        if (is_user_logged_in()) {
            return '<p>Je bent al ingelogd.</p>';
        }

        $errors = FreeSignupHandler::getErrors();

        ob_start();

        if (!empty($errors)) {
            echo '<div class="bloompy-errors" style="color: red;">';
            foreach ($errors as $error) {
                echo '<p>' . esc_html($error) . '</p>';
            }
            echo '</div>';
        }
        ?>
        <div class="bloompy_signup">
            <div class="bloompy_step_1">
                <div class="bloompy_header">Aanmelden</div>
        <form method="POST" action="" class="bloompy_form">
            <?php wp_nonce_field('bloompy_free_signup_action', 'bloompy_nonce'); ?>
            <input type="hidden" name="bloompy_free_signup" value="1" />
            <!-- Honeypot field -->
            <div class="bloompy_form_element"  style="display: none;">
                <label>Leave this field empty</label>
                <input type="text" name="website" />
            </div>
            <div class="bloompy_form_element">
                <label for="first_name">Voornaam</label><br>
                <input type="text" name="first_name" id="first_name" class="bloompy-signup-input" required />
            </div>
            <div class="bloompy_form_element">
                <label for="last_name">Achternaam</label><br>
                <input type="text" name="last_name" id="last_name" class="bloompy-signup-input" required />
            </div>
            <div class="bloompy_form_element">
                <label for="email">E-mailadres</label><br>
                <input type="email" name="email" id="email" class="bloompy-signup-input" required />
            </div>
            <div class="bloompy_form_element">
                <button type="submit" class="bloompy_btn_primary bloompy_signup_btn">Meld je gratis aan</button>
            </div>
        </form>
        </div>
        </div>
        <?php
        return ob_get_clean();
    }
} 