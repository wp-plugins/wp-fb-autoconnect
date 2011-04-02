<?php
/* Plugin Name: WP-FB-AutoConnect
 * Description: A LoginLogout widget with Facebook Connect button, offering hassle-free login for your readers. Clean and extensible. Supports BuddyPress.
 * Author: Justin Klein
 * Version: 1.9.2
 * Author URI: http://www.justin-klein.com/
 * Plugin URI: http://www.justin-klein.com/projects/wp-fb-autoconnect
 */


/*
 * Copyright 2010 Justin Klein (email: justin@justin-klein.com)
 * 
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * -------------------------------------------
 *
 * --------Adding to WP-FB-AutoConnect--------
 * If you're interested in creating a derived plugin, please contact me (Justin Klein) first;
 * although duplicating and supplementing the existing code is one approach, doing so 
 * also means that your duplicate will not benefit from any future updates or bug fixes.
 * Instead, with a bit of coordination I'm sure we could arrange for your addon to tie into the
 * existing hooks & filters (which I can supplement if needed), allowing users to benefit 
 * both from your improvements as well as any future fixes I may provide.
 * 
 * If you do choose to create and release a derived plugin anyway, please just be sure not to represent it
 * as a fully original work, nor attempt to confuse it with the original WP-FB-AutoConnect by means of 
 * its name or otherwise.  You should give credit to the original plugin in a plainly visible location,
 * such as the admin panel and readme file.  Also, please do not remove my links to Paypal or the 
 * Premium Addon as these are the only means through which I fund the considerable time I've devoted to 
 * creating and maintaining this otherwise free plugin.  If you'd like to add your own link that's of course
 * fine, but just make sure it's no more prominent than the original.
 * 
 * Put simply, be fair.  I've put hundreds of hours into this work, and I *have* experienced someone else 
 * trying to claim credit for it.  Please don't be that person.  I'm happy if you feel it's worthy of 
 * additional development, but would appreciate it if you'd work with me and not against me.  Expanding upon 
 * my work in the spirit of free software is welcome.  Stealing my credit and donations is not.
 * 
 * Thanks! :)
 *
 */


require_once("__inc_opts.php");
@include_once(realpath(dirname(__FILE__))."/../WP-FB-AutoConnect-Premium.php");
if( !defined('JFB_PREMIUM') ) @include_once("Premium.php");
require_once("AdminPage.php");
require_once("Widget.php");


/**********************************************************************/
/*******************************GENERAL********************************/
/**********************************************************************/

/*
 * Output a Facebook Connect Button.  Note that the button will not function until you've called 
 * jfb_output_facebook_init().  I use document.write() because the button isn't XHTML valid.
 * NOTE: The button tag itself maybe overwritten by the Premium addon (wpfb_output_button filter)
 */
function jfb_output_facebook_btn()
{
    global $jfb_name, $jfb_version, $jfb_js_callbackfunc, $opt_jfb_valid;
    echo "<!-- $jfb_name Button v$jfb_version -->\n";
    if( !get_option($opt_jfb_valid) )
    {
        echo "<!--WARNING: Invalid or Unset Facebook API Key-->";
        return;
    }
    ?>
    <span class="fbLoginButton">
    <script type="text/javascript">//<!--
    <?php 
    $btnTag = "document.write('<fb:login-button v=\"2\" size=\"small\" onlogin=\"$jfb_js_callbackfunc();\">Login with Facebook</fb:login-button>');";  
    echo apply_filters('wpfb_output_button', $btnTag );
    ?>
    //--></script>
    </span>
    
    <?php
    do_action('wpfb_after_button');
}


/*
 * As an alternative to jfb_output_facebook_btn, this will setup an event to automatically popup the
 * Facebook Connect dialog as soon as the page finishes loading (as if they clicked the button manually) 
 */
function jfb_output_facebook_instapopup( $callbackName=0 )
{
    global $jfb_js_callbackfunc;
    if( !$callbackName ) $callbackName = $jfb_js_callbackfunc;
    ?>
    <script type="text/javascript">//<!--
    function showPopup()
    {
        FB.ensureInit( function(){FB.Connect.requireSession(<?php echo $callbackName?>);}); 
    }
    window.onload = showPopup;
    //--></script>
    <?php
}


/*
 * Output the JS to init the Facebook API, which will also setup a <fb:login-button> if present.
 * Output this in the footer, so it always comes after the buttons.
 * NOTE: This hook maybe removed & replaced by the Premium plugin.
 */
add_action('wp_footer', 'jfb_output_facebook_init');
function jfb_output_facebook_init()
{
    global $jfb_name, $jfb_version, $opt_jfb_app_id, $opt_jfb_api_key, $opt_jfb_valid;
    if( !get_option($opt_jfb_valid) ) return;
    $xd_receiver = plugins_url(dirname(plugin_basename(__FILE__))) . "/facebook-platform/xd_receiver.htm";
    echo "\n<!-- $jfb_name Init v$jfb_version -->\n";
    ?>
    <script type="text/javascript" src="https://ssl.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/<?php do_action('wpfb_output_facebook_locale') ?>"></script>
    <script type="text/javascript">//<!--
        FB.init("<?php echo get_option($opt_jfb_api_key)?>","<?php echo $xd_receiver?>");
    //--></script>
    <?php  
}



/*
 * Output the JS callback function that'll handle FB logins.
 * NOTE: The Premium addon may alter its behavior via the hooks below.
 */
add_action('wp_footer', 'jfb_output_facebook_callback');
function jfb_output_facebook_callback($redirectTo=0, $callbackName=0)
{
     //Make sure the plugin is setup properly before doing anything
     global $jfb_name, $jfb_version;
     global $opt_jfb_ask_perms, $opt_jfb_req_perms, $opt_jfb_valid, $jfb_nonce_name;
     global $jfb_js_callbackfunc, $opt_jfb_ask_stream, $jfb_callback_list;
     if( !get_option($opt_jfb_valid) ) return;
     
     //Get out our params
     if( !$redirectTo )  $redirectTo = htmlspecialchars($_SERVER['REQUEST_URI']);
     if( !$callbackName )$callbackName = $jfb_js_callbackfunc;
     echo "\n<!-- $jfb_name Callback v$jfb_version -->\n";
     
     //Make sure we haven't already output a callback with this name
     if( in_array($callbackName, $jfb_callback_list) )
     {
         echo "\n<!--jfb_output_facebook_callback has already generated a callback named $callbackName!  Skipping.-->\n";
         return;
     }
     else
        array_push($jfb_callback_list, $callbackName);
     
     //Output an html form that we'll submit via JS once the FB login is complete; it redirects us to the PHP script that logs us into WP.  
?>
  
  <form id="wp-fb-ac-fm" name="<?php echo $callbackName ?>_form" method="post" action="<?php echo plugins_url(dirname(plugin_basename(__FILE__))) . "/_process_login.php"?>" >
      <input type="hidden" name="redirectTo" value="<?php echo $redirectTo?>" />
<?php 
      //An action to allow the user to inject additional data in the form, to be transferred to the login script
      do_action('wpfb_add_to_form');
?>
      <?php wp_nonce_field ($jfb_nonce_name) ?>   
    </form><?php

    //Output the JS callback function, which Facebook will automatically call once it's been logged in.
    ?><script type="text/javascript">//<!--
    function <?php echo $callbackName ?>()
    {

<?php 
		//An action to allow the user to inject additional javascript to get executed before the login takes place
		do_action('wpfb_add_to_js', $callbackName);

        //Optionally request permissions to get their real email and to publish to their wall before redirecting to the logon script.
        $ask_for_email_permission = get_option($opt_jfb_ask_perms) || get_option($opt_jfb_req_perms);
        if( $ask_for_email_permission )                                                   		//Ask for email
            echo "        FB.Connect.showPermissionDialog('".apply_filters('wpfb_extended_permissions','email')."', function(reply1)\n        {\n";
        if( get_option($opt_jfb_ask_stream) )                                                   //Ask for publish to wall
            echo "        FB.Connect.showPermissionDialog('".apply_filters('wpfb_extended_permissions','publish_stream')."', function(reply2)\n        {\n";

        //If we're not requiring their email, just redirect them (no matter if they approve or not)
        if( !get_option($opt_jfb_req_perms) )
        {
            echo apply_filters('wpfb_submit_loginfrm', "document." . $callbackName . "_form.submit();\n" );
        }        
        
        //If we REQUIRE their email address, make sure they accept the extended permissions before redirecting to the logon script            
        else
        {
            echo "            FB.Facebook.apiClient.users_hasAppPermission('email', function (emailCheck)\n".
                 "            {\n". 
		         "                 if(emailCheck)\n".
		         "                 {\n";
            echo apply_filters('wpfb_submit_loginfrm', "document." . $callbackName . "_form.submit();\n");
            echo "                 }\n".
                 "                 else\n".
                 "                 {\n";
            echo apply_filters('wpfb_login_rejected', '');
            echo "                     alert('Sorry, this site requires an e-mail address to log you in.');\n".
                 "                 }\n".
                 "            });\n";
        }
        
        //Close up the functions
        if( $ask_for_email_permission )
        	echo "        });\n";
        if( get_option($opt_jfb_ask_stream) )
        	echo "        });\n";
        ?>
    }
    //--></script><?php
    
    //DEBUG (to try and figure out the "nonce check failed" problem)
    global $opt_jfb_generated_nonce;
    update_option($opt_jfb_generated_nonce, jfb_debug_nonce_components());
}




/**********************************************************************/
/*******************************CREDIT*********************************/
/**********************************************************************/
global $opt_jfb_show_credit;
if( get_option($opt_jfb_show_credit) ) add_action('wp_footer', 'jfb_show_credit');
function jfb_show_credit()
{
    global $jfb_homepage;
    echo "Facebook login by <a href=\"$jfb_homepage\">WP-FB-AutoConnect</a>";
}


/**********************************************************************/
/*******************************AVATARS********************************/
/**********************************************************************/

/**
 * Legacy Support: there used to be two separate options for WP and BP; it's now just one option
 */
if( get_option($opt_jfb_bp_avatars) )
{
    delete_option($opt_jfb_bp_avatars);
    update_option($opt_jfb_wp_avatars, 1);    
}


/**
  * Optionally replace WORDPRESS avatars with FACEBOOK profile pictures
  */
if( get_option($opt_jfb_wp_avatars) ) add_filter('get_avatar', 'jfb_wp_avatar', 10, 5);
function jfb_wp_avatar($avatar, $id_or_email, $size, $default, $alt)
{
    //First, get the userid
	if (is_numeric($id_or_email))	    
	    $user_id = $id_or_email;
	else if(is_object($id_or_email) && !empty($id_or_email->user_id))
	   $user_id = $id_or_email->user_id;
	else if(is_string($id_or_email))
	   $user_id = get_user_by('email', $id_or_email ); 

	//If we couldn't get the userID, just return default behavior (email-based gravatar, etc)
	if(!isset($user_id) || !$user_id) return $avatar;

	//Now that we have a userID, let's see if we have their facebook profile pic stored in usermeta
	$fb_img = get_usermeta($user_id, 'facebook_avatar_thumb');
	
	//If so, replace the avatar! Otherwise, fallback on what WP core already gave us.
	if($fb_img) $avatar = "<img alt='fb_avatar' src='$fb_img' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
    return $avatar;
}


/*
 * Optionally replace BUDDYPRESS avatars with FACEBOOK profile pictures
 */
if( get_option($opt_jfb_wp_avatars) ) add_filter( 'bp_core_fetch_avatar', 'jfb_bp_avatar', 10, 4 );    
function jfb_bp_avatar($avatar, $params='')
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


/**********************************************************************/
/******************************USERNAMES*******************************/
/**********************************************************************/
    
/*
 * Optionally modify the FB_xxxxxx to something "prettier", based on the user's real name on Facebook
 */
global $opt_jfb_username_style;
if( get_option($opt_jfb_username_style) == 1 || get_option($opt_jfb_username_style) == 2 ) add_filter( 'wpfb_insert_user', 'jfb_pretty_username', 10, 2 );
function jfb_pretty_username( $wp_userdata, $fb_userdata )
{
    global $jfb_log, $opt_jfb_username_style;
    $jfb_log .= "WP: Converting username to \"pretty\" username...\n";
    
    //Create a username from the user's Facebook name
    if( get_option($opt_jfb_username_style) == 1 )
        $name = "FB_" . str_replace( ' ', '', $fb_userdata['first_name'] . "_" . $fb_userdata['last_name'] );
    else
        $name = str_replace( ' ', '', $fb_userdata['first_name'] . "." . $fb_userdata['last_name'] );
    
    //Strip all non-alphanumeric characters, and make sure we've got something left.  If not, we'll just leave the FB_xxxxx username as is.
    $name = sanitize_user($name, true);
    if( strlen($name) == 0 || $name == "FB__" )
    {
        $jfb_log .= "WP: Error - Completely non-alphanumeric Facebook name cannot be used; leaving as default.\n";
        return $wp_userdata;
    }
    
    //Make sure the name is unique: if we've already got a user with this name, append a number to it.
    $counter = 1;
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
        
    //Done!
    $wp_userdata['user_login']   = $username;
    $wp_userdata['user_nicename']= $username;
    $jfb_log .= "WP: Name successfully converted to $username.\n";
    return $wp_userdata;
}



/**********************************************************************/
/*******************BUDDYPRESS (previously in BuddyPress.php)**********/
/**********************************************************************/

/*
 * Default the username style to "Pretty Usernames" if BP is detected.
 */
add_action( 'bp_init', 'jfb_turn_on_prettynames' );
function jfb_turn_on_prettynames()
{
    global $opt_jfb_username_style;
    add_option($opt_jfb_username_style, 2);
}


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
  }
}

    
/**********************************************************************/
/****************************IE compatibility**************************/
/**********************************************************************/

/**
 * The old Facebook API isn't compatible with IE9
 * (see http://stackoverflow.com/questions/5323474/ie9-error-sec7111-https-security-is-compromised-when-using-the-facebook-rest)
 * We can solve this by forcing it to behave like IE8 via a metatag: <meta http-equiv="X-UA-Compatible" content="IE=8" />
 * However, because this metatag must come immediately after <head>, and we have no guarantee when a given
 * theme will will actually call wp_head, we need to do a little "hack" to make sure it comes first:
 * 1) Starting with get_header, capture all the output until wp_head (which we know is *somewhere* between <head> and </head>)
 * 2) Parse the captured content and insert the metatag immediately after <head>.
 * 3) Output the captured (& modified) header fragment
 * 4) Stop the buffer capture so the output will continue as normal
 */
if( !get_option($opt_jfb_disable_ie9_hack) )
{
    add_action('get_header', 'jfb_ie9_fix_start');
    add_action('wp_head',    'jfb_ie9_fix_finish', 1);
}
function jfb_ie9_fix_start()
{  //Start buffer capturing before we load the header template
   ob_start(); 
}
function jfb_ie9_fix_finish()
{
    //Stop capturing - we've now got the "header" template in our buffer, up to where it calls wp_head
    $header = ob_get_contents();
    ob_end_clean();
    
    //Get the part of the header up to the "<head>" tag (i.e. DOCTYPE & <html> tags)
    $p1_to_head_pos = stripos($header, "<head");
    $p1_to_head = substr($header, 0, $p1_to_head_pos);
    $header = substr($header, $p1_to_head_pos);
    
    //Get the part of the header within the <head> tag (if present), and close it up
    $p2_head_contents_pos = stripos($header, ">");
    $p2_head_contents = substr($header, 0, $p2_head_contents_pos) . ">";
    $header = substr($header, $p2_head_contents_pos+1);
    
    //And the rest is the remainder of our captured output
    $p3_after_head = $header;
    
    //Now piece it back together, inserting our new metatag right after <head>
    echo $p1_to_head;
    echo $p2_head_contents;
    echo "\n<meta http-equiv=\"X-UA-Compatible\" content=\"IE=8\" />";
    echo $p3_after_head;
}


/**
  * Include the FB class in the <html> tag (only when not already logged in)
  * So stupid IE will render the button correctly
  */
add_filter('language_attributes', 'jfb_output_fb_namespace');
function jfb_output_fb_namespace()
{
    global $current_user;
    if( isset($current_user) && $current_user->ID != 0 ) return;
    if( has_filter( "language_attributes", "wordbooker_schema" ) ) return;
    echo 'xmlns:fb="http://www.facebook.com/2008/fbml"';
}


/**********************************************************************/
/***************************Error Reporting****************************/
/**********************************************************************/

register_activation_hook(__FILE__, 'jfb_activate');
register_deactivation_hook(__FILE__, 'jfb_deactivate');

?>