<?php

$aalCj = new aalModule('cj', 'Commission Junction Links', 6);
$aalModules[] = $aalCj;

$aalCj->aalModuleHook('content', 'aalCjDisplay');

add_action('admin_init', 'aal_cj_register_settings');

function aal_cj_register_settings() { 
    register_setting( 'aal_cj_settings', 'aal_cjactive' );
    register_setting( 'aal_cj_settings', 'aal_cj_pat' );
    register_setting( 'aal_cj_settings', 'aal_cj_cid' );
    register_setting( 'aal_cj_settings', 'aal_cj_pid' );
    register_setting( 'aal_cj_settings', 'aal_cj_active_merchants' );
}

function aalCjDisplay() {
?>
<script type="text/javascript">
var aal_cj_nonce = '<?php echo wp_create_nonce("aal_cj_nonce"); ?>';

function aal_cj_validate() {
    var isActive = document.querySelector('input[name="aal_cjactive"]').checked;
    if(isActive) {
        if(!document.aal_cjform.aal_cj_pat.value) { alert("Please add your CJ Personal Access Token"); return false; }
        if(!document.aal_cjform.aal_cj_cid.value) { alert("Please add your CJ Company ID (CID)"); return false; }
        if(!document.aal_cjform.aal_cj_pid.value) { alert("Please add your CJ Website ID (PID)"); return false; }
    }

    var checkboxes = document.querySelectorAll('.aal_cj_merchant_cb');
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

        if (allChecked || checkedIds.length === 0) {
            document.getElementById('aal_cj_active_merchants').value = 'all';
        } else {
            document.getElementById('aal_cj_active_merchants').value = JSON.stringify(checkedIds);
        }
    }
    return true;
}

jQuery(document).ready(function($) {
    var container = $('#aal_cj_merchants_container');
    if (container.length === 0) return;

    var pat = $('input[name="aal_cj_pat"]').val();
    var cid = $('input[name="aal_cj_cid"]').val();

    if (!pat || !cid) {
        container.html('<p style="color: #666;"><em>Save your CJ Personal Access Token and Company ID to load your approved advertisers.</em></p>');
        return;
    }

    container.html('<p><em>Connecting to CJ Affiliate to load your approved advertisers...</em></p>');

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aal_cj_get_merchants',
            security: aal_cj_nonce
        },
        success: function(response) {
            if (response.success) {
                var advertisers = response.data.advertisers;
                var saved = response.data.saved;
                
                if (advertisers.length === 0) {
                    container.html('<p>We connected successfully, but no approved advertisers were found in your CJ account.</p>');
                    return;
                }

                var html = '<br/><h4>Select Advertisers to Extract Links From:</h4><br/>';

                $.each(advertisers, function(index, adv) {
                    var isChecked = (saved === 'all' || $.inArray(adv.id, saved) !== -1) ? 'checked' : '';
                    html += '<label style="display:inline-block; width: 30%; margin-bottom: 10px;">';
                    html += '<input type="checkbox" class="aal_cj_merchant_cb" value="' + adv.id + '" ' + isChecked + ' /> ';
                    html += adv.name;
                    html += '</label>';
                });
                container.html(html);
            } else {
                container.html('<p style="color:red;">Error loading advertisers: ' + response.data + '</p>');
            }
        },
        error: function() {
            container.html('<p style="color:red;">A server error occurred while trying to fetch CJ advertisers.</p>');
        }
    });
});
</script>
    
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
    <h2>Commission Junction (CJ) Links</h2>
    <br /><br />
    Once you add your API credentials, the plugin will automatically search your approved CJ catalogs and text links to find relevant products for your keyphrases.<br />
    <br /><br />
                
<div class="aal_general_settings">
    <form method="post" action="options.php" name="aal_cjform" onsubmit="return aal_cj_validate();"> 
<?php
        settings_fields( 'aal_cj_settings' );
        do_settings_sections('aal_cj_settings_display');
?>

    <span class="aal_label">Enable CJ Affiliate:</span> 
    <input type="checkbox" name="aal_cjactive" value="1" <?php checked( '1', get_option('aal_cjactive'), 'checked'); ?> /> Activate CJ module
    <br /><br />

    <h4>CJ API settings:</h4>
    <br />
    <span class="aal_label">Personal Access Token:</span> 
    <input class="aal_big_input" type="password" name="aal_cj_pat" value="<?php echo esc_attr(get_option('aal_cj_pat')); ?>" autocomplete="new-password" />
    <br /><br />
    
    <span class="aal_label">Company ID (CID):</span> 
    <input class="aal_big_input" type="text" name="aal_cj_cid" value="<?php echo esc_attr(get_option('aal_cj_cid')); ?>" />
    <br /><br />

    <span class="aal_label">Website ID (PID):</span> 
    <input class="aal_big_input" type="text" name="aal_cj_pid" value="<?php echo esc_attr(get_option('aal_cj_pid')); ?>" />
    <br /><br />
    
    <p>You can get your <strong>Personal Access Token</strong> from your CJ Developer Portal. Your <strong>Company ID (CID)</strong> and <strong>Website ID (PID)</strong> can be found in your standard CJ Account dashboard (under Accounts > Web / Properties).</p>  
    <br /><br />
    
    <input type="hidden" name="aal_cj_active_merchants" id="aal_cj_active_merchants" value="<?php echo esc_attr(get_option('aal_cj_active_merchants', 'all')); ?>" />
    
    <div id="aal_cj_merchants_container" style="margin-bottom: 20px;"></div>

<?php
    submit_button('Save');
    echo '</form></div>';
    
    update_option('aal_settings_updated', time());  
?>
    <a href="<?php echo admin_url('admin.php?page=aal_apimanagement'); ?>" class="button button-primary">Back to API Management</a>
</div>
<?php
}

add_action('wp_ajax_aal_cj_get_merchants', 'aal_cj_get_merchants_ajax');
function aal_cj_get_merchants_ajax() {
    check_ajax_referer('aal_cj_nonce', 'security');
    
    if (!current_user_can('manage_options')) { wp_send_json_error('Unauthorized'); }

    $pat = trim(get_option('aal_cj_pat'));
    $cid = trim(get_option('aal_cj_cid'));

    if (empty($pat) || empty($cid)) { wp_send_json_error('Credentials missing'); }

    $args = array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Bearer ' . $pat,
            'Accept'        => 'application/xml' // FIXED: CJ REST APIs only accept XML
        )
    );

    // Fetch Joined Advertisers
    $advertisers_url = "https://advertiser-lookup.api.cj.com/v2/advertiser-lookup?advertiser-ids=joined&requestor-cid=" . urlencode($cid);
    $response = wp_remote_get($advertisers_url, $args);
    
  //  print_r($response);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        $error_msg = is_wp_error($response) ? $response->get_error_message() : 'HTTP Code: ' . wp_remote_retrieve_response_code($response);
        wp_send_json_error('Failed to fetch advertisers from CJ API. ' . $error_msg);
    }

    $body = wp_remote_retrieve_body($response);
    
    // Parse the XML safely
    $xml = @simplexml_load_string($body);

    $saved_merchants_option = get_option('aal_cj_active_merchants', 'all');
    $saved_merchants = ($saved_merchants_option !== 'all') ? json_decode($saved_merchants_option, true) : 'all';

    $active_campaign_ids = array();
    $output_advertisers = array();

    // Navigate the XML structure: <cj-api><advertisers><advertiser>
    if ($xml && isset($xml->advertisers->advertiser)) {
        foreach ($xml->advertisers->advertiser as $adv) {
            $adv_id = (string)$adv->{'advertiser-id'}; // Hyphenated XML nodes require this syntax
            $output_advertisers[] = array(
                'id'   => $adv_id,
                'name' => (string)$adv->{'advertiser-name'}
            );
            $active_campaign_ids[] = $adv_id;
        }
    }

    // Self-Cleaning Logic
    if (is_array($saved_merchants)) {
        $saved_merchants = array_map('strval', $saved_merchants);
        $cleaned_merchants = array_intersect($saved_merchants, $active_campaign_ids);
        
        if (count($cleaned_merchants) === count($active_campaign_ids) || empty($cleaned_merchants)) {
            update_option('aal_cj_active_merchants', 'all');
            $saved_merchants = 'all';
        } elseif (count($cleaned_merchants) !== count($saved_merchants)) {
            $saved_merchants = array_values($cleaned_merchants);
            update_option('aal_cj_active_merchants', json_encode($saved_merchants));
        }
    }

    wp_send_json_success(array(
        'advertisers' => $output_advertisers,
        'saved'       => $saved_merchants
    ));
}



function aal_cj_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks ) {
    $pat = trim(get_option('aal_cj_pat'));
    $cid = trim(get_option('aal_cj_cid'));
    $pid = trim(get_option('aal_cj_pid'));
    $cjactive = get_option('aal_cjactive');
    
    $saved_merchants_option = get_option('aal_cj_active_merchants', 'all');
    $active_merchants = ($saved_merchants_option !== 'all' && !empty($saved_merchants_option)) ? json_decode($saved_merchants_option, true) : 'all';

    // Bail if missing credentials
    if(!$cjactive || empty($pat) || empty($cid) || empty($pid)) { 
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw); 
    }

    $cjlinks = array();
    $awidgetcode = array(); 
    $found_link = false;
    
    // --- NEW: Handle the "All" Merchants GraphQL limitation ---
    if ($active_merchants === 'all') {
        $transient_key = 'aal_cj_joined_ids_' . $cid;
        $active_merchants = get_transient($transient_key);
        
        if (false === $active_merchants) {
            $adv_url = "https://advertiser-lookup.api.cj.com/v2/advertiser-lookup?advertiser-ids=joined&requestor-cid=" . urlencode($cid);
            $adv_args = array( 'headers' => array( 'Authorization' => 'Bearer ' . $pat, 'Accept' => 'application/xml' ) );
            $adv_response = wp_remote_get($adv_url, $adv_args);
            $active_merchants = array();
            if (!is_wp_error($adv_response) && wp_remote_retrieve_response_code($adv_response) == 200) {
                $xml = @simplexml_load_string(wp_remote_retrieve_body($adv_response));
                if ($xml && isset($xml->advertisers->advertiser)) {
                    foreach ($xml->advertisers->advertiser as $adv) {
                        $active_merchants[] = (string)$adv->{'advertiser-id'};
                    }
                }
                set_transient($transient_key, $active_merchants, 86400); // Cache for 24 hours
            }
        }
    }

    $args = array(
        'timeout'     => 15,
        'blocking'    => true,
        'headers'     => array(
            'Authorization' => 'Bearer ' . $pat,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json'
        )
    );

    // Prepare partnerIds based on user checklist or joined cache
    $partner_ids_string = '';
    $partner_ids_comma = 'joined';
    
    if (is_array($active_merchants) && count($active_merchants) > 0) {
        $partner_ids_string = 'partnerIds: ["' . implode('", "', $active_merchants) . '"],'; // GraphQL Format
        $partner_ids_comma = implode(',', $active_merchants); // REST Format
    }

    // ==========================================
    // STEP 1: Product Search (GraphQL API)
    // ==========================================
    $graphql_url = "https://ads.api.cj.com/query";
    
    // FIX: Changed 'link' to 'clickUrl' to get the monetized tracking link
    $graphql_query = '{ shoppingProducts(companyId: "' . $cid . '", ' . $partner_ids_string . ' keywords: "' . esc_attr($keyword) . '", limit: 5) { resultList { id title description price { amount currency } clickUrl } } }';
    
    $graphql_args = $args;
    $graphql_args['body'] = json_encode(array('query' => $graphql_query));

    $catalog_response = wp_remote_post( $graphql_url, $graphql_args );

    if ( ! is_wp_error( $catalog_response ) && wp_remote_retrieve_response_code( $catalog_response ) == 200 ) {
        $cat_body = json_decode( wp_remote_retrieve_body( $catalog_response ), true );
        
        if ( ! empty( $cat_body['data']['shoppingProducts']['resultList'] ) ) {
            foreach ( $cat_body['data']['shoppingProducts']['resultList'] as $item ) {
                
                // FIX: Extracting clickUrl instead of link
                if ( ! empty( $item['clickUrl'] ) ) {
                    $link = $item['clickUrl'];
                    
                    $found = 0;
                    foreach($alinks as $aa) {
                        if(isset($aa->link) && $link == $aa->link) $found = 1;
                        if(isset($aa->url) && $link == $aa->url) $found = 1;      
                    }
                    
                    if($found != 1) {
                        $alink = new stdClass();
                        $alink->key = $keyword;
                        $alink->url = $link;
                        $cjlinks[] = $alink;
                        $found_link = true;
                        break; 
                    }
                }
            }
        }
    }

    // ==========================================
    // STEP 2: Link Search API (REST Fallback)
    // ==========================================
    if ( ! $found_link ) {
        
        $search_url = "https://link-search.api.cj.com/v2/link-search?website-id=" . urlencode($pid) . "&advertiser-ids=" . urlencode($partner_ids_comma) . "&keywords=" . urlencode($keyword) . "&records-per-page=15";
        
        $rest_args = $args;
        unset($rest_args['Content-Type']); // Remove JSON content-type
        $rest_args['headers']['Accept'] = 'application/xml'; // ENFORCE XML FOR THIS ENDPOINT
        
        $search_response = wp_remote_get( $search_url, $rest_args );

        if ( ! is_wp_error( $search_response ) && wp_remote_retrieve_response_code( $search_response ) == 200 ) {
            $body = wp_remote_retrieve_body( $search_response );
            
            // Parse XML safely
            $xml = @simplexml_load_string($body);
            
            // Navigate the XML structure: <cj-api><links><link>
            if ( $xml && isset( $xml->links->link ) ) {
                foreach ( $xml->links->link as $link_item ) {
                    
                    $click_url = (string)$link_item->clickUrl;
                    
                    if ( ! empty( $click_url ) ) {
                        $link = $click_url;
                        
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
                            $cjlinks[] = $alink;
                            $found_link = true;
                            break; 
                        }
                    }
                }
            }
        }
    }

    $nrk++;
    sleep(1); 

    return array(
        'links'  => $cjlinks,
        'widget' => $awidgetcode,
        'nrk'    => $nrk,
        'nrw'    => $nrw
    );
}