<?php
// File: /modules/profitshare/profitshare.php

$aalProfitshare = new aalModule('profitshare', 'Profitshare Links', 4);
$aalModules[] = $aalProfitshare;

$aalProfitshare->aalModuleHook('content', 'aalProfitshareDisplay');

/**
 * Helper function to handle strict Profitshare HMAC Authentication
 */
function aal_profitshare_api_request($method, $endpoint, $body = null) {
    $api_user = trim(get_option('aal_profitshare_user'));
    $api_key  = trim(get_option('aal_profitshare_key'));
    
    if (empty($api_user) || empty($api_key)) {
        return false;
    }

    $date = gmdate('D, d M Y H:i:s T');
    $route = trim($endpoint, '/'); 
    
    // The exact Signature String format: Method + Route + / + User + Date
    $signature_string = strtoupper($method) . $route . '/' . $api_user . $date;
    $auth_hash = hash_hmac('sha1', $signature_string, $api_key);

    $args = array(
        'timeout' => 15,
        'method'  => strtoupper($method),
        'headers' => array(
            'X-PS-Client' => $api_user,
            'X-PS-Accept' => 'json', // FIXED: Must be exactly 'json'
            'X-PS-Auth'   => $auth_hash,
            'Date'        => $date
        )
    );

    if ($body && strtoupper($method) === 'POST') {
        $args['body'] = $body;
    }

    $url = "https://api.profitshare.ro/" . $route . '/';
    
    if (strtoupper($method) === 'POST') {
        $response = wp_remote_post($url, $args);
    } else {
        $response = wp_remote_get($url, $args);
    }

    return $response;
}


function aal_profitshare_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks ) {
	
	
	// B. Per-Post Hard Limit (Strictly 2 Links max per article, whether Cache or API)
    static $ps_links_displayed_this_run = 0;
    if ($ps_links_displayed_this_run >= 2) {
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }
    
    // 1. Module and Settings Validation
    $ps_active = get_option('aal_profitshare_active');
    $adv_id    = trim(get_option('aal_profitshare_advertiser_id'));
    
    if (!$ps_active || empty($adv_id)) {
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }
    
    
    // Define the dictionary mapping Profitshare Advertiser IDs to their specific Search URLs
$profitshare_search_urls = array(
    '35'     => 'https://www.emag.ro/search/{keyword}',
    '56983'  => 'https://www.rubyfashion.ro/cauta/?q={keyword}',
    '58221'  => 'https://vegis.ro/cautare/?q={keyword}',
    '58867'  => 'https://www.educlass.ro/cautare.html?q={keyword}',
    '59438'  => 'https://www.pcmadd.com/cautare?q={keyword}',
    '61047'  => 'https://www.libris.ro/rezultate-cautare?q={keyword}',
    '61861'  => 'https://profitshare.ro/', // Network Homepage
    '62479'  => 'https://www.abdcomputer.ro/cautare?q={keyword}',
    '62570'  => 'https://perfectbijoux.ro/?s={keyword}&post_type=product',
    '63454'  => 'https://www.priveboutique.net/cauta?q={keyword}',
    '67678'  => 'https://www.citgrup.ro/cautare.html?q={keyword}',
    '68331'  => 'https://iconicul.ro/?s={keyword}&post_type=product',
    '69959'  => 'https://www.mycloset.ro/catalogsearch/result/?q={keyword}',
    '71041'  => 'https://www.hiris.ro/cautare?q={keyword}',
    '71765'  => 'https://conectoo.com/', // B2B SaaS
    '74774'  => 'https://mindblower.ro/?s={keyword}&post_type=product',
    '81396'  => 'https://www.sportpartner.ro/cautare/?q={keyword}',
    '83901'  => 'https://www.itgalaxy.ro/cauta/?q={keyword}',
    '87557'  => 'https://www.dwyn.ro/cauta/{keyword}',
    '88017'  => 'https://techstar.ro/cautare?controller=search&s={keyword}',
    '88324'  => 'https://www.vonmag.ro/cauta?q={keyword}',
    '88736'  => 'https://www.fashiondays.ro/search/?q={keyword}',
    '96348'  => 'https://alecoair.ro/cautare?q={keyword}',
    '98562'  => 'https://seku.ro/?s={keyword}&post_type=product',
    '100816' => 'https://www.emobili.ro/cautare?q={keyword}',
    '103131' => 'https://www.vexio.ro/cautare/?q={keyword}',
    '103212' => 'https://www.daedalusonline.eu/', // Survey Site
    '111470' => 'https://case-smart.ro/?s={keyword}&post_type=product',
    '112145' => 'https://www.scufita-rosie.ro/cautare?q={keyword}',
    '115529' => 'https://www.vesa.ro/?s={keyword}',
    '118697' => 'https://dalisticq-shop.com/search?q={keyword}',
    '118702' => 'https://watch24.ro/?s={keyword}&post_type=product',
    '124336' => 'https://www.anvelope-oferte.ro/cautare?q={keyword}',
    '124829' => 'https://mathaus.ro/search?text={keyword}',
    '127683' => 'https://www.dualstore.ro/cautare?q={keyword}',
    '127700' => 'https://hostico.ro/domenii/?domain={keyword}',
    '128052' => 'https://www.pint.ro/cauta?q={keyword}',
    '130078' => 'https://magazinuldegene.ro/?s={keyword}&post_type=product',
    '130510' => 'https://www.contakt.ro/cautare?q={keyword}',
    '132216' => 'https://www.geekmall.ro/cautare?s={keyword}',
    '133049' => 'https://vapetronic.ro/?s={keyword}&post_type=product',
    '134190' => 'https://www.decolandia.ro/cautare?q={keyword}',
    '138584' => 'https://www.forit.ro/cautare/?q={keyword}',
    '140338' => 'https://axi-card.ro/', // Finance
    '141061' => 'https://creditprime.ro/', // Finance
    '141953' => 'https://www.parmashop.ro/cauta/?q={keyword}',
    '142963' => 'https://hotpick.ro/?s={keyword}&post_type=product',
    '148979' => 'https://www.fornello.ro/cautare?q={keyword}',
    '149286' => 'https://startco.ro/', // Legal / B2B
    '149514' => 'https://www.karcher.com/ro/search.html?query={keyword}',
    '150390' => 'https://navigatiiandroid.ro/?s={keyword}&post_type=product',
    '150852' => 'https://www.sole.ro/cautare?q={keyword}',
    '159248' => 'https://unicorn-naturals.ro/?s={keyword}&post_type=product',
    '160977' => 'https://www.exclusive-home.ro/cautare?q={keyword}',
    '163078' => 'https://www.coffeepoint.ro/cautare?q={keyword}',
    '163312' => 'https://www.depozitsolar.ro/?s={keyword}&post_type=product',
    '165505' => 'https://anvelino.ro/cautare?q={keyword}',
    '166230' => 'https://streamstore.ro/?s={keyword}&post_type=product',
    '166234' => 'https://novodoors.ro/?s={keyword}&post_type=product',
    '166235' => 'https://evrik.ro/?s={keyword}&post_type=product',
    '166563' => 'https://startdecor.ro/?s={keyword}&post_type=product',
    '167474' => 'https://giftspot.ro/?s={keyword}&post_type=product'
);

if (!isset($profitshare_search_urls[$adv_id])) {
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }
    
    $adv_search_template = $profitshare_search_urls[$adv_id];

    // ==========================================================
    // 3. GENERATE THE TARGET URL FIRST (The New Cache Strategy)
    // ==========================================================
    $target_url = str_replace('{keyword}', urlencode($keyword), $adv_search_template);

    // 4. Load Global Dictionary Cache & Hash the Target URL
    $ps_cache = get_option('aal_profitshare_links_cache', array());
    $cache_key = md5($target_url);

    $ps_links = array();
    $found_link = false;
    $final_url = '';

    // 5. Check Cache (Lightning Fast)
    if (isset($ps_cache[$cache_key])) {
        $final_url = $ps_cache[$cache_key];
        $found_link = true;
    } 
    else {
        // 6. CACHE MISS: Enforce Limits before calling API
        
        // A. Global Hard Limit (200 Links)
        if (count($ps_cache) >= 200) {
            return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
        }

// C. Hard API Call Limit (Stop hitting endpoint after 300 calls to protect account)
        $api_calls_made = (int) get_option('aal_profitshare_api_calls', 0);
        if ($api_calls_made >= 300) {
            return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
        }

        // 7. Generate Link via Profitshare API
        $body = array(
            'name'          => 'AAL: ' . $keyword, 
            'url'           => $target_url,
            'advertiser_id' => $adv_id
        );

        // Increment the hard counter right before we make the request
        update_option('aal_profitshare_api_calls', $api_calls_made + 1, 'no');

        $response = aal_profitshare_api_request('POST', '/affiliate-links/', $body);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
            $response_body = wp_remote_retrieve_body($response);
            
            // Try parsing as JSON first
            $body_json = json_decode($response_body, true);
            
            // Robust parsing: Handle XML if Profitshare forces it, otherwise handle JSON
            if (strpos($response_body, '<?xml') !== false) {
                $xml = @simplexml_load_string($response_body);
                if ($xml && isset($xml->resul->ps_url)) {
                    $final_url = (string) $xml->resul->ps_url;
                }
            } elseif (!empty($body_json)) {
                // If JSON is returned successfully, target the exact ps_url key
                if (isset($body_json['result']['ps_url'])) {
                    $final_url = $body_json['result']['ps_url'];
                } elseif (isset($body_json['result']['resul']['ps_url'])) {
                    $final_url = $body_json['result']['resul']['ps_url'];
                } elseif (isset($body_json['result'][0]['ps_url'])) {
                    $final_url = $body_json['result'][0]['ps_url'];
                }
            }
            
            if (!empty($final_url)) {
                // Save to Dictionary & Database (No Autoload)
                $ps_cache[$cache_key] = $final_url;
                update_option('aal_profitshare_links_cache', $ps_cache, 'no');
                
               // $ps_links_generated_this_run++;
                $found_link = true;
            }
        }
        
        // Prevent API rate limit slamming
        $nrk++;
        sleep(1);
    }

    // 8. Build Link Object if found
    if ($found_link && !empty($final_url)) {
        
        // Duplicate Check against existing links in post
        $found = 0;
        foreach($alinks as $aa) {
            if(isset($aa->link) && $final_url == $aa->link) $found = 1;
            if(isset($aa->url) && $final_url == $aa->url) $found = 1;      
        }
        
		if($found != 1) {
            $alink = new stdClass();
            $alink->key = $keyword;
            $alink->url = $final_url;
            $ps_links[] = $alink;
            
            // Increment the strict post-level counter
            $ps_links_displayed_this_run++;
        }
    }

    return array(
        'links'  => $ps_links,
        'widget' => array(),
        'nrk'    => $nrk,
        'nrw'    => $nrw
    );
}

// Register Settings
add_action('admin_init', 'aal_profitshare_register_settings');
function aal_profitshare_register_settings() { 
    register_setting('aal_profitshare_settings', 'aal_profitshare_user');
    register_setting('aal_profitshare_settings', 'aal_profitshare_key');
    register_setting('aal_profitshare_settings', 'aal_profitshare_active');
    register_setting('aal_profitshare_settings', 'aal_profitshare_advertiser_id');
}

// Settings UI
function aalProfitshareDisplay() {
    $ps_cache = get_option('aal_profitshare_links_cache', array());
    $cache_count = count($ps_cache);
    ?>

<script type="text/javascript">
var aal_ps_nonce = '<?php echo wp_create_nonce("aal_ps_nonce"); ?>';

function aal_profitshare_validate() {
    var isActive = document.querySelector('input[name="aal_profitshare_active"]').checked;
    if(isActive) {
        if(!document.aal_profitshare_form.aal_profitshare_user.value) { 
            alert("Please add your Profitshare API User."); 
            return false; 
        }
        if(!document.aal_profitshare_form.aal_profitshare_key.value) { 
            alert("Please add your Profitshare API Key."); 
            return false; 
        }
    }
    return true;
}

jQuery(document).ready(function($) {
    var container = $('#aal_ps_advertisers_container');
    if (container.length === 0) return;

    var api_user = $('input[name="aal_profitshare_user"]').val();
    var api_key = $('input[name="aal_profitshare_key"]').val();

    if (!api_user || !api_key) {
        container.html('<p style="color: #666;"><em>Save your API User and Key to load the advertiser list.</em></p>');
        return;
    }

    container.html('<p><em>Connecting to Profitshare to load advertisers...</em></p>');

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aal_profitshare_get_advertisers',
            security: aal_ps_nonce
        },
        success: function(response) {
            if (response.success) {
                var advs = response.data.advertisers;
                var saved_id = response.data.saved_id;
                
                var html = '<select name="aal_profitshare_advertiser_id" id="ps_adv_select">';
                html += '<option value="">-- Select Primary Advertiser --</option>';
                
                $.each(advs, function(index, adv) {
                    var isSelected = (adv.id == saved_id) ? 'selected' : '';
                    html += '<option value="' + adv.id + '" ' + isSelected + '>' + adv.name + '</option>';
                });
                html += '</select>';
                
                container.html(html);
                
            } else {
                container.html('<p style="color:red;">Error loading advertisers. Please check your API keys.</p>');
            }
        },
        error: function() {
            container.html('<p style="color:red;">A server error occurred while connecting to Profitshare.</p>');
        }
    });
});
</script>
    
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
    <h2>Profitshare Links (Romania)</h2>
    <br />

    <?php if ($cache_count >= 200): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-left: 5px solid #f5c6cb; margin-bottom: 20px;">
        <strong>API Hard Limit Reached (200 Links)</strong><br>
        For usability and performance safety, Auto Affiliate Links has stopped generating new tracking URLs in your Profitshare account. The plugin will continue to serve the <?php echo $cache_count; ?> links already generated from local memory. If you need to raise this limit for a larger website, please contact support.
    </div>
    <?php endif; ?>

    <div style="background: #fff3cd; color: #856404; padding: 15px; border-left: 5px solid #ffeeba; margin-bottom: 20px;">
        <strong>Disclaimer:</strong> Every time a new keyword is found, this module generates a permanent tracking link directly inside your Profitshare dashboard. To protect your account from clutter, the plugin limits generation to 2 links per article, and 200 links total. Generated links cannot be deleted by this plugin; they can only be deleted manually from your Profitshare account.
    </div>

<div class="aal_general_settings">
    <form method="post" action="options.php" name="aal_profitshare_form" onsubmit="return aal_profitshare_validate();"> 
<?php
        settings_fields('aal_profitshare_settings');
        do_settings_sections('aal_profitshare_settings_display');
?>

    <span class="aal_label">Enable Profitshare:</span> 
    <input type="checkbox" name="aal_profitshare_active" value="1" <?php checked('1', get_option('aal_profitshare_active'), 'checked'); ?> /> Activate module
    <br /><br />

    <h4>Profitshare API Credentials:</h4>
    <span class="aal_label">API User:</span> 
    <input class="aal_big_input" type="text" name="aal_profitshare_user" value="<?php echo esc_attr(get_option('aal_profitshare_user')); ?>" />
    <br /><br />
    
    <span class="aal_label">API Key:</span> 
    <input class="aal_big_input" type="text" name="aal_profitshare_key" value="<?php echo esc_attr(get_option('aal_profitshare_key')); ?>" />
    <br /><br />
    
    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;" />

    <h4>Advertiser Settings:</h4>
    <p>Select your <strong>Primary Advertiser</strong>. All automatically generated search links will point to this merchant.</p>
    
    <span class="aal_label">Primary Advertiser:</span>
    <div id="aal_ps_advertisers_container" style="display:inline-block;"></div>
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

// AJAX Handler to fetch advertisers list securely
add_action('wp_ajax_aal_profitshare_get_advertisers', 'aal_profitshare_get_advertisers_ajax');
function aal_profitshare_get_advertisers_ajax() {
    check_ajax_referer('aal_ps_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $response = aal_profitshare_api_request('GET', '/affiliate-advertisers/');
    
    //print_r($response); die();

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        wp_send_json_error('Failed to fetch advertisers.');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $advertisers = isset($body['result']) ? $body['result'] : array();

    $output = array();
    foreach ($advertisers as $adv) {
        $output[] = array(
            'id'   => $adv['id'],
            'name' => $adv['name']
        );
    }

    wp_send_json_success(array(
        'advertisers' => $output,
        'saved_id'    => get_option('aal_profitshare_advertiser_id')
    ));
}