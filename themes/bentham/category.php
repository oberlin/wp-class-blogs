<?php get_header(); ?>
<?php get_sidebar(); ?>

<div id="content">

	<?php
		bentham_show_archive_page_title(
			__( 'Category', 'bentham' ),
			single_cat_title( '', false ) );
		get_template_part( 'loop', 'category' );
	?>

</div>

<?php get_footer(); ?>
