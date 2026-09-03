<?php
// File: /modules/twoperformant/twoperformant.php

$aalTwoPerformant = new aalModule('twoperformant', '2Performant Links', 5);
$aalModules[] = $aalTwoPerformant;
$aalTwoPerformant->aalModuleHook('content', 'aalTwoPerformantDisplay');

// 1. Link Generator Worker
function aal_twoperformant_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks ) {
    
    // 1. Module and Settings Validation
    $tp_active   = get_option('aal_twoperformant_active');
    $aff_code    = trim(get_option('aal_twoperformant_aff_code'));
    $merchant_id = trim(get_option('aal_twoperformant_merchant_id'));
    $search_url  = trim(get_option('aal_twoperformant_search_url'));
    
    if (!$tp_active || empty($aff_code) || empty($merchant_id) || empty($search_url)) {
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }

    // 2. Per-Post Hard Limit (Strictly 2 Links max per article)
    static $tp_links_displayed_this_run = 0;
    if ($tp_links_displayed_this_run >= 2) {
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
    }

    // 3. Construct the Target Search URL using the saved template
    $target_url = str_replace('{keyword}', urlencode($keyword), $search_url);

    // 4. Generate the 100% Static 2Performant Tracking Link
    $final_url = "https://event.2performant.com/events/click?ad_type=quicklink&aff_code={$aff_code}&unique={$merchant_id}&redirect_to=" . urlencode($target_url);

    $tp_links = array();
    $found = 0;
    
    // 5. Duplicate Check
    foreach($alinks as $aa) {
        if(isset($aa->link) && $final_url == $aa->link) $found = 1;
        if(isset($aa->url) && $final_url == $aa->url) $found = 1;      
    }
    
    if($found != 1) {
        $alink = new stdClass();
        $alink->key = $keyword;
        $alink->url = $final_url;
        $tp_links[] = $alink;
        
        // Increment the strict post-level counter
        $tp_links_displayed_this_run++;
    }

    return array(
        'links'  => $tp_links,
        'widget' => array(),
        'nrk'    => $nrk,
        'nrw'    => $nrw
    );
}

// 2. Register Settings
add_action('admin_init', 'aal_twoperformant_register_settings');
function aal_twoperformant_register_settings() { 
    register_setting('aal_twoperformant_settings', 'aal_twoperformant_active');
    register_setting('aal_twoperformant_settings', 'aal_twoperformant_aff_code');
    register_setting('aal_twoperformant_settings', 'aal_twoperformant_merchant_id');
    register_setting('aal_twoperformant_settings', 'aal_twoperformant_search_url');
    register_setting('aal_twoperformant_settings', 'aal_twoperformant_merchant_name');
}

// 3. Admin UI
function aalTwoPerformantDisplay() {
    $saved_merchant_id = get_option('aal_twoperformant_merchant_id');
    $saved_merchant_name = get_option('aal_twoperformant_merchant_name');
?>
<script type="text/javascript">
function aal_twoperformant_validate() {
    var isActive = document.querySelector('input[name="aal_twoperformant_active"]').checked;
    if(isActive) {
        if(!document.aal_tp_form.aal_twoperformant_aff_code.value) { 
            alert("Please add your 2Performant Affiliate Code (aff_code)."); 
            return false; 
        }
        if(!document.aal_tp_form.aal_twoperformant_merchant_id.value) {
            alert("Please search and select a merchant from the list.");
            return false;
        }
    }
    return true;
}

jQuery(document).ready(function($) {
    var $searchInput = $('#tp_merchant_search');
    var $hiddenId = $('#aal_twoperformant_merchant_id');
    var $hiddenName = $('#aal_twoperformant_merchant_name');
    var $hiddenUrl = $('#aal_twoperformant_search_url');
    var $resultsList = $('#tp_autocomplete_results');
    var $loadingMsg = $('#tp_loading_msg');
    
    var tp_merchants = null;

    // Fetch the JSON from your central server securely
    $.getJSON('https://autoaffiliatelinks.com/api/get_2performant_merchants.php')
        .done(function(data) {
            if(data.success && data.merchants) {
                tp_merchants = data.merchants;
                $loadingMsg.html('<span style="color:green;">&#10003; Merchant database loaded successfully.</span>');
                $searchInput.prop('disabled', false);
            }
        })
        .fail(function() {
            $loadingMsg.html('<span style="color:red;">Error: Could not connect to merchant database. Please refresh.</span>');
        });

    // Handle Smart Search Typing
    $searchInput.on('input', function() {
        if (!tp_merchants) return;
        
        var val = $(this).val().toLowerCase();
        $resultsList.empty();
        
        if (!val) {
            $resultsList.hide();
            return;
        }
        
        var matches = 0;
        $.each(tp_merchants, function(key, data) {
            if (data.name.toLowerCase().indexOf(val) > -1) {
                var $li = $('<li>').text(data.name)
                    .css({ padding: '8px 10px', cursor: 'pointer', borderBottom: '1px solid #eee' })
                    .data('id', data.unique)
                    .data('url', data.search)
                    .data('name', data.name);
                
                $li.on('mouseenter', function() { $(this).css('background', '#f0f0f1'); });
                $li.on('mouseleave', function() { $(this).css('background', '#fff'); });
                
                // When a merchant is clicked
                $li.on('click', function() {
                    $searchInput.val($(this).data('name'));
                    $hiddenId.val($(this).data('id'));
                    $hiddenUrl.val($(this).data('url'));
                    $hiddenName.val($(this).data('name'));
                    $resultsList.hide();
                });
                
                $resultsList.append($li);
                matches++;
                
                if (matches > 50) return false; // Limit visual results to 50 for performance
            }
        });
        
        if (matches > 0) {
            $resultsList.show();
        } else {
            $resultsList.hide();
        }
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#tp_merchant_search_container').length) {
            $resultsList.hide();
        }
    });
});
</script>

<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
    <h2>2Performant Links (Romania)</h2>
    <br />
    
    <div style="background: #e1f5fe; color: #01579b; padding: 15px; border-left: 5px solid #03a9f4; margin-bottom: 20px;">
        <strong>How to find your 2Performant Keys:</strong><br>
        1. Log into your 2Performant account and go to <strong>Tools -> Quicklinks</strong>.<br>
        2. Generate a Quicklink for any approved merchant. You will get a long URL.<br>
        3. Look at the URL structure. It will look like this: <code>...<strong>aff_code=a1b2c3d4e</strong>&unique=987654321...</code><br>
        4. Your <strong>Affiliate Code</strong> is the 9-character string after <code>aff_code=</code>.
    </div>

    <div class="aal_general_settings">
        <form method="post" action="options.php" name="aal_tp_form" onsubmit="return aal_twoperformant_validate();"> 
<?php
        settings_fields('aal_twoperformant_settings');
        do_settings_sections('aal_twoperformant_settings_display');
?>

        <span class="aal_label">Enable 2Performant:</span> 
        <input type="checkbox" name="aal_twoperformant_active" value="1" <?php checked('1', get_option('aal_twoperformant_active'), 'checked'); ?> /> Activate module
        <br /><br />

        <h4>Account Settings:</h4>
        <span class="aal_label">Your Affiliate Code:</span> 
        <input class="aal_big_input" type="text" name="aal_twoperformant_aff_code" value="<?php echo esc_attr(get_option('aal_twoperformant_aff_code')); ?>" placeholder="e.g. a1b2c3d4e" />
        <br /><em>(The string from the aff_code parameter)</em>
        <br /><br />
        
        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;" />

        <h4>Merchant Selection:</h4>
        <p>This list contains all advertisers on the 2Performant network. <strong>Please ensure you choose an advertiser that you are actively approved for.</strong></p>
        
        <div id="tp_merchant_search_container" style="position:relative; display:inline-block;">
            <span class="aal_label">Primary Merchant:</span>
            <input type="text" id="tp_merchant_search" class="aal_big_input" autocomplete="off" placeholder="Start typing to search..." value="<?php echo esc_attr($saved_merchant_name); ?>" disabled />
            <span id="tp_loading_msg" style="margin-left: 10px; font-size: 12px; color: #666;"><em>Loading merchant database...</em></span>
            
            <input type="hidden" name="aal_twoperformant_merchant_id" id="aal_twoperformant_merchant_id" value="<?php echo esc_attr($saved_merchant_id); ?>" />
            <input type="hidden" name="aal_twoperformant_merchant_name" id="aal_twoperformant_merchant_name" value="<?php echo esc_attr($saved_merchant_name); ?>" />
            <input type="hidden" name="aal_twoperformant_search_url" id="aal_twoperformant_search_url" value="<?php echo esc_attr(get_option('aal_twoperformant_search_url')); ?>" />
            
            <ul id="tp_autocomplete_results" style="display:none; position:absolute; left: 160px; top: 32px; width: 340px; max-height: 250px; overflow-y: auto; background: #fff; border: 1px solid #ccc; margin: 0; padding: 0; z-index: 100; box-shadow: 0 4px 6px rgba(0,0,0,0.1); list-style: none;">
            </ul>
        </div>
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