<?php

/*
 * Tell WP about the Admin page
 */
add_action('admin_menu', 'jfb_add_admin_page', 99);
function jfb_add_admin_page()
{ 
    add_options_page('WP-FB AutoConnect Options', 'WP-FB AutoConn', 'administrator', "wp-fb-autoconnect", 'jfb_admin_page');
}


/**
  * Link to Settings on Plugins page 
  */
add_filter('plugin_action_links', 'jfb_add_plugin_links', 10, 2);
function jfb_add_plugin_links($links, $file)
{
    if( dirname(plugin_basename( __FILE__ )) == dirname($file) )
        $links[] = '<a href="options-general.php?page=' . "wp-fb-autoconnect" .'">' . __('Settings','sitemap') . '</a>';
    return $links;
}


/*
 * Output the Admin page
 */
function jfb_admin_page()
{
    global $opt_jfb_api_key, $opt_jfb_api_sec, $opt_jfb_email_to, $opt_jfb_delay_redir, $jfb_homepage;
    global $opt_jfb_ask_perms, $opt_jfb_hide_button, $opt_jfb_always_inc, $opt_jfb_mod_done, $opt_jfb_valid;
    ?>
    <div class="wrap">
    <?php
      if( isset($_POST['main_opts_updated']) )
      {
          update_option( $opt_jfb_api_key, $_POST[$opt_jfb_api_key] );
          update_option( $opt_jfb_api_sec, $_POST[$opt_jfb_api_sec] );
          update_option( $opt_jfb_ask_perms, $_POST[$opt_jfb_ask_perms] );
          
          //When we save the main options, try to connect to Facebook with the key and secret, to make sure they're valid
          if(version_compare('5', PHP_VERSION, "<=")) require_once('facebook-platform/client/facebook.php');
          else                                        require_once('facebook-platform/php4client/facebook.php');
          $facebook = new Facebook($_POST[$opt_jfb_api_key], $_POST[$opt_jfb_api_sec], null, true);
          $facebook->api_client->session_key = 0;          
          $isValid = true;
          try
          {
              $appInfo = $facebook->api_client->admin_getAppProperties(array('app_id', 'application_name'));
          }
          catch (Exception $e)
          {
              $isValid = false;
          }
          
          if( !$isValid ):
              jfb_auth(plugin_basename( __FILE__ ), $GLOBALS['jfb_version'], 3, 'ERROR: ' . $_POST[$opt_jfb_api_key]);
              update_option( $opt_jfb_valid, 0 );
              ?><div class="updated"><p><strong>ERROR:</strong> Facebook could not validate your session key and secret!  Are you sure you've entered them correctly?</p></div><?php
          else : 
              $appID = sprintf("%.0f", $appInfo['app_id']);  
              update_option( $opt_jfb_valid, 1 );
              jfb_auth(plugin_basename( __FILE__ ), $GLOBALS['jfb_version'], 2, $appID . ' - "' . $appInfo['application_name'] . '"' );
              ?><div class="updated"><p><strong>Main Options saved for <?php echo '"' . $appInfo['application_name'] . '" (AppID ' . $appID . ')' ?></strong></p></div><?php
          endif;
      }
      if( isset($_POST['debug_opts_updated']) )
      {
          if( $_POST[$opt_jfb_email_to] )   update_option( $opt_jfb_email_to, get_bloginfo('admin_email') );
          else                              update_option( $opt_jfb_email_to, 0 );
          update_option( $opt_jfb_delay_redir, $_POST[$opt_jfb_delay_redir] );
          update_option( $opt_jfb_hide_button, $_POST[$opt_jfb_hide_button] );          
          update_option( $opt_jfb_always_inc, $_POST[$opt_jfb_always_inc] );
          ?><div class="updated"><p><strong><?php _e('Debug Options saved.', 'mt_trans_domain' ); ?></strong></p></div><?php
      }
      if( isset($_POST['mod_rewrite_update']) )
      {
          add_action('generate_rewrite_rules', 'jfb_add_rewrites');
          add_filter('mod_rewrite_rules', 'jfb_fix_rewrites');
          global $wp_rewrite;
          $wp_rewrite->flush_rules();
          update_option( $opt_jfb_mod_done, true );
          ?><div class="updated"><p><strong><?php _e('HTACCESS Updated.', 'mt_trans_domain' ); ?></strong></p></div><?php          
      }
    ?>
    <h2>WP-FB AutoConnect Options</h2>
      <div style="position:absolute; right:60px; margin-top:-50px;">
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_s-xclick" />
        <input type="hidden" name="hosted_button_id" value="T88Y2AZ53836U" />
        <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" />
        <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
      </form>
      </div>
      
    To allow your users to login with their Facebook accounts, you must first setup a Facebook Application for your website:<br /><br />
    <ol>
      <li>Visit <a href="http://www.facebook.com/developers/createapp.php" target="_lnk">www.facebook.com/developers/createapp.php</a></li>
      <li>Type in a name (i.e. the name of your website).  This is the name your users will see on the Facebook login popup.</li>
      <li>Copy the API Key and Secret to the boxes below.</li>
      <li>Click the "Connect" tab (back on Facebook) and under Connect URL, enter the URL to your website with a trailing slash (i.e. http://www.example.com/).</li>
      <li>Click the "Advanced" tab and enter your site's domain under "Email Domain" (i.e. example.com).  This is only required if you want to access your users' email addresses (optional).</li>
      <li>Click "Save Changes" (on Facebook).</li>
      <li>Click "Save" below.</li>
    </ol>
    <br />That's it!  Now you can add this plugin's <a href="<?php echo admin_url('widgets.php')?>">sidebar widget</a> and allow your readers to login with their Facebook accounts.<br /><br />
    For more complete documentation and help, visit the <a href="<?php echo $jfb_homepage?>">plugin homepage</a>.<br />
     
    <br />
    <hr />
    
    <h4>Main Options:</h4>
    <form name="formMainOptions" method="post" action="">
        <input type="text" size="40" name="<?php echo $opt_jfb_api_key?>" value="<?php echo get_option($opt_jfb_api_key) ?>" /> API Key<br />
        <input type="text" size="40" name="<?php echo $opt_jfb_api_sec?>" value="<?php echo get_option($opt_jfb_api_sec) ?>" /> API Secret<br /><br />
        <input type="checkbox" name="<?php echo $opt_jfb_ask_perms?>" value="1" <?php echo get_option($opt_jfb_ask_perms)?'checked="checked"':''?> /> Ask the user for permission to get their email address<br />
        <input type="hidden" name="main_opts_updated" value="1" />
        <div class="submit"><input type="submit" name="Submit" value="Save" /></div>
    </form>
    <hr />
    
    <h4>Mod Rewrite Rules</h4>
    <?php
    if (get_option($opt_jfb_mod_done))
        echo "It looks like your htaccess has already been updated.  If you're having trouble with autologin links, make sure the file is writable and click the Update button again.";
    else
        echo "In order to use this plugin's autologin shortcut links (i.e. www.example.com/autologin/5), your .htaccess file needs to be updated.  Click the button below to update it now.<br /><br />Note that this is an advanced feature and won't be needed by most users; see the plugin's homepage for documentation."
    ?>
    <form name="formMainOptions" method="post" action="">
        <input type="hidden" name="mod_rewrite_update" value="1" />
        <div class="submit"><input type="submit" name="Submit" value="Update Now" /></div>
    </form>
    <hr />
    
    <h4>Debug Options:</h4>
    <form name="formDebugOptions" method="post" action="">
        <input type="checkbox" name="<?php echo $opt_jfb_email_to?>" value="1" <?php echo get_option($opt_jfb_email_to)?'checked="checked"':''?> /> Send all event logs to <i><?php echo get_bloginfo('admin_email')?></i><br />
        <input type="checkbox" name="<?php echo $opt_jfb_delay_redir?>" value="1" <?php echo get_option($opt_jfb_delay_redir)?'checked="checked"':''?> /> Delay redirect after login (Not for production sites!)<br />
        <input type="checkbox" name="<?php echo $opt_jfb_hide_button?>" value="1" <?php echo get_option($opt_jfb_hide_button)?'checked="checked"':''?> /> Hide Facebook Button<br />
        <input type="checkbox" name="<?php echo $opt_jfb_always_inc?>" value="1" <?php echo get_option($opt_jfb_always_inc)?'checked="checked"':''?> /> Always include the Facebook API JavaScript (even when logged in)<br />
        <input type="hidden" name="debug_opts_updated" value="1" />
        <div class="submit"><input type="submit" name="Submit" value="Save" /></div>
    </form>
      
   </div><?php
}


/*
 * Append our RewriteRule to htaccess so we can use links like www.example.com/autologin/123
 * This gets invoked by the generate_rewrite_rules filter when we call $wp_rewrite->flush_rules(),
 * which is triggered by the Update Now button
 */
function jfb_add_rewrites($wp_rewrite)
{
    $autologin = explode(get_bloginfo('url'), plugins_url(dirname(plugin_basename(__FILE__))));
    $autologin = trim($autologin[1] . "/_autologin.php", "/") . '?p=$1';
    $wp_rewrite->non_wp_rules = $wp_rewrite->non_wp_rules + array('autologin[/]?([0-9]*)$' => $autologin);
}

/*
 * Wordpress is HARDCODED to specify every rewriterule as [QSA,L]; the only way to get a redirect is to string-replace it.
 */
function jfb_fix_rewrites($rules)
{
    $autologin = explode(get_bloginfo('url'), plugins_url(dirname(plugin_basename(__FILE__))));
    $autologin = trim($autologin[1] . "/_autologin.php", "/") . '?p=$1';
    $rules = str_replace($autologin . ' [QSA,L]', $autologin . ' [R,L]', $rules);
    return $rules;
}

?>