<?php
/*
 * Plugin name: IMCA Youtube Feed
 * Description: Plugin's settings page
*/

/**
 * Plugin's Settings Page
 */
add_action('admin_menu', 'imca_ytf_add_plugin_page');
function imca_ytf_add_plugin_page()
{
    add_options_page('YouTube feed', 'YouTube feed', 'manage_options', 'imca-yt-feed', 'imca_ytf_options_page_output');
}

function imca_ytf_options_page_output()
{
    ?>
    <div class="wrap">
        <h2><?php echo get_admin_page_title() ?></h2>

        <form action="options.php" method="POST">
            <?php
            settings_fields('imca_ytf_options_group');     // hidden protection fields
            do_settings_sections('imca_ytf_settings_page'); // Sections with options. We have only single 'imca_ytf_section_general'
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register Settings.
 * All options will be stored in Array. Not one value - one option
 */
add_action('admin_init', 'imca_ytf_plugin_settings');
function imca_ytf_plugin_settings()
{
    // parameters: $option_group, $option_name, $imca_ytf_sanitize_callback
    register_setting('imca_ytf_options_group', IMCA_YTF_OPTION_NAME, 'imca_ytf_sanitize_callback');

    // Parameters: $id, $title, $callback, $page
    add_settings_section('imca_ytf_section_general', 'YouTube Feed Settings', '', 'imca_ytf_settings_page');
    // Parameters: $id, $title, $callback, $page, $section, $args
    add_settings_field(
        'imca_ytf_api_key',
        'API key',
        'fill_imca_option_input',
        'imca_ytf_settings_page',
        'imca_ytf_section_general',
        ['name' => 'imca_ytf_api_key', 'type' => 'text']
    );
    add_settings_field(
        'imca_ytf_playlist_id',
        'Playlist ID',
        'fill_imca_option_input',
        'imca_ytf_settings_page',
        'imca_ytf_section_general',
        ['name' => 'imca_ytf_playlist_id', 'type' => 'text']
    );
    add_settings_field(
        'imca_ytf_api_url',
        'YouTube API URL',
        'fill_imca_option_input',
        'imca_ytf_settings_page',
        'imca_ytf_section_general',
        ['name' => 'imca_ytf_api_url', 'type' => 'url']
    );
    add_settings_field(
        'imca_ytf_items_on_page',
        'Number of videos on a page',
        'fill_imca_option_input',
        'imca_ytf_settings_page',
        'imca_ytf_section_general',
        ['name' => 'imca_ytf_items_on_page', 'type' => 'number']
    );
    add_settings_field(
        'imca_ytf_update_period',
        'Update period (in seconds)',
        'fill_imca_option_input',
        'imca_ytf_settings_page',
        'imca_ytf_section_general',
        ['name' => 'imca_ytf_update_period', 'type' => 'number']
    );

}

## Fill option for plain text fields
function fill_imca_option_input($attr)
{
    $name = $attr['name'];
    $type = $attr['type'] ? $attr['type'] : 'text';

    $val = get_option(IMCA_YTF_OPTION_NAME);
    $val = $val ? $val[$name] : null;
    ?>
    <input class="imca_ytf-input" type="<?php echo $type ?>" name="imca_ytf_options[<?php echo $name ?>]"
           value="<?php echo esc_attr($val) ?>"/>
    <?php
}


## Data sanitize
function imca_ytf_sanitize_callback($options)
{
    // Sanitize
    foreach ($options as $name => & $val) {
        if ($name == 'imca_ytf_api_key' || $name == 'admin_email')
            $val = strip_tags($val);
    }
    return $options;
}


function imca_ytf_get_admin_email()
{
    $val = get_option('imca_options');
    $mail_to = $val ? $val['admin_email'] : null;

    if (!$mail_to) $mail_to = get_option('admin_email');

    return $mail_to;
}
