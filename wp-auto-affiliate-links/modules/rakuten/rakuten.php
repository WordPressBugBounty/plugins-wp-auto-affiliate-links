<?php
// File: /modules/rakuten/rakuten.php

$aalRakuten = new aalModule('rakuten', 'Rakuten Advertising Links', 5);
$aalModules[] = $aalRakuten;
$aalRakuten->aalModuleHook('content', 'aalRakutenDisplay');

function aal_rakuten_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks ) {
    
    $client_id = trim(get_option('aal_rakuten_clientid'));
    $client_secret = trim(get_option('aal_rakuten_secret'));
    $account_sid = trim(get_option('aal_rakutensid'));
    $rakuten_id = trim(get_option('aal_rakutenid')); // Used later for deep linking
    $is_active = get_option('aal_rakutenactive');
    $display_widget = get_option('aal_rakutendisplaywidget');
    $display_links = 1;

    // Bail if critical API credentials are missing
    if (!$is_active || empty($client_id) || empty($client_secret) || empty($account_sid)) { 
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw); 
    }

    $rakutenlinks = array();
    $awidgetcode = array(); 
    
    // 1. FETCH OR LOAD THE OAUTH BEARER TOKEN
    $transient_name = 'aal_rakuten_token_' . md5($client_id);
    $token = get_transient( $transient_name );

    if ( ! $token ) {
        $token_endpoint = 'https://api.linksynergy.com/token';
        $auth_string = base64_encode( $client_id . ':' . $client_secret );
        
        $token_args = array(
            'method'  => 'POST',
            'timeout' => 15,
            'headers' => array( 
                'Authorization' => 'Bearer ' . $auth_string,
                'Content-Type'  => 'application/x-www-form-urlencoded'
            ),
            'body'    => array(
                'scope' => $account_sid
            )
        );
        
        $token_req = wp_remote_post( $token_endpoint, $token_args );
        
        if ( ! is_wp_error( $token_req ) && wp_remote_retrieve_response_code( $token_req ) === 200 ) {
            $token_body = json_decode( wp_remote_retrieve_body( $token_req ) );
            if ( isset( $token_body->access_token ) ) {
                $token = $token_body->access_token;
                set_transient( $transient_name, $token, 3000 ); 
            }
        }
    }

    if ( ! $token ) {
        $nrk++; sleep(2);
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }

    // 2. CALL THE PRODUCT SEARCH API
    $api_url = "https://api.linksynergy.com/productsearch/1.0?keyword=" . urlencode( $keyword );
    $args = array(
        'method'      => 'GET',
        'timeout'     => 15,
        'blocking'    => true,
        'headers'     => array( 'Authorization' => 'Bearer ' . $token )
    );

    $api_response = wp_remote_get( $api_url, $args );
    
    if ( is_wp_error( $api_response ) ) {
        $nrk++; sleep(2);
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }
    
    $response_code = wp_remote_retrieve_response_code( $api_response );
    if ( $response_code === 401 ) delete_transient( $transient_name );
    
    if ( $response_code !== 200 ) {
        $nrk++; sleep(2);
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }
    
    $response_body = wp_remote_retrieve_body( $api_response );
    if ( empty( $response_body ) ) {
        $nrk++; sleep(2);
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }
    
    // 3. PARSE THE XML RESPONSE
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string( $response_body );
    
    if ( $xml === false || !isset( $xml->item ) ) {
        $nrk++; sleep(2);
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }
    
    // 4. EXTRACT THE AFFILIATE LINK
    foreach ( $xml->item as $item ) {
        if ( $display_widget && $nrw <= 2 && !empty($item->imageurl) ) {
            $awidget = new stdClass();
            $awidget->url   = (string) $item->linkurl;
            $awidget->image = (string) $item->imageurl;
            $awidget->title = (string) $item->productname;
            $awidget->price = (string) $item->price;
            $awidgetcode[]  = $awidget;
            $nrw++;
        }

        if ( $display_links && !empty( $item->linkurl ) ) {
            $link = (string) $item->linkurl;
            $found = 0;
            foreach($alinks as $aa) {
                if(isset($aa->link) && $link == $aa->link) $found = 1;
                if(isset($aa->url) && $link == $aa->url) $found = 1;      
            }
            if($found != 1) {
                $alink = new stdClass();
                $alink->key = $keyword;
                $alink->url = $link;
                $rakutenlinks[] = $alink;
                break;
            }
        }
    }

    $nrk++;
    sleep(2); 
    return array('links' => $rakutenlinks, 'widget' => $awidgetcode, 'nrk' => $nrk, 'nrw' => $nrw);
}

// 5. THE LEAN UI SETTINGS
add_action( 'admin_init', 'aal_rakuten_register_settings' );

function aal_rakuten_register_settings() { 
    register_setting( 'aal_rakuten_settings', 'aal_rakutenactive' );
    register_setting( 'aal_rakuten_settings', 'aal_rakuten_clientid' );
    register_setting( 'aal_rakuten_settings', 'aal_rakuten_secret' );
    register_setting( 'aal_rakuten_settings', 'aal_rakutensid' ); 
    register_setting( 'aal_rakuten_settings', 'aal_rakutenid' ); 
    register_setting( 'aal_rakuten_settings', 'aal_rakutendisplaywidget' );
}

function aalRakutenDisplay() {
?>
<script type="text/javascript">
function aal_rakuten_validate() {
    var isActive = document.querySelector('input[name="aal_rakutenactive"]').checked;
    if(isActive) {
        if(!document.aal_rakutenform.aal_rakutensid.value) { alert("Please add your 7-digit Rakuten Account SID"); return false; }
        if(!document.aal_rakutenform.aal_rakuten_clientid.value) { alert("Please add your Rakuten Client ID"); return false; }
        if(!document.aal_rakutenform.aal_rakuten_secret.value) { alert("Please add your Rakuten Client Secret"); return false; }
    }
    return true;
}
</script>
    
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
    <h2>Rakuten Advertising Affiliates</h2>
    <br /><br />
    Connect the plugin to the Rakuten Product Search API to automatically search your approved merchants' catalogs and generate monetized tracking links.<br />
    <br /><br />
                
<div class="aal_general_settings">
    <form method="post" action="options.php" name="aal_rakutenform" onsubmit="return aal_rakuten_validate();"> 
<?php
        settings_fields( 'aal_rakuten_settings' );
        do_settings_sections('aal_rakuten_settings_display');
?>

    <span class="aal_label">Enable Rakuten:</span> 
    <input type="checkbox" name="aal_rakutenactive" value="1" <?php checked( '1', get_option('aal_rakutenactive'), 'checked'); ?> /> Activate Rakuten module
    <br /><br />

    <h4>Rakuten API Authentication:</h4>
    <br />
    
    <span class="aal_label">Account SID (7-digits):</span> 
    <input class="aal_big_input" type="text" name="aal_rakutensid" value="<?php echo esc_attr(get_option('aal_rakutensid')); ?>" placeholder="e.g., 1234567" />
    <br /><br />

    <span class="aal_label">Client ID:</span> 
    <input class="aal_big_input" type="text" name="aal_rakuten_clientid" value="<?php echo esc_attr(get_option('aal_rakuten_clientid')); ?>" />
    <br /><br />
    
    <span class="aal_label">Client Secret:</span> 
    <input class="aal_big_input" type="text" name="aal_rakuten_secret" value="<?php echo esc_attr(get_option('aal_rakuten_secret')); ?>" />
    <br /><br />

    <h4>Manual Deep Linking Setup:</h4>
    <span class="aal_label">Tracking ID (11 chars):</span> 
    <input class="aal_big_input" type="text" name="aal_rakutenid" value="<?php echo esc_attr(get_option('aal_rakutenid')); ?>" placeholder="e.g., abc123def45" />
    <br /><br />

    <span class="aal_label">Display Product Widgets:</span> 
    <input type="checkbox" name="aal_rakutendisplaywidget" value="1" <?php checked( '1', get_option('aal_rakutendisplaywidget'), 'checked'); ?> /> Display Visual Product Boxes (if theme supports it)
    <br /><br />

<?php
    submit_button('Save');
    echo '</form></div>';
    update_option('aal_settings_updated', time());  
?>
    <a href="<?php echo admin_url('admin.php?page=aal_apimanagement'); ?>" class="button button-primary">Back to API Management</a>
</div>
<?php
}
?>