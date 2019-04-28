<?php

include('../cache.php');

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

function server_cache_example() {
    echo '<h1>Server Cache Example</h1>';

    $data = cache_fetch('example', 'server-cache');

    if ($data === false) {
        $result = get_some_data();

        $data = cache_store('example', 'server-cache', $result);
    }

    if (cache_check('example', 'server-cache', $data->version)) {
        echo '<p>Data up-to-date!</p>';
    }

    cache_delete('example', 'server-cache');

    if (!cache_check('example', 'server-cache', $data->version)) {
        echo '<p>Data could not get verified!</p>';
    }
}

server_cache_example();
