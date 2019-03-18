<?php

/*
  Plugin Name: Caching
  Plugin URI: https://www.asgaros.de
  Description: Extends WordPress with a client-server caching-framework.
  Version: 1.0.0
  Author: Thomas Belser
*/

if (!defined('ABSPATH')) exit;

include_once('csc/cache.php');

function client_cache_front_scripts() {
    $plugin_url = plugin_dir_url(__FILE__);
    wp_enqueue_script('client-cache-cookie', $plugin_url.'csc/libs_client/js.cookie.js', array('jquery'));
    wp_enqueue_script('client-cache', $plugin_url.'csc/cache.js', array('jquery'));
}
add_action('wp_enqueue_scripts', 'client_cache_front_scripts');






function cache_the_content($content) {
    $content = cacheable_view('wordpress', 'post-'.get_the_ID(), $content);

    return $content;
}
add_filter('the_content', 'cache_the_content');




class Cache {
    function __construct() {
        add_action('added_option', array($this, 'cache_added_option'), 10, 2);
        add_action('updated_option', array($this, 'cache_updated_option'), 10, 3);
        add_action('deleted_option', array($this, 'cache_deleted_option'), 10, 1);
    }

    function register_option_caching($option) {
        add_filter('pre_option_'.$option, array($this, 'fetch_get_option'), 10, 3);
        add_filter('default_option_'.$option, array($this, 'store_get_option'), 10, 2);
        add_filter('option_'.$option, array($this, 'store_get_option'), 10, 3);
    }

    function cache_added_option($option, $value) {
        cache_store('wordpress', 'option-'.$option, $value);
    }

    function cache_updated_option($option, $old_value, $value) {
        cache_store('wordpress', 'option-'.$option, $value);
    }

    function cache_deleted_option($option) {
        cache_delete('wordpress', 'option-'.$option);
    }

    function fetch_get_option($value, $option, $default) {
        $option = cache_fetch('wordpress', 'option-'.$option, true);

        if ($option !== false) {
            $value = $option;
        }

        return $value;
    }

    function store_get_option($value, $option, $passed_default = false) {
        cache_store('wordpress', 'option-'.$option, $value);

        return $value;
    }
}

$cache = new Cache();

$cache->register_option_caching('WPLANG');
$cache->register_option_caching('can_compress_scripts');
$cache->register_option_caching('theme_switched');
