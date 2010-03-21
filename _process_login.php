<?php
/**
  * This file handles Facebook Connect login requests.  When a user logs in via the FB popup window,
  * the js_callbackfunc will redirect us here.  We then use information from FB to log them into WP.
  * See the bottom of this file for notes on the Facebook API
  */

//Include our options and the Wordpress core
require_once("__inc_opts.php");
require_once("__inc_wp.php");
$jfb_log = "Starting login process (Client: " . $_SERVER['REMOTE_ADDR'] . ")...\n";


//Check the nonce to make sure this was a valid login attempt (not a hack)
if( wp_verify_nonce ($_REQUEST ['_wpnonce'], $jfb_nonce_name) != 1 )
    j_die("Failed nonce check. Login aborted.");
$jfb_log .= "WP: nonce check passed\n";

    
//Get the redirect URL
if( !isset($_POST['redirectTo']) || !$_POST['redirectTo'] )
    j_die("Error: Missing POST Data (redirect)");
$redirectTo = $_POST['redirectTo'];
$jfb_log .= "WP: Found redirect URL ($redirectTo)\n";


//Connect to FB and make sure we've got a valid session (we should already from the cookie set by JS)  
if(version_compare('5', PHP_VERSION, "<=")) require_once('facebook-platform/client/facebook.php');
else                                        require_once('facebook-platform/php4client/facebook.php');
$facebook = new Facebook(get_option($opt_jfb_api_key), get_option($opt_jfb_api_sec), null, true);    
$fb_uid = $facebook->get_loggedin_user();
if(!$fb_uid) j_die("Error: Failed to get the Facebook session. Please verify your API Key and Secret.");
$jfb_log .= "FB: Connected to session (uid $fb_uid)\n";


//Get the user info from FB
$fbuser = $facebook->api_client->users_getInfo($fb_uid, array('name','first_name','last_name','profile_url','contact_email', 'email_hashes'));
$fbuser = $fbuser[0];
if( !$fbuser ) j_die("Error: Could not access the Facebook API client (failed on users_getInfo())."); 
$jfb_log .= "FB: Got user info (".$fbuser['name'].")\n";


//See if we were given permission to access the user's email
//This isn't required, and will only matter if it's a new user without an existing WP account
//(since we'll auto-register an account for them, using the contact_email we get from Facebook - if we can...)
if( $fbuser['contact_email'] )
    $jfb_log .= "FB: Email privilege granted (" .$fbuser['contact_email'] . ")\n";
else
{
    $fbuser['contact_email'] = $jfb_default_email;
    $jfb_log .= "FB: Email privilege denied\n";
}


//Run a hook so users can`examine this Facebook user *before* letting them login.  You might use this
//to limit logins based on friendship status - if someone isn't your friend, you could redirect them
//to an error page (and terminate this script).
do_action('wpfb_connect', array('FB_ID' => $fb_uid, 'facebook' => $facebook) );


//Examine all existing WP users to see if any of them match this Facebook user. 
//First we check their meta: whenever a user logs in with FB, this plugin tags them with usermeta
//so we can find them again easily.  This obviously will only work for returning FB Connect users.
$wp_users = get_users_of_blog();
$wp_user_hashes = array();
$jfb_log .= "WP: Searching for user by meta...\n";
foreach ($wp_users as $wp_user)
{
    $user_data = get_userdata($wp_user->ID);
    $meta_uid  = get_usermeta($wp_user->ID, $jfb_uid_meta_name);
    if( $meta_uid && $meta_uid == $fb_uid )
    {
        $user_login_id   = $wp_user->ID;
        $user_login_name = $user_data->user_login;
        $jfb_log .= "WP: Found existing user by meta (" . $user_login_name . ")\n";
        break;
    }

    //In case we don't find them by meta, we'll need to search for them by email below.
    //Precalculate each non-FB-connected user's mail-hash (http://wiki.developers.facebook.com/index.php/Connect.registerUsers)
    if( !$meta_uid )
    {
        $email= strtolower(trim($user_data->user_email));
        $hash = sprintf('%u_%s', crc32($email), md5($email));
        $wp_user_hashes[$wp_user->ID] = array('email_hash' => $hash);
    }
}


//If we couldn't find any usermeta identifying an existing WP user for this FB user, we'll use
//FB to see if they've registered an email address on FB that matches any of our WP users' emails.
//We can do this even without the email extended_permission, because we use email *hashes*.
if( !$user_login_id && count($wp_user_hashes) > 0 )
{
    if(version_compare(PHP_VERSION, '5', "<"))
    {
        $jfb_log .= "FP: CANNOT search for users by email in PHP4\n";
    }
    else
    {
        //First we send Facebook a list of email hashes we want to check against this user
        //It *should* accept 1000 at a time, but some users have reported crashes...so I'll try 750...
        $insert_limit = 750;
        $jfb_log .= "FP: Searching for user by email...\n";
        $jfb_log .= "    Registering hashes for " . count($wp_user_hashes) . " candidates of " . count($wp_users) . " total users...\n";
        $hash_chunks = array_chunk( $wp_user_hashes, $insert_limit );
        foreach( $hash_chunks as $num => $hashes )
        {
            $jfb_log .= "    #" . ($num*$insert_limit) . "-" . ($num*$insert_limit+count($hashes)-1) . "\n";
            $ret = $facebook->api_client->connect_registerUsers(json_encode($wp_user_hashes));
            if( !$ret ) j_die("Error: Could not register hashes with Facebook (connect_registerUsers).\n");
        }  
        
        //Next we get the hashes for the current FB user; This will only return hashes we
        //registered above, so if we get back nothing we know the current FB user is not on our blog
        $jfb_log .= "    Looking up current user...\n";
        $this_fbuser_hashes = $facebook->api_client->users_getInfo($fb_uid, array('email_hashes'));
        $this_fbuser_hashes = $this_fbuser_hashes[0]['email_hashes'];
        
        //If we did get back a hash, all we need to do is find which WP user it came from - and that's who's logging in! 
        if(!empty($this_fbuser_hashes)) 
        {
            foreach( $this_fbuser_hashes as $this_fbuser_hash )
            {
                foreach( $wp_user_hashes as $this_wpuser_id => $this_wpuser_hash )
                {
                    if( $this_fbuser_hash == $this_wpuser_hash['email_hash'] )
                    {
                        $user_login_id   = $this_wpuser_id;
                        $user_data       = get_userdata($user_login_id);
                        $user_login_name = $user_data->user_login;
                        $jfb_log .= "FB: Found existing user by email (" . $user_login_name . ")\n";
                        break;
                    }
                }
            }    
        }
    }
}


//If we found an existing user, check if they'd previously denied access to their email but have now allowed it.
//If so, we'll want to update their WP account with their *real* email.
if( $user_login_id )
{
    if( $user_data->user_email == $jfb_default_email && $fbuser['contact_email'] != $jfb_default_email )
    {
        $jfb_log .= "WP: Previously denied email has now been allowed; updating to (".$fbuser['contact_email'].")\n";
        $user_upd = array();
        $user_upd['ID']         = $user_login_id;
        $user_upd['user_email'] = $fbuser['contact_email'];
        wp_update_user($user_upd);
    }
}


//If we STILL don't have a user_login_id, the FB user who's logging in has never been to this blog.
//We'll auto-register them a new account.  Note that if they haven't allowed email permissions, the
//account we register will have a bogus email address (but that's OK, since we still know their Facebook ID)
if( !$user_login_id )
{
    $jfb_log .= "WP: No user found. Automatically registering (FB_". $fb_uid . ")\n";
    $user_data = array();
    $user_data['user_login']    = "FB_" . $fb_uid;
    $user_data['user_pass']     = substr( md5( uniqid( microtime() ).$_SERVER["REMOTE_ADDR"] ), 0, 15);
    $user_data['first_name']    = $fbuser['first_name'];
    $user_data['last_name']     = $fbuser['last_name'];
    $user_data['user_nicename'] = $fbuser['name'];
    $user_data['display_name']  = $fbuser['first_name'];
    $user_data['user_url']      = $fbuser["profile_url"];
    $user_data['user_email']    = $fbuser['contact_email'];
    
    //Run a filter so the user can be modified to something different before registration
    $user_data = apply_filters('wpfb_insert_user', $user_data, $fbuser );
    
    //Insert a new user to our database and notify the site admin
    $user_login_id   = wp_insert_user($user_data);
    $user_login_name = $user_data['user_login'];
    wp_new_user_notification($user_login_name);
}


//Tag the user with our meta so we can recognize them next time, without resorting to email hashes
update_usermeta($user_login_id, $jfb_uid_meta_name, $fb_uid);
$jfb_log .= "WP: Updated usermeta ($jfb_uid_meta_name)\n";


//Log them in, and run a custom action.  You can use this to modify a logging-in user however you like, i.e:
//  -Check if the user is friends with you on Facebook, and if so, give them special permissions.
//  -Set the user's Wordpress avatar to their Facebook icon
//  -Add an item to a "Recent Facebook Visitors" log
//  -...etc
wp_set_auth_cookie($user_login_id);
do_action('wpfb_login', array('WP_ID' => $user_login_id, 'FB_ID' => $fb_uid, 'facebook' => $facebook) );
do_action('wp_login', $user_login_name);


//Email logs if requested
$jfb_log .= "Login complete!\n";
$jfb_log .= "   WP User : $user_login_name (" . admin_url("user-edit.php?user_id=$user_login_id") . ")\n";
$jfb_log .= "   FB User : " . $fbuser['name'] . " (" . $fbuser["profile_url"] . ")\n";
$jfb_log .= "   Redirect: " . $redirectTo . "\n";
j_mail("Facebook Login: " . $user_login_name);


//Redirect the user back to where they were
$delay_redirect = get_option($opt_jfb_delay_redir);
if( !isset($delay_redirect) || !$delay_redirect )
{
    header("Location: " . $redirectTo);
    exit;
}
?>
<!doctype html public "-//w3c//dtd html 4.0 transitional//en">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Logging In...</title>
    </head>
    <body>
        <?php echo "<pre>".$jfb_log."</pre>"?>
        <?php echo '<a href="'.$redirectTo.'">Continue</a>'?>
    </body>
</html>
<?php


/*
NOTES:
->Basic FB Connect Tutorial: http://wiki.developers.facebook.com/index.php/Facebook_Connect_Tutorial1
->Facebook Javascript API: http://developers.facebook.com/docs/?u=facebook.jslib.FB
->How authentication works: http://wiki.developers.facebook.com/index.php/How_Connect_Authentication_Works
->Note: The FB API is available in JS and PHP; a session that's been started in either of these languages
        can be used in the other: http://wiki.developers.facebook.com/index.php/Using_Facebook_Connect_with_Server-Side_Libraries
        Once you login with Javascript, it creates a session cookie.  Then if you create a new Facebook object in PHP with the same
        API key, it'll automatically activate the session found in the cookie set by JS.
->Note: It's easiest to connect in Javascript (via a popup) then transfer to PHP (as I've done here), but you can also login directly with PHP
        by creating a new Facebook instance, generating a token with auth_token, ask the user to click the login URL, then get the session key
        by using getSession() with this token (as done in Facebook Photo Fetcher).  See: http://forum.developers.facebook.com/viewtopic.php?pid=148426
->Note: An api_key and api_secret are NOT the same as a session_key and session_secret; the api_key identifies the APPLICATION (i.e. this webpage),
        and the SESSION represents an active user connected to this website (about whom we can pull profile info).
*/
?>