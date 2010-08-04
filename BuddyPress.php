<?php
/*
 * BuddyPress-specific actions/filters go here.
 */



/*
 * Add a Facebook Login button to the Buddypress sidebar login widget
 * NOTE: If you use this, you mustn't also use the built-in widget - just one or the other!
 */
add_action( 'bp_after_sidebar_login_form', 'jfb_bp_add_fb_login_button' );
function jfb_bp_add_fb_login_button()
{
  if ( !is_user_logged_in() )
  {
      echo "<p></p>";
      jfb_output_facebook_btn();
      jfb_output_facebook_init();
      jfb_output_facebook_callback();
  }
}



/*
 * Modify the userdata for BuddyPress by changing login names from the default FB_xxxxxx
 * to something prettier for BP's social link system
 */
add_filter( 'wpfb_insert_user', 'jfp_bp_modify_userdata', 10, 2 );
function jfp_bp_modify_userdata( $wp_userdata, $fb_userdata )
{
    $counter = 1;
    $name = str_replace( ' ', '', $fb_userdata['first_name'] . $fb_userdata['last_name'] );
    if ( username_exists( $name ) )
    {
        do
        {
            $username = $name;
            $counter++;
            $username = $username . $counter;
        } while ( username_exists( $username ) );
    }
    else
    {
        $username = $name;
    }
    $username = strtolower( sanitize_user($username) );

    $wp_userdata['user_login']   = $username;
    $wp_userdata['user_nicename']= $username;
    return $wp_userdata;
}



/**********************************************************************/
/***********Below here is Avatar Code, added in 1.2.0******************/
/**********************************************************************/


/**
  * Every time a user connects, ask facebook for their current profile pic and store it in usermeta
  * (that way if they update their pic on facebook, it gets updated locally whenever they login)
  */
add_action('wpfb_login', 'jfb_bp_fetch_profile_pic');
function jfb_bp_fetch_profile_pic($args)
{
  //Get the user's small and large profile pics, and store them in usermeta.
  //This will be referenced by a bp_core_fetch_avatar filter.
  $client    = $args['facebook']->api_client;
  $result    = $client->users_getInfo($args['FB_ID'], array('pic_square', 'pic_big'));
  if( !is_array($result) ) j_die("ERROR: Failed to get profile picture from Facebook!");
  update_usermeta($args['WP_ID'], 'facebook_avatar_thumb', $result[0]['pic_square']);
  update_usermeta($args['WP_ID'], 'facebook_avatar_full', $result[0]['pic_big']);
}


/** When BP asks for the avatar, provide the one we've fetched from Facebook, if available.
  * Otherwise, resort to the default behavior. 
  */
add_filter( 'bp_core_fetch_avatar', 'jfb_get_facebook_avatar', 10, 4 );
function jfb_get_facebook_avatar($avatar, $params='')
{
    //First, get the userid
	global $comment;
	if (is_object($comment))	$user_id = $comment->user_id;
	if (is_object($params)) 	$user_id = $params->user_id;
	if (is_array($params))
	{
		if ($params['object']=='user')
			$user_id = $params['item_id'];
	}

	//Then see if we have a Facebook avatar for that user
	if( $params['type'] == 'full' && get_usermeta($user_id, 'facebook_avatar_full'))
		return '<img alt="avatar" src="' . get_usermeta($user_id, 'facebook_avatar_full') . '" class="avatar" />';
    else if( get_usermeta($user_id, 'facebook_avatar_thumb') )
	    return '<img alt="avatar" src="' . get_usermeta($user_id, 'facebook_avatar_thumb') . '" class="avatar" />';
	else
        return $avatar;
}



?>