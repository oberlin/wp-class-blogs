<?php

/**
 * The base class for any plugin that is part of the class-blogs suite.
 *
 * This mainly provides descended classes with a framework to manage plugin
 * options, cached values, plugin identifiers and paths to static media files.
 *
 * In addition to these helper methods, this class also provides a lightweight
 * framework for handling common plugin actions.  Plugins may provide a value
 * for the `$default_options` attribute that provide their default options, and
 * they can also provide a value for `$admin_media` attribute, which holds
 * information on any CSS or JavaScript files used by their admin pages.  Lastly,
 * a plugin can define an `enable_admin_page` method that can run code to register
 * and enable an admin page when the user is on the admin side of their blog.
 *
 * An example, showing how to create a new plugin and use the features provided
 * by this base class, is as follows:
 *
 * class ClassBlogs_Plugins_Example extends ClassBlogs_Plugins_BasePlugin
 * {
 *     protected $default_options = array(
 *         'counter'    => 0,
 *         'is_example' => true
 *     );
 *
 *     protected $admin_media = array(
 *         'css' => array( 'styles.css' ),
 *         'js'  => array( 'script.js' )
 *     );
 *
 *     protected function enable_admin_page( $admin )
 *     {
 *         $admin->add_admin_page(
 *             $this->get_uid(),
 *             'Example Admin',
 *             array( $this, 'admin_page' )
 *         );
 *     }
 *
 *     public function admin_page()
 *     {
 *         // Code to render an admin page goes here...
 *     }
 *
 *     public function demonstrate()
 *     {
 *
 *         echo "The plugin's unique identifier is: " . $this->get_uid() . "\n";
 *
 *         // A site is created with three class blogs on it, with IDs of 1, 2, and 3.
 *         assert( count( $this->get_all_blog_ids() ) === 3 );
 *
 *         $options = $this->get_options();
 *         assert( count( $options ) === 2 );
 *         assert( $options['counter'] === 0 );
 *         assert( $options['is_example'] === true );
 *         assert( $this->get_option( 'counter' ) === $options['counter'] );
 *         assert( $this->option_is_empty( 'empty' ) === true );
 *
 *         $this->update_option( 'counter', 1 );
 *         assert( $this->get_option( 'counter' ) === 1 );
 *         $options['counter'] = 2;
 *         $this->update_options( $options );
 *         assert( $this->get_option( 'counter' ) === 2 );
 *
 *         switch_to_blog( 1 );
 *         assert( $this->get_cache( 'cached' ) === null );
 *         $this->set_cache( 'cached', true );
 *         $this->set_cache( 'other', true );
 *         assert( $this->get_cache( 'cached' ) === true );
 *         assert( $this->get_cache( 'other' ) === true );
 *         switch_to_blog( 2 );
 *         assert( $this->get_cache( 'cached' ) === null );
 *         switch_to_blog( 1 );
 *         $this->clear_cache( 'cached' );
 *         assert( $this->get_cache( 'cached' ) === null );
 *         assert( $this->get_cache( 'other' ) === true );
 *         $this->clear_cache();
 *         assert( $this->get_cache( 'other' ) === null );
 *
 *         switch_to_blog( 1 );
 *         assert( $this->get_site_cache( 'cached' ) === null );
 *         $this->set_site_cache( 'cached', true );
 *         $this->set_site_cache( 'other', true );
 *         assert( $this->get_site_cache( 'cached' ) === true );
 *         assert( $this->get_site_cache( 'other' ) === true );
 *         switch_to_blog( 2 );
 *         assert( $this->get_site_cache( 'cached' ) === true );
 *         $this->clear_site_cache( 'cached' );
 *         assert( $this->get_site_cache( 'cached' ) === null );
 *         assert( $this->get_site_cache( 'other' ) === true );
 *         $this->clear_site_cache();
 *         assert( $this->get_site_cache( 'other' ) === null );
 *     }
 * }
 *
 * $plugin = new ClassBlogs_Plugins_Example();
 * $plugin->demonstrate();
 *
 * @package ClassBlogs_Plugins
 * @subpackage BasePlugin
 * @since 0.1
 */
abstract class ClassBlogs_Plugins_BasePlugin
{

	/**
	 * The default options for the plugin.
	 *
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array();

	/**
	 * The internally stored options for the plugin.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private $_options;

	/**
	 * The name of the option key used to track class-blogs keys stored in
	 * a blog's individual cache.
	 *
	 * @access private
	 * @var string
	 * @since 0.2
	 */
	const _CACHE_TRACKER_OPTION = 'cb_cache_keys';

	/**
	 * The name of the option key used to track class-blogs keys stored in the
	 * sitewide cache.
	 *
	 * @access private
	 * @var string
	 * @since 0.2
	 */
	const _SW_CACHE_TRACKER_OPTION = 'cb_sw_cache_keys';

	/**
	 * The admin CSS or JavaScript used by the plugin.
	 *
	 * A plugin can add admin media by providing values for the `css` or `js`
	 * keys of the array, which should be assigned to arrays of strings, where
	 * each string is the name of the media file.  CSS files will be interpreted
	 * as relative to the CSS media directory (`media/css`) and JavaScript files
	 * will be viewed as relative to the JavaScript media directory (`media/js`).
	 *
	 * @access protected
	 * @var array
	 * @since 0.2
	 */
	protected $admin_media = array(
		'css' => array(),
		'js'  => array()
	);

	/**
	 * Initialize common admin options and provide a hook for plugins to
	 * perform further configuration options.
	 */
	public function __construct()
	{
		// Provide plugins with the option of enabling an admin page
		if ( is_admin() ) {
    		add_action( 'admin_footer', array( $this, '_add_admin_js' ) );
    		add_action( 'admin_head',   array( $this, '_add_admin_css' ) );
    		add_action( 'admin_menu',   array( $this, '_maybe_enable_admin_page' ) );
    	}
	}

	/**
	 * Generates a pseudo-unique ID for the plugin.
	 *
	 * This ID is automatically generated from a truncated and modified version
	 * of the current plugin's classname.
	 *
	 * @return string the plugin's pseudo-unique ID
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function get_uid()
	{
		return strtolower( str_replace( 'ClassBlogs_Plugins', 'cb', get_class( $this ) ) );
	}

	/**
	 * Returns a list of all blog IDs on the site.
	 *
	 * @return array a list of all blog IDs on the site
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function get_all_blog_ids()
	{
		global $wpdb;
		$blog_ids = array();

		$blogs = $wpdb->get_results( $wpdb->prepare( "
			SELECT blog_id FROM $wpdb->blogs
			WHERE site_id = %d AND archived = '0' AND deleted = '0'",
			$wpdb->siteid ) );
		foreach ( $blogs as $blog ) {
			$blog_ids[] = $blog->blog_id;
		}
		return $blog_ids;
	}

	/**
	 * Gets the options for the current plugin.
	 *
	 * If no options are found for the plugin, options are set using the values
	 * contained in the $default_options  variable.  If this variable is
	 * empty, it is assumed that no options are used for the plugin.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_options()
	{
		$options_id = $this->get_uid();
		$options = get_site_option( $options_id );
		if ( empty( $options ) ) {
			$options = $this->default_options;
			$this->update_options( $options );
		}
		$this->_options = $options;
	}

	/**
	 * Return the value of the requested plugin option.
	 *
	 * @param  string $name the name of the plugin option
	 * @return mixed        the value of the plugin option
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function get_option( $name )
	{
		if ( ! isset( $this->options ) && ! empty( $this->default_options ) ) {
			$this->_get_options();
		}
		if ( $this->_options && array_key_exists( $name, $this->_options ) ) {
			return $this->_options[$name];
		} else {
			return null;
		}
	}

	/**
	 * Returns true if the given options's value is empty.
	 *
	 * This will be true if the option has a value that counts as empty, or
	 * if the option is not set.
	 *
	 * @param  string $name the name of the option
	 * @return bool         whether the option's value is empty
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function option_is_empty( $name )
	{
		$value = $this->get_option( $name );
		return empty( $value );
	}

	/**
	 * Returns an array containing all the current plugin options.
	 *
	 * @return array a hash of the plugin's current options
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function get_options()
	{
		$this->_get_options();
		return $this->_options;
	}

	/**
	 * Updates the value of a single option.
	 *
	 * @param string $option the name of the plugin value to update
	 * @param mixed  $value  the new value of the plugin option
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function update_option( $option, $value )
	{
		$options = $this->get_options();
		$options[$option] = $value;
		$this->update_options( $options );
	}

	/**
	 * Updates the options used by a plugin.
	 *
	 * @param array $options an array of the plugin's options
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function update_options( $options )
	{
		update_site_option( $this->get_uid(), $options );
		$this->_get_options();
	}

	/**
	 * Adds a value to the cache using the function provided.
	 *
	 * This makes it easy for the other cache methods of this class to add a
	 * value to either the current blog's cache or the sitewide one.  Whenever
	 * a key is added to the cache, a record is kept of that to facilitate
	 * clearing any cached values from the cache later.
	 *
	 * If `WP_DEBUG` is true, this will never add a value to the cache.
	 *
	 * @param string $cache_fn   the name of the function to use to set the cache data
	 * @param string $tracker    the name of an option key to use to track the cache keys added
	 * @param string $key        the key under which to cache the data
	 * @param mixed  $value      the data to cache
	 * @param int    $expiration the expiry time for the value in seconds
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _set_cache( $cache_fn, $tracker, $key, $value, $expiration )
	{
		if ( WP_DEBUG ) {
			return;
		}

		// Add the key to the list of cached keys if it's not there
		$full_key = $this->_make_cache_key_name( $key );
		$keys = get_site_option( $tracker );
		if ( empty( $keys ) ) {
			$keys = array();
		}
		if ( ! array_key_exists( $full_key, $keys ) ) {
			$keys[$full_key] = true;
			update_site_option( $tracker, $keys );
		}

		// Cache the value using the provided function
		call_user_func( $cache_fn, $full_key, $value, $expiration );
	}

	/**
	 * Adds a value to the cache.
	 *
	 * This will never add a value to the cache if WP_DEBUG is true.
	 *
	 * @param string the key under which to cache the data
	 * @param mixed  the data to cache
	 * @param int    the optional number of seconds for which to cache the value
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function set_cache( $key, $value, $expiration = ClassBlogs_Settings::DEFAULT_CACHE_LENGTH )
	{
		$this->_set_cache(
			'set_transient',
			self::_CACHE_TRACKER_OPTION,
			$key, $value, $expiration );
	}

	/**
	 * Functions identically to `set_cache`, but uses the sitewide cache.
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function set_site_cache( $key, $value, $expiration = ClassBlogs_Settings::DEFAULT_CACHE_LENGTH )
	{
		$this->_set_cache(
			'set_site_transient',
			self::_SW_CACHE_TRACKER_OPTION,
			$key, $value, $expiration );
	}

	/**
	 * Retrives the request cache value from the given cache.
	 *
	 * This is a generalized function that makes it easier for the cache methods
	 * of this class to get a value from either the current blog's cache or
	 * the sitewide cache.
	 *
	 * If the cache value is not found, a null value is returned.  If WP_DEBUG
	 * is true, this will always return null.
	 *
	 * @param  string $cache_fn the name of the function to use to get the cached value
	 * @param  string $key      the cache key whose value should be retrieved
	 * @return mixed            the cached value or null
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _get_cache( $cache_fn, $key )
	{
		if ( WP_DEBUG ) {
			return null;
		}

		$cached = call_user_func( $cache_fn, $this->_make_cache_key_name( $key ) );
		if ( $cached === false ) {
			return null;
		} else {
			return $cached;
		}
	}

	/**
	 * Retrieves the requested cache value.
	 *
	 * If the cache value is not found, a null value is returned.  If WP_DEBUG
	 * is true, this will always return null.
	 *
	 * @param  string $key the cache key whose value should be retrieved
	 * @return mixed       the cached value or null
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function get_cache( $key )
	{
		return $this->_get_cache( 'get_transient', $key );
	}

	/**
	 * Functions identically to `get_cache`, but uses the site cache.
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function get_site_cache( $key )
	{
		return $this->_get_cache( 'get_site_transient', $key );
	}

	/**
	 * Clears a single cached value, or the entire cache.
	 *
	 * This is a generalized function that allows the cache methods of this plugin
	 * to easily remove values from either a single blog's cache or the sitewide
	 * cache.
	 *
	 * If no value is passed, all keys tracked in the given option key will
	 * be removed.
	 *
	 * @param string $cache_fn the name of the function to use to delete a cached value
	 * @param string $tracker  an option key that contains a list of cached keys
	 * @param string $key      the optional name of the cache key to clear
	 *
	 * @access private
	 * @since 0.2
	 */
	protected function _clear_cache( $cache_fn, $tracker, $key=null )
	{

		// If a key was passed, delete a single cached value.  If no key was
		// passed, get the list of cached keys stored in the option defined by
		// `$tracker` and remove each one of them.
		if ( $key ) {
			call_user_func( $cache_fn, $this->_make_cache_key_name( $key ) );
		} else {
			$cached_keys = get_site_option( $tracker );
			if ( ! empty( $cached_keys ) ) {
				foreach ( $cached_keys as $cached_key => $null ) {
					call_user_func( $cache_fn, $cached_key );
				}
				update_site_option( $tracker, array() );
			}
		}
	}

	/**
	 * Clears a single cached value.
	 *
	 * If no value is passed, all keys added by the class-blogs plugins to the
	 * cache will be removed.
	 *
	 * @param string $key the cache key to clear
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function clear_cache( $key=null )
	{
		$this->_clear_cache(
			'delete_transient',
			self::_CACHE_TRACKER_OPTION,
			$key );
	}

	/**
	 * Functions identically to `clear_cache`, but uses the site cache.  This
	 * means that if no key is passed, all values in the sitewide cache put there
	 * by class-blogs plugins will be removed.
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function clear_site_cache( $key=null )
	{
		$this->_clear_cache(
			'delete_site_transient',
			self::_SW_CACHE_TRACKER_OPTION,
			$key );
	}

	/**
	 * Generates the name of the cache key used to store data for the current plugin.
	 *
	 * @param  string $key the base name of the cache key
	 * @return string      the full name of the cache key
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _make_cache_key_name( $key ) {
		return $this->get_uid() . '_' . $key;
	}

	/**
	 * Registers a widget.
	 *
	 * @param object $widget the widget class
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function register_widget( $widget )
	{
		register_widget( $widget );
	}

	/**
	 * Registers a widget that should only be available on the root blog.
	 *
	 * This makes the widget only appear as a selection on the admin side if the
	 * user is an admin on the root blog and the root blog is being edited, but
	 * will show the widget to any user viewing the root blog.
	 *
	 * @param mixed  $widget the widget class
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function register_root_only_widget( $widget )
	{
		global $blog_id;
		if ( (int) $blog_id === ClassBlogs_Settings::get_root_blog_id() && ( ! is_admin() || $this->current_user_is_admin_on_root_blog() ) ) {
			$this->register_widget( $widget );
		}
	}

	/**
	 * Returns true if the current user is an administrator on the root blog.
	 *
	 * @return bool whether the current user is an admin on the root blog
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function current_user_is_admin_on_root_blog()
	{
		return current_user_can_for_blog(
			ClassBlogs_Settings::get_root_blog_id(),
			'administrator' );
	}

	/**
	 * Returns markup to make a checkbox or select box selected if it is true.
	 *
	 * @param  string $option the name of the option
	 * @return string         possible markup for a checked attribute
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function option_to_selected_attribute( $option )
	{
		return ( $this->get_option( $option ) ) ? 'checked="checked"' : "";
	}

	/**
	 * Enables a possible admin page associated with a child plugin.
	 *
	 * This simply provides a shortcut for any child plugins to register an admin
	 * page that is part of the class blogs menu group.  A child plugin can override
	 * the `enable_admin_page` method called by this to register an admin page.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _maybe_enable_admin_page()
	{
		if ( ClassBlogs_Utils::on_root_blog_admin() ) {
			$admin = ClassBlogs_Admin::get_admin();
			$this->enable_admin_page( $admin );
		}
	}

	/**
	 * Allows a child plugin to easily register an admin page visible to the
	 * superuser and any users with admin rights on the root blog.
	 *
	 * The child plugin that wishes to add a root admin page can override this
	 * function with code that registers the admin page using the ClassBlogs_Admin
	 * instance passed in `$admin`.
	 *
	 * @param object $admin a ClassBlogs_Admin instance
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function enable_admin_page( $admin ) {}

	/**
	 * Adds the plugin's admin CSS to the page.
	 *
	 * This uses the value of the `$admin_media` member variable to determine
	 * which admin files should be added.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _add_admin_css()
	{
		if ( array_key_exists( 'css', $this->admin_media ) ) {
			$css_url = esc_url( ClassBlogs_Utils::get_base_css_url() );
			foreach ( $this->admin_media['css'] as $css_file ) {
				printf( '<link rel="stylesheet" href="%s%s" />',
					$css_url, $css_file );
			}
		}
	}

	/**
	 * Adds the plugin's admin JavaScript to the page.
	 *
	 * This uses the value of the `$admin_media` member variable to determine
	 * which admin files should be added.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _add_admin_js()
	{
		if ( array_key_exists( 'js', $this->admin_media ) ) {
			$js_url = ClassBlogs_Utils::get_base_js_url();
			foreach ( $this->admin_media['js'] as $js_file ) {
				wp_register_script(
					$this->get_uid(),
					$js_url . $js_file,
					array( 'jquery' ),
					ClassBlogs_Settings::VERSION,
					true );
			}
			wp_print_scripts( $this->get_uid() );
		}
	}
}

?>
