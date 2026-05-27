<?php


//Cache actions

add_action( 'wp_ajax_aal_cache_set', 'aal_cache_set_func' );
add_action( 'wp_ajax_nopriv_aal_cache_set', 'aal_cache_set_func' );


function aal_cache_set_func() {
    
    check_ajax_referer( 'aalcachesetnonce', 'cachesetnonce' ); 
    
    if ( !isset($_POST['api_payload']) || !isset($_POST['api_signature']) ) {
        echo 'failed';
        wp_die();
    }

    $payload_string = wp_unslash($_POST['api_payload']);
    $signature = base64_decode(wp_unslash($_POST['api_signature']));

    $public_key = <<<'EOD'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzMt3P4hcTE/KxjVPtqVn
wtQ4/EyPRBtpqZx/YsshRNveLqCdM9425VDLJ/SRbVp0FvtyfQ4PODWIv+PpLcfO
zd/YQRq50JMfcS61Iyuamt0mcodzS321qfkAav0+kWca8fcv6Lulkt41QpXLSQAj
wQc9+WBvvVNmhYB5c0q54S+uGc3JGludyu+MRZf7n+mMcI6G5Hv4DRasPNkAni6L
iMhR4558mt5LREXTEKJCKk0rfUNOsgJkjYGx0F1qaGaMHaUwcjKgFDJyOpxmfdJC
wpd/9NGelgAn5W/NReGSTKpJcutkGGIJwYwYtrA9Zi6Qxnz0Kt0d7wvg/UZ6WpWn
ZwIDAQAB
-----END PUBLIC KEY-----
EOD;

    
    $is_valid = openssl_verify($payload_string, $signature, $public_key, OPENSSL_ALGO_SHA256);

    if ( $is_valid !== 1 ) {
        echo 'failed';
        wp_die();
    }    

    
    $cache_data = json_decode($payload_string, true);

    if(isset($_POST['aalpostid']) && !empty($_POST['aalpostid'])) {
        
        
        $postidnr = intval(preg_replace('/[^0-9]/', '', $_POST['aalpostid']));
        
        if ($postidnr === 0 || !get_post_status($postidnr)) {
            echo 'nopost';
            wp_die();
        }

        
        $links = array();
        if(isset($cache_data['links']) && is_array($cache_data['links'])) {
            foreach($cache_data['links'] as $l) {
                $lob = new stdClass();
                // Folosim sanitize_url care e specific pentru link-uri, eliminând scripturile
                $lob->url = sanitize_url($l['url']);
                $lob->key = sanitize_text_field($l['key']);
                $links[] = $lob;
            }
        }
        
        $awidgets = '';
        if(isset($cache_data['amazonwidget']) && is_array($cache_data['amazonwidget'])) {
            $awidgets = array();
            foreach($cache_data['amazonwidget'] as $w) {
                $wob = new stdClass();
                $wob->price = sanitize_text_field($w['price']);
                $wob->url   = sanitize_url($w['url']);	
                $wob->image = sanitize_url($w['image']);	
                $wob->title = sanitize_text_field($w['title']);				
                $awidgets[] = $wob;
            }		
        }
        
        
        $response = new stdClass();
        $response->links = $links;
        if(is_array($awidgets)) {
            $response->amazonwidget = $awidgets;
        }
        $response->updated = time();
        
        
        $jsonlinks = wp_json_encode($response);
        
        update_post_meta($postidnr, 'aal_cache_links', $jsonlinks);
        echo 'success';
        
    } else {
        echo 'failed';
    }
    
    wp_die();
}


add_action( 'wp_ajax_aal_cache_get', 'aal_cache_get_func' );
add_action( 'wp_ajax_nopriv_aal_cache_get', 'aal_cache_get_func' );


function aal_cache_get_func() {
	
	check_ajax_referer( 'aalcachegetnonce', 'cachegetnonce' ); 
	
	
				// =========================================================
            // Count usage
            // =========================================================
            $current_month_key = 'aal_views_' . date('Y_m');
            $current_views = get_option( $current_month_key, false );
            
            if ( $current_views === false ) {
                add_option( $current_month_key, 1, '', 'no' );
            } else {
                update_option( $current_month_key, intval( $current_views ) + 1 );
            }
            // end count usage	
	
	
	
	
	if(isset($_POST['aalpostid']) && $_POST['aalpostid'] && is_numeric(substr($_POST['aalpostid'],0, 5)) ) {
		
		$postidnr = substr(sanitize_text_field($_POST['aalpostid']),0, 5);
		$now = time();
		
		if(get_post_status($postidnr)) { 
		
		
		if(get_post_meta($postidnr,'aal_cache_links', true)) {
		
				$pmt = json_decode(get_post_meta($postidnr,'aal_cache_links', true));		
				
				if(is_array($pmt) || is_object($pmt)) {
				
					$links = array();
					$links = $pmt->links;
					$updated = $pmt->updated;
					$now = time();
					
					//echo $updated;
					//echo get_post_modified_time('U',false,$postidnr);
			
					if(($now - $updated) < (6*60*60)) {
						
						if($updated > get_option('aal_settings_updated')) {
						
							if($updated > get_post_modified_time('U',false,$postidnr)) {
							
								//print_r($links);
								
								echo get_post_meta($postidnr,'aal_cache_links', true);			
								
								//echo $return;
							
							}
							else {
								echo 'post updated';			
							}	
							
						}
						else {
							echo 'settings updated';		
						}			 
							
					}
					else {
						echo 'expired';			
					}
				}
				else {
					echo 'nometa';				
				}
			}
			else {
				echo 'nometa';			
			}

		}
		else {
			echo 'nopost';		
		}
	}
	
	else {
		echo 'failed';
	}
	
	exit();
	die();
}