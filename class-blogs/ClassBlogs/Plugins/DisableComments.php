<?php

ClassBlogs::require_cb_file( 'Admin.php' );
ClassBlogs::require_cb_file( 'BasePlugin.php' );

/**
 * A plugin that can disable commenting on every post across the entire site.
 *
 * This provides an admin menu option available only to a professor on the admin
 * side of the root blog that allows them to disable commenting on all blogs
 * that are part of the class blog.  This is generally used at the end of the
 * semester to put the blog into a very light lockdown mode.
 *
 * @package ClassBlogs_Plugins
 * @subpackage DisableComments
 * @since 0.1
 */
class ClassBlogs_Plugins_DisableComments extends ClassBlogs_BasePlugin
{
	/**
	 * Default options for the plugin.
	 *
	 * @access protected
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array(
		'comments_disabled' => false
	);

	/**
	 * Register hooks to preventing commenting.
	 */
	function __construct() {
		parent::__construct();

		// Apply filters to disable comments on any pages or posts on the site
		if ( $this->get_option( 'comments_disabled' ) ) {
			add_filter( 'comments_open', array( $this, '_always_show_comments_as_closed' ), 10, 2 );
			if ( ! is_admin() ) {
				add_filter( 'the_posts', array( $this, '_close_comment_status_on_posts' ) );
			}
		}
	}

	/**
	 * Makes comments always appear to be off for any item on the blog.
	 *
	 * @param  bool $is_open whether the comment is open
	 * @param  int  $post_id the ID of the post or page
	 * @return bool          false, to flag comments as closed
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _always_show_comments_as_closed( $is_open, $post_id )
	{
		return false;
	}

	/**
	 * Makes the comment status of any post always be closed.
	 *
	 * @param  array $posts the current list of posts
	 * @return array        the posts with their comment status set to closed
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _close_comment_status_on_posts( $posts )
	{
		foreach ( $posts as $post ) {
			$post->comment_status = 'closed';
		}
		return $posts;
	}

	/**
	 * Configures the plugin's admin page.
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function enable_admin_page( $admin )
	{
		$admin->add_admin_page( $this->get_uid(), __( 'Disable Commenting', 'classblogs' ), array( $this, '_admin_page' ) );
	}

	/**
	 * Displays the plugin's admin page.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _admin_page()
	{

		// Change the state of sitewide commenting if switching
		if ( $_POST ) {
			check_admin_referer( $this->get_uid() );
			$this->update_option( 'comments_disabled', $_POST['comment_status'] === 'disabled' );
			ClassBlogs_Admin::show_admin_message( __( 'Your sitewide commenting options have been updated', 'classblogs' ) );
		}
?>
		<div class="wrap">

			<?php ClassBlogs_Admin::show_admin_icon();  ?>
			<h2><?php _e( 'Disable Commenting', 'classblogs' ); ?></h2>

			<p>
				<?php _e( 'This page allows you to disable commenting on every blog on this site, which includes both the root blog and the student blogs.  Any new posts will have commenting disabled by default, and all existing posts will not be able to receive comments.', 'classblogs' ); ?>
			</p>

			<form method="post" action="">

					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Commenting on All Blogs is', 'classblogs' ); ?></th>
							<td>
								<input type="radio" name="comment_status" value="enabled" id="comments-enabled" <?php if ( ! $this->get_option( 'comments_disabled' ) ): ?>checked="checked"<?php endif; ?> />
								<label for="comments-enabled"><?php _e( 'Enabled', 'classblogs' ); ?></label>
								<input type="radio" name="comment_status" value="disabled" id="comments-disabled" <?php if ( $this->get_option( 'comments_disabled' ) ): ?>checked="checked"<?php endif; ?> />
								<label for="comments-disabled"><?php _e( 'Disabled', 'classblogs' ); ?></label>
							</td>
						</tr>
					</table>

				<?php wp_nonce_field( $this->get_uid() ); ?>
				<p class="submit"><input type="submit" class="button-primary" name="Submit" value="<?php _e( 'Update Commenting Status', 'classblogs' ); ?>" /></p>
			</form>
		</div>
<?php
	}
}

ClassBlogs::register_plugin(
	'disable_comments',
	'ClassBlogs_Plugins_DisableComments',
	__( 'Disable Comments', 'classblogs' ),
	__( 'Provides you with the option of disabling commenting on all current and future posts published by you and your students.', 'classblogs' )
);

?>
