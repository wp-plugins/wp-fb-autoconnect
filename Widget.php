<?php


/**
  * Sidebar LoginLogout widget with Facebook Connect button
  **/
class Widget_LoginLogout extends WP_Widget
{
    //////////////////////////////////////////////////////
    //Init the Widget
    function Widget_LoginLogout()
    { 
        $this->WP_Widget( false, "WP-FB AutoConnect Basic", array( 'description' => __('A sidebar Login/Logout form with Facebook Connect button', 'wp-fb-ac') ) );
    }
     
    //////////////////////////////////////////////////////
    //Output the widget's content.
    function widget( $args, $instance )
    {
        //Get args and output the title
        extract( $args );
        echo $before_widget;
        $title = apply_filters('widget_title', $instance['title']);
        if( $title ) echo $before_title . $title . $after_title;
        
        //If logged in, show "Welcome, User!"
        if( is_user_logged_in() ):
        ?>
            <div style='text-align:center'>
              <?php 
                $userdata = wp_get_current_user();
                _e('Welcome', 'wp-fb-ac') . ', ' . $userdata->display_name;
              ?>!<br />
              <small>
                <a href="<?php echo get_option('siteurl')?>/wp-admin/profile.php"><?php _e("Edit Profile", 'wp-fb-ac')?></a> | <a href=" <?php echo wp_logout_url( $_SERVER['REQUEST_URI'] )?>"><?php _e("Logout", 'wp-fb-ac')?></a>
              </small>
            </div>
        <?php
        //Otherwise, show the login form (with Facebook Connect button)
        else:
        ?>
            <form name='loginform' id='loginform' action='<?php echo wp_login_url(); ?>' method='post'>
                <label><?php _e("User", 'wp-fb-ac')?>:</label><br />
                <input type='text' name='log' id='user_login' class='input' tabindex='20' /><input type='submit' name='wp-submit' id='wp-submit' value='<?php _e("Login", 'wp-fb-ac')?>' tabindex='23' /><br />
                <label><?php _e("Pass", 'wp-fb-ac')?>:</label><br />
                <input type='password' name='pwd' id='user_pass' class='input' tabindex='21' />
                <span id="forgotText"><a href="<?php echo wp_lostpassword_url()?>" rel="nofollow" ><?php _e('Forgot', 'wp-fb-ac')?>?</a></span><br />
                <?php //echo "<input name='rememberme' type='hidden' id='rememberme' value='forever' />";?>
                <?php echo wp_register('',''); ?>
                <input type='hidden' name='redirect_to' value='<?php echo htmlspecialchars($_SERVER['REQUEST_URI'])?>' />
            </form>
            <?php
            global $opt_jfb_hide_button;
            if( !get_option($opt_jfb_hide_button) )
            {
                jfb_output_facebook_btn();
                //jfb_output_facebook_init(); This is output in wp_footer as of 1.5.4
                //jfb_output_facebook_callback(); This is output in wp_footer as of 1.9.0
            }
        endif;
        echo $after_widget;
    }
    
    
    //////////////////////////////////////////////////////
    //Update the widget settings
    function update( $new_instance, $old_instance )
    {
        $instance = $old_instance;
        $instance['title'] = $new_instance['title'];
        return $instance;
    }

    ////////////////////////////////////////////////////
    //Display the widget settings on the widgets admin panel
    function form( $instance )
    {
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'wp-fb-ac'); ?>:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
        </p>
        <?php
    }
}


//Register the widget
add_action( 'widgets_init', 'register_jfbLogin' );
function register_jfbLogin() { register_widget( 'Widget_LoginLogout' ); }

?>