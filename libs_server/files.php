<?php

function file_store($key, $data) {
    // Set and create folder.
    $folder = getcwd().'\\file-cache\\';

    if (!is_dir($folder)) {
        mkdir($folder);
    }

    // Build path.
    $path = $folder.$key;

    // Create binding for the file first.
    $binding = @fopen($path, 'wb');

    // Check if the binding could get created.
    if ($binding) {
        // Convert data into JSON.
        $data = json_encode($data);

        // Write the content to the file.
        $writing = @fwrite($binding, $data);

        // Close the file.
        fclose($binding);

        // Clear statcache so we can set the correct permissions.
        clearstatcache();

        // Get information about the file.
        $file_stats = @stat(dirname($path));

        // Update the permissions of the file.
        $file_permissions = $file_stats['mode'] & 0007777;
        $file_permissions = $file_permissions & 0000666;
        @chmod($path, $file_permissions);

        // Clear statcache again so PHP is aware of the new permissions.
        clearstatcache();

        return true;
    }

    return false;
}

function file_fetch($key) {
    $path = getcwd().'\\file-cache\\'.$key;

    // Create binding for the file first.
    $binding = @fopen($path, 'r');

    // Check if the binding could get created.
    if ($binding) {
        // Ensure that the file is not empty.
        $file_size = @filesize($path);

        if (isset($file_size) && $file_size > 0) {
            // Read the complete file.
            $file_data = fread($binding, $file_size);
            $file_data = json_decode($file_data);

            // Close the file.
            fclose($binding);

            // Return the file data.
            return $file_data;
        }
    }

    return false;
}

function file_delete($key) {
    $path = getcwd().'\\file-cache\\'.$key;

    if (file_exists($path)) {
        unlink($path);

        return true;
    }

    return false;
}

function file_delete_all() {
    $path = getcwd().'\\file-cache';
    recursive_delete($path);
    return true;
}

function recursive_delete($str) {
    if (is_file($str)) {
        return @unlink($str);
    } else if (is_dir($str)) {
        $scan = glob(rtrim($str, '/').'/*');

        foreach ($scan as $path) {
            recursive_delete($path);
        }

        return @rmdir($str);
    }
}
