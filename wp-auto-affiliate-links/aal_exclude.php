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
	
	$excluded_id = filter_input(INPUT_POST, 'aal_excluded_item_link_id', FILTER_SANITIZE_SPECIAL_CHARS);
	check_ajax_referer( 'aal_excluded_item_link_action'. $excluded_id, 'aal_excluded_item_link_nonce' );
            
    // $update_exclude_posts= filter_input(INPUT_POST, 'aal_exclude_posts', FILTER_SANITIZE_SPECIAL_CHARS);
   $update_exclude_posts=  $_POST['aal_exclude_posts'];
    $update_exclude_posts=  implode(',', $update_exclude_posts);
    $update_exclude_posts= esc_sql(htmlentities($update_exclude_posts));
    $update_exclude_posts = filter_var($update_exclude_posts, FILTER_SANITIZE_SPECIAL_CHARS);
    delete_option('aal_exclude');add_option( 'aal_exclude', $update_exclude_posts);

    
    die();
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
                    <b>Exclude all posts/pages published before this date:  </b>:
                    <input type="date" name="aal_excluderulesdatebefore" id="aal_excluderulesdatebefore" value="<?php echo get_option('aal_excluderulesdatebefore'); ?>" />Use this option if you want links to show only in posts published after the selected date
                    <br /><br />
                    <?php wp_nonce_field( 'aal_ecluderulesdatebeforena', 'aal_excluderulesdatebefore_nonce' ); ?>
                    <input type="hidden" name="aal_excluderulesaction" id="aal_excluderulesaction"  value="1" />
                    <input  class="button-primary"  type="submit" value="Save"/> 
                </form>

<br /><br />

<h3>Manually exclude posts/pages</h3>                
                
                 <form name="aal_add_exclude_posts_form" id="aal_add_exclude_posts_form" method="post">
                    <b>Enter post/page ID </b>:
                    <?php wp_nonce_field( 'aal_excludepostbyid_action', 'aal_excludepostbyid_nonce' ); ?>
                    <input type="text" name="aal_exclude_post_id" id="aal_add_exclude_post_id"  size="10" />
                    <input  class="button-primary"  type="submit" value="Exclude Post"/>
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
                <h4>Excluded Posts</h4>
                <form class="aal_exclude_posts">
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
				
				  			$aal_excluded_item_link_nonce = wp_create_nonce( 'aal_excluded_item_link_action'. $aal_exclude_post_id );
				  	
                    echo "<div class='aal_excludeditem'>
                            <div class='aal_excludedcol aal_excludedidcol'>".$aal_exclude_post_id."</div>
                            <div class='aal_excludedcol aal_excludedtitle'> <a href='".get_permalink($aal_exclude_post_id)."'>".get_the_title($aal_exclude_post_id)."</a></div> 
                            <div class='aal_excludedcol'>  ". $status ." </div> 
                            <div class='aal_excludedcol'> <a href='javascript:' id='".$aal_exclude_post_id."' data-id='". $aal_exclude_post_id ."' data-security='". $aal_excluded_item_link_nonce ."' class='aal_delete_exclude_link'><img src='".plugin_dir_url(__FILE__)."images/delete.png'/></a></div><br/>
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