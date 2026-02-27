<?php

defined('ABSPATH') or die();

use function \BookneticAddon\Newsletters\bkntc__;

$assignMode = $parameters['assign_mode'] ?? '';
$services = $parameters['services'] ?? [];
$mailblueLists = $parameters['mailblue_lists'] ?? [];
$mailblueMap = $parameters['mailblue_map'] ?? [];
$mailblueDefault = $parameters['mailblue_default'] ?? '';
$mailchimpLists = $parameters['mailchimp_lists'] ?? [];
$mailchimpMap = $parameters['mailchimp_map'] ?? [];
$mailchimpDefault = $parameters['mailchimp_default'] ?? '';
$message = $parameters['message'] ?? '';

?>
<div style="max-width:700px;margin:40px auto;padding:24px;background:#fff;border-radius:8px;box-shadow:0 2px 8px #0001;">
    <form method="post">
        <?php if ($message): ?>
            <div style="color:green;margin-bottom:12px;">✔ <?php echo esc_html($message); ?></div>
        <?php endif; ?>
        <?php if ($assignMode === 'mailblue'): ?>
            <a href="<?php echo admin_url('admin.php?page=bloompy&module=newsletters') ?>" style="color:#3b82f6;text-decoration:underline;cursor:pointer;">&larr; <?php echo bkntc__('Back to default list'); ?></a>
            <h3 style="margin-bottom:12px;"><?php echo bkntc__('Assign MailBlue Lists to Services'); ?></h3>
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;"><?php echo bkntc__('Service'); ?></th>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;"><?php echo bkntc__('MailBlue List'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td style="padding:8px;border-bottom:1px solid #eee;"><?php echo esc_html($service['name']); ?></td>
                            <td style="padding:8px;border-bottom:1px solid #eee;">
                                <select name="mailblue_service_<?php echo (int)$service['id']; ?>" style="width:100%;padding:6px;">
                                    <option value=""><?php echo bkntc__('-- No list --'); ?></option>
                                    <?php foreach ($mailblueLists as $list): ?>
                                        <option value="<?php echo esc_attr($list['id']); ?>" <?php if (($mailblueMap[$service['id']] ?? '') == $list['id']) echo 'selected'; ?>>
                                            <?php echo esc_html($list['name'] ?? $list['id']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($assignMode === 'mailchimp'): ?>
            <a href="<?php echo admin_url('admin.php?page=bloompy&module=newsletters') ?>" style="color:#3b82f6;text-decoration:underline;cursor:pointer;">&larr; <?php echo bkntc__('Back to default list'); ?></a>
            <h3 style="margin-bottom:12px;"><?php echo bkntc__('Assign MailChimp Lists to Services'); ?></h3>
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;"><?php echo bkntc__('Service'); ?></th>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;"><?php echo bkntc__('MailChimp List'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td style="padding:8px;border-bottom:1px solid #eee;"><?php echo esc_html($service['name']); ?></td>
                            <td style="padding:8px;border-bottom:1px solid #eee;">
                                <select name="mailchimp_service_<?php echo (int)$service['id']; ?>" style="width:100%;padding:6px;">
                                    <option value=""><?php echo bkntc__('-- No list --'); ?></option>
                                    <?php foreach ($mailchimpLists as $list): ?>
                                        <option value="<?php echo esc_attr($list['id']); ?>" <?php if (($mailchimpMap[$service['id']] ?? '') == $list['id']) echo 'selected'; ?>>
                                            <?php echo esc_html($list['name'] ?? $list['id']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <button type="submit" style="margin-top:24px;padding:10px 24px;font-size:16px;background:#3b82f6;color:#fff;border:none;border-radius:4px;"><?php echo bkntc__('Save'); ?></button>
    </form>
</div> 