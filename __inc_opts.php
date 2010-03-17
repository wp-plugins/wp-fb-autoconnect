<?php

//General Info
global $jfb_version, $jfb_homepage;
$jfb_version    = "1.0.4";
$jfb_homepage   = "http://www.justin-klein.com/projects/wp-fb-autoconnect";


//Database options
global $opt_jfb_api_key, $opt_jfb_api_sec, $opt_jfb_email_to, $opt_jfb_delay_redir, $opt_jfb_ask_perms;
global $opt_jfb_req_perms, $opt_jfb_hide_button, $opt_jfb_always_inc, $opt_jfb_mod_done, $opt_jfb_valid;
$opt_jfb_api_key    = "jfb_api_key";
$opt_jfb_api_sec    = "jfb_api_sec";
$opt_jfb_email_to   = "jfb_email_to";
$opt_jfb_delay_redir= "jfb_delay_redirect";
$opt_jfb_ask_perms  = "jfb_ask_permissions";
$opt_jfb_req_perms  = "jfb_req_permissions";
$opt_jfb_hide_button= "jfb_hide_button";
$opt_jfb_always_inc = "jfb_always_inc";
$opt_jfb_mod_done   = "jfb_modrewrite_done";
$opt_jfb_valid      = "jfb_session_valid";


//Shouldn't ever need to change these
global $jfb_nonce_name, $jfb_uid_meta_name, $jfb_js_callbackfunc, $jfb_default_email;
$jfb_nonce_name     = "ahe4t50q4efy0";
$jfb_uid_meta_name  = "facebook_uid";
$jfb_js_callbackfunc= "jfb_js_login_callback";
$jfb_default_email  = 'email@unknown.com';


//Error reporting function
function j_die($msg)
{
    j_mail("Facebook Login Error", $msg);
    die($msg);
}

//Log reporting function
function j_mail($subj, $msg)
{
    global $opt_jfb_email_to;
    $email_to = get_option($opt_jfb_email_to);
    if( isset($email_to) && $email_to )
        mail($email_to, $subj, $msg);
}

?>