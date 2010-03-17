=== Plugin Name ===
Contributors: Justin_K
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T88Y2AZ53836U
Tags: facebook connect, facebook, connect, widget, login, logon
Requires at least: 2.5
Tested up to: 2.9.2
Stable tag: 1.0.2

A LoginLogout widget with Facebook Connect button, offering hassle-free login for your readers. Clean and extensible.


== Description ==

The simple concept behind WP-FB AutoConnect is to offer an easy-to-use, no-thrills widget that lets readers login to your blog with either their Facebook account or local blog credentials. Although many "Facebook Connect" plugins do exist, most of them are either overly complex and difficult to customize, or fail to provide a seamless experience for new  visitors. I wrote this plugin to provide what the others didn't:

* No user interaction is required - the login process is transparent to new and returning users alike.
* Existing WP users who connect with FB retain the same local user accounts as before.
* New visitors will be given new WP user accounts, which can be retained even if you remove the plugin.
* Custom logging options can notify you whenever someone connects with Facebook.
* Custom actions allow you to modify connecting users according to their Facebook accounts.
* No contact with Facebook servers after the login completes - so no slow pageloads.
* Simple, well-documented source makes it easy to extend and customize.
* Won't bloat your database with duplicate user accounts, extra fields, or unnecessary complications.

This plugin is a great starting point for coders looking to add customized Facebook integration to their blogs.  For complete information, see the [plugin's homepage](http://www.justin-klein.com/projects/wp-fb-autoconnect).


== Installation ==

To allow your users to login with their Facebook accounts, you must first setup an Application for your site:

1. Visit [www.facebook.com/developers/createapp.php](http://www.facebook.com/developers/createapp.php)
2. Type in a name (i.e. the name of your blog). This is what Facebook will show on the login popup.
3. Note the API Key and Secret; you'll need them in a minute.
4. Click the "Connect" tab and enter your site's URL under "Connect URL" (i.e. http://www.example.com/)
5. Click the "Advanced" tab and enter your site's domain under "Email Domain" (i.e. example.com). This is only required if you want to be able to access your users' email addresses (optional).
6. Click "Save Changes."

Then you can install the plugin:

1. Download the latest version from [here](http://wordpress.org/extend/plugins/wp-fb-autoconnect/), unzip it, and upload the extracted files to your plugins directory.
2. Login to your Wordpress admin panel and activate the plugin.
3. Navigate to Settings -> WP-FB AutoConn.
4. Enter your Application's API Key and Secret (obtained above), and click "Save."
5. Navigate to Appearance -> Widgets, and add the WP-FB AutoConnect widget to your sidebar

That's it - users should now be able to use the widget to login to your blog with their Facebook accounts.

For  more information on exactly how this plugin's login process works and how it can be customized, see the [homepage](http://www.justin-klein.com/projects/wp-fb-autoconnect).


== Frequently Asked Questions ==

[FAQ](http://www.justin-klein.com/projects/wp-fb-autoconnect#faq)


== Screenshots ==

[Screenshots](http://www.justin-klein.com/projects/wp-fb-autoconnect#demo)


== Changelog ==

= 1.0.2 (2010-03-16) =
* Fix API_Key validation check - should work properly now!

= 1.0.1 (2010-03-16) =
* Convert PHP short tags to long tags for server compatability

= 1.0.0 (2010-03-16) =
* First Release