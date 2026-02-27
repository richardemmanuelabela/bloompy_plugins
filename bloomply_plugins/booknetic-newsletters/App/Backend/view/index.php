<?php

defined('ABSPATH') or die();

$apiKey = $parameters['mailblue_api_key'] ?? '';
$apiUrl = $parameters['mailblue_api_url'] ?? '';
$message = $parameters['message'] ?? '';
$mailblueLists = $parameters['mailblue_lists'] ?? [];
$services = $parameters['services'] ?? [];
$mailblueMap = $parameters['mailblue_map'] ?? [];
$mailblueDefault = $parameters['mailblue_default'] ?? '';
$assignMode = $parameters['assign_mode'] ?? '';
$mailchimpApiKey = $parameters['mailchimp_api_key'] ?? '';
$mailchimpDataCenter = $parameters['mailchimp_data_center'] ?? '';
$mailchimpLists = $parameters['mailchimp_lists'] ?? [];
$mailchimpMap = $parameters['mailchimp_map'] ?? [];
$mailchimpDefault = $parameters['mailchimp_default'] ?? '';
?>

<div style="max-width:700px;margin:40px auto;padding:24px;background:#fff;border-radius:8px;box-shadow:0 2px 8px #0001;">
    <h2 style="margin-bottom:24px;">Newsletter Integrations</h2>
    <form method="post">
        <fieldset style="border:1px solid #eee;padding:16px 24px 24px 24px;border-radius:6px;margin-bottom:32px;">
            <legend style="font-weight:bold;">MailBlue</legend>
            <?php if ($message): ?>
                <div style="color:green;margin-bottom:12px;">✔ <?php echo esc_html($message); ?></div>
            <?php endif; ?>
            <?php if ($assignMode === 'mailblue'): ?>
                <div style="margin-top:32px;">
                    <a href="<?php echo admin_url('admin.php?page=bloompy&module=newsletters') ?>" style="color:#3b82f6;text-decoration:underline;cursor:pointer;">&larr; Back to default list</a>
                    <h3 style="margin-bottom:12px;">Assign MailBlue Lists to Services</h3>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f3f4f6;">
                                <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Service</th>
                                <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">MailBlue List</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td style="padding:8px;border-bottom:1px solid #eee;"><?php echo esc_html($service['name']); ?></td>
                                    <td style="padding:8px;border-bottom:1px solid #eee;">
                                        <select name="mailblue_service_<?php echo (int)$service['id']; ?>" style="width:100%;padding:6px;">
                                            <option value="">-- No list --</option>
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
                </div>
            <?php elseif ($assignMode === ''): ?>
                <?php if ($apiKey && $apiUrl): ?>
                    <div style="margin-bottom:16px;">
                        <label for="mailblue_api_key" style="display:block;font-weight:500;">API Key</label>
                        <input type="text" id="mailblue_api_key" name="mailblue_api_key" value="<?php echo esc_attr($apiKey); ?>" style="width:100%;padding:8px;">
                    </div>
                    <div style="margin-bottom:16px;">
                        <label for="mailblue_api_url" style="display:block;font-weight:500;">API URL</label>
                        <input type="text" id="mailblue_api_url" name="mailblue_api_url" value="<?php echo esc_attr($apiUrl); ?>" style="width:100%;padding:8px;">
                    </div>
                    <div style="margin-top:32px;">
                        <h3 style="margin-bottom:12px;">Default MailBlue List</h3>
                        <select name="mailblue_default_list" style="width:100%;padding:8px;max-width:400px;">
                            <option value="">-- No default list --</option>
                            <?php foreach ($mailblueLists as $list): ?>
                                <option value="<?php echo esc_attr($list['id']); ?>" <?php if ($mailblueDefault == $list['id']) echo 'selected'; ?>>
                                    <?php echo esc_html($list['name'] ?? $list['id']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div style="margin-top:16px;">
                            <a href="<?php echo admin_url('admin.php?page=bloompy&module=newsletters&assign=mailblue') ?>" style="color:#3b82f6;text-decoration:underline;cursor:pointer;">Assign lists to individual services</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </fieldset>

        <fieldset style="border:1px solid #eee;padding:16px 24px 24px 24px;border-radius:6px;">
            <legend style="font-weight:bold;">MailChimp</legend>
            <?php if ($assignMode === 'mailchimp'): ?>
                <div style="margin-top:32px;">
                    <a href="<?php echo admin_url('admin.php?page=bloompy&module=newsletters') ?>" style="color:#3b82f6;text-decoration:underline;cursor:pointer;">&larr; Back to default list</a>
                    <h3 style="margin-bottom:12px;">Assign MailChimp Lists to Services</h3>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f3f4f6;">
                                <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Service</th>
                                <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">MailChimp List</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td style="padding:8px;border-bottom:1px solid #eee;"><?php echo esc_html($service['name']); ?></td>
                                    <td style="padding:8px;border-bottom:1px solid #eee;">
                                        <select name="mailchimp_service_<?php echo (int)$service['id']; ?>" style="width:100%;padding:6px;">
                                            <option value="">-- No list --</option>
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
                </div>
            <?php elseif ($assignMode === ''): ?>
                <div style="margin-bottom:16px;">
                    <label for="mailchimp_api_key" style="display:block;font-weight:500;">API Key</label>
                    <input type="text" id="mailchimp_api_key" name="mailchimp_api_key" value="<?php echo esc_attr($mailchimpApiKey); ?>" style="width:100%;padding:8px;">
                </div>
                <div style="margin-bottom:16px;">
                    <label for="mailchimp_data_center" style="display:block;font-weight:500;">Data Center</label>
                    <input type="text" id="mailchimp_data_center" name="mailchimp_data_center" value="<?php echo esc_attr($mailchimpDataCenter); ?>" style="width:100%;padding:8px;">
                    <small style="color:#888;">Example: us21 (see your API key or MailChimp dashboard)</small>
                </div>
                <div style="margin-top:32px;">
                    <h3 style="margin-bottom:12px;">Default MailChimp List</h3>
                    <select name="mailchimp_default_list" style="width:100%;padding:8px;max-width:400px;">
                        <option value="">-- No default list --</option>
                        <?php foreach ($mailchimpLists as $list): ?>
                            <option value="<?php echo esc_attr($list['id']); ?>" <?php if ($mailchimpDefault == $list['id']) echo 'selected'; ?>>
                                <?php echo esc_html($list['name'] ?? $list['id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin-top:16px;">
                        <a href="<?php echo admin_url('admin.php?page=bloompy&module=newsletters&assign=mailchimp') ?>" style="color:#3b82f6;text-decoration:underline;cursor:pointer;">Assign lists to individual services</a>
                    </div>
                </div>
            <?php endif; ?>
        </fieldset>
        <button type="submit" style="margin-top:24px;padding:10px 24px;font-size:16px;background:#3b82f6;color:#fff;border:none;border-radius:4px;">Save</button>
    </form>
</div>