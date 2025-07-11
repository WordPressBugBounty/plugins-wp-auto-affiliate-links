<?php 

$aalCustomFeed = new aalModule('customfeed','Custom Feed',23);

//update_option('aal_customfeedactive',1);


$aalCustomFeed->aalModuleHook('content','aalCustomFeedDisplay');
function aalCustomFeedDisplay() {

	?>
	
	
<div class="wrap">  
        <div class="icon32" id="icon-options-general"></div>  
        
        
                <h2>Custom Feed Upload</h2>
                <br/><br />


				Upload your datafeed. The format should be keyword,url. The separator is the character who separate the columns, it can be a comma ( , ), a vertical bar ( | ) or a tab ( exactly 4 spaces ). For tab, just write "tab" in the text field. If you don't know, open the feed file in notepad or any other simple text editor. You can edit your datafeed in Microsoft Excell or Libre Office Calc. Make sure you save the file in csv format ( not in xls or odt ). Upon saving, you can select the separator. All the links inside the datafeed will be added to your affiliate links. 
				<br />
				<br />
				
				
			<form name="aal_import_form" method="post" enctype="multipart/form-data" onsubmit="">
			<?php wp_nonce_field( 'aal_importfile_actionnonce', 'aal_importfile_nonce' ); ?>
			Upload the file here:    <input name="aal_import_file" type="file" /><br />
			Separator: <select name="aal_import_separator" onchange="if(document.aal_import_form.aal_import_separator.options[document.aal_import_form.aal_import_separator.selectedIndex].value == 'other') document.aal_import_form.aal_import_other.style.display = 'block'; else document.aal_import_form.aal_import_other.style.display = 'none';">
			<option value="|">| ( vertical line )</option>
			<option value="tab">Tab</option>
			<option value=",">, ( comma )</option>
			<option value=";">; ( semicolon )</option>
			<option value=".">. ( dot )</option>
			<option value="other">other ( specify below )</option>
			</select>
			<br /><input type="text" name="aal_import_other" value="|" style="display: none;"/>
			<br />
			<input type="submit" value="Import" /><input type="hidden" name="MAX_FILE_SIZE" value="10000000" /><input type="hidden" name="aal_import_check" value="1" />
			</form>
			
	</div>
				
	<?php

}



$aalModules[] = $aalCustomFeed;

add_action('admin_init', 'aalModuleCustomFeedAction');
function aalModuleCustomFeedAction() {
global $wpdb;
        $table_name = $wpdb->prefix . "automated_links";
		if ( !current_user_can("publish_pages") ) return;
        
        
	if(isset($_POST['aal_import_check'])) {
		
		check_admin_referer( 'aal_importfile_actionnonce', 'aal_importfile_nonce' );
	
		//$sasid = filter_input(INPUT_POST, 'aal_sasid', FILTER_SANITIZE_SPECIAL_CHARS);
		//$scontent = file_get_contents($_FILES['aal_sasfeed']['tmp_name']);
		//print_r($_FILES['aal_sasfeed']);
		
		$separator = $_POST['aal_import_separator'];
		if($separator=='tab') $separator = "\t";
		if($separator=='other') $separator = $_POST['aal_import_other'];
		if(!$separator) $separator = "|";
		
		$deleted = 1;
		$import_count = 0;
		$duplicates = 0;
		$duplicatestext = '';
		global $aal_import_error;
		
		if($_FILES['aal_import_file']['tmp_name']) {
			$mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv','application/csv','text/comma-separated-values','application/excel','application/vnd.msexcel','text/anytext','application/txt');
			if(in_array($_FILES['aal_import_file']['type'],$mimes)){

				$handle = fopen($_FILES['aal_import_file']['tmp_name'], "r");
				while (($data = fgetcsv($handle, 1000, $separator,'"')) !== FALSE) {
				//$link = str_replace("YOURUSERID", $sasid, $data[4]);
					$title = '';
					$samelink = '';
					$disclosureoff = '';
					$link = '';
					$keywords = '';
					$stats = '';
					if(array_key_exists(1, $data)) $link = sanitize_url(esc_url_raw(esc_sql($data[1])));
					if(array_key_exists(0, $data)) $keywords = sanitize_text_field(esc_sql($data[0]));
					if(array_key_exists(2, $data)) $title = sanitize_text_field(esc_sql($data[2]));
					if(array_key_exists(5, $data)) $stats = sanitize_text_field(esc_sql($data[5]));
					if(array_key_exists(3, $data)) $samelink = sanitize_text_field(esc_sql($data[3]));
					if(array_key_exists(4, $data)) $disclosureoff = sanitize_text_field(esc_sql($data[4]));
					
							
					
					if($link && $keywords && strlen($link)<1000 && strlen($keywords)<2000 ) {
						
						if($deleted == 1 && $_POST['aal_import_overwrite']=='delete') {
							$deleted = 0;
							$wpdb->query("TRUNCATE TABLE $table_name");
						
						}				
						
						$import_count++;
						$existing = $wpdb->get_results( $wpdb->prepare("SELECT id,link,keywords,meta,stats FROM ". $table_name ." WHERE link = '%s' ",$link));			
						if(isset($existing[0]) && $existing[0]->link) {
							if($existing[0]->keywords == $keywords) {
									$duplicates = $duplicates + 1;
									continue;
								}
							else {
								$keywords = $existing[0]->keywords .','. $keywords;
								$wpdb->query($wpdb->prepare("UPDATE ". $table_name ." SET keywords = '%s' WHERE id = '%d' ",$keywords,$existing[0]->id)); 
							}
						}
						else {
							$meta = new StdClass();
							$meta->title = $title;
							$meta->samelink = $samelink;
							$meta->disclosureoff = $disclosureoff;
							$jmeta = json_encode($meta);
							$wpdb->insert( $table_name, array( 'link' => $link, 'keywords' => $keywords, 'meta' => $jmeta, 'stats' => $stats ) );
						}
					}
				}
				fclose($handle);
				if($duplicates && $duplicates > 0) $duplicatestext = $duplicates .' duplicate links found.';
				$aal_import_error = '<div class="update-message notice inline notice-alt updated-message notice-success"><p>'. $import_count .' links imported. '. $duplicatestext .'</p></div>';		
				//wp_redirect("admin.php?page=aal_topmenu#aal_panel4");
			}
			else {
			  $aal_import_error = '<div class="update-message notice inline notice-error notice-alt"><p>File type not supported. Please upload a text/csv file</p></div>';	
			}
		}
		else {
			$aal_import_error = '<div class="update-message notice inline notice-error notice-alt"><p>Please choose a file</p></div>';		
		}
		
		
		// echo $scontent;
		
		//die();
		
	
	
	}

}

?>