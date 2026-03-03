<?php

//Installation of plugin
function aal_install($network_wide) {
	
	
	
	    if ( is_multisite() && $network_wide ) { 

        foreach (get_sites(['fields'=>'ids']) as $blog_id) {
            switch_to_blog($blog_id);
            
            
 				global $wpdb; 
				$table_name = $wpdb->prefix . "automated_links";
				
				//TODO: Instead of deleting, check if it is already added;
				if(!get_option('aal_target')) add_option( 'aal_target', '_blank');
				if(!get_option('aal_notimes')) add_option( 'aal_notimes', '3');
				if(!get_option('aal_showhome')) add_option( 'aal_showhome', 'true');
				if(!get_option('aal_showlist')) add_option( 'aal_showlist', 'true');
				
				update_option( 'aal_pluginstatus', 'active');  
				$displayc[] = 'post';
				$displayc[] = 'page';
				$dc = json_encode($displayc); 
				if(!get_option('aal_displayc')) add_option( 'aal_displayc', $dc);
				
				
			
				//if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			
				$sql = "CREATE TABLE " . $table_name . " (
				  id mediumint(9) NOT NULL AUTO_INCREMENT,
				  link text NOT NULL,
				  keywords text,
				  meta text,
				  medium varchar(255),
				  grup int(5),
				  grup_desc varchar(255),
				  stats text,
				  PRIMARY KEY  (id)
				  ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
			    
			        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			        dbDelta($sql);
			        
			        
				  
				 $sql2 = "CREATE TABLE " . $wpdb->prefix . "aal_statistics (
				  id int(9) NOT NULL AUTO_INCREMENT,
				  link varchar(1000),
				  time int(50),
				  linkid int(9),
				  keyword varchar(200),
				  loccat varchar(50),
				  loctype varchar(50),
				  locid int(9),
				  locurl varchar(1000),
				  ip varchar(30),
				  PRIMARY KEY  (id)
				  ) CHARACTER SET utf8 COLLATE utf8_general_ci;";	        
			        
			    dbDelta($sql2);  			        
			        
			        
			        
			        
			       // $wpdb->last_error;
       // die();           
            
            
            restore_current_blog();
        } 

    } else {
        	global $wpdb; 
				$table_name = $wpdb->prefix . "automated_links";
				
				
				if(!get_option('aal_target')) add_option( 'aal_target', '_blank');
				if(!get_option('aal_notimes')) add_option( 'aal_notimes', '3');
				if(!get_option('aal_showhome')) add_option( 'aal_showhome', 'true');
				if(!get_option('aal_showlist')) add_option( 'aal_showlist', 'true');
				
				update_option( 'aal_pluginstatus', 'active');  
				$displayc[] = 'post';
				$displayc[] = 'page';
				$dc = json_encode($displayc); 
				if(!get_option('aal_displayc')) add_option( 'aal_displayc', $dc);
				
				
			
				//if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			
				$sql = "CREATE TABLE " . $table_name . " (
				  id mediumint(9) NOT NULL AUTO_INCREMENT,
				  link text NOT NULL,
				  keywords text,
				  meta text,
				  medium varchar(255),
				  grup int(5),
				  grup_desc varchar(255),
				  stats text,
				  PRIMARY KEY  (id)
				  ) CHARACTER SET utf8 COLLATE utf8_general_ci;";	
			    
			        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			        dbDelta($sql);
			        
			        
	
				  
				  
				 $sql2 = "CREATE TABLE " . $wpdb->prefix . "aal_statistics (
				  id int(9) NOT NULL AUTO_INCREMENT,
				  link varchar(1000),
				  time int(50),
				  linkid int(9),
				  keyword varchar(200),
				  loccat varchar(50),
				  loctype varchar(50),
				  locid int(9),
				  locurl varchar(1000),
				  ip varchar(30),
				  PRIMARY KEY  (id)
				  ) CHARACTER SET utf8 COLLATE utf8_general_ci;";	        
			        
			    dbDelta($sql2);    
			        
			        
			    //    $wpdb->last_error;
       // die();
    }
    
    

	
}


function aal_setup_new_blog($blog_id) {

    //replace with your base plugin path E.g. dirname/filename.php
    if ( is_plugin_active_for_network( 'wp-auto-affiliate-links/WP-auto-affiliate-links.php' ) ) {
        switch_to_blog($blog_id);
       
       global $wpdb; 
				$table_name = $wpdb->prefix . "automated_links";
				
				//TODO: Instead of deleting, check if it is already added;
				if(!get_option('aal_target')) add_option( 'aal_target', '_blank');
				if(!get_option('aal_notimes')) add_option( 'aal_notimes', '3');
				if(!get_option('aal_showhome')) add_option( 'aal_showhome', 'true');
				if(!get_option('aal_showlist')) add_option( 'aal_showlist', 'true');
				
				update_option( 'aal_pluginstatus', 'active');  
				$displayc[] = 'post';
				$displayc[] = 'page';
				$dc = json_encode($displayc); 
				if(!get_option('aal_displayc')) add_option( 'aal_displayc', $dc);
				
				
			
				//if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			
				$sql = "CREATE TABLE " . $table_name . " (
				  id mediumint(9) NOT NULL AUTO_INCREMENT,
				  link text NOT NULL,
				  keywords text,
				  meta text,
				  medium varchar(255),
				  grup int(5),
				  grup_desc varchar(255),
				  stats text,
				  PRIMARY KEY  (id)
				  ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
			    
			        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			        dbDelta($sql);
			        
			        
				  
				 $sql2 = "CREATE TABLE " . $wpdb->prefix . "aal_statistics (
				  id int(9) NOT NULL AUTO_INCREMENT,
				  link varchar(1000),
				  time int(50),
				  linkid int(9),
				  keyword varchar(200),
				  loccat varchar(50),
				  loctype varchar(50),
				  locid int(9),
				  locurl varchar(1000),
				  ip varchar(30),
				  PRIMARY KEY  (id)
				  ) CHARACTER SET utf8 COLLATE utf8_general_ci;";	        
			        
			    dbDelta($sql2);  			        
			        
			        
			        
			        
			       // $wpdb->last_error;
       // die();
       
       
       
       
        restore_current_blog();
    } 

}

add_action('wpmu_new_blog', 'aal_setup_new_blog');


define('AAL_NOTICE_VER', '1.0'); 

function aal_admin_notice() {
    if (!current_user_can('activate_plugins')) return;

    $dismissed_ver = get_option('aal_notice_dismissed_ver');
    
    if ($dismissed_ver !== AAL_NOTICE_VER && !get_option('aal_apikey')) {
        ?>
        <div class="notice notice-info is-dismissible aal-notice-pro" data-notice-ver="<?php echo AAL_NOTICE_VER; ?>">
            <p>
                <?php 
                //_e('Thank you for using Auto Affiliate Links. Upgrade to <a href="https://autoaffiliatelinks.com/wp-auto-affiliate-links-pro/" >Auto Affiliate Links PRO</a> for advanced features.', 'wp-auto-affiliate-links'); 
                _e('Thank you for using Auto Affiliate Links. Your feedback is important to us, please give us few minutes to tell us what you think about our plugin and help us to know what we should improve <a href="https://autoaffiliatelinks.com/auto-affiliate-links-feedback-form-2026/">Click here to send us feedback</a>.', 'wp-auto-affiliate-links'); 
                
   
                ?>
            </p>
        </div>
        <?php
    }


    $api_status = get_option('aal_apistatus');
    if (get_option('aal_apikey')) {
        if ($api_status === 'expired') {
            echo '<div class="notice notice-error"><p>' . __('Your subscription is expired. Please <a href="...">renew</a>.', 'wp-auto-affiliate-links') . '</p></div>';
        } elseif ($api_status === 'invalid') {
            echo '<div class="notice notice-error"><p>' . __('Invalid API key. Please <a href="...">register</a>.', 'wp-auto-affiliate-links') . '</p></div>';
        }
    }
}


function aalDismissNotice() {
    check_ajax_referer('aal-ajax-nonce', 'security');
    
    // We store the version number we are dismissing
    $version = sanitize_text_field($_POST['version']);
    update_option('aal_notice_dismissed_ver', $version);
    
    wp_send_json_success();
}


add_action( 'admin_notices', 'aal_admin_notice' );
add_action('wp_ajax_aal_dismiss_notice', 'aalDismissNotice');






?>
