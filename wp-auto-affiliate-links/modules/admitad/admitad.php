<?php
// File: /modules/admitad/admitad.php

$aalAdmitad = new aalModule('admitad','Admitad Links', 4);
$aalModules[] = $aalAdmitad;

$aalAdmitad->aalModuleHook('content','aalAdmitadDisplay');

add_action( 'admin_init', 'aal_admitad_register_settings' );

function aal_admitad_register_settings() { 
    register_setting( 'aal_admitad_settings', 'aal_admitad_client_id' );
    register_setting( 'aal_admitad_settings', 'aal_admitad_client_secret' );
    register_setting( 'aal_admitad_settings', 'aal_admitad_adspace_id' );
    register_setting( 'aal_admitad_settings', 'aal_admitadactive' );
    register_setting( 'aal_admitad_settings', 'aal_admitad_active_merchants' );
}



// Helper function to manage Admitad OAuth2 Token
// Helper function to manage Admitad OAuth2 Token
function aal_admitad_get_token() {
    $client_id = trim(get_option('aal_admitad_client_id'));
    $client_secret = trim(get_option('aal_admitad_client_secret'));

    if (empty($client_id) || empty($client_secret)) {
        return false;
    }

    $transient_name = 'aal_admitad_token_' . md5($client_id);
    $token = get_transient($transient_name);

    if (!$token) {
        $token_endpoint = 'https://api.admitad.com/token/';
        
        $token_args = array(
            'method'  => 'POST',
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'Content-Type'  => 'application/x-www-form-urlencoded'
            ),
            'body' => 'grant_type=client_credentials&client_id=' . urlencode($client_id) . '&scope=advcampaigns websites'
        );
        
        $token_req = wp_remote_post($token_endpoint, $token_args);
        
        if (!is_wp_error($token_req) && wp_remote_retrieve_response_code($token_req) === 200) {
            $token_body = json_decode(wp_remote_retrieve_body($token_req), true);
            
            if (!empty($token_body['access_token'])) {
                $token = $token_body['access_token'];
                // Token expires in 7 days, cache for 6 days (518400 secs)
                $expires_in = isset($token_body['expires_in']) ? intval($token_body['expires_in']) - 3600 : 518400;
                set_transient($transient_name, $token, $expires_in);
            }
        }
    }

    return $token;
}

// Unified AJAX Handler for Ad Spaces and Campaigns
add_action('wp_ajax_aal_admitad_get_data', 'aal_admitad_get_data_ajax');
function aal_admitad_get_data_ajax() {
    check_ajax_referer('aal_admitad_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    // 1. Get the Token
    $token = aal_admitad_get_token();
    if (!$token) {
        wp_send_json_error('Failed to generate OAuth token. Please check your Client ID and Secret.');
    }

    $args = array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json'
        )
    );

    // 2. Fetch Ad Spaces (Websites)
    $websites_url = "https://api.admitad.com/websites/?limit=100";
    $web_response = wp_remote_get($websites_url, $args);

    if (is_wp_error($web_response) || wp_remote_retrieve_response_code($web_response) != 200) {
        wp_send_json_error('Failed to fetch Ad Spaces from Admitad.');
    }

    $web_body = json_decode(wp_remote_retrieve_body($web_response), true);
    $websites = isset($web_body['results']) ? $web_body['results'] : array();

    $output_websites = array();
    foreach ($websites as $web) {
        $output_websites[] = array(
            'id'   => $web['id'],
            'name' => $web['name']
        );
    }

    // 3. Auto-Select Logic (If user has no ID saved, but only has 1 website, auto-save it)
    $adspace_id = trim(get_option('aal_admitad_adspace_id'));
    if (empty($adspace_id) && count($output_websites) == 1) {
        $adspace_id = $output_websites[0]['id'];
        update_option('aal_admitad_adspace_id', $adspace_id);
    }

    // 4. Fetch Campaigns if Ad Space is selected
    $output_campaigns = array();
    $saved_merchants = 'all';

    if (!empty($adspace_id)) {
        $campaigns_url = "https://api.admitad.com/advcampaigns/?website=" . urlencode($adspace_id) . "&connection_status=active&limit=500";
        $camp_response = wp_remote_get($campaigns_url, $args);
        

        if (!is_wp_error($camp_response) && wp_remote_retrieve_response_code($camp_response) == 200) {
            $camp_body = json_decode(wp_remote_retrieve_body($camp_response), true);
            $campaigns = isset($camp_body['results']) ? $camp_body['results'] : array();

            $active_campaign_ids = array();
            foreach ($campaigns as $camp) {
                $output_campaigns[] = array(
                    'id'   => $camp['id'],
                    'name' => $camp['name']
                );
                $active_campaign_ids[] = (string)$camp['id'];
            }

            // Self-Cleaning Logic
            $saved_merchants_option = get_option('aal_admitad_active_merchants', 'all');
            $saved_merchants = ($saved_merchants_option !== 'all') ? json_decode($saved_merchants_option, true) : 'all';

            if (is_array($saved_merchants)) {
                $saved_merchants = array_map('strval', $saved_merchants);
                $cleaned_merchants = array_intersect($saved_merchants, $active_campaign_ids);
                
                if (count($cleaned_merchants) === count($active_campaign_ids) || empty($cleaned_merchants)) {
                    update_option('aal_admitad_active_merchants', 'all');
                    $saved_merchants = 'all';
                } elseif (count($cleaned_merchants) !== count($saved_merchants)) {
                    $saved_merchants = array_values($cleaned_merchants);
                    update_option('aal_admitad_active_merchants', json_encode($saved_merchants));
                }
            }
        }
    }

    wp_send_json_success(array(
        'websites'   => $output_websites,
        'adspace_id' => $adspace_id,
        'campaigns'  => $output_campaigns,
        'saved'      => $saved_merchants
    ));
}


function aal_admitad_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks ) {
    
    $admitadactive = get_option('aal_admitadactive');
    $adspace_id = trim(get_option('aal_admitad_adspace_id'));
    
    $saved_merchants_option = get_option('aal_admitad_active_merchants', 'all');
    $active_merchants = ($saved_merchants_option !== 'all' && !empty($saved_merchants_option)) ? json_decode($saved_merchants_option, true) : 'all';

    // 1. Bail if module is inactive or Ad Space is missing
    if ( ! $admitadactive || empty($adspace_id) ) { 
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw); 
    }

    // 2. Fetch the cached OAuth2 Token
    $token = aal_admitad_get_token();
    if ( ! $token ) {
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw); 
    }

    $admitadlinks = array();
    $awidgetcode = array(); 
    $found_link = false;
    
    $args = array(
        'timeout'     => 15,
        'blocking'    => true,
        'headers'     => array(
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json'
        )
    );

    // ==========================================
    // CAMPAIGN MATCHING (Brand Name Fallback)
    // Admitad does not offer a live product search API. 
    // We match keywords against approved campaign names.
    // ==========================================
    $campaigns_url = "https://api.admitad.com/advcampaigns/?website=" . urlencode($adspace_id) . "&connection_status=active&limit=500";
    $camp_response = wp_remote_get( $campaigns_url, $args );
    
  //  print_r($camp_response); die();
    

    if ( ! is_wp_error( $camp_response ) && wp_remote_retrieve_response_code( $camp_response ) == 200 ) {
        $camp_body = json_decode( wp_remote_retrieve_body( $camp_response ), true );
        
        if ( ! empty( $camp_body['results'] ) ) {
            foreach ( $camp_body['results'] as $camp ) {
                
                // Filter by active merchant
                $campaign_id = isset($camp['id']) ? (string)$camp['id'] : '';
                if ($active_merchants !== 'all' && $campaign_id && !in_array($campaign_id, $active_merchants)) {
                    continue; // Skip if this merchant is unchecked
                }
                
                // PHP String Match: Check if keyword matches the Campaign Name
                $name_match = ( !empty($camp['name']) && stripos( $camp['name'], $keyword ) !== false );
                
                if ( $name_match ) {
                    
                    // We found a matching brand! Now we generate its tracking link.
                    $target_url = isset($camp['site_url']) ? $camp['site_url'] : '';
                    
                    if ( ! empty( $target_url ) ) {
                        // Call Admitad's Deeplink API to generate the monetized link
                        $deeplink_url = "https://api.admitad.com/deeplink/" . urlencode($adspace_id) . "/advcampaign/" . urlencode($campaign_id) . "/?ulp=" . urlencode($target_url);
                        $dl_response = wp_remote_get( $deeplink_url, $args );

                        if ( ! is_wp_error( $dl_response ) && wp_remote_retrieve_response_code( $dl_response ) == 200 ) {
                            $dl_body = json_decode( wp_remote_retrieve_body( $dl_response ), true );

                            if ( ! empty( $dl_body['deeplink'] ) ) {
                                $link = $dl_body['deeplink'];
                                
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
                                    $admitadlinks[] = $alink;
                                    $found_link = true;
                                    break; // Stop after finding the first valid match
                                }
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
        'links'  => $admitadlinks,
        'widget' => $awidgetcode,
        'nrk'    => $nrk,
        'nrw'    => $nrw
    );
}


function aalAdmitadDisplay() {
    ?>

<script type="text/javascript">
var aal_admitad_nonce = '<?php echo wp_create_nonce("aal_admitad_nonce"); ?>';

function aal_admitad_validate() {
    var isActive = document.querySelector('input[name="aal_admitadactive"]').checked;
    
    if(isActive) {
        if(!document.aal_admitadform.aal_admitad_client_id.value) { 
            alert("Please add your Admitad Client ID"); 
            return false; 
        }
        if(!document.aal_admitadform.aal_admitad_client_secret.value) { 
            alert("Please add your Admitad Client Secret"); 
            return false; 
        }

    }

    // Process the dynamic checkboxes before saving
    var checkboxes = document.querySelectorAll('.aal_admitad_merchant_cb');
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
            document.getElementById('aal_admitad_active_merchants').value = 'all';
        } else {
            document.getElementById('aal_admitad_active_merchants').value = JSON.stringify(checkedIds);
        }
    }
    return true;
}

jQuery(document).ready(function($) {
    
    var container = $('#aal_admitad_merchants_container');
    if (container.length === 0) return;

    var clientId = $('input[name="aal_admitad_client_id"]').val();
    var clientSecret = $('input[name="aal_admitad_client_secret"]').val();

    // FIXED: We removed adSpaceId from this check! 
    // We only need the Client ID and Secret to fetch the Ad Spaces.
    if (!clientId || !clientSecret) {
        container.html('<p style="color: #666;"><em>Save your Client ID and Client Secret to load your Ad Spaces.</em></p>');
        return;
    }

    container.html('<p><em>Connecting to Admitad to load your Ad Spaces and merchants...</em></p>');

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aal_admitad_get_data',
            security: aal_admitad_nonce
        },
        success: function(response) {
            if (response.success) {
                
                // 1. Populate the Ad Space Dropdown
                var websites = response.data.websites;
                var current_adspace = response.data.adspace_id;
                var $adspaceSelect = $('#aal_admitad_adspace_id');
                
                if (websites.length > 0) {
                    $adspaceSelect.empty();
                    $adspaceSelect.append('<option value="">-- Select an Ad Space --</option>');
                    $.each(websites, function(index, web) {
                        var isSelected = (web.id == current_adspace) ? 'selected' : '';
                        $adspaceSelect.append('<option value="' + web.id + '" ' + isSelected + '>' + web.name + '</option>');
                    });
                }

                // If user changes the Ad Space, prompt them to Save
                $adspaceSelect.off('change').on('change', function() {
                    container.html('<p style="color:#01579b;"><em>You changed your Ad Space. Please click <strong>Save</strong> below to load the merchants for this specific website.</em></p>');
                });

                // 2. Render Campaigns (if Ad Space is selected)
                var campaigns = response.data.campaigns;
                var saved = response.data.saved;
                
                if (!current_adspace) {
                    container.html('<p style="color: #666;"><em>Please select your Ad Space from the dropdown above and click Save to load your merchants.</em></p>');
                    return;
                }

                if (campaigns.length === 0) {
                    container.html('<p>We connected successfully, but no approved campaigns were found for this Ad Space.</p>');
                    return;
                }

                var html = '<br/><h4>Select Merchants to Extract Links From:</h4><br/>';

                $.each(campaigns, function(index, camp) {
                    var isChecked = (saved === 'all' || $.inArray(camp.id.toString(), saved) !== -1) ? 'checked' : '';
                    html += '<label style="display:inline-block; width: 30%; margin-bottom: 10px;">';
                    html += '<input type="checkbox" class="aal_admitad_merchant_cb" value="' + camp.id + '" ' + isChecked + ' /> ';
                    html += camp.name;
                    html += '</label>';
                });

                container.html(html);
                
            } else {
                container.html('<p style="color:red;">Error connecting to Admitad: ' + response.data + '</p>');
            }
        },
        error: function() {
            container.html('<p style="color:red;">A server error occurred while trying to communicate with Admitad.</p>');
        }
    });
});
</script>
    
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
        
    <h2>Admitad Links</h2>
    <br /><br />
        
    Once you add your API credentials, the plugin will automatically search your approved Admitad merchants to find relevant products for your keyphrases.<br />
    <br /><br />
                
<div class="aal_general_settings">
    <form method="post" action="options.php" name="aal_admitadform" onsubmit="return aal_admitad_validate();"> 
<?php
        settings_fields( 'aal_admitad_settings' );
        do_settings_sections('aal_admitad_settings_display');
?>

    <span class="aal_label">Enable Admitad:</span> 
    <input type="checkbox" name="aal_admitadactive" value="1" <?php checked( '1', get_option('aal_admitadactive'), 'checked'); ?> /> Activate Admitad module
    <br /><br />

    <h4>Admitad API settings:</h4>
    <br />
    <span class="aal_label">Client ID:</span> 
    <input class="aal_big_input" type="text" name="aal_admitad_client_id" value="<?php echo esc_attr(get_option('aal_admitad_client_id')); ?>" />
    <br /><br />
    
    <span class="aal_label">Client Secret:</span> 
    <input class="aal_big_input" type="password" name="aal_admitad_client_secret" value="<?php echo esc_attr(get_option('aal_admitad_client_secret')); ?>" autocomplete="new-password" />
    <br /><br />

<span class="aal_label">Ad Space (Website):</span> 
    <select name="aal_admitad_adspace_id" id="aal_admitad_adspace_id" class="aal_big_input">
        <option value="<?php echo esc_attr(get_option('aal_admitad_adspace_id')); ?>">
            <?php echo esc_attr(get_option('aal_admitad_adspace_id')) ? 'Current Ad Space ID: ' . esc_attr(get_option('aal_admitad_adspace_id')) : '-- Save API Keys to Load Ad Spaces --'; ?>
        </option>
    </select>
    
    <p>You can get your API credentials from your Admitad dashboard under <strong>Settings -> API and Apps</strong>. Your Ad Space ID can be found in the URL when viewing your website settings.</p>  
    <br /><br />
    
    <input type="hidden" name="aal_admitad_active_merchants" id="aal_admitad_active_merchants" value="<?php echo esc_attr(get_option('aal_admitad_active_merchants', 'all')); ?>" />
    
    <div id="aal_admitad_merchants_container" style="margin-bottom: 20px;">
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
?>