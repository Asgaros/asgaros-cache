<?php

include('cache.php');

// A debug-output function.
function debug_output($object) {
    print_r('<pre style="font-size: 11px;">');
    print_r($object);
    print_r('</pre>');
}

// Installs test-data.
if (!empty($_GET['install_test_data']) && $_GET['install_test_data'] === '1') {
    // Establish connection.
    $connection = new PDO('mysql:host=localhost;dbname=caching', 'root', '');

    // Get the first test-data.
    $query = $connection->prepare('SELECT * FROM data_storage WHERE id = 1;');
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    // Convert result-array into json.
    $json = json_encode($result);

    // Create cache-object.
    $data = array();
    $data['component']  = 'performance-test';
    $data['identifier'] = 'data';
    $data['type']       = 'data';
    $data['version']    = 1;
    $data['data']       = $json;

    // Save into cache-table.
    $statement = $connection->prepare("INSERT INTO data_cache (component, identifier, type, version, data) VALUES (:component, :identifier, :type, :version, :data);");
    $statement->execute($data);

    // Create key.
    $key = $data['component'].'_'.$data['identifier'];

    file_store($key, $data['data']);
}








if (!empty($_GET['run_write_test']) && $_GET['run_write_test'] === '1') {
    global $connection;
    $query = $connection->prepare('SELECT * FROM data_cache WHERE component = "time-test" AND query_id = "example-dataset-1";');
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    $loops = 99;

    echo '<h1>Write '.$loops.' datasets</h1>';

    echo '<h2>Write to database</h2>';

    // Start time-measurement.
    $start = microtime(true);

    // Run loops.
    for ($i = 0; $i < $loops; $i++) {
        //write_data_via('sql', $result['data'], $i);
    }

    // Stop time-measurement and display result.
    echo (microtime(true) - $start);








    echo '<h2>Write to file</h2>';

    // Start time-measurement.
    $start = microtime(true);

    // Run loops.
    for ($i = 0; $i < $loops; $i++) {
        write_data_via('file', $result['data'], $i);
    }

    // Stop time-measurement and display result.
    echo (microtime(true) - $start);






    echo '<h2>Write to redis</h2>';

    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    // Start time-measurement.
    $start = microtime(true);

    // Run loops.
    for ($i = 0; $i < $loops; $i++) {
        write_data_via('redis', $result['data'], $i);
    }

    // Stop time-measurement and display result.
    echo (microtime(true) - $start);






    echo '<h2>Write to APCu</h2>';

    // Start time-measurement.
    $start = microtime(true);

    // Run loops.
    for ($i = 0; $i < $loops; $i++) {
        write_data_via('apcu', $result['data'], $i);
    }

    // Stop time-measurement and display result.
    echo (microtime(true) - $start);
}














if (!empty($_GET['run_time_test']) && $_GET['run_time_test'] === '1') {
    $loops = 10;
    $random = false;

    echo '<h1>Read '.$loops.' datasets</h1>';

    echo '<h2>Read from database</h2>';
    access_data_via('sql', $random, $loops);

    echo '<h2>Read from file</h2>';
    access_data_via('file', $random, $loops);

    // Redis download:
    // php extension: https://pecl.php.net/package/redis/4.2.0/windows
    // windows server: https://github.com/dmajkic/redis/downloads
    // tutorial: https://www.tutorialspoint.com/redis/redis_php.htm
    //echo '<h2>Read from redis</h2>';
    //access_data_via('redis', $random, $loops);

    // APCu
    // https://pecl.php.net/package/APCu/5.1.15/windows
    echo '<h2>Read from APCu</h2>';
    access_data_via('apcu', $random, $loops);

    // Caching API
    echo '<h2>Read from Server Cache using API</h2>';

    // Prepare APCu.
    $key = 'performance-test_data-1';
    $result = file_fetch($key);
    cache_store('performance-test', 'data-1', $result);

    // Start time-measurement.
    $start = microtime(true);

    // Run loops.
    for ($i = 0; $i < $loops; $i++) {
        $result = cache_fetch('performance-test', 'data-1');
    }

    // Stop time-measurement and display result.
    $time_elapsed_secs = microtime(true) - $start;
    echo $time_elapsed_secs;
}

function access_data_via($type, $random = false, $loops = 10) {
    $id = 'data-1';

    if ($random) {
        $id = 'data-'.rand(1,2);
    }

    if ($type === 'sql') {
        // Establish connection.
        $connection = new PDO('mysql:host=localhost;dbname=caching', 'root', '');

        // Start time-measurement.
        $start = microtime(true);

        // Run loops.
        for ($i = 0; $i < $loops; $i++) {
            $query = $connection->prepare('SELECT * FROM data_cache WHERE component = "performance-test" AND identifier = "'.$id.'";');
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);
        }

        // Stop time-measurement and display result.
        $time_elapsed_secs = microtime(true) - $start;
        echo $time_elapsed_secs;
        return;
    }

    if ($type === 'file') {
        // Set key.
        $key = 'performance-test_'.$id;

        // Start time-measurement.
        $start = microtime(true);

        // Run loops.
        for ($i = 0; $i < $loops; $i++) {
            $result = file_fetch($key);
        }

        // Stop time-measurement and display result.
        $time_elapsed_secs = microtime(true) - $start;
        echo $time_elapsed_secs;
        return;
    }

    if ($type === 'redis') {
        // Prepare Redis.
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $key = 'performance-test_data-1';
        $result = file_fetch($key);
        $redis->set('performance-test_data-1', $result);
        $redis->set('performance-test_data-2', $result);

        // Start time-measurement.
        $start = microtime(true);

        // Run loops.
        for ($i = 0; $i < $loops; $i++) {
            $result = $redis->get('performance-test_'.$id);
        }

        // Stop time-measurement and display result.
        $time_elapsed_secs = microtime(true) - $start;
        echo $time_elapsed_secs;
        return;
    }

    if ($type === 'apcu') {
        // Prepare APCu.
        $key = 'performance-test_data-1';
        $result = file_fetch($key);
        apcu_add('performance-test_data-1', $result);
        apcu_add('performance-test_data-2', $result);

        // Start time-measurement.
        $start = microtime(true);

        // Run loops.
        for ($i = 0; $i < $loops; $i++) {
            $result = apcu_fetch('performance-test_'.$id);
        }

        // Stop time-measurement and display result.
        $time_elapsed_secs = microtime(true) - $start;
        echo $time_elapsed_secs;
        return;
    }
}

function write_data_via($type, $data, $counter) {
    switch ($type) {
        case 'sql':
            global $connection;
            $query = $connection->prepare('INSERT INTO data_cache (key, data) VALUES (?, ?);');
			$query->execute(array('example-write-test-'.$counter, $data));
            return;
        break;
        case 'file':
            $key = 'example-write-test-'.$counter;
            $result = file_store($key, $data);
            return;
        break;
        case 'redis':
            global $redis;
            $result = $redis->set('example-write-test-'.$counter, $data);
            return;
        break;
        case 'apcu':
            $result = apcu_add('example-write-test-'.$counter, $data);
            return;
        break;
    }
}
