<?
/*
 * Use the key and secret to generate an auth_token, just to make sure they're valid.
 * If so, return a Facebook API instance.  Otherwise, return null.
 * This is in a separate file because FB's php5 library uses exceptions, which I can't include in the main plugin with PHP4
 */

function jfb_validate_key($key, $secret)
{
      require_once('php4client/facebook.php');
      $facebook = new Facebook($key, $secret, null, true);
      $facebook->api_client->session_key = 0;
      $token = $facebook->api_client->auth_createToken();
      if( $token == null ) return null;
      else                 return $facebook;
}

?>