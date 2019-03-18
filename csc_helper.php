<?php

// A debug-output function.
function debug_output($object) {
    print_r('<pre style="font-size: 11px;">');
    print_r($object);
    print_r('</pre>');
}

// Installs time-test-data.
if (!empty($_GET['install_time_test_data']) && $_GET['install_time_test_data'] === '1') {
    // PDO Documentation: http://php.net/manual/de/pdo.connections.php

    // Get the first test-data.
    $query = $connection->prepare('SELECT * FROM data_storage WHERE id = 1;');
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    // Convert result-array into json.
    $json = json_encode($result);

    // Create cache-object.
    $data = array();
    $data['component']  = 'time-test';
    $data['query_id']   = '1';
    $data['type']       = 'data';
    $data['version']    = 1;
    $data['data']       = $json;

    // Save into cache-table.
    $statement = $connection->prepare("INSERT INTO data_cache (component, query_id, type, version, data) VALUES (:component, :query_id, :type, :version, :data);");
    $statement->execute($data);

    // Save into file.
    $key = $data['component'].'_'.$data['query_id'];
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
    $loops = 99999;
    $random = true;

    echo '<h1>Read '.$loops.' datasets</h1>';

    echo '<h2>Read from database</h2>';

    // Start time-measurement.
    $start = microtime(true);

    // Run loops.
    for ($i = 0; $i < $loops; $i++) {
        access_data_via('sql', $random);
    }

    // Stop time-measurement and display result.
    $time_elapsed_secs = microtime(true) - $start;
    echo $time_elapsed_secs;

    echo '<h2>Read from file</h2>';

    // Start time-measurement.
    $start = microtime(true);

    // Run loops.
    for ($i = 0; $i < $loops; $i++) {
        access_data_via('file', $random);
    }

    // Stop time-measurement and display result.
    $time_elapsed_secs = microtime(true) - $start;
    echo $time_elapsed_secs;


    // Redis download:
    // php extension: https://pecl.php.net/package/redis/4.2.0/windows
    // windows server: https://github.com/dmajkic/redis/downloads
    // tutorial: https://www.tutorialspoint.com/redis/redis_php.htm

    echo '<h2>Read from redis</h2>';

    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    $key = 'time-test_example-dataset-1';
    $result = file_fetch($key);

    // Save data into redis-server.
    $redis->set('time-test_example-dataset-1', $result);
    $redis->set('time-test_example-dataset-2', $result);

    // Start time-measurement.
    $start = microtime(true);

    // Run loops.
    for ($i = 0; $i < $loops; $i++) {
        access_data_via('redis', $random);
    }

    // Stop time-measurement and display result.
    $time_needed = microtime(true) - $start;
    echo $time_needed;






    // APCu
    // https://pecl.php.net/package/APCu/5.1.15/windows

    echo '<h2>Read from APCu</h2>';

    $key = 'time-test_example-dataset-1';
    $result = file_fetch($key);

    // Save data
    apcu_add('time-test_example-dataset-1', $result);
    apcu_add('time-test_example-dataset-2', $result);

    // Start time-measurement.
    $start = microtime(true);

    // Run loops.
    for ($i = 0; $i < $loops; $i++) {
        access_data_via('apcu', $random);
    }

    // Stop time-measurement and display result.
    $time_elapsed_secs = microtime(true) - $start;
    echo $time_elapsed_secs;
}

function access_data_via($type, $random = false) {
    $id = 'example-dataset-1';

    if ($random) {
        $id = 'example-dataset-'.rand(1,2);
    }

    if ($type === 'sql') {
        global $connection;
        $query = $connection->prepare('SELECT * FROM data_cache WHERE component = "time-test" AND query_id = "'.$id.'";');
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return;
    }

    if ($type === 'file') {
        $key = 'time-test_'.$id;
        $result = file_fetch($key);
        return;
    }

    if ($type === 'redis') {
        global $redis;
        $result = $redis->get('time-test_'.$id);
        return;
    }

    if ($type === 'apcu') {
        $result = apcu_fetch('time-test_'.$id);
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
