<?php



function aalAddExcludePost(){
			global $wpdb;            
            	 if ( !current_user_can("publish_pages") ) die();
            	 
            	 check_ajax_referer( 'aal_excludepostbyid_action', 'aal_excludepostbyid_nonce' );
            
                $aal_exclude_id= filter_input(INPUT_POST, 'aal_post', FILTER_SANITIZE_SPECIAL_CHARS);
                $aal_posts =get_option('aal_exclude');
                
                $post = get_post($aal_exclude_id);
                $data['post_title'] = $post->post_title;
                if(!$post->ID) {
                die('nopost');
					}
					
					$aal_posts_array = explode(',',$aal_posts);
					if(in_array($post->ID,$aal_posts_array)) {
						die('duplicate');					
					}
               
                
                if($aal_posts=='')$aal_exclude=$aal_exclude_id;
                    else $aal_exclude=$aal_posts.",".$aal_exclude_id;
                    
                 
                delete_option('aal_exclude'); 
                add_option( 'aal_exclude', $aal_exclude);
                echo " <div class='aal_excludedcol aal_excludedtitle'><a href='".get_permalink($post->ID)."'>".get_the_title($post->ID)."</a></div>  <div class='aal_excludedcol'>  ". get_post_status($post->ID) ." </div>                           ";
                               
                die();
}


function aalUpdateExcludePosts(){

    check_ajax_referer('aal_update_exclude_action', 'aal_global_nonce');
            
    $update_exclude_posts = isset($_POST['aal_exclude_posts']) ? $_POST['aal_exclude_posts'] : '';
    
 
    $update_exclude_posts = esc_sql(htmlentities($update_exclude_posts));
    $update_exclude_posts = filter_var($update_exclude_posts, FILTER_SANITIZE_SPECIAL_CHARS);
    
 
    delete_option('aal_exclude');
    add_option('aal_exclude', $update_exclude_posts);
    
    wp_die();
}

//Ajax for exclude search


function aal_search_posts_to_exclude_callback() {
    // Basic permissions check
    if ( !current_user_can('manage_options') ) {
        wp_send_json(array());
    }

    $search_term = sanitize_text_field( $_POST['search_term'] );

// --- FETCH EXCLUDED IDs ---
    $excluded_ids = array(); 
    $raw_excluded = get_option('aal_exclude', '');
    
    if ( !empty($raw_excluded) ) {
        $excluded_ids = array_map('intval', explode(',', $raw_excluded));
    }

    // Query parameters
    $args = array(
        's'              => $search_term,
        'post_type'      => array('post', 'page'), // Add custom post types if needed
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'post__not_in'   => $excluded_ids, // WP_Query will automatically filter these out!
    );

    $query = new WP_Query( $args );
    $results = array();

    if ( $query->have_posts() ) {
        foreach ( $query->posts as $post ) {
            $results[] = array(
                'id'    => $post->ID,
                'title' => get_the_title( $post->ID )
            );
        }
    }

    wp_send_json( $results );
}	

function wpaal_exclude_posts() {
	global $wpdb;
	$table_name = $wpdb->prefix . "automated_links";	
	


	//Exclude flush action
	if(isset($_POST['aal_flush_exclude_check']) && $_POST['aal_flush_exclude_check']=="ok") {
			if ( isset( $_POST['aal_flush_exclude_nonce_field'] )  && wp_verify_nonce( $_POST['aal_flush_exclude_nonce_field'], 'aal_flush_exclude_nonce' )) {
			
					 delete_option('aal_exclude');			
			
			} 
	
	}
	
	
	


	
	?>
	
	
<div class="wrap">  
        <div class="icon32" id="icon-options-general"></div>  
 
         
        
                <h2>Exclude Posts/Pages from Auto Affiliate Links</h2>
                <br /><br /><br />
                
<h3>Automatic rules for post exclusions</h3>

<form name="aal_excluderules" id="aal_excluderules" method="post">
    <p>
        <b>Exclude all posts/pages published before this date:</b><br />
        <input type="date" name="aal_excluderulesdatebefore" id="aal_excluderulesdatebefore" value="<?php echo esc_attr(get_option('aal_excluderulesdatebefore')); ?>" />
        <span class="description">Links will only show in posts published <b>after</b> this date.</span>
    </p>

    <p>
        <b>Exclude all posts/pages published after this date:</b><br />
        <input type="date" name="aal_excluderulesdateafter" id="aal_excluderulesdateafter" value="<?php echo esc_attr(get_option('aal_excluderulesdateafter')); ?>" />
        <span class="description">Links will only show in posts published <b>before</b> this date.</span>
    </p>

<?php

$aal_date_notice = '';

//Code to display current setting
$before_raw = get_option('aal_excluderulesdatebefore');
$after_raw = get_option('aal_excluderulesdateafter');

$date_format = get_option('date_format'); // Uses your WordPress site's date setting
$before_pretty = !empty($before_raw) ? '<b>' . date_i18n($date_format, strtotime($before_raw)) . '</b>' : '';
$after_pretty = !empty($after_raw) ? '<b>' . date_i18n($date_format, strtotime($after_raw)) . '</b>' : '';

if ($before_pretty && $after_pretty) {
    $aal_date_notice = "Posts published before $before_pretty and after $after_pretty are currently excluded.";
} elseif ($before_pretty) {
    $aal_date_notice = "Posts published before $before_pretty are currently excluded.";
} elseif ($after_pretty) {
    $aal_date_notice = "Posts published after $after_pretty are currently excluded.";
}

?>


<?php if (isset($aal_date_notice) && $aal_date_notice): ?>
        <p style="color: #46b450; font-weight: 500; margin-bottom: 15px;">
            <span class="dashicons dashicons-calendar-alt" style="font-size: 18px; margin-right: 5px;"></span> 
            <?php echo $aal_date_notice; ?>
        </p>
    <?php endif; ?>    
    

    <?php wp_nonce_field( 'aal_excluderules_action', 'aal_excluderules_nonce' ); ?>
    <input type="hidden" name="aal_excluderulesaction" value="1" />
    
    <input class="button-primary" type="submit" value="Save Rules"/> 
    
    <button type="button" id="aal_reset_date_rules" class="button" style="margin-left: 10px;">Reset date exclusion rules</button>
</form>
<br /><br />

<?php wpaal_exclude_authors_ui(); ?>

<br /><br />

<h3>Manually exclude posts/pages</h3>                
                
                 <form name="aal_add_exclude_posts_form" id="aal_add_exclude_posts_form" method="post">
                    <b>Enter post/page ID </b>:
                    <?php wp_nonce_field( 'aal_excludepostbyid_action', 'aal_excludepostbyid_nonce' ); ?>
                    <input type="text" name="aal_exclude_post_id" id="aal_add_exclude_post_id"  size="10" /> 
                    <input  class="button-primary"  type="submit" value="Exclude Post"/>  <label>You can add multiple ids separated by comma (,) </label>
                </form>
                
 <br />
 
                  <form name="aal_add_exclude_posts_byurl_form" id="aal_add_exclude_posts_byurl_form" method="post">
                    <b>Enter post/page URL </b>:
                    <input type="text" name="aal_exclude_post_url" id="aal_add_exclude_post_url"  size="10" />
                    <input type="hidden" name="aal_exclude_post_byurl_check" value="1" />
                    <?php wp_nonce_field( 'aal_excludepostbyurl_action', 'aal_excludepostbyurl_nonce' ); ?>
                    <input  class="button-primary"  type="submit" value="Exclude Post"/>
                </form>
                
					<br />
					<h3>Search and Exclude Posts</h3>
					<div id="aal_search_exclude_container">
					    <b>Search by Title:</b>
					    <input type="text" id="aal_search_post_input" placeholder="Type at least 3 characters..." size="30" autocomplete="off" />
					    <button type="button" id="aal_trigger_search_btn" class="button">Search</button>
					    <span id="aal_search_spinner" style="display:none; color: #666;"><i> Searching...</i></span>
					    
					    <br/><br/>
					    
					    <div id="aal_search_results_container" style="display:none;">
					        <select id="aal_search_results" multiple="multiple" style="width: 100%; max-width: 400px; height: 150px;">
					        </select>
					        <br/>
					        <button type="button" id="aal_select_all_btn" class="button">Select All</button>
					        <br/><br/>
					        <button type="button" id="aal_submit_search_exclude" class="button-primary">Exclude Selected Posts</button>
					        <?php wp_nonce_field( 'aal_excludepostbyid_action', 'aal_excludepostbyid_nonce_search' ); ?>
					    </div>
					</div>             
                
                
                
                <br />
                <h4>Excluded Posts</h4>
					<form class="aal_exclude_posts">
					    <input type="hidden" id="aal_global_exclude_nonce" value="<?php echo wp_create_nonce('aal_update_exclude_action'); ?>" />
					    

                <?php 
                $aal_exclude_posts=get_option('aal_exclude');
                $aal_exclude_posts_array=explode(',', $aal_exclude_posts);
                
                
                if($aal_exclude_posts_array[0]) {
                
?>


			  	<div class='aal_excludeditemheader'>
				  		<div class='aal_excludedcol aal_excludedidcol'>
				  			Post ID
				  		</div>
				  		<div class='aal_excludedcol aal_excludedtitle'>
				  			Post/Page Title
				  		</div>
				  		<div class='aal_excludedcol'>
				  			Status
				  		</div>
				  		<div class='aal_excludedcol'>
				  			Actions
				  		</div>
				  	
				  	</div>
				  	<div style='clear: both;'></div>



<?php
                
                
                
                
                foreach ($aal_exclude_posts_array as $aal_exclude_post_id)
                  if($aal_exclude_post_id!='') { 
						$exclude_title = get_the_title($aal_exclude_post_id);
						if(!$exclude_title) $status = 'post with the given id does not exist';
							else $status = get_post_status($aal_exclude_post_id);
						
				  	if($status = 'publish') { $status = 'Published'; }
				

				  	
					echo "<div class='aal_excludeditem'>
                            <div class='aal_excludedcol aal_excludedidcol'>".$aal_exclude_post_id."</div>
                            <div class='aal_excludedcol aal_excludedtitle'> <a href='".get_permalink($aal_exclude_post_id)."'>".get_the_title($aal_exclude_post_id)."</a></div> 
                            <div class='aal_excludedcol'>  ". $status ." </div> 
                            <div class='aal_excludedcol'> <a href='javascript:;' class='aal_delete_exclude_link'><img src='".plugin_dir_url(__FILE__)."images/delete.png'/></a></div><br/>
                          </div><div style='clear: both;'></div>";
					
				}	//endforeach
                
               
		} // end if
		else {
			?>
				No excluded posts.
			
			<?php
			
		}
                
                ?>
                </form>
                
                <span class="aal_exclude_status"> </span>
                
                
     <br />
    <br />
        <br />
    <br />
    <form method="post" name="aal_flush_exclude" onsubmit="return confirm('This will remove all posts from exclusion. Are you sure?'); ">
    <input type="hidden" name="aal_flush_exclude_check" value="ok" />
    <input type="submit" class="button-primary" value="Flush excluded posts" />
    <?php wp_nonce_field( 'aal_flush_exclude_nonce', 'aal_flush_exclude_nonce_field' ); ?>
    </form>   
                
                
                
    <br />
    <br />
  <p>If you have problems or questions about the plugin, or if you just want to send a suggestion or request to our team, you can use the <a href="https://wordpress.org/support/plugin/wp-auto-affiliate-links">support forum</a>. Make sure that you consult our <a href="https://wordpress.org/plugins/wp-auto-affiliate-links/faq/">FAQ section</a> first. </p>
  
  </div>
 
	
	<?php
}




?>