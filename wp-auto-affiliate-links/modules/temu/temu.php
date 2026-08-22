<?php

$aalTemu = new aalModule('temu', 'Temu Affiliate Links', 15);
$aalModules[] = $aalTemu;

$aalTemu->aalModuleHook('content', 'aalTemuDisplay');

add_action( 'admin_init', 'aal_temu_register_settings' );

function aal_temu_register_settings() { 
    register_setting( 'aal_temu_settings', 'aal_temunetwork' );
    register_setting( 'aal_temu_settings', 'aal_temuid' );
    register_setting( 'aal_temu_settings', 'aal_temuawinmid' ); // New setting for country-specific Merchant ID
}

function aalTemuDisplay() {
    $selected_network = get_option( 'aal_temunetwork', 'direct' );
    $temu_id = get_option( 'aal_temuid', '' );
    $temu_awinmid = get_option( 'aal_temuawinmid', '' );
?>

<script type="text/javascript">
function aal_temu_toggle_fields() {
    var networkSelect = document.getElementById('aal_temunetwork_select');
    var awinContainer = document.getElementById('aal_temu_awin_container');
    
    if (networkSelect.value === 'awin') {
        awinContainer.style.display = 'block';
    } else {
        awinContainer.style.display = 'none';
    }
}

function aal_temu_validate() {
    var form = document.forms['aal_temuform'];
    if (!form['aal_temuid'].value) { 
        alert("Please enter your Temu Affiliate ID or Awin Publisher ID."); 
        return false; 
    }
    if (form['aal_temunetwork'].value === 'awin' && !form['aal_temuawinmid'].value) {
        alert("Please enter the Awin Merchant ID for the local Temu program you joined.");
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    aal_temu_toggle_fields();
});
</script>
    
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
        
    <h2>Temu Links Settings</h2>
    <br /><br />
    Configure your Temu monetization method.
    <br /><br />
        
    <div class="aal_general_settings">
        <form method="post" action="options.php" name="aal_temuform" onsubmit="return aal_temu_validate();"> 
            <?php
                settings_fields( 'aal_temu_settings' );
                do_settings_sections( 'aal_temu_settings_display' );
            ?>

            <span class="aal_label">Network / Platform:</span> 
            <select name="aal_temunetwork" id="aal_temunetwork_select" onchange="aal_temu_toggle_fields();">
                <option value="direct" <?php selected( $selected_network, 'direct' ); ?>>Temu Direct Program (Referral / Influencer)</option>
                <option value="awin" <?php selected( $selected_network, 'awin' ); ?>>Awin Network</option>
            </select>
            <br /><br />

            <span class="aal_label">Affiliate / Publisher ID:</span> 
            <input type="text" name="aal_temuid" value="<?php echo esc_attr( $temu_id ); ?>" />
            <br />
            <p class="description" style="margin-left: 150px; font-size: 12px; color: #666;">
                If using <strong>Temu Direct</strong>, enter your referral/affiliate code.<br />
                If using <strong>Awin</strong>, enter your Awin Publisher / Account ID.
            </p>
            <br />

            <!-- Conditional Awin Merchant ID Field -->
            <div id="aal_temu_awin_container" style="display: <?php echo ($selected_network === 'awin') ? 'block' : 'none'; ?>;">
                <span class="aal_label">Temu Awin Merchant ID:</span> 
                <input type="text" name="aal_temuawinmid" value="<?php echo esc_attr( $temu_awinmid ); ?>" placeholder="e.g. 118231" />
                <br />
                <p class="description" style="margin-left: 150px; font-size: 12px; color: #666;">
                    Enter the specific Awin Advertiser ID (MID) for the regional Temu program you are approved for (e.g., UK: <code>118231</code>, US/Global, etc.).
                </p>
                <br />
            </div>

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