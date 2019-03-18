<?php

/*
Plugin Name: Caching Drop-In
*/

include_once(ABSPATH.'wp-content/plugins/caching/csc/cache.php');

// Adds data to the cache, if the cache key doesn't already exist.
function wp_cache_add($key, $data, $group = '', $expire = 0) {
	global $wp_object_cache;
	return $wp_object_cache->add($key, $data, $group, (int)$expire);
}

// Closes the cache.
function wp_cache_close() {
	return true;
}

// Decrements numeric cache item's value.
function wp_cache_decr($key, $offset = 1, $group = '') {
	global $wp_object_cache;
	return $wp_object_cache->decr($key, $offset, $group);
}

// Removes the cache contents matching key and group.
function wp_cache_delete($key, $group = '') {
	global $wp_object_cache;
	return $wp_object_cache->delete($key, $group);
}

// Removes all cache items.
function wp_cache_flush() {
	global $wp_object_cache;
	return $wp_object_cache->flush();
}

// Retrieves the cache contents from the cache by key and group.
function wp_cache_get($key, $group = '', $force = false, &$found = null) {
	global $wp_object_cache;
	return $wp_object_cache->get($key, $group, $force, $found);
}

// Increment numeric cache item's value
function wp_cache_incr($key, $offset = 1, $group = '') {
	global $wp_object_cache;
	return $wp_object_cache->incr($key, $offset, $group);
}

// Sets up Object Cache Global and assigns it.
function wp_cache_init() {
	$GLOBALS['wp_object_cache'] = new WP_Object_Cache();
}

// Replaces the contents of the cache with new data.
function wp_cache_replace($key, $data, $group = '', $expire = 0) {
	global $wp_object_cache;
	return $wp_object_cache->replace($key, $data, $group, (int)$expire);
}

// Saves the data to the cache.
function wp_cache_set($key, $data, $group = '', $expire = 0) {
	global $wp_object_cache;
	return $wp_object_cache->set($key, $data, $group, (int)$expire);
}

// Switches the internal blog ID.
function wp_cache_switch_to_blog($blog_id) {
	global $wp_object_cache;
	$wp_object_cache->switch_to_blog($blog_id);
}

// Adds a group or set of groups to the list of global groups.
function wp_cache_add_global_groups($groups) {
	global $wp_object_cache;
	$wp_object_cache->add_global_groups($groups);
}

function wp_cache_add_non_persistent_groups($groups) {
	// Default cache doesn't persist so nothing to do here.
}

function wp_cache_reset() {
	_deprecated_function(__FUNCTION__, '3.5.0', 'WP_Object_Cache::reset()');
	global $wp_object_cache;
	$wp_object_cache->reset();
}

class WP_Object_Cache {
	public $cache_hits = 0;
	public $cache_misses = 0;
	protected $global_groups = array();
	private $blog_prefix;
	private $multisite;

	public function __get($name) {
		return $this->$name;
	}

	public function __set($name, $value) {
		return $this->$name = $value;
	}

	public function __isset($name) {
		return isset($this->$name);
	}

	public function __unset($name) {
		unset($this->$name);
	}

	// Adds data to the cache if it doesn't already exist.
	public function add($key, $data, $group = 'default', $expire = 0) {
		if (wp_suspend_cache_addition()) {
			return false;
		}

		if (empty($group)) {
			$group = 'default';
		}

		$id = $key;

		if ($this->multisite && !isset($this->global_groups[$group])) {
			$id = $this->blog_prefix.$key;
		}

		if ($this->_exists($id, $group)) {
			return false;
		}

		return $this->set($key, $data, $group, (int)$expire);
	}

	// Sets the list of global cache groups.
	public function add_global_groups($groups) {
		$groups = (array)$groups;
		$groups = array_fill_keys($groups, true);
		$this->global_groups = array_merge($this->global_groups, $groups);
	}

	// Decrements numeric cache item's value.
	public function decr($key, $offset = 1, $group = 'default') {
		if (empty($group)) {
			$group = 'default';
		}

		if ($this->multisite && !isset($this->global_groups[$group])) {
			$key = $this->blog_prefix.$key;
		}

		if (!$this->_exists($key, $group)) {
			return false;
		}

		if (!is_numeric(cache_fetch($group, $key, true))) {
			cache_store($group, $key, 0);
		}

		$offset = (int)$offset;

		cache_store($group, $key, cache_fetch($group, $key, true) - $offset);

		if (cache_fetch($group, $key, true) < 0) {
			cache_store($group, $key, 0);
		}

		return cache_fetch($group, $key, true);
	}

	// Removes the contents of the cache key in the group.
	public function delete($key, $group = 'default', $deprecated = false) {
		if (empty($group)) {
			$group = 'default';
		}

		if ($this->multisite && !isset($this->global_groups[$group])) {
			$key = $this->blog_prefix.$key;
		}

		if (!$this->_exists($key, $group)) {
			return false;
		}

		cache_delete($group, $key);
		return true;
	}

	// Clears the object cache of all data.
	public function flush() {
		cache_flush();
		return true;
	}

	/**
	 * Retrieves the cache contents, if it exists.
	 *
	 * The contents will be first attempted to be retrieved by searching by the
	 * key in the cache group. If the cache is hit (success) then the contents
	 * are returned.
	 *
	 * On failure, the number of cache misses will be incremented.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $key    What the contents in the cache are called.
	 * @param string     $group  Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool       $force  Optional. Unused. Whether to force a refetch rather than relying on the local
	 *                           cache. Default false.
	 * @param bool       $found  Optional. Whether the key was found in the cache (passed by reference).
	 *                           Disambiguates a return of false, a storable value. Default null.
	 * @return false|mixed False on failure to retrieve contents or the cache contents on success.
	 */
	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
			$key = $this->blog_prefix . $key;
		}

		if ( $this->_exists( $key, $group ) ) {
			$found             = true;
			$this->cache_hits += 1;
			if ( is_object(cache_fetch($group, $key, true) ) ) {
				return clone cache_fetch($group, $key, true);
			} else {
				return cache_fetch($group, $key, true);
			}
		}

		$found               = false;
		$this->cache_misses += 1;
		return false;
	}

	/**
	 * Increments numeric cache item's value.
	 *
	 * @since 3.3.0
	 *
	 * @param int|string $key    The cache key to increment
	 * @param int        $offset Optional. The amount by which to increment the item's value. Default 1.
	 * @param string     $group  Optional. The group the key is in. Default 'default'.
	 * @return false|int False on failure, the item's new value on success.
	 */
	public function incr( $key, $offset = 1, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
			$key = $this->blog_prefix . $key;
		}

		if ( ! $this->_exists( $key, $group ) ) {
			return false;
		}

		if ( ! is_numeric( cache_fetch($group, $key, true) ) ) {
			cache_store($group, $key, 0);
		}

		$offset = (int) $offset;

		cache_store($group, $key, cache_fetch($group, $key, true) + $offset);

		if ( cache_fetch($group, $key, true) < 0 ) {
			cache_store($group, $key, 0);
		}

		return cache_fetch($group, $key, true);
	}

	/**
	 * Replaces the contents in the cache, if contents already exist.
	 *
	 * @since 2.0.0
	 *
	 * @see WP_Object_Cache::set()
	 *
	 * @param int|string $key    What to call the contents in the cache.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int        $expire Optional. When to expire the cache contents. Default 0 (no expiration).
	 * @return bool False if not exists, true if contents were replaced.
	 */
	public function replace( $key, $data, $group = 'default', $expire = 0 ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$id = $key;
		if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
			$id = $this->blog_prefix . $key;
		}

		if ( ! $this->_exists( $id, $group ) ) {
			return false;
		}

		return $this->set( $key, $data, $group, (int) $expire );
	}

	/**
	 * Resets cache keys.
	 *
	 * @since 3.0.0
	 *
	 * @deprecated 3.5.0 Use switch_to_blog()
	 * @see switch_to_blog()
	 */
	public function reset() {
		_deprecated_function( __FUNCTION__, '3.5.0', 'switch_to_blog()' );

		// Clear out non-global caches since the blog ID has changed.
		foreach ( array_keys( $this->cache ) as $group ) {
			if ( ! isset( $this->global_groups[ $group ] ) ) {
				unset( $this->cache[ $group ] );
			}
		}
	}

	public function set($key, $data, $group = 'default', $expire = 0) {
		if (empty($group)) {
			$group = 'default';
		}

		if ($this->multisite && !isset($this->global_groups[$group])) {
			$key = $this->blog_prefix.$key;
		}

		if (is_object($data)) {
			$data = clone $data;
		}

		cache_store($group, $key, $data);
		return true;
	}

	public function stats() {
		echo '<p>';
		echo "<strong>Cache Hits:</strong> {$this->cache_hits}<br />";
		echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
		echo '</p>';
	}

	// Switches the internal blog ID.
	public function switch_to_blog($blog_id) {
		$blog_id = (int)$blog_id;
		$this->blog_prefix = $this->multisite ? $blog_id.':' : '';
	}

	protected function _exists($key, $group) {
		if (cache_fetch($group, $key) !== false) {
			return true;
		} else {
			return false;
		}
	}

	public function __construct() {
		$this->multisite = is_multisite();
		$this->blog_prefix = $this->multisite ? get_current_blog_id().':' : '';
        register_shutdown_function(array($this, '__destruct'));
	}

	public function __destruct() {
		return true;
	}
}
