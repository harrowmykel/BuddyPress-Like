<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BP_LIKE_VERSION' ) ) {
    define( 'BP_LIKE_VERSION', '1.0.0' );
}

if ( ! defined( 'BP_LIKE_DB_VERSION' ) ) {
    define( 'BP_LIKE_DB_VERSION', '53' );
}

if ( ! defined( 'BPLIKE_PATH' ) ) {
    define( 'BPLIKE_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
}

// load translations
load_plugin_textdomain( 'buddypress-like', false,  basename( dirname( dirname( __FILE__ ) ) ) . '/languages/' );

/**
 * bp_like_get_text()
 *
 * Returns a custom text string from the database
 *
 */
function bp_like_get_text( $text = false ) {
    $settings = get_site_option( 'bp_like_settings' );
    $text_strings = $settings['text_strings'];

    if ( isset( $text_strings[$text] ) && ! empty( $text_strings[$text] ) )
      $string = $text_strings[$text];
    else
      $string = bp_like_get_default_text_strings( $text );

    return $string;
}

if ( is_admin() ) {
    require_once( BPLIKE_PATH . 'admin/admin.php' );
}
require_once( BPLIKE_PATH . 'includes/button-functions.php' );
require_once( BPLIKE_PATH . 'includes/templates/activity-update.php' );
require_once( BPLIKE_PATH . 'includes/templates/activity-comment.php' );
require_once( BPLIKE_PATH . 'includes/install-functions.php' );
require_once( BPLIKE_PATH . 'includes/activity-functions.php' );
require_once( BPLIKE_PATH . 'includes/ajax.php' );
require_once( BPLIKE_PATH . 'includes/no-ajax.php' );
require_once( BPLIKE_PATH . 'includes/like-functions.php' );
require_once( BPLIKE_PATH . 'includes/scripts.php' );
require_once( BPLIKE_PATH . 'includes/settings.php' );
require_once( BPLIKE_PATH . 'includes/class-bplike-likes.php' );
require_once( BPLIKE_PATH . 'includes/screens.php' );
require_once( BPLIKE_PATH . 'includes/template.php' );
require_once( BPLIKE_PATH . 'includes/shortcodes.php' );
require_once( BPLIKE_PATH . 'includes/bplike-likes-functions.php' );


if ( bp_like_get_settings( 'enable_blog_post_support' ) == 1 ) {
  require_once( BPLIKE_PATH . 'includes/templates/blog-post.php' );
  require_once( BPLIKE_PATH . 'includes/templates/blog-comment.php' );
  require_once( BPLIKE_PATH . 'includes/templates/bbp-reply.php' );
}

if ( bp_is_active( 'notifications' ) && bp_like_get_settings( 'enable_notifications' ) == 1 ) {
  require_once( BPLIKE_PATH . 'includes/notifications.php' );
}
