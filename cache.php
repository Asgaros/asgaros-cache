<?php

require('libs_server/files.php');

function cache_store($component, $identifier, $data) {
    // Get timestamp.
    $version = time();

    // Build unique key.
    $key = $component.'_'.$identifier;

    // Build the object.
    $object = new stdClass();
    $object->component = $component;
    $object->identifier = $identifier;
    $object->version = $version;
    $object->data = $data;

    if (extension_loaded('apcu')) {
        apcu_store($key, $object);
    } else {
        file_store($key, $object);
    }

    // Return cached object.
    return $object;
}

function cache_fetch($component, $identifier, $data_only = false) {
    // Build unique key.
    $key = $component.'_'.$identifier;

    $object = false;

    if (extension_loaded('apcu')) {
        $object = apcu_fetch($key);
    } else {
        $object = file_fetch($key);
    }

    if (!$object) {
        return false;
    } else if ($data_only) {
        return $object->data;
    } else {
        return $object;
    }
}

function cache_delete($component, $identifier) {
    $key = $component.'_'.$identifier;

    if (extension_loaded('apcu')) {
        $object = apcu_delete($key);
    } else {
        $object = file_delete($key);
    }
}

function cache_check($component, $identifier, $timestamp) {
    $object = cache_fetch($component, $identifier);

    if (!$object) {
        // Data does not exists in cache anymore.
        return false;
    } else if ($object->version != $timestamp) {
        // Cached data is newer.
        return false;
    } else {
        // Given data is up-to-date.
        return true;
    }
}

function cache_flush() {
    if (extension_loaded('apcu')) {
        apcu_clear_cache();
    } else {
        file_delete_all();
    }
}

function cacheable_endpoint($component, $identifier, $callback, $return = false) {
    // Check that required parameters are not empty.
    if (empty($_GET['component']) || empty($_GET['identifier'])) {
        return;
    }

    // Check that required parameters belong to the endpoint.
    if ($_GET['component'] !== $component || $_GET['identifier'] !== $identifier) {
        return;
    }

    // Set the version.
    $version = 0;

    if (!empty($_GET['version']) && is_numeric($_GET['version']) && abs($_GET['version']) > 0) {
        $version = abs($_GET['version']);
    }

    // Get data from cache or use callback-function.
    $data = cache_fetch($component, $identifier);

    if ($data === false) {
        if (is_callable($callback)) {
            $data = $callback();
        } else {
            $data = $callback;
        }

        $data = cache_store($component, $identifier, $data);
    }

    // Build return-value based on version.
    if (cache_check($component, $identifier, $version)) {
        $data = 'up-to-date';
    } else {
        $data = json_encode($data);
    }

    if ($return === true) {
        return $data;
    } else {
        echo $data;
    }
}

function cacheable_view($component, $identifier, $callback) {
    $view = cache_fetch($component, $identifier);

    if ($view === false) {
        if (is_callable($callback)) {
            $view = $callback();
        } else {
            $view = $callback;
        }

        $view = cache_store($component, $identifier, $view);
    }

    $output = '<div data-type="cacheable" data-component="'.$component.'" data-identifier="'.$identifier.'" data-version="'.$view->version.'">';

    if (view_is_announced($component, $identifier, $view->version) === false) {
        $output .= $view->data;
    }

    $output .= '</div>';

    return $output;
}

function view_is_announced($component, $identifier, $version) {
    if (isset($_COOKIE['cached_views'])) {
        $key = $component.'-'.$identifier;
        $cached_views = json_decode(stripslashes($_COOKIE['cached_views']));

        if (property_exists($cached_views, $key) && $cached_views->$key->version == $version) {
            return true;
        }
    }

    return false;
}
