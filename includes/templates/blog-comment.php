<?php
/**
 * BuddyPress Like - Blog Post Comment Button
 *
 * This function is used to display the BuddyPress Like button on blog post comments on the WordPress site.
 *
 * @package BuddyPress Like
 *
 */
/*
 * bplike_blog_post_comment_button()
 *
 * Outputs Like/Unlike button for blog post comments.
 *
 */
function bplike_blog_post_comment_button( $content ) {
    global $post;

    if ( ! is_user_logged_in() ) {
        return $content;
    }

    if ( is_admin() || !bp_like_get_settings('bp_like_post_types') ||
        !in_array($post->post_type, bp_like_get_settings('bp_like_post_types'))) {
        return $content;
    }

    $vars = bp_like_get_template_vars( get_comment_ID(), 'blog_post_comment' );
    extract( $vars );
    ob_start();

    if(bp_like_use_ajax()):
    ?>
    <br>
    <a class="blogpost_comment <?php echo $classes; ?>" id="bp-like-blogpost-comment-<?php echo get_comment_ID(); ?>" title="<?php echo $title; ?>" data-like-type="blog_post_comment">
        <span class="like-text"><?php echo bp_like_get_text( 'like' ); ?></span>
        <span class="unlike-text"><?php echo bp_like_get_text( 'unlike' ); ?></span>
        <span class="like-count"><?php echo ($liked_count?$liked_count:''); ?></span>
    </a>
    <br>
    <!-- not ajax -->
    <?php else: ?>
        <br>
        <a href="<?php echo $static_like_unlike_link; ?>"  class="blogpost_comment <?php echo $classes; ?>" id="bp-like-blogpost-comment-<?php echo get_comment_ID(); ?>" title="<?php echo $title; ?>" data-like-type="blog_post_comment">
            <span class="like-text"><?php echo bp_like_get_text( 'like' ); ?></span>
            <span class="unlike-text"><?php echo bp_like_get_text( 'unlike' ); ?></span>
            <span class="like-count"><?php echo ($liked_count?$liked_count:''); ?></span>
        </a>
        <br>
    <?php
    endif;

    bp_like_view_who_likes( get_comment_ID(), 'blog_post_comment', '<span', '</span>');

	$content .= ob_get_clean();

	return $content;
}
add_filter('get_comment_text', 'bplike_blog_post_comment_button');

