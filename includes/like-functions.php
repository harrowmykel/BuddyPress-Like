<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * bp_like_is_liked()
 *
 * Checks to see whether the user has liked a given item.
 *
 */
function bp_like_is_liked( $item_id, $type, $user_id) {

    if ( ! $type || ! $item_id ) {
        return false;
    }

    if ( isset( $user_id ) ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
    }

	return BPLIKE_LIKES::item_is_liked($item_id, $type, $user_id);
}

/**
 * bp_like_add_user_like()
 *
 * Registers that the user likes a given item.
 *
 */
function bp_like_add_user_like( $item_id, $type ) {

    $liked_count = 0;

    if ( ! isset( $user_id ) ) {
        $user_id = get_current_user_id();
    }
    if ( ! $item_id || ! is_user_logged_in() ) {
        return false;
    }

    if ( BPLIKE_LIKES::get_user_like($item_id, $type, $user_id) )
        return false;

	$like = new BPLIKE_LIKES();
	$like->liker_id = $user_id;
	$like->item_id = $item_id;
	$like->like_type = $type;
	$like->date_created = current_time( 'mysql' );
	$like->save();

	$liked_count = count(  BPLIKE_LIKES::get_likers($item_id, $type) );

    if ( $type == 'activity_update' ) {
        $group_id = 0;

        // check if this item is in a group or not, assign group id if so
        if ( bp_is_active( 'groups' ) && bp_is_group() ) {
          $group_id = bp_get_current_group_id();
        }

        bp_like_post_to_stream( $item_id, $user_id, $group_id );

        do_action('bp_like_activity_update_add_like', $user_id, $item_id);

    } elseif ( $type == 'blog_post' ) {

        /* save total like count, so posts can be ordered by likes */
        update_post_meta( $item_id , 'bp_liked_count_total' , $liked_count );

        if ( bp_like_get_settings( 'post_to_activity_stream' ) == 1 ) {
            $post = get_post( $item_id );
            $author_id = $post->post_author;

            $liker = bp_core_get_userlink( $user_id );
            $permalink = get_permalink( $item_id );
            $title = $post->post_title;
            $author = bp_core_get_userlink( $post->post_author );

            if ( $user_id == $author_id ) {
                $action = bp_like_get_text( 'record_activity_likes_own_blogpost' );
            } elseif ( $user_id == 0 ) {
                $action = bp_like_get_text( 'record_activity_likes_a_blogpost' );
            } else {
                $action = bp_like_get_text( 'record_activity_likes_users_blogpost' );
            }

            /* Filter out the placeholders */
            $action = str_replace( '%user%', $liker, $action );
            $action = str_replace( '%permalink%', $permalink, $action );
            $action = str_replace( '%title%', $title, $action );
            $action = str_replace( '%author%', $author, $action );

            /* Grab the content and make it into an excerpt of 140 chars if we're allowed */
            if ( bp_like_get_settings( 'show_excerpt' ) == 1 ) {
                $content = $post->post_content;
                if ( strlen( $content ) > bp_like_get_settings( 'excerpt_length' ) ) {
                    $content = substr( $content, 0, bp_like_get_settings( 'excerpt_length' ) );
                    $content = $content . '...';
                }
            };

            bp_activity_add(
                    array(
                        'action' => $action,
                        'content' => $content,
                        'component' => 'bp-like',
                        'type' => 'blogpost_liked',
                        'user_id' => $user_id,
                        'item_id' => $item_id,
                        'primary_link' => $permalink
                    )
            );
        }

        do_action('bp_like_blog_post_add_like', $user_id, $item_id);
    } else {
		/* Do nothing special for now */
        do_action("bp_like_${type}_add_like", $user_id, $item_id);
    }

    ?>
    <span class="like-text"><?php echo bp_like_get_text( 'like' ); ?></span>
    <span class="unlike-text"><?php echo bp_like_get_text( 'unlike' ); ?></span>
    <span class="like-count"><?php echo $liked_count; ?></span><?php
}

/**
 * bp_like_remove_user_like()
 *
 * Registers that the user has unliked a given item.
 *
 */
function bp_like_remove_user_like( $item_id = '' , $type = '' ) {

    if ( ! $item_id ) {
        return false;
    }

    if ( ! isset( $user_id ) ) {

        $user_id = get_current_user_id();
    }

    if ( 0 == $user_id ) {
      // todo replace this with an internal wordpress string.
      // maybe use wp_die() here?
        __('Sorry, you must be logged in to like that.', 'buddypress-like');
        return false;
    }

	if ( $like = BPLIKE_LIKES::get_user_like($item_id, $type, $user_id) )
		$like->delete();

	$liked_count = count(  BPLIKE_LIKES::get_likers($item_id, $type) );

    if ( $type == 'activity_update' ) {

        if ( bp_is_group() ) {

            $bp = buddypress();
            $update_id = bp_activity_get_activity_id(
                array(
                  'user_id'           => $user_id,
                  'component'         => $bp->groups->id,
                  'type'              => 'activity_liked',
                  'item_id'           => bp_get_current_group_id(),
                  'secondary_item_id' => $item_id,
                )
            );

            if ( $update_id ) {
                bp_activity_delete(
                    array(
                       'id'                => $update_id,
                       'user_id'           => $user_id,
                       'secondary_item_id' => $item_id,
                       'type'              => 'activity_liked',
                       'component'         => $bp->groups->id,
                       'item_id'           => bp_get_current_group_id()
                    )
                );
            }

        } else {
            /* Remove the update on the users profile from when they liked the activity. */
            $update_id = bp_activity_get_activity_id(
                array(
                    'item_id' => $item_id,
                    'component' => 'bp-like',
                    'type' => 'activity_liked',
                    'user_id' => $user_id
                )
            );

            if ( $update_id ) {
                bp_activity_delete(
                        array(
                           'id' => $update_id,
                           'user_id' => $user_id
                        )
                );
            }
        }

    } elseif ( $type == 'activity_comment' ) {

        /* Do nothing special for now */

    } elseif ( $type == 'blog_post' ) {

        /* update total like count, so posts can be ordered by likes */
        update_post_meta( $item_id , 'bp_liked_count_total' , $liked_count );

        /* Remove the update on the users profile from when they liked the activity. */
        $update_id = bp_activity_get_activity_id(
                array(
                    'item_id' => $item_id,
                    'component' => 'bp-like',
                    'type' => 'blogpost_liked',
                    'user_id' => $user_id
                )
        );

        if ( $update_id ) {
            bp_activity_delete(
                array(
                    'id' => $update_id,
                    'item_id' => $item_id,
                    'component' => 'bp-like',
                    'type' => 'blogpost_liked',
                    'user_id' => $user_id
                )
            );
        }
    } elseif ( $type == 'blog_post_comment' ) {

       /* Do nothing special for now */
    }

    do_action("bp_like_remove_like", $user_id, $item_id);
    ?>
    <span class="like-text"><?php echo bp_like_get_text( 'like' ); ?></span>
    <span class="unlike-text"><?php echo bp_like_get_text( 'unlike' ); ?></span>
    <span class="like-count"><?php echo ($liked_count?$liked_count:''); ?></span><?php
}

/*
 * bp_like_get_some_likes()
 *
 * Description: Returns a defined number of likers, beginning with more recent.
 *
 */
function bp_like_get_some_likes( $id, $type, $start, $end) {

	$users_who_like = BPLIKE_LIKES::get_likers($id, $type);

    $string = $start . ' class="users-who-like" id="users-who-like-' . $id . '">';

    // if the current users likes the item
    if ( in_array( get_current_user_id(), $users_who_like ) ) {
        if ( count( $users_who_like ) == 0 ) {
          // if noone likes this, do nothing as nothing gets outputted

        } elseif ( count( $users_who_like ) == 1 ) {

            $string .= '<small>';
            $string .= bp_like_get_text( 'get_likes_only_liker' );
            $string .= '</small>';

        } elseif ( count( $users_who_like ) == 2 ) {

            // find where the current_user is in the array $users_who_like
            $key = array_search( get_current_user_id(), $users_who_like );

            // removing current user from $users_who_like
            array_splice( $users_who_like, $key, 1 );

            $one = bp_core_get_userlink( $users_who_like[0] );

            $string .= '<small>';
            $string .= bp_like_get_text( 'you_and_username_like_this' );
            $string .= '</small>';

            $string = sprintf( $string , $one );

        } elseif ( count( $users_who_like ) == 3 ) {

              $key = array_search( get_current_user_id(), $users_who_like );

              // removing current user from $users_who_like
              array_splice( $users_who_like, $key, 1 );

              $others = count ($users_who_like);
              $one = bp_core_get_userlink( $users_who_like[$others - 2] );
              $two = bp_core_get_userlink( $users_who_like[$others - 1] );

              $string .= '<small>';
              $string .= bp_like_get_text( 'you_and_two_usernames_like_this' );
              $string .= '</small>';

              $string = sprintf( $string , $one , $two );

        } elseif (  count( $users_who_like ) > 3 ) {

              $key = array_search( get_current_user_id(), $users_who_like );

              // removing current user from $users_who_like
              array_splice( $users_who_like, $key, 1 );

              $others = count ($users_who_like);

              // output last two people to like (2 at end of array)
              $one = bp_core_get_userlink( $users_who_like[$others - 2] );
              $two = bp_core_get_userlink( $users_who_like[$others - 1] );

              $others = $others - 2;
              $string .= '<small>';
              $string .= _n( 'You, %s, %s and %d other like this.', 'You, %s, %s and %d others like this.', $others, 'buddypress-like' );
              $string .= '</small>';

              $string = sprintf( $string , $one , $two , $others );
        }
    } else {

        if ( count( $users_who_like ) == 0 ) {
          // if noone likes this, do nothing as nothing gets outputted

        } elseif ( count( $users_who_like ) == 1 ) {

            $string .= '<small>';
            $string .= bp_like_get_text( 'one_likes_this' );
            $string .= '</small>';

            $one = bp_core_get_userlink( $users_who_like[0] );

            $string = sprintf($string, $one);

        } elseif ( count( $users_who_like ) == 2 ) {

            $one = bp_core_get_userlink( $users_who_like[0] );
            $two = bp_core_get_userlink( $users_who_like[1] );

            $string .= '<small>';
            $string .= bp_like_get_text( 'two_like_this' );
            $string .= '</small>';

            $string = sprintf( $string , $one, $two );

        } elseif ( count( $users_who_like ) == 3 ) {

              $one = bp_core_get_userlink( $users_who_like[0] );
              $two = bp_core_get_userlink( $users_who_like[1] );
              $three = bp_core_get_userlink( $users_who_like[2] );

              $string .= '<small>';
              $string .= bp_like_get_text( 'three_like_this' );
              $string .= '</small>';

              $string = sprintf( $string , $one , $two, $three );

        } elseif (  count( $users_who_like ) > 3 ) {

              $others = count ($users_who_like);

              // output last two people to like (3 at end of array)
              $one = bp_core_get_userlink( $users_who_like[ $others - 1] );
              $two = bp_core_get_userlink( $users_who_like[$others - 2] );
              $three = bp_core_get_userlink( $users_who_like[$others - 3] );

              $others = $others - 3;

              $string .= '<small>';
              $string .= _n('%s, %s, %s and %d other like this.', '%s, %s, %s and %d others like this.', $others, 'buddypress-like' );
              $string .= '</small>';

              $string = sprintf( $string , $one , $two , $three, $others );
              //error_log("Like: $string");
        }
    }

    echo $string;
}


/*
 * bp_like_get_some_likes()
 *
 * Description: Returns a defined number of likers, beginning with more recent.
 *
 */
function bp_like_get_template_vars( $id, $type ) {
  $vars = array();

  $is_liked = bp_like_is_liked( $id, $type, get_current_user_id() );
  $vars['classes']  = $is_liked?'unlike':'like';
  $vars['classes'] .= bp_like_get_settings('bp_like_toggle_button')?' toggle':'';
  $vars['liked_count'] = count(  BPLIKE_LIKES::get_likers( $id, $type) );
  $vars['title'] = bp_like_get_text( ( $is_liked?'unlike_this_item':'like_this_item' ) );

  return $vars;
}

/**
 *
 * view_who_likes() hook
 *
 */
function view_who_likes( $id,  $type, $start = '<p', $end = '</p>') {

    do_action( 'bp_like_before_view_who_likes' );

    do_action( 'view_who_likes', $id, $type, $start, $end );

    do_action( 'bp_like_after_view_who_likes' );

}

// TODO comment why this is here
add_action( 'view_who_likes' , 'bp_like_get_some_likes', 10, 4 );
