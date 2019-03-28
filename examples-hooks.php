function output_some_html() {
    echo '<div id="my-content">';
        do_action('custom_output');
    echo '</div>';
}

output_some_html();


add_action('output_some_html', 'add_output');

function add_output() {
    echo 'Lorem Ipsum Dolor ...';
}





$debug_mode = false;
$debug_mode = apply_filters('change_debug_mode', $debug_mode);

if ($debug_mode === true) {
    echo 'Debug mode enabled!';
}




add_filter('change_debug_mode', 'activate_debug_mode');

function activate_debug_mode($current_value) {
    if ($current_value === false) {
        return true;
    }

    return $current_value;
}
