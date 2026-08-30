<?php

$aalEtsy = new aalModule('etsy', 'Etsy Affiliate Links', 16);
$aalModules[] = $aalEtsy;

$aalEtsy->aalModuleHook('content', 'aalEtsyDisplay');

add_action( 'admin_init', 'aal_etsy_register_settings' );

function aal_etsy_register_settings() { 
    register_setting( 'aal_etsy_settings', 'aal_etsyactive' );
    register_setting( 'aal_etsy_settings', 'aal_etsyrakutenid' );
}

function aalEtsyDisplay() {
    $etsy_active = get_option( 'aal_etsyactive' );
    $etsy_rakutenid = get_option( 'aal_etsyrakutenid', '' );
?>

<script type="text/javascript">
function aal_etsy_validate() {
    var form = document.forms['aal_etsyform'];
    if (form['aal_etsyactive'].checked && !form['aal_etsyrakutenid'].value) { 
        alert("Please enter your Rakuten Publisher ID to activate Etsy links."); 
        return false; 
    }
    return true;
}
</script>
    
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
        
    <h2>Etsy Affiliate Links Settings</h2>
    <br /><br />
    Connect the plugin to Etsy via Rakuten Advertising to automatically generate monetized product links.
    <br /><br />
        
    <div class="aal_general_settings">
        <form method="post" action="options.php" name="aal_etsyform" onsubmit="return aal_etsy_validate();"> 
            <?php
                settings_fields( 'aal_etsy_settings' );
                do_settings_sections( 'aal_etsy_settings_display' );
            ?>

            <span class="aal_label">Enable Etsy:</span> 
            <input type="checkbox" name="aal_etsyactive" value="1" <?php checked( '1', $etsy_active ); ?> /> Activate Etsy module
            <br /><br />

            <span class="aal_label">Rakuten Publisher ID:</span> 
            <input class="aal_big_input" type="text" name="aal_etsyrakutenid" value="<?php echo esc_attr( $etsy_rakutenid ); ?>" />
            <br />
            <p class="description" style="margin-left: 150px; font-size: 12px; color: #666;">
                Enter your 11-character Rakuten Advertising Publisher ID.<br />
                This ID will be used to automatically track your Etsy commissions.
            </p>
            <br />

            <?php
                submit_button( 'Save' );
                echo '</form></div>';
                
                update_option( 'aal_settings_updated', time() );
            ?>
            
        <br />
        <a href="<?php echo admin_url( 'admin.php?page=aal_apimanagement' ); ?>" class="button button-primary">Back to API Management</a>
    </div>
</div>

<?php
}