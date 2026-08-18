<?php
// File: /modules/impact/impact.php

$aalImpact = new aalModule('impact','Impact Links',3);
$aalModules[] = $aalImpact;

$aalImpact->aalModuleHook('content','aalImpactDisplay');



function aal_impact_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks ) {
	
	
    
    $sid = trim(get_option('aal_impactsid'));
    $token = trim(get_option('aal_impacttoken'));
    $impactactive = get_option('aal_impactactive');
    $saved_merchants_option = get_option('aal_impact_active_merchants', 'all');
    $active_merchants = ($saved_merchants_option !== 'all' && !empty($saved_merchants_option)) ? json_decode($saved_merchants_option, true) : 'all';

    // If missing credentials, bail immediately
    if(!$impactactive || empty($sid) || empty($token)) { 
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw); 
    }

    $impactlinks = array();
    $awidgetcode = array(); 
    $found_link = false;
    
    $args = array(
        'timeout'     => 15,
        'blocking'    => true,
        'headers'     => array(
            'Authorization' => 'Basic ' . base64_encode( $sid . ':' . $token ),
            'Accept'        => 'application/json'
        )
    );

    // ==========================================
    // STEP 1: Smart Catalog Search (Fuzzy Match)
    // ==========================================
    $catalog_url = "https://api.impact.com/Mediapartners/" . urlencode($sid) . "/Catalogs/ItemSearch?Keyword=" . urlencode($keyword) . "&PageSize=5";
    $catalog_response = wp_remote_get( $catalog_url, $args );

    if ( ! is_wp_error( $catalog_response ) && wp_remote_retrieve_response_code( $catalog_response ) == 200 ) {
        $cat_body = json_decode( wp_remote_retrieve_body( $catalog_response ), true );
        
        if ( ! empty( $cat_body['Items'] ) ) {
            foreach ( $cat_body['Items'] as $item ) {
            	
            	// Filter by active merchant
                $campaign_id = isset($item['CampaignId']) ? $item['CampaignId'] : '';
                if ($active_merchants !== 'all' && $campaign_id && !in_array($campaign_id, $active_merchants)) {
                    continue; // Skip if this merchant is unchecked
                }
                
                if ( ! empty( $item['Url'] ) ) {
                    $link = $item['Url'];
                    
                    // Duplicate Check
                    $found = 0;
                    foreach($alinks as $aa) {
                        if(isset($aa->link) && $link == $aa->link) $found = 1;
                        if(isset($aa->url) && $link == $aa->url) $found = 1;      
                    }
                    
                    if($found != 1) {
                        $alink = new stdClass();
                        $alink->key = $keyword;
                        $alink->url = $link;
                        $impactlinks[] = $alink;
                        $found_link = true;
                        break; // Stop after finding the first valid link
                    }
                }
            }
        }
    }

    // ==========================================
    // STEP 2: Legacy Marketplace Fallback (Exact Match)
    // Runs only if the Smart Search found nothing or threw an error
    // ==========================================
    if ( ! $found_link ) {
        
        $search_url = "https://api.impact.com/Mediapartners/" . urlencode($sid) . "/Marketplace/Products?Query=Name~%27" . urlencode($keyword) . "%27&ProgramRelationship=MY_BRANDS&SortBy=RELEVANCE&PageSize=15";
         $search_response = wp_remote_get( $search_url, $args );

        if ( ! is_wp_error( $search_response ) && wp_remote_retrieve_response_code( $search_response ) == 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $search_response ), true );
            
            if ( ! empty( $body['Results'] ) ) {
                
                foreach ( $body['Results'] as $product ) {
                	
                	
                	// Filter by active merchant (Impact nests the ID deeply in this specific endpoint)
                    $campaign_id = isset($product['Offers'][0]['Program']['Id']) ? $product['Offers'][0]['Program']['Id'] : '';
                    if ($active_merchants !== 'all' && $campaign_id && !in_array($campaign_id, $active_merchants)) {
                        continue; // Skip if this merchant is unchecked
                    }
                    
                    // Safety check to ensure it has offers and the URI string
                    if ( !empty( $product['Offers'] ) && !empty( $product['Offers'][0]['GenerateShortUrlUri'] ) ) {
                        
                        $generate_uri = $product['Offers'][0]['GenerateShortUrlUri'];
                        
                        // FIX A: Change 'Vanity' to 'Regular' to bypass the 5,000 link limit
                        $generate_uri = str_replace('Type=Vanity', 'Type=Regular', $generate_uri);
                        
                        // FIX B: Dynamically replace the numeric Short ID with the Account SID 
                        $generate_uri = preg_replace('~^/Mediapartners/[^/]+/~', '/Mediapartners/' . urlencode($sid) . '/', $generate_uri);
                        
                        $link_api_url = "https://api.impact.com" . $generate_uri;
                        
                        // FIX C: Generating a link requires a POST request
                        $link_response = wp_remote_post( $link_api_url, $args );
                        
                        if ( ! is_wp_error( $link_response ) && wp_remote_retrieve_response_code( $link_response ) == 200 ) {
                            
                            $link_body = json_decode( wp_remote_retrieve_body( $link_response ), true );
                            $final_tracking_link = '';
                            
                            if ( !empty($link_body['TrackingURL']) ) {
                                $final_tracking_link = $link_body['TrackingURL'];
                            } elseif ( !empty($link_body['TrackingUrl']) ) {
                                $final_tracking_link = $link_body['TrackingUrl'];
                            } elseif ( !empty($link_body['TrackingLink']) ) {
                                $final_tracking_link = $link_body['TrackingLink'];
                            }
                            
                            if ( ! empty($final_tracking_link) ) {
                                
                                $found = 0;
                                foreach($alinks as $aa) {
                                    if(isset($aa->link) && $final_tracking_link == $aa->link) $found = 1;
                                    if(isset($aa->url) && $final_tracking_link == $aa->url) $found = 1;      
                                }
                                
                                if($found != 1) {
                                    $alink = new stdClass();
                                    $alink->key = $keyword;
                                    $alink->url = $final_tracking_link;
                                    $impactlinks[] = $alink;
                                    $found_link = true;
                                    break; 
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // ==========================================
    // STEP 3: Fallback to Text Ads (Local PHP Search)
    // Runs only if Step 1 and Step 2 found nothing
    // ==========================================
    if ( ! $found_link ) {
        
        $ads_url = "https://api.impact.com/Mediapartners/" . urlencode($sid) . "/Ads?Type=TEXT_LINK&PageSize=100";
        $ads_response = wp_remote_get( $ads_url, $args );

        if ( ! is_wp_error( $ads_response ) && wp_remote_retrieve_response_code( $ads_response ) == 200 ) {
            $ads_body = json_decode( wp_remote_retrieve_body( $ads_response ), true );
            
            if ( ! empty( $ads_body['Ads'] ) ) {
                foreach ( $ads_body['Ads'] as $ad ) {
                    
                    // Filter by active merchant
                    $campaign_id = isset($ad['CampaignId']) ? $ad['CampaignId'] : '';
                    if ($active_merchants !== 'all' && $campaign_id && !in_array($campaign_id, $active_merchants)) {
                        continue; // Skip if this merchant is unchecked
                    }
                    
                    // PHP String Match: Check if keyword is in the Ad Name or Description
                    $name_match = ( !empty($ad['Name']) && stripos( $ad['Name'], $keyword ) !== false );
                    $desc_match = ( !empty($ad['Description']) && stripos( $ad['Description'], $keyword ) !== false );

                    if ( $name_match || $desc_match ) {
                        
                        if ( ! empty( $ad['TrackingLink'] ) ) {
                            $link = $ad['TrackingLink'];
                            
                            // Duplicate Check
                            $found = 0;
                            foreach($alinks as $aa) {
                                if(isset($aa->link) && $link == $aa->link) $found = 1;
                                if(isset($aa->url) && $link == $aa->url) $found = 1;      
                            }
                            
                            if($found != 1) {
                                $alink = new stdClass();
                                $alink->key = $keyword;
                                $alink->url = $link; 
                                $impactlinks[] = $alink;
                                $found_link = true;
                                break; // Stop after finding the first valid Ad link
                            }
                        }
                    }
                }
            }
        }
    }

    // Always increment the quota tracker to prevent infinite loops on failing keywords
    $nrk++;
    sleep(1); 

    return array(
        'links'  => $impactlinks,
        'widget' => $awidgetcode,
        'nrk'    => $nrk,
        'nrw'    => $nrw
    );
}


// Optional: Register the module if you use the class-based structure
// $aalImpact = new aalModule('impact','Impact Links',4);
// $aalModules[] = $aalImpact;
// $aalImpact->aalModuleHook('content','aalImpactDisplay');


add_action( 'admin_init', 'aal_impact_register_settings' );

function aal_impact_register_settings() { 
    register_setting( 'aal_impact_settings', 'aal_impactsid' );
    register_setting( 'aal_impact_settings', 'aal_impacttoken' );
    register_setting( 'aal_impact_settings', 'aal_impactactive' );
    register_setting( 'aal_impact_settings', 'aal_impact_active_merchants' );
}

function aalImpactDisplay() {
    
    ?>

<script type="text/javascript">
var aal_impact_nonce = '<?php echo wp_create_nonce("aal_impact_nonce"); ?>';

function aal_impact_validate() {
    var isActive = document.querySelector('input[name="aal_impactactive"]').checked;
    
    if(isActive) {
        if(!document.aal_impactform.aal_impactsid.value) { 
            alert("Please add your Impact Account SID"); 
            return false; 
        }
        if(!document.aal_impactform.aal_impacttoken.value) { 
            alert("Please add your Impact Auth Token"); 
            return false; 
        }
    }

    // Process the dynamic checkboxes before saving
    var checkboxes = document.querySelectorAll('.aal_impact_merchant_cb');
    if (checkboxes.length > 0) {
        var checkedIds = [];
        var allChecked = true;
        
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                checkedIds.push(cb.value);
            } else {
                allChecked = false;
            }
        });

        // If all are checked, or none are checked, we default to 'all' to save DB space
        if (allChecked || checkedIds.length === 0) {
            document.getElementById('aal_impact_active_merchants').value = 'all';
        } else {
            document.getElementById('aal_impact_active_merchants').value = JSON.stringify(checkedIds);
        }
    }
    return true;
}

jQuery(document).ready(function($) {
    
    var container = $('#aal_impact_merchants_container');
    if (container.length === 0) return;

    // Check if user has entered credentials yet
    var sid = $('input[name="aal_impactsid"]').val();
    var token = $('input[name="aal_impacttoken"]').val();

    if (!sid || !token) {
        container.html('<p style="color: #666;"><em>Save your Account SID and Auth Token to load your approved merchants.</em></p>');
        return;
    }

    // Show loading state
    container.html('<p><em>Connecting to Impact.com to load your approved merchants...</em></p>');

    // Call the PHP AJAX handler
    $.ajax({
        url: ajaxurl, // WordPress globally defines this in the admin dashboard
        type: 'POST',
        data: {
            action: 'aal_impact_get_merchants',
            security: aal_impact_nonce // From the nonce we created in Step 2
        },
        success: function(response) {
            if (response.success) {
                var campaigns = response.data.campaigns;
                var saved = response.data.saved; // This will be an array of IDs, or the string 'all'
                
                if (campaigns.length === 0) {
                    container.html('<p>We connected successfully, but no approved campaigns were found in your Impact account.</p>');
                    return;
                }

                var html = '<br/><h4>Select Merchants to Extract Links From:</h4><br/>';

                // Loop through all campaigns and build the checkboxes
                $.each(campaigns, function(index, camp) {
                    
                    // If 'all', everything is checked. If it's an array, check if this ID is in it.
                    var isChecked = (saved === 'all' || $.inArray(camp.id, saved) !== -1) ? 'checked' : '';
                    
                    html += '<label style="display:inline-block; width: 30%; margin-bottom: 10px;">';
                    html += '<input type="checkbox" class="aal_impact_merchant_cb" value="' + camp.id + '" ' + isChecked + ' /> ';
                    html += camp.name;
                    html += '</label>';
                });

                container.html(html);
                
            } else {
                container.html('<p style="color:red;">Error loading merchants: ' + response.data + '</p>');
            }
        },
        error: function() {
            container.html('<p style="color:red;">A server error occurred while trying to fetch Impact merchants.</p>');
        }
    });
});


</script>
    
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
        
    <h2>Impact.com Links</h2>
    <br /><br />
        
    Once you add your Impact Account SID and Auth Token, the plugin will automatically search your approved Impact merchants to find relevant products for your keyphrases.<br />
    <br /><br />
                
<div class="aal_general_settings">
    <form method="post" action="options.php" name="aal_impactform" onsubmit="return aal_impact_validate();"> 
<?php
        settings_fields( 'aal_impact_settings' );
        do_settings_sections('aal_impact_settings_display');
?>

    <span class="aal_label">Enable Impact.com:</span> 
    <input type="checkbox" name="aal_impactactive" value="1" <?php checked( '1', get_option('aal_impactactive'), 'checked'); ?> /> Activate Impact module
    <br /><br />

    <h4>Impact API settings:</h4>
    <br />
    <span class="aal_label">Account SID:</span> 
    <input class="aal_big_input" type="text" name="aal_impactsid" value="<?php echo esc_attr(get_option('aal_impactsid')); ?>" />
    <br /><br />
    
    <span class="aal_label">Auth Token:</span> 
    <input class="aal_big_input" type="text" name="aal_impacttoken" value="<?php echo esc_attr(get_option('aal_impacttoken')); ?>" />
    <br /><br />
    
    <p>You can get your Account SID and Auth Token from your Impact.com dashboard. Go to <strong>Settings -> Technical -> API</strong> and click "Create Access Token". Make sure that you add the required scopes to your API key, by checking the boxes for "Ads", "Campaigns", "Catalogs" and Products when generating the token.</p>  
    <br /><br />
    
    <input type="hidden" name="aal_impact_active_merchants" id="aal_impact_active_merchants" value="<?php echo esc_attr(get_option('aal_impact_active_merchants', 'all')); ?>" />
    
    <div id="aal_impact_merchants_container" style="margin-bottom: 20px;">
        </div>

<?php
    submit_button('Save');
    echo '</form></div>';
    
    update_option('aal_settings_updated', time());  
?>
    <a href="<?php echo admin_url('admin.php?page=aal_apimanagement'); ?>" class="button button-primary">Back to API Management</a>

<?php
    echo '</div>';
}













add_action('wp_ajax_aal_impact_get_merchants', 'aal_impact_get_merchants_ajax');
function aal_impact_get_merchants_ajax() {
    check_ajax_referer('aal_impact_nonce', 'security');
    
    // Ensure only authorized admins can trigger this
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $sid = trim(get_option('aal_impactsid'));
    $token = trim(get_option('aal_impacttoken'));

    if (empty($sid) || empty($token)) {
        wp_send_json_error('Credentials missing');
    }

    $args = array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $sid . ':' . $token ),
            'Accept'        => 'application/json'
        )
    );

    $campaigns_url = "https://api.impact.com/Mediapartners/" . urlencode($sid) . "/Campaigns";
    $response = wp_remote_get($campaigns_url, $args);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        wp_send_json_error('Failed to fetch campaigns');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $campaigns = isset($body['Campaigns']) ? $body['Campaigns'] : array();

    // Fetch saved merchants
    $saved_merchants_option = get_option('aal_impact_active_merchants', 'all');
    $saved_merchants = ($saved_merchants_option !== 'all') ? json_decode($saved_merchants_option, true) : 'all';

    $active_campaign_ids = array();
    $output_campaigns = array();

    foreach ($campaigns as $camp) {
        $output_campaigns[] = array(
            'id'   => $camp['CampaignId'],
            'name' => $camp['CampaignName']
        );
        $active_campaign_ids[] = $camp['CampaignId'];
    }

    // Self-Cleaning Logic (Requirement #6)
    if (is_array($saved_merchants)) {
        $cleaned_merchants = array_intersect($saved_merchants, $active_campaign_ids);
        
        if (count($cleaned_merchants) === count($active_campaign_ids) || empty($cleaned_merchants)) {
            // If all matched merchants are active, or the array is empty, reset to 'all'
            update_option('aal_impact_active_merchants', 'all');
            $saved_merchants = 'all';
        } elseif (count($cleaned_merchants) !== count($saved_merchants)) {
            // Some outdated merchants were removed, update the DB instantly
            $saved_merchants = array_values($cleaned_merchants);
            update_option('aal_impact_active_merchants', json_encode($saved_merchants));
        }
    }

    wp_send_json_success(array(
        'campaigns' => $output_campaigns,
        'saved'     => $saved_merchants
    ));
}