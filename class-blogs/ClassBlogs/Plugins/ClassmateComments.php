<?php

ClassBlogs::require_cb_file( 'BasePlugin.php' );

/**
 * A plugin that automatically approves any comments left by a logged-in user
 * with an account on any blog on the site.
 *
 * This makes it so that any comments left by logged-in students will
 * automatically appear on any other student's blog, which is done in the hopes
 * of reducing confusion for those new to blogging.
 *
 * @package ClassBlogs_Plugins
 * @subpackage ClassmateComments
 * @since 0.1
 */
class ClassBlogs_Plugins_ClassmateComments extends ClassBlogs_BasePlugin
{

	/**
	 * Registers the auto-approval comment hook.
	 */
	public function __construct()
	{
		add_action( 'wp_insert_comment', array( $this, '_approve_classmate_comments' ), 10, 2 );
	}

	/**
	 * Automatically approve any comments left by a classmate.
	 *
	 * @param int    $id      the database ID of the comment
	 * @param object $comment the saved comment object
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _approve_classmate_comments( $id, $comment )
	{
		if ( ! $comment->comment_approved ) {
			if ( $comment->user_id || get_user_by( 'email', $comment->comment_author_email ) ) {
				$comment->comment_approved = 1;
				wp_update_comment( (array) $comment );
			}
		}
	}
}

ClassBlogs::register_plugin(
	'classmate_comments',
	'ClassBlogs_Plugins_ClassmateComments',
	__( 'Classmate Comments', 'classblogs' ),
	__( "Automatically approves any comment left by a logged-in student on another student's blog.", 'classblogs' )
);

?>
