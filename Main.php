<?php
/* Plugin Name: WP-FB-AutoConnect
 * Description: A LoginLogout widget with Facebook Connect button, offering hassle-free login for your readers.  Also provides a good starting point for coders looking to add more customized Facebook integration to their blogs.
 * Author: Justin Klein
 * Version: 1.1.1
 * Author URI: http://www.justin-klein.com/
 * Plugin URI: http://www.justin-klein.com/projects/wp-fb-autoconnect
 */

require_once("__inc_opts.php");
require_once("AdminPage.php");
require_once("Widget.php");


/*
 * Output a Facebook Connect Button.  Note that the button will not function until you've called 
 * jfb_output_facebook_init().  I use document.write() because the button isn't XHTML valid.
 */
function jfb_output_facebook_btn()
{
    global $jfb_name, $jfb_version, $jfb_js_callbackfunc, $opt_jfb_valid;
    echo "<!-- $jfb_name v$jfb_version -->\n";
    if( !get_option($opt_jfb_valid) )
    {
        echo "<!--WARNING: Invalid or Unset Facebook API Key-->";
        return;
    }
    ?>
    <script type="text/javascript">//<!--
    document.write('<span id="fbLoginButton"><fb:login-button v="2" size="small" onlogin="<?php echo $jfb_js_callbackfunc?>();">Login with Facebook</fb:login-button></span>');
    //--></script>
    <?php
}


/*
 * As an alternative to jfb_output_facebook_btn, this will setup an event to automatically popup the
 * Facebook Connect dialog as soon as the page finishes loading (as if they clicked the button manually) 
 */
function jfb_output_facebook_instapopup()
{
    global $jfb_js_callbackfunc;
    ?>
    <script type="text/javascript">//<!--
    function showPopup()
    {
        FB.ensureInit( function(){FB.Connect.requireSession(<?php echo $jfb_js_callbackfunc?>);}); 
    }
    window.onload = showPopup;
    //--></script>
    <?php
}


/*
 * Output the JS to init the Facebook API, which will also setup a <fb:login-button> if present. 
 */
function jfb_output_facebook_init()
{
    global $opt_jfb_api_key, $opt_jfb_valid;
    if( !get_option($opt_jfb_valid) ) return;
    $xd_receiver = plugins_url(dirname(plugin_basename(__FILE__))) . "/facebook-platform/xd_receiver.htm";
    ?>
    <script type="text/javascript" src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php"></script>
    <script type="text/javascript">//<!--
    FB.init("<?php echo get_option($opt_jfb_api_key)?>","<?php echo $xd_receiver?>");
    //--></script>
    <?php    
}



/*
 * Output the JS callback function that'll handle FB logins
 */
function jfb_output_facebook_callback($redirectTo=0)
{
     global $opt_jfb_ask_perms, $opt_jfb_req_perms, $opt_jfb_valid, $jfb_nonce_name, $jfb_js_callbackfunc;
     if( !get_option($opt_jfb_valid) ) return;
     if( !$redirectTo ) $redirectTo = $_SERVER['REQUEST_URI'];
     $process_logon = plugins_url(dirname(plugin_basename(__FILE__))) . "/_process_login.php";
 ?>
    <form name="fblogin_form" action="<?php echo $process_logon?>" method="post">
      <input type="hidden" name="redirectTo" value="<?php echo $redirectTo?>" />
      <?php wp_nonce_field ($jfb_nonce_name) ?>   
    </form>
    <script type="text/javascript">//<!--
    function <?php echo $jfb_js_callbackfunc?>()
    {
        //Make sure we have a valid session
        if (!FB.Facebook.apiClient.get_session())
        { alert('Facebook failed to log you in!'); return; }

        <?php 
        //Optionally request permissions to get their real email address before redirecting to the logon script.
        $ask_for_email_permission = get_option($opt_jfb_ask_perms) || get_option($opt_jfb_req_perms);
        if( $ask_for_email_permission ) echo "FB.Connect.showPermissionDialog('email', function(reply){\n";
        if( get_option($opt_jfb_req_perms) ) echo 'if(!reply){ alert("Sorry, this site requires an e-mail address to log you in."); return; }';
        ?>
                document.fblogin_form.submit();
        <?php if( $ask_for_email_permission ) echo "});\n"; ?>
    }
    //--></script><?php
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
    echo 'xmlns:fb="http://www.facebook.com/2008/fbml"';
}



/*
 * If this is BuddyPress, switch ON the option to include bp filters by default
 */
global $opt_jfb_buddypress;
add_action( 'bp_init', 'jfb_turn_on_bp' );
function jfb_turn_on_bp()
{
    add_option($opt_jfb_buddypress, 1);
}



/**
  * If the BuddyPress option is on, include its filters
  */
if( get_option($opt_jfb_buddypress) ) require_once("BuddyPress.php");


/*
 * Activate
 */
register_activation_hook(__FILE__, 'jfb_activate');
register_deactivation_hook(__FILE__, 'jfb_deactivate');

?>