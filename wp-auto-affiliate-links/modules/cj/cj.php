<?php


$aalCj = new aalModule('cj','Commission Junction Links',6);
$aalModules[] = $aalCj;

$aalCj->aalModuleHook('content','aalCjDisplay');
$aalCj->aalModuleHook('actions','aalCjActions');


add_action( 'admin_init', 'aal_cj_register_settings' );


function aal_cj_register_settings() { 
   //register_setting( 'aal_cj_settings', 'aal_cjactive' );
}

function aalCjDisplay() {
	
?>


<script type="text/javascript">




function aal_cj_validate() {
	
		if(!document.aal_cj.aal_cjfeed.value) { alert("Please select a file to upload"); return false; }
		
		return true;
				
	}



	
</script>
	
	
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
        
        
                <h2>Commission Junction Links</h2>
                <br /><br />
                Upload any file you get from an affiliate merchant and press "Upload".  Keywords will be automatically generated.<br />
                This feature will only work if you have set the API Key in the "API Key" menu, and the API key is valid and active.
                <br /><br />
                
<div class="aal_general_settings">
		<form method="post" action="options.php" name="aal_cjform" > 
<?php
	//	settings_fields( 'aal_cj_settings' );
	//	do_settings_sections('aal_cj_settings_display');
		
?>
				<!-- <span class="aal_label">Status: </span><select name="aal_cjactive">
			<option value="0" <?php if(get_option('aal_cjactive')=='0') echo "selected"; ?> > Inactive</option>
			<option value="1" <?php if(get_option('aal_cjactive')=='1') echo "selected"; ?> >Active</option>
		</select> -->



<?php
	//submit_button('Save');
	echo '</form></div>';
	
	if(get_option('aal_cjactive') ) {
		
		global $uploadmessage;
?>

	<h3>Upload a Commission Junction .txt file</h3>
<div class="aal_general_settings">

	<form name="aal_cj" method="post" enctype="multipart/form-data" onsubmit="return aal_cj_validate();">
	<span class="aal_label">File: </span><input name="aal_cjfeed" type="file" /><br/>
			Separator: <select name="aal_cj_separator" >
			
			<option value="tab">Tab</option>
			<option value="|">| ( vertical line )</option>
			<option value=",">, ( comma )</option>
			<option value="other">other ( specify below )</option>
			</select><br/>
			<input type="submit" value="Upload" />
<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
<input type="hidden" name="aal_cjaction" value="1" />
	</form>
	<?php echo $uploadmessage; ?>
	<br /><br />
	<a href="<?php echo admin_url('admin.php?page=aal_apimanagement'); ?>" class="button button-primary">Back to API Management</a>

</div>




<?php

	}
	
	update_option('aal_settings_updated',time());	
	
	?>
	
	<br /><br />
	<h3>Your Commission Junction links</h3>
	<br />
	
	
	<?php
	
	echo aal_showcustomlinks('cj');
	
	
	?>
	<br /><br />
	<hr>
	<br /><br />
	To add Commission Junction Links follow these steps:
	
<ol style="font-weight: bold" >
<li>Login into your commission junction account and click on “Account” tab.
<li>On the secondary menu, click on “Subscriptions” ( last menu item ).
<li>Click on “Create product export”
<li>Select your usual email contact, “TAB” export format, a website from the list, set a name for your subscription.
<li>Important. At the bottom on “Transport Method” select Email, so you can receive the datafeed to your email address and add your desired email address ( where you want to receive your file ).
<li>Select the product catalog that you want to add into Wp Auto Affiliate Links. Once you select it from the dropdown it will be added to the list. You can select multiple catalogs.
<li>Click save and your are done. Wait for the email with your datafeed.
</ol>
	
	
	<?php
	echo '</div>';

}


function aalCjActions() {
	global $wpdb;
	 $table_name = $wpdb->prefix . "automated_links";
	
	if(isset($_POST['aal_cjaction'])) {
		
		
		$separator = $_POST['aal_cj_separator'];
		if($separator=='tab') $separator = "\t";
		if(!$separator) $separator = "\t";		
		
		
		
	
		global $uploadmessage;
		if($_FILES['aal_cjfeed']["error"]) { $uploadmessage = "File was too large"; }
		else {			
		
		$handle = fopen($_FILES['aal_cjfeed']['tmp_name'], "r");
		while (($data = fgetcsv($handle, 100000, $separator)) !== FALSE) {
			//print_r($data);
			$link = $data[7];
			if(!strpos($link, 'ttp')) continue;
			$merchant = $data[0];
			//$link = str_replace("YOURUSERID", $sasid, $data[4]);
			$meta = $data[5];
			if($link && $meta && $merchant) {
				
					$sle = new stdClass(); 
					$sle->link = $link;
					$sle->merchant = $merchant;
					$sle->title = $meta;
					$slearray[] = $sle;
					
				
				}
		
		
		
		}
		fclose($handle);

		$slejson = json_encode($slearray); 
		$postcontent = "slejson=". urlencode($slejson) ."&apikey=". get_option('aal_apikey');
		$response = aal_post($postcontent, 'http://autoaffiliatelinks.com/api/cj.php');
		
		echo $response;


		$uploadmessage = "Upload succesfull";
		
		}
		
	
	
	}	
	
}




?>