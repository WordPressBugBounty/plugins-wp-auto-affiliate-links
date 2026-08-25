<?php
// File: /modules/aliexpress/aliexpress.php

$aalAliExpress = new aalModule('aliexpress', 'AliExpress Links', 4);
$aalModules[] = $aalAliExpress;

$aalAliExpress->aalModuleHook('content', 'aalAliExpressDisplay');

function aal_aliexpress_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks ) {

    
    $app_key = trim(get_option('aal_aliexpress_appkey'));
    $app_secret = trim(get_option('aal_aliexpress_appsecret'));
    $tracking_id = trim(get_option('aal_aliexpress_trackingid'));
    $is_active = get_option('aal_aliexpressactive');
    $endpoint_setting = get_option('aal_aliexpress_endpoint', 'query'); // Default to 'query'

    // If missing credentials or inactive, bail immediately
    if (!$is_active || empty($app_key) || empty($app_secret)) { 
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw); 
    }

    $aliexpresslinks = array();
    $awidgetcode = array(); 
    
    $gateway_url = 'https://api-sg.aliexpress.com/sync';
    $api_method = ($endpoint_setting === 'smartmatch') ? 'aliexpress.affiliate.product.smartmatch' : 'aliexpress.affiliate.product.query';

    // 1. Define System Parameters
    $sys_params = array(
        'method'      => $api_method,
        'app_key'     => $app_key,
        'timestamp'   => date('Y-m-d H:i:s'),
        'format'      => 'json',
        'v'           => '2.0',
        'sign_method' => 'md5'
    );

    // 2. Define Application Parameters
    $app_params = array(
        'keywords'        => $keyword,
        'target_currency' => 'USD',
        'target_language' => 'EN',
        'page_no'         => '1'
    );

    if ( !empty($tracking_id) ) {
        $app_params['tracking_id'] = $tracking_id;
    }

    // Endpoint-specific parameters
    if ( $endpoint_setting === 'smartmatch' ) {
        $app_params['device_id'] = 'aal_engine'; // Required dummy ID for smartmatch
    } else {
        $app_params['page_size'] = '5';
        $app_params['sort'] = 'LAST_VOLUME_DESC'; // Force best-selling items to top for basic query
    }

    // 3. Merge and Generate the MD5 Cryptographic Signature
    $api_params = array_merge($sys_params, $app_params);
    ksort($api_params); 

    $sign_string = $app_secret;
    foreach ($api_params as $key => $value) {
        $sign_string .= $key . $value;
    }
    $sign_string .= $app_secret; 
    $api_params['sign'] = strtoupper(md5($sign_string));

    // 4. Execute the Request
    $args = array(
        'timeout'     => 15,
        'blocking'    => true,
        // FIX: Force PHP to encode spaces as %20 (RFC 3986) to prevent AliExpress API search failures
        'body'        => http_build_query($api_params, '', '&', PHP_QUERY_RFC3986)
    );

    $response = wp_remote_post( $gateway_url, $args );

    if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        // The JSON root key dynamically changes based on the method used
        $response_key = str_replace('.', '_', $api_method) . '_response';
        
        if ( isset( $body[$response_key]['resp_result']['result']['products']['product'] ) ) {
            
            $products = $body[$response_key]['resp_result']['result']['products']['product'];
            
            foreach ( $products as $item ) {
                
                // We MUST use promotion_link. product_detail_url does not track commissions securely.
                if ( ! empty( $item['promotion_link'] ) ) {
                    $link = $item['promotion_link'];
                    
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
                        $aliexpresslinks[] = $alink;
                        break; // Stop after finding the first valid link
                    }
                }
            }
        }
    }

    // Always increment the quota tracker to prevent infinite loops
    $nrk++;
    sleep(1); 

    return array(
        'links'  => $aliexpresslinks,
        'widget' => $awidgetcode,
        'nrk'    => $nrk,
        'nrw'    => $nrw
    );
}

add_action( 'admin_init', 'aal_aliexpress_register_settings' );

function aal_aliexpress_register_settings() { 
    register_setting( 'aal_aliexpress_settings', 'aal_aliexpressactive' );
    register_setting( 'aal_aliexpress_settings', 'aal_aliexpress_appkey' );
    register_setting( 'aal_aliexpress_settings', 'aal_aliexpress_appsecret' );
    register_setting( 'aal_aliexpress_settings', 'aal_aliexpress_trackingid' );
    register_setting( 'aal_aliexpress_settings', 'aal_aliexpress_endpoint' );
}

function aalAliExpressDisplay() {
    ?>

<script type="text/javascript">
function aal_aliexpress_validate() {
    var isActive = document.querySelector('input[name="aal_aliexpressactive"]').checked;
    
    if(isActive) {
        if(!document.aal_aliexpressform.aal_aliexpress_appkey.value) { 
            alert("Please add your AliExpress AppKey"); 
            return false; 
        }
        if(!document.aal_aliexpressform.aal_aliexpress_appsecret.value) { 
            alert("Please add your AliExpress App Secret"); 
            return false; 
        }
    }
    return true;
}
</script>
    
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
        
    <h2>AliExpress Affiliates</h2>
    <br /><br />
        
    Connect the plugin to the AliExpress Open Platform to automatically search the global marketplace and generate monetized tracking links for your keywords.<br />
    <br /><br />
                
<div class="aal_general_settings">
    <form method="post" action="options.php" name="aal_aliexpressform" onsubmit="return aal_aliexpress_validate();"> 
<?php
        settings_fields( 'aal_aliexpress_settings' );
        do_settings_sections('aal_aliexpress_settings_display');
?>

    <span class="aal_label">Enable AliExpress:</span> 
    <input type="checkbox" name="aal_aliexpressactive" value="1" <?php checked( '1', get_option('aal_aliexpressactive'), 'checked'); ?> /> Activate AliExpress module
    <br /><br />

    <h4>AliExpress API Settings:</h4>
    <br />
    <span class="aal_label">AppKey:</span> 
    <input class="aal_big_input" type="text" name="aal_aliexpress_appkey" value="<?php echo esc_attr(get_option('aal_aliexpress_appkey')); ?>" />
    <br /><br />
    
    <span class="aal_label">App Secret:</span> 
    <input class="aal_big_input" type="text" name="aal_aliexpress_appsecret" value="<?php echo esc_attr(get_option('aal_aliexpress_appsecret')); ?>" />
    <br /><br />

    <span class="aal_label">Tracking ID (Optional):</span> 
    <input class="aal_big_input" type="text" name="aal_aliexpress_trackingid" value="<?php echo esc_attr(get_option('aal_aliexpress_trackingid')); ?>" placeholder="Leave blank to use default account ID" />
    <br /><br />
    
    <h4>Search Algorithm:</h4>
    <span class="aal_label">API Endpoint:</span> 
    <select name="aal_aliexpress_endpoint" id="aal_aliexpress_endpoint">
        <option value="query" <?php selected( get_option('aal_aliexpress_endpoint', 'query'), 'query' ); ?>>Standard Product Search (Recommended for beginners)</option>
        <option value="smartmatch" <?php selected( get_option('aal_aliexpress_endpoint', 'query'), 'smartmatch' ); ?>>AI Smart Match (Requires Advanced API Approval)</option>
    </select>
    <p style="margin-left: 160px; max-width: 600px; color: #666;"><em>Note: The <strong>Standard</strong> search filters by highest sales volume to prevent keyword stuffing. The <strong>Smart Match</strong> uses AliExpress's AI recommendation engine for better relevance, but you must manually apply for the "Advanced API" permission group in your developer console to use it.</em></p>
    <br /><br />

    <div style="background: #fff; padding: 15px; border: 1px solid #ddd; max-width: 750px;">
        <strong>How to get your API Credentials:</strong>
        <ol style="margin-top: 10px; margin-bottom: 0;">
            <li>Go to the <a href="https://console.aliexpress.com/" target="_blank">AliExpress Open Platform Console</a> and log in with your affiliate account.</li>
            <li>Click <strong>Create App</strong> and select the <strong>Affiliates API</strong> category.</li>
            <li>Fill out the form. <em>(For the "Callback URL", just enter your website's homepage URL)</em>.</li>
            <li>Once created, click <strong>Apply Online</strong> to submit it for instant auto-approval.</li>
            <li>Copy your <strong>AppKey</strong> and <strong>App Secret</strong> from the App Overview page and paste them above.</li>
        </ol>
    </div>
    <br /><br />

<?php
    submit_button('Save');
    echo '</form></div>';
    
    update_option('aal_settings_updated', time());  
?>
    <a href="<?php echo admin_url('admin.php?page=aal_apimanagement'); ?>" class="button button-primary">Back to API Management</a>

<?php
    echo '</div>';
}