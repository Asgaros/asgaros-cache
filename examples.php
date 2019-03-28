<?php

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
        echo '</p>Data could not get verified!</p>';
    }
}

//server_cache_example();
