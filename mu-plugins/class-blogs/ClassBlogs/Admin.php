<?php

/**
 * A class to handle shared administrative class blogs functions
 *
 * This mainly provides an interface that lets plugins that are part of
 * the class blogs suite add a network-admin submenu, using the add_admin_page()
 * method.
 *
 * To access the admin interface, you will need to get a reference to an
 * instance of it by calling the ClassBlogs_Admin::get_admin() static method.
 * Trying to create an instance using the constructor will not work.
 *
 * @package ClassBlogs
 * @since 0.1
 */
class ClassBlogs_Admin
{

	/**
	 * The ID used for the class blogs admin menu
	 *
	 * @access private
	 * @var string
	 */
	const _MENU_ID = "class-blogs";

	/**
	 * The capability required for a user to see the admin menu
	 *
	 * @access private
	 * @var string
	 */
	const _MENU_CAPABILITY = "manage_sites";

	/**
	 * An instance of the class, used to keep it a singleton
	 *
	 * @access private
	 * @var object
	 */
	private static $_instance;

	/**
	 * A mapping of plugin UIDs to their admin page IDs
	 *
	 * @access private
	 * @var array
	 */
	private $_page_ids;

	/**
	 * Registers admin hooks
	 *
	 * @access private
	 */
	private function __construct()
	{
		if ( is_admin() ) {
			add_action( 'network_admin_menu', array( $this, 'configure_admin_interface' ) );
		}
	}

	/**
	 * Return an instance of the admin class, instantiating one if it doesnt' exist
	 *
	 * @return object an instance of a ClassBlogs_Admin class
	 *
	 * @since 0.1
	 */
	public function get_admin()
	{
		if ( ! isset(self::$_instance ) ) {
			self::$_instance = new ClassBlogs_Admin();
		}
		return self::$_instance;
	}

	/**
	 * Creates the base class blogs admin menu on the network admin page
	 *
	 * @since 0.1
	 */
	public function configure_admin_interface()
	{
		if ( is_super_admin() ) {
			$page = add_menu_page(
				__( 'Class Blogs', 'classblogs' ),
				__( 'Class Blogs', 'classblogs' ),
				self::_MENU_CAPABILITY,
				self::_MENU_ID,
				array( $this, 'class_blogs_admin_page' ) );
		}
	}

	/**
	 * Handles the display of the class blogs base admin page
	 *
	 * @since 0.1
	 */
	public function class_blogs_admin_page()
	{
?>
		<div class="wrap">
			<h2><?php _e( 'Class Blogs', 'classblogs' ); ?></h2>

			<p>
				<?php _e(
					'The class blogs plugin suite will help you manage a blog for a class where you have control over the main blog and each student has full ownership of a child blog.', 'classblogs' );
				?>
			</p>
			<p>
				<?php _e(
					'The plugins that are part of this suite are provided in the list below.  Not every plugin has configurable options, but the ones that do should appear as links in the admin menu in the lower left.', 'classblogs' )
				?>
			</p>

			<h3><?php _e( 'Plugins', 'classblogs' ); ?></h3>

			<h4><?php _e( 'Classmate Comments', 'classblogs' ); ?></h4>
			<p><?php _e( "Automatically approves any comment left by a logged-in student on another student's blog.", 'classblogs' ); ?></p>

			<h4><?php _e( 'Disable Comments', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a network-admin option to disable commenting on all blogs used by this class.', 'classblogs' ); ?></p>

			<h4><?php _e( 'New User Configuration', 'classblogs' ); ?></h4>
			<p><?php _e( 'Creates a first and last name for a newly added user based on their email address.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Random Image', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget that displays a randomly selected image chosen from all the images used on all blogs that are part of this class.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Sitewide Comments', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget that shows recent comments left on all student blogs.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Sitewide Posts', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget that shows recent posts made on all student blogs and allows for displaying all recent sitewide posts on the main blog.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Sitewide Tags', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget sitewide tag cloud widget, and allows all usages of a single tag on all student blogs to be viewed.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Student Blog Links', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a network-admin option that allows you to add links of your choosing as the first sidebar widget on all student blogs.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Student Blog List', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget that shows a list of all student blogs that are part of this class.', 'classblogs' ); ?></p>

			<h4><?php _e( 'YouTube Class Playlist', 'classblogs' ); ?></h4>
			<p><?php _e( 'Allows you to link a YouTube playlist with this blog that is automatically updated whenever students embed YouTube videos in a post.', 'classblogs' ); ?></p>

		</div>
<?php
	}

	/**
	 * Adds a new page to the class blogs admin group
	 *
	 * @param  string $uid   the calling plugin's unique identifier
	 * @param  string $title the title of the admin page
	 * @param  object $view  a reference to the view that handles the page
	 * @return string        the page ID of the page created
	 *
	 * @since 0.1
	 */
	public function add_admin_page( $uid, $title, $view_function )
	{
		$page_id = strtolower( sanitize_title_with_dashes( $title ) );
		$this->_page_ids[$uid] = $page_id;
		add_submenu_page(
			self::_MENU_ID,
			$title,
			$title,
			self::_MENU_CAPABILITY,
			$page_id,
			$view_function );
		return $page_id;
	}

	/**
	 * Returns the URL for the admin page registered with the given UID
	 *
	 * @param  string $uid the calling plugin's unique identifier
	 * @return string      the admin page's URL
	 */
	public function get_page_url( $uid )
	{
		$site = get_current_site();
		return sprintf( 'http://%s%swp-admin/network/admin.php?page=%s',
			$site->domain,
			$site->path,
			$this->_page_ids[ $uid ]
		);
	}
}

$admin = ClassBlogs_Admin::get_admin();

?>