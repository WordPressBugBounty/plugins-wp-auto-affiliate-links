<?php


$aalTradeDoubler = new aalModule('tradedoubler','TradeDoubler Links', 4);
$aalModules[] = $aalTradeDoubler;

$aalTradeDoubler->aalModuleHook('content','aalTradedoublerDisplay');

// 1. Register Tradedoubler Settings
add_action( 'admin_init', 'aal_tradedoubler_register_settings' );
function aal_tradedoubler_register_settings() { 
    register_setting( 'aal_tradedoubler_settings', 'aal_tradedoubleractive' );
    register_setting( 'aal_tradedoubler_settings', 'aal_tradedoubler_token' );
    register_setting( 'aal_tradedoubler_settings', 'aal_tradedoubler_active_feeds' ); // Stores our feeds
}

// 2. Display the UI
function aalTradedoublerDisplay() { 
?>
<script type="text/javascript">
var aal_tradedoubler_nonce = '<?php echo wp_create_nonce("aal_tradedoubler_nonce"); ?>';

function aal_tradedoubler_validate() {
    var isActive = document.querySelector('input[name="aal_tradedoubleractive"]').checked;
    
    if(isActive) {
        if(!document.aal_tradedoublerform.aal_tradedoubler_token.value) { 
            alert("Please add your Tradedoubler Token"); 
            return false; 
        }
    }

    // Process the dynamic checkboxes before saving
    var checkboxes = document.querySelectorAll('.aal_tradedoubler_feed_cb');
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
            document.getElementById('aal_tradedoubler_active_feeds').value = 'all';
        } else {
            document.getElementById('aal_tradedoubler_active_feeds').value = JSON.stringify(checkedIds);
        }
    }
    return true;
}
</script>

<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
    <h2>Tradedoubler Links</h2>
    <br /><br />
    
    <div class="aal_general_settings">
        <form method="post" action="options.php" name="aal_tradedoublerform" onsubmit="return aal_tradedoubler_validate();"> 
<?php
        settings_fields('aal_tradedoubler_settings');
        do_settings_sections('aal_tradedoubler_settings_display');
?>
        <span class="aal_label">Enable Tradedoubler:</span> 
        <input type="checkbox" name="aal_tradedoubleractive" value="1" <?php checked( '1', get_option('aal_tradedoubleractive'), 'checked'); ?> /> Activate Tradedoubler module
        <br /><br />

        <h4>Tradedoubler API Settings:</h4>
        <br />
        <span class="aal_label">Publisher Token:</span> 
        <input class="aal_big_input" type="text" name="aal_tradedoubler_token" value="<?php echo esc_attr(get_option('aal_tradedoubler_token')); ?>" />
        <br /><br />

        <p>You can get your Publisher Token from your Tradedoubler dashboard under <strong>Settings -> Tokens</strong>. Look for the token associated with the "PRODUCTS" API.</p>  
        <br /><br />

        <input type="hidden" name="aal_tradedoubler_active_feeds" id="aal_tradedoubler_active_feeds" value="<?php echo esc_attr(get_option('aal_tradedoubler_active_feeds', 'all')); ?>" />

        <div id="aal_tradedoubler_feeds_container" style="margin-bottom: 20px;">
        </div>

<?php
        submit_button('Save');
        echo '</form></div>';
?>
        <a href="<?php echo admin_url('admin.php?page=aal_apimanagement'); ?>" class="button button-primary">Back to API Management</a>
    </div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var container = $('#aal_tradedoubler_feeds_container');
    if (container.length === 0) return;

    var token = $('input[name="aal_tradedoubler_token"]').val();

    if (!token) {
        container.html('<p style="color: #666;"><em>Save your Tradedoubler Token to load your available product feeds.</em></p>');
        return;
    }

    container.html('<p><em>Connecting to Tradedoubler to load your product feeds...</em></p>');

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aal_tradedoubler_get_feeds',
            security: aal_tradedoubler_nonce
        },
        success: function(response) {
            if (response.success) {
                var feeds = response.data.feeds;
                var saved = response.data.saved; 
                
                if (feeds.length === 0) {
                    container.html('<p>We connected successfully, but no active product feeds were found for this token.</p>');
                    return;
                }

                var html = '<br/><h4>Select Product Feeds to Extract Links From:</h4><br/>';

                $.each(feeds, function(index, feed) {
                    var isChecked = (saved === 'all' || $.inArray(feed.id.toString(), saved) !== -1) ? 'checked' : '';
                    
                    html += '<label style="display:inline-block; width: 30%; margin-bottom: 10px; vertical-align: top;">';
                    html += '<input type="checkbox" class="aal_tradedoubler_feed_cb" value="' + feed.id + '" ' + isChecked + ' /> ';
                    html += feed.name;
                    html += '</label>';
                });

                container.html(html);
                
            } else {
                container.html('<p style="color:red;">Error loading feeds: ' + response.data + '</p>');
            }
        },
        error: function() {
            container.html('<p style="color:red;">A server error occurred while trying to fetch Tradedoubler feeds.</p>');
        }
    });
});
</script>
<?php
} // End of aalTradedoublerDisplay function


// 3. AJAX Handler to Fetch Feeds
add_action('wp_ajax_aal_tradedoubler_get_feeds', 'aal_tradedoubler_get_feeds_ajax');
function aal_tradedoubler_get_feeds_ajax() {
    check_ajax_referer('aal_tradedoubler_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $token = trim(get_option('aal_tradedoubler_token'));
    if (empty($token)) {
        wp_send_json_error('Token missing');
    }

    // Connect to the Product Feeds API endpoint
    $feeds_url = "https://api.tradedoubler.com/1.0/productFeeds.json?token=" . urlencode($token);
    
    $args = array(
        'timeout' => 15,
        'headers' => array(
            'Accept' => 'application/json'
        )
    );

    $response = wp_remote_get($feeds_url, $args);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        wp_send_json_error('Failed to fetch feeds from Tradedoubler API.');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    // The JSON response contains a 'feeds' array
    $feeds = isset($body['feeds']) ? $body['feeds'] : array();

    $saved_feeds_option = get_option('aal_tradedoubler_active_feeds', 'all');
    $saved_feeds = ($saved_feeds_option !== 'all') ? json_decode($saved_feeds_option, true) : 'all';

    $active_feed_ids = array();
    $output_feeds = array();

    foreach ($feeds as $feed) {
        $feed_id = (string)$feed['feedId'];
        $output_feeds[] = array(
            'id'   => $feed_id,
            'name' => $feed['name']
        );
        $active_feed_ids[] = $feed_id;
    }

    // Self-Cleaning Logic (Removes feeds from the DB if they were disconnected on Tradedoubler)
    if (is_array($saved_feeds)) {
        $saved_feeds = array_map('strval', $saved_feeds);
        $cleaned_feeds = array_intersect($saved_feeds, $active_feed_ids);
        
        if (count($cleaned_feeds) === count($active_feed_ids) || empty($cleaned_feeds)) {
            update_option('aal_tradedoubler_active_feeds', 'all');
            $saved_feeds = 'all';
        } elseif (count($cleaned_feeds) !== count($saved_feeds)) {
            $saved_feeds = array_values($cleaned_feeds);
            update_option('aal_tradedoubler_active_feeds', json_encode($saved_feeds));
        }
    }

    wp_send_json_success(array(
        'feeds' => $output_feeds,
        'saved' => $saved_feeds
    ));
}