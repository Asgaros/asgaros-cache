<?php

// Load libraries.
require('cache.php');

// Loads some example-data from a database.
function get_some_data() {
    // Open database-connection.
    $connection = new PDO('mysql:host=localhost;dbname=caching', 'root', '');

    // Get some test-data.
    $query = $connection->prepare('SELECT * FROM data_storage WHERE id = 1;');
    $query->execute();
    $data = $query->fetch(PDO::FETCH_ASSOC);

    // Close database-connection.
    $connection = null;

    // Return data.
    return $data;
}

$callback_example_test_view_output = function() {
    $output = '';
    $data = get_some_data();

    if (!empty($data)) {
        foreach ($data as $key => $value) {
            $output .= '<h3>'.$key.'</h3>';

            if (strlen($value) > 50) {
                $output .= '<code>'.mb_substr($value, 0, 50, 'UTF-8').' &#8230;'.'</code>';
            } else {
                $output .= '<code>'.$value.'</code>';
            }
        }
    }

    return $output;
};

require('endpoints.php');


// Outputs a normal HTML-document.
if (!empty($_GET['mode']) && $_GET['mode'] === 'normal') {
    echo '<!DOCTYPE HTML>'.PHP_EOL;
    echo '<html>'.PHP_EOL;
    echo '<head>'.PHP_EOL;
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'.PHP_EOL;
    	echo '<title>Client Server Cache</title>'.PHP_EOL;
        echo '<script src="libs_client/jquery-3.3.1.js"></script>'.PHP_EOL;
        echo '<script src="libs_client/js.cookie.js"></script>'.PHP_EOL;
        echo '<script src="cache.js"></script>'.PHP_EOL;
    echo '</head>'.PHP_EOL;

    echo '<body>'.PHP_EOL;
        echo cacheable_view('example', 'test-view', $callback_example_test_view_output);
    echo '</body>'.PHP_EOL;
    echo '</html>'.PHP_EOL;
}
