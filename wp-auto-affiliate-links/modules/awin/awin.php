<?php

$aalAwin = new aalModule('awin','Awin Links',5);
$aalModules[] = $aalAwin;

$aalAwin->aalModuleHook('content','aalAwinDisplay');
$aalAwin->aalModuleHook('actions','aalAwinActions');


add_action( 'admin_init', 'aal_awin_register_settings' );


function aal_awin_register_settings() { 
   register_setting( 'aal_awin_settings', 'aal_awinid' );
   register_setting( 'aal_awin_settings', 'aal_awinactive' );
}

function aalAwinDisplay() {
	
?>

<script type="text/javascript">

function aal_awin_validate() {
	if(!document.aal_awin.aal_awinfeed.value) { alert("Please select a file to upload"); return false; }
	return true;			
}
	
</script>
	
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
        
    <h2>Awin Links</h2>
    <br /><br />
    First, add your Awin Publisher ID and save it. Then upload a datafeed file (.csv) you downloaded from the Awin Dashboard.<br />
    Login into your Awin Affiliate Dashboard. In the top menu, go to Toolbox -> Create a feed. There, you can select advertisers, categories, and other options.<br />
    Click Next and in the next page click Download to download the file. Make sure that you extract the archive before uploading. You must upload the .csv file (not the gzip or zip).<br />
    This feature will only work if you have set the API Key in the "API Key" menu, and the API key is valid and active.
    <br /><br />
                
    <div class="aal_general_settings">
		<form method="post" action="options.php" name="aal_awinform" > 
        <?php
            settings_fields( 'aal_awin_settings' );
            do_settings_sections('aal_awin_settings_display');
        ?>
		<span class="aal_label">Publisher ID:</span> <input type="text" name="aal_awinid" value="<?php echo get_option('aal_awinid'); ?>" /><br />
		
        <span class="aal_label">Status: </span>
        <select name="aal_awinactive">
			<option value="0" <?php if(get_option('aal_awinactive')=='0') echo "selected"; ?> > Inactive</option>
			<option value="1" <?php if(get_option('aal_awinactive')=='1') echo "selected"; ?> >Active</option>
		</select> 

        <?php
            submit_button('Save');
            echo '</form></div>';
            
            update_option('aal_settings_updated',time());	
            
            // Only show upload form if ID is set and module is Active
            if(get_option('aal_awinid') && get_option('aal_awinactive') ) {
                
                global $uploadmessage;
        ?>

        <h3>Upload an Awin Datafeed (.csv)</h3>
        <div class="aal_general_settings">

            <form name="aal_awin" method="post" enctype="multipart/form-data" onsubmit="return aal_awin_validate();">
                <span class="aal_label">File: </span><input name="aal_awinfeed" type="file" />
                <input type="submit" value="Upload" />
                <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
                <input type="hidden" name="aal_awinaction" value="1" />
            </form>
            
            <?php echo $uploadmessage; ?>
            
            <br /><br />
            <a href="<?php echo admin_url('admin.php?page=aal_apimanagement'); ?>" class="button button-primary">Back to API Management</a>

        </div>

        <?php } ?>
	
	<br /><br />
	<h3>Your Awin links</h3>
	<br />
	
	<?php
	echo aal_showcustomlinks('awin');
	echo '</div>';
}


function aalAwinActions() {
	global $wpdb;
    
	if(isset($_POST['aal_awinaction'])) {
	
		$awinid = get_option('aal_awinid');
		global $uploadmessage;

		if($_FILES['aal_awinfeed']["error"]) { 
            $uploadmessage = "File was too large or failed to upload"; 
        } else {			
		
            $handle = fopen($_FILES['aal_awinfeed']['tmp_name'], "r");
            if ($handle === FALSE) {
                $uploadmessage = "Could not open file.";
                return;
            }

            // 1. READ HEADER ROW (To Map Columns)
            // Awin CSVs are standard comma separated, but sometimes quoted.
            $header = fgetcsv($handle, 0, ","); 
            
            if (!$header) {
                 $uploadmessage = "Could not read CSV headers.";
                 fclose($handle);
                 return;
            }

            // 2. ANALYZE HEADERS (Harvester Logic)
            $useful_headers = [];
            $link_index = -1;
            $merchant_index = -1;

            foreach ($header as $index => $col_name) {
                $col_name = strtolower(trim($col_name));
                
                // Find the Deep Link
                if ($col_name === 'aw_deep_link' || $col_name === 'click_url') {
                    $link_index = $index;
                    continue;
                }

                // Find Merchant Name (Optional, but good for display)
                if ($col_name === 'merchant_name' || $col_name === 'advertiser_name') {
                    $merchant_index = $index;
                }

                // Find Content Columns (Title, Desc, Brand, Category)
                if ( preg_match( '/(name|title|brand|category)/', $col_name ) ) {
                    // Avoid image URLs or IDs which might contain these words
                    if ( strpos($col_name, 'url') === false && strpos($col_name, 'id') === false ) {
                        $useful_headers[] = $index;
                    }
                }
            }

            $slearray = array();
            
            // 3. PROCESS ROWS
            if ($link_index !== -1) {
                while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                    
                    // Skip empty rows or rows missing the link
                    if (!isset($row[$link_index]) || empty($row[$link_index])) continue;

                    $link = $row[$link_index];
                    $context_string = '';
                    $merchant = ($merchant_index !== -1 && isset($row[$merchant_index])) ? $row[$merchant_index] : 'Awin Merchant';

                    // Concatenate all useful text into one "Title" string for keywords
                    foreach ($useful_headers as $idx) {
                        if (isset($row[$idx]) && !empty($row[$idx])) {
                            $context_string .= $row[$idx] . ' '; 
                        }
                    }
                    
                    $context_string = trim($context_string);

                    if($link && $context_string) {
                        $sle = new stdClass(); 
                        $sle->link = $link;
                        $sle->merchant = $merchant;
                        $sle->title = substr($context_string, 0, 500); // Limit length to avoid massive payloads
                        $slearray[] = $sle;
                    }
                }
            } else {
                $uploadmessage = "Error: Could not find 'aw_deep_link' column in CSV.";
                fclose($handle);
                return;
            }
            
            fclose($handle);

            // 4. SEND TO API
            if (!empty($slearray)) {
                $slejson = json_encode($slearray); 
                
                // Note: We are sending to 'awin.php' now. You need to create this endpoint.
                $postcontent = "slejson=". urlencode($slejson) ."&awinid=". $awinid ."&apikey=". get_option('aal_apikey');
                $response = aal_post($postcontent, 'http://api.autoaffiliatelinks.com/awin.php');
                
                echo $response; // Debug output from API
                $uploadmessage = "Upload successful! " . count($slearray) . " links processed.";
            } else {
                $uploadmessage = "No valid links found in file.";
            }
		
		}
	}	
}
?>