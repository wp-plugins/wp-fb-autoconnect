<?
/*
 * BuddyPress-specific filters go here.  This is included only if the "Include BUDDYPRESS filters"
 * option is selected in the plugin's admin panel.
 */



/*
 * Add a Facebook Login button to the Buddypress sidebar login widget
 * NOTE: If you use this, you mustn't also use the built-in widget - just one or the other!
 */
function bp_add_fb_login_button()
{
  if ( !is_user_logged_in() )
  {
      jfb_output_facebook_btn();
      jfb_output_facebook_init();
      jfb_output_facebook_callback();
  }
}
add_action( 'bp_after_sidebar_login_form', 'bp_add_fb_login_button' );



/*
 * Change the usernames from the default FB_xxxxxx to something prettier for BuddyPress' social link system
 */
function bp_fbconnect_modify_userdata( $userdata, $userinfo )
{ 
    $userdata = array(
        'user_pass' => wp_generate_password(),
        'user_login' => $fbusername,
        'display_name' => fbc_get_displayname($userinfo),
        'user_url' => fbc_make_public_url($userinfo),
        'user_email' => $userinfo['proxied_email']
    );

    $fb_bp_user_login = strtolower( str_replace( ' ', '', fbc_get_displayname($userinfo) ) );
    
    $counter = 1;
    if ( username_exists( $fb_bp_user_login ) ) {
        do {
            $username = $fb_bp_user_login;
            $counter++;
            $username = $username . $counter;
        } while ( username_exists( $username ) );

        $userdata['user_login'] = $username;
    } else {
        $userdata['user_login'] = $fb_bp_user_login;
    }
    
    return $userdata;
}
add_filter( 'fbc_insert_user_userdata', 'bp_fbconnect_modify_userdata', 10, 2 );



?>