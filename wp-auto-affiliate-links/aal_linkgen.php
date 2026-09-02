<?php
// File: aal_linkgen.php

add_action( 'wp_ajax_aal_linkgen_get', 'aal_linkgen_ajax' );
add_action( 'wp_ajax_nopriv_aal_linkgen_get', 'aal_linkgen_ajax' );

function aal_linkgen_ajax() {
    
    check_ajax_referer( 'aalamazonnonce', 'security' ); 
    
    
		$amazonactive = get_option('aal_amazonactive');
		$amazonid = get_option('aal_amazonid');
		$is_amazon_ready = ($amazonactive && $amazonid);
		
		$impactactive = get_option('aal_impactactive');
		$impactsid = get_option('aal_impactsid');
		$impacttoken = get_option('aal_impacttoken');
		$is_impact_ready = ($impactactive && $impactsid && $impacttoken);
		
		$aliexpressactive = get_option('aal_aliexpressactive');
		$aliexpress_appkey = get_option('aal_aliexpress_appkey');
		$aliexpress_appsecret = get_option('aal_aliexpress_appsecret');
		$is_aliexpress_ready = ($aliexpressactive && !empty($aliexpress_appkey) && !empty($aliexpress_appsecret));

// Rakuten Variables
		$rakutenactive = get_option('aal_rakutenactive');
		$rakuten_clientid = get_option('aal_rakuten_clientid');
		$rakuten_secret = get_option('aal_rakuten_secret'); 
		$rakutensid = get_option('aal_rakutensid'); 
		$is_rakuten_ready = ($rakutenactive && !empty($rakuten_clientid) && !empty($rakuten_secret) && !empty($rakutensid));

// Profitshare Variables
		$profitshareactive = get_option('aal_profitshare_active');
		$profitshare_user = get_option('aal_profitshare_user');
		$profitshare_key = get_option('aal_profitshare_key');
		$is_profitshare_ready = ($profitshareactive && !empty($profitshare_user) && !empty($profitshare_key));

		// If ALL local APIs are inactive or missing credentials, kill the script to save resources
		if(!$is_amazon_ready && !$is_impact_ready && !$is_aliexpress_ready && !$is_rakuten_ready && !$is_profitshare_ready) { exit(); die(); }
		
		$amazoncat = get_option('aal_amazoncat');
		$amazonlocal = get_option('aal_amazonlocal');
		
		$amazondisplaylinks = get_option('aal_amazondisplaylinks');
		$amazondisplaywidget = get_option('aal_amazondisplaywidget');
		if(!$amazondisplaywidget) $amazondisplaylinks = 1;

		  
    
    
	if(isset($_POST['keywords']) && is_array($_POST['keywords'])) $keywords = array_map( 'sanitize_text_field', $_POST['keywords'] );
	if(isset($_POST['notimes'])) $notimes = sanitize_text_field($_POST['notimes']);
	//if($notimes>7) $notimes = 7;
	$alinks = array();
	$awidgets = array();
	
		if(!$keywords[0]) { echo 'no keys'; die(); }

	//Verify payload and save initial cache
	
	$pro_links = array();
	$pro_widgets = array();
	$postidnr = isset($_POST['aalpostid']) ? intval(preg_replace('/[^0-9]/', '', $_POST['aalpostid'])) : 0;

	if ( $postidnr > 0 && isset($_POST['api_payload']) && isset($_POST['api_signature']) ) {
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

		// Dacă plicul de la PRO API e valid, extragem link-urile lui primitive
		if ( openssl_verify($payload_string, $signature, $public_key, OPENSSL_ALGO_SHA256) === 1 ) {
			$cache_data = json_decode($payload_string, true);
			if ( isset($cache_data['links']) && is_array($cache_data['links']) ) {
				$pro_links = $cache_data['links'];
			}
			if ( isset($cache_data['amazonwidget']) && is_array($cache_data['amazonwidget']) ) {
				$pro_widgets = $cache_data['amazonwidget'];
			}

			// SALVĂM IMEDIAT UN CACHE TEMPORAR. Dacă Amazon dă eroare mai jos,
			// articolul are deja un cache local setat și nu va mai rula la infinit!
			$temp_cache = new stdClass();
			$temp_cache->links = $pro_links;
			if(!empty($pro_widgets)) {
				$temp_cache->amazonwidget = $pro_widgets;
			}
			$temp_cache->updated = time();
			update_post_meta($postidnr, 'aal_cache_links', wp_slash(wp_json_encode($temp_cache)));
		}
	}	

//end verification



    $is_amazon_ready = ($amazonactive && $amazonid);


	$nrk = 0;
	$nrw = 0;
	
	 $amazon_error = '';
	
	
    // 3. The Master Router Loop
    if( $keywords && is_array($keywords) ) {
        foreach($keywords as $keyword) {    

            $link_found_for_keyword = false;
            

			if($nrk>=$notimes) if(!$amazondisplaywidget || $nrk>2) break;
			$searchstring = $keyword;            
            
            

            // --- A. Amazon Worker ---
           
            if ( $is_amazon_ready && function_exists('aal_amazon_search_keyword') && empty($amazon_error) ) {
                $amazon_results = aal_amazon_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks );
                
                $nrk = $amazon_results['nrk'];
                $nrw = $amazon_results['nrw'];

                if ( !empty($amazon_results['links']) ) {
                    $alinks = array_merge($alinks, $amazon_results['links']);
                    $link_found_for_keyword = true;
                }
                if ( !empty($amazon_results['widget']) ) {
                    $awidgets = array_merge($awidgets, $amazon_results['widget']);
                }
                
                if ( isset($amazon_results['error']) && !empty($amazon_results['error']) ) {
                    $amazon_error = $amazon_results['error'];
                }
            }

// --- B. Impact Worker ---
            // If Amazon DID NOT find a link, and Impact is ready, we search Impact.
            if ( !$link_found_for_keyword && $is_impact_ready && function_exists('aal_impact_search_keyword') ) {
                
                $impact_results = aal_impact_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks );
                
                $nrk = $impact_results['nrk'];
                $nrw = $impact_results['nrw'];

                if ( !empty($impact_results['links']) ) {
                    // We merge Impact links directly into $alinks. 
                    // This way, api.js receives them normally and displays them instantly.
                    $alinks = array_merge($alinks, $impact_results['links']);
                    $link_found_for_keyword = true;
                }
            }
            
            

// --- C. AliExpress Worker ---
            // If Amazon and Impact DID NOT find a link, and AliExpress is ready, we search AliExpress.
            if ( !$link_found_for_keyword && $is_aliexpress_ready && function_exists('aal_aliexpress_search_keyword') ) {
                
                $aliexpress_results = aal_aliexpress_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks );
                
                $nrk = $aliexpress_results['nrk'];
                $nrw = $aliexpress_results['nrw'];

                if ( !empty($aliexpress_results['links']) ) {
                    // We merge AliExpress links directly into $alinks.
                    // This way, api.js receives them normally and displays them instantly.
                    $alinks = array_merge($alinks, $aliexpress_results['links']);
                    $link_found_for_keyword = true;
                }
                
                // (Optional) If you ever decide to generate visual widgets for AliExpress, 
                // this ensures they are safely merged just like Amazon!
                if ( !empty($aliexpress_results['widget']) ) {
                    $awidgets = array_merge($awidgets, $aliexpress_results['widget']);
                }
            }
            
            
 // --- D. Rakuten Worker ---
            // If Amazon, Impact, and AliExpress DID NOT find a link, and Rakuten is ready, we search Rakuten.
            if ( !$link_found_for_keyword && $is_rakuten_ready && function_exists('aal_rakuten_search_keyword') ) {
                
                $rakuten_results = aal_rakuten_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks );
                
                $nrk = $rakuten_results['nrk'];
                $nrw = $rakuten_results['nrw'];

                if ( !empty($rakuten_results['links']) ) {
                    // We merge Rakuten links directly into $alinks.
                    // This way, api.js receives them normally and displays them instantly.
                    $alinks = array_merge($alinks, $rakuten_results['links']);
                    $link_found_for_keyword = true;
                }
                
                // If you ever decide to generate visual widgets for Rakuten, 
                // this ensures they are safely merged just like Amazon!
                if ( !empty($rakuten_results['widget']) ) {
                    $awidgets = array_merge($awidgets, $rakuten_results['widget']);
                }
            }           
                        
            
  // --- E. Profitshare Worker ---
            // If previous networks DID NOT find a link, and Profitshare is ready, we search Profitshare.
            if ( !$link_found_for_keyword && $is_profitshare_ready && function_exists('aal_profitshare_search_keyword') ) {
                
                $profitshare_results = aal_profitshare_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks );
                
                $nrk = $profitshare_results['nrk'];
                $nrw = $profitshare_results['nrw'];

                if ( !empty($profitshare_results['links']) ) {
                    // We merge Profitshare links directly into $alinks.
                    $alinks = array_merge($alinks, $profitshare_results['links']);
                    $link_found_for_keyword = true;
                }
            }
            
            
                      
            
            
            
        }
    }
    


    // --- START: Prepare final links and update cache ---
    if ( $postidnr > 0 && (!empty($alinks) || !empty($awidgets)) ) {
        $final_links = array();
        
        if ( is_array($alinks) ) {
            foreach ($alinks as $alink) {
                $final_links[] = $alink;
            }
        }
        
        foreach ($pro_links as $plink) {
            $final_links[] = $plink;
        }

        $limit = isset($_POST['notimes']) ? intval($_POST['notimes']) : 999;
        if ( count($final_links) > $limit ) {
            $final_links = array_slice($final_links, 0, $limit);
        }

        $final_widgets = !empty($awidgets) ? $awidgets : $pro_widgets;

        $final_cache = new stdClass();
        $final_cache->links = $final_links;
        if (!empty($final_widgets)) {
            $final_cache->amazonwidget = $final_widgets;
        }
        $final_cache->updated = time();

        // Actualizăm cache-ul local cu varianta completă
        update_post_meta($postidnr, 'aal_cache_links', wp_slash(wp_json_encode($final_cache)));   
    }
    // --- END prepare final links and update cache ---

    // 4. Send Payload Back to Javascript
    $jsonresult = new StdClass();
    $jsonresult->amazonlinks = $alinks;
    $jsonresult->amazonwidget = $awidgets;
    if ( !empty($amazon_error) ) {
        $jsonresult->amazonerror = $amazon_error;
    }
    echo json_encode($jsonresult);

    wp_die();
}