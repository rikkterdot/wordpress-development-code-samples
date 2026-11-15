<?php
/**
 * Settings template for backend.
 */

 // Call to test Google Sheets connection
 if (isset($_POST['test_google_sheets'])) {
    $private = new \BuiltMighty\IMETF\imetfPrivate('imetric_finale', '1.0.0');
    $result = $private->test_google_sheets_connection();

    if (is_wp_error($result)) {
        $message = $result->get_error_message();
        add_settings_error('google_sheets_test', 'error', esc_html($message), 'error');
    } else {
        add_settings_error('google_sheets_test', 'success', esc_html__('Connection successful! Data fetched from Google Sheets.', 'imetr_finale'), 'updated');
    }
}

if (isset($_POST['process_google_sheets_data'])) {
    $private = new \BuiltMighty\IMETF\imetfPrivate('imetric_finale', '1.0.0');
    $result = $private->process_google_sheets_data();

    if (is_wp_error($result)) {
        $message = $result->get_error_message();
        add_settings_error('google_sheets_test', 'error', esc_html($message), 'error');
    } else {
        add_settings_error('google_sheets_test', 'success', esc_html__('Processed!', 'imetr_finale'), 'updated');
    }
}

?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Imetric Finale Connector Settings', 'imetr_finale'); ?></h1>

        <!-- Show any settings errors -->
        <?php settings_errors('google_sheets_test'); ?>

        <div class="card">
            <form method="post" action="options.php" enctype="multipart/form-data">
                <?php settings_fields('imetr_finale_settings'); ?>
                <?php do_settings_sections('imetr_finale_settings'); ?>

                <table class="form-table settings-table">
                    <tr valign="top">
                        <th scope="row">Spreadsheet ID</th>
                        <td class="flex">
                            <input type="text" name="imetric_finale_spreadsheet_id" value="<?php echo esc_attr(get_option('imetric_finale_spreadsheet_id')); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sheet Name</th>
                        <td class="flex">
                            <input type="text" name="imetric_finale_spreadsheet_name" value="<?php echo esc_attr(get_option('imetric_finale_spreadsheet_name')); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">JSON Key File</th>
                        <td class="flex">
                            <input type="file" name="imetric_finale_json_key" />
                            <?php if ($uploaded_file = get_option('imetric_finale_json_key_url')): ?>
                                <p>Uploaded File: <a href="<?php echo esc_url($uploaded_file); ?>" target="_blank"><?php echo basename($uploaded_file); ?></a></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr />

            <!-- Test Google Sheets Connection Form -->
            <form method="post">
                <button type="submit" name="test_google_sheets" class="button button-secondary">
                    <?php esc_html_e('Test Google Sheets Connection', 'imetr_finale'); ?>
                </button>
                <button type="submit" name="process_google_sheets_data" class="button button-secondary">
                    <?php esc_html_e('Process Sheets (delete after testing)', 'imetr_finale'); ?>
                </button>
            </form>
        </div>
    </div>
</div>
