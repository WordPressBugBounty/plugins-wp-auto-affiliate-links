<?php


$aalAmazon = new aalModule('amazon','Amazon Links',3);
$aalModules[] = $aalAmazon;

$aalAmazon->aalModuleHook('content','aalAmazonDisplay');


function aal_amazon_search_keyword( $keyword, $notimes, $nrk, $nrw, $alinks ) {
    
    // Your Client ID (previously AWS Access Key)
    $aws_access_key_id = trim(get_option('aal_amazonapikey'));
    // Your Client Secret (previously AWS Secret Key)
    $aws_secret_key = trim(get_option('aal_amazonsecret'));
    
    $amazonactive = get_option('aal_amazonactive');
    $amazonid = get_option('aal_amazonid');
    $amazoncat = get_option('aal_amazoncat');
    $amazonlocal = get_option('aal_amazonlocal');
    
    $amazondisplaylinks = get_option('aal_amazondisplaylinks');
    $amazondisplaywidget = get_option('aal_amazondisplaywidget');
    if(!$amazondisplaywidget) $amazondisplaylinks = 1;


            
    if($amazoncat) $acategory = $amazoncat;
    else $acategory = 'All';

    if($amazonlocal) $amazonlocal = $amazonlocal;
    else $amazonlocal = 'com';  
    
    $amazonlinks = array();
    $awidgetcode = array();
    $searchstring = $keyword;
    

    // Check if we have the necessary credentials
    if(!$amazonactive || !$amazonid) { 
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw, 'error' => 'Missing Amazon Associate ID, or Amazon module is inactive.'); 
    }   
    
	$api_not_eligible = get_transient( 'aal_amz_not_eligible_' . md5($aws_access_key_id) );

    if(!$aws_access_key_id || !$aws_secret_key || $api_not_eligible) {
    	
    	//Creating links to search results
    	if ($amazondisplaylinks) {
            // Construct the raw search URL
            $search_url = "https://www.amazon." . $amazonlocal . "/s?k=" . urlencode($searchstring) . "&creatorsDisableRedirect=true&tag=" . $amazonid;
            
            $found = 0;
            foreach($alinks as $aa) {
                if($search_url == $aa->link) $found = 1;      
            }
            
            if($found != 1) {
                $alink = new StdClass();
                $alink->key = $searchstring;
                $alink->url = $search_url;
                $amazonlinks[] = $alink;
            }
        }
    
    }
    else {

	    // 1. DETERMINE OAUTH ENDPOINT BASED ON REGION
	    $token_endpoint = 'https://api.amazon.com/auth/o2/token'; // Default NA
	    $eu_regions = array('co.uk', 'de', 'fr', 'it', 'es', 'nl', 'se', 'pl', 'com.tr', 'eg', 'sa', 'ae', 'in');
	    $fe_regions = array('co.jp', 'com.au', 'sg');
	    
	    if ( in_array( $amazonlocal, $eu_regions ) ) {
	        $token_endpoint = 'https://api.amazon.co.uk/auth/o2/token';
	    } elseif ( in_array( $amazonlocal, $fe_regions ) ) {
	        $token_endpoint = 'https://api.amazon.co.jp/auth/o2/token';
	    }
	
	    // 2. FETCH OR LOAD THE OAUTH BEARER TOKEN
	    $transient_name = 'aal_amz_token_' . md5($aws_access_key_id);
	    $token = get_transient( $transient_name );
	    $token_error_debug = '';
	
	    if ( ! $token ) {
	        $token_args = array(
	            'method'  => 'POST',
	            'timeout' => 10,
	            'headers' => array( 'Content-Type' => 'application/json' ),
	            'body'    => json_encode( array(
	                'grant_type'    => 'client_credentials',
	                'client_id'     => $aws_access_key_id,
	                'client_secret' => $aws_secret_key,
	                'scope'         => 'creatorsapi::default'
	            ) )
	        );
	        
	        $token_req = wp_remote_post( $token_endpoint, $token_args );
	        
	        if ( is_wp_error( $token_req ) ) {
	            $nrk++; sleep(2);
	            return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw, 'error' => 'WP HTTP Error fetching token: ' . $token_req->get_error_message());
	        }
	
	        $token_code = wp_remote_retrieve_response_code( $token_req );
	        $token_body_raw = wp_remote_retrieve_body( $token_req );
	        
	        if ( $token_code === 200 ) {
	            $token_body = json_decode( $token_body_raw );
	            if ( isset( $token_body->access_token ) ) {
	                $token = $token_body->access_token;
	                set_transient( $transient_name, $token, 3500 ); 
	            } else {
	                $token_error_debug = 'Token JSON missing access_token key. Body: ' . $token_body_raw;
	            }
	        } else {
	            $token_error_debug = 'Token API returned HTTP ' . $token_code . ' | Body: ' . $token_body_raw;
	        }
	    }
	
	    if ( ! $token ) {
	        $nrk++; sleep(2);
	        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw, 'error' => 'Failed to retrieve Auth Token. Details: ' . $token_error_debug);
	    }
	
	    // 3. CALL CREATORS API SEARCHITEMS
	    $marketplace = "www.amazon." . $amazonlocal;
	    $catalog_url = "https://creatorsapi.amazon/catalog/v1/searchItems";
	    
	    $resources = array(
	        "images.primary.medium", 
	        "itemInfo.title",
	        "offersV2.listings.price"
	    );
	
	    $payload = array(
	        "keywords"    => $searchstring,
	        "searchIndex" => $acategory,
	        "partnerTag"  => $amazonid,
	        "marketplace" => $marketplace,
	        "resources"   => $resources
	    );
	    
	    $args = array(
	        'method'      => 'POST',
	        'timeout'     => 15,
	        'redirection' => 5,
	        'httpversion' => '1.0',
	        'blocking'    => true,
	        'headers'     => array(
	            'Authorization' => 'Bearer ' . $token,
	            'Content-Type'  => 'application/json',
	            'x-marketplace' => $marketplace
	        ),
	        'body'        => json_encode( $payload ),
	    );
	
	    $api_response = wp_remote_post( $catalog_url, $args );
	    
	    if ( is_wp_error( $api_response ) ) {
	        $nrk++; sleep(2);
	        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw, 'error' => 'WP HTTP Error fetching Catalog: ' . $api_response->get_error_message());
	    }
	    
	    $response_code = wp_remote_retrieve_response_code( $api_response );
	    $response_body = wp_remote_retrieve_body( $api_response );
	    
	    // Clear token if unauthorized
	    if ( $response_code === 401 ) {
	        delete_transient( $transient_name );
	    }
	    
	    // If Amazon rejects the request, catch the exact reason
if ( $response_code !== 200 ) {
        
        // If the account doesn't have enough sales, cache this failure for 2 hours to force the fallback
        if ( strpos($response_body, 'AssociateNotEligible') !== false ) {
            set_transient( 'aal_amz_not_eligible_' . md5($aws_access_key_id), true, 2 * HOUR_IN_SECONDS );
        }
        
        $nrk++; 
        sleep(2);
        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw, 'error' => 'Amazon API Error HTTP ' . $response_code . ' | Body: ' . $response_body);
    }
	    
	    if ( empty( $response_body ) ) {
	        $nrk++; sleep(2);
	        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw, 'error' => 'Amazon API returned an empty response body.');
	    }
	    
	    $jsitems = json_decode($response_body);
	    
	    if ( !isset($jsitems->searchResult) || empty($jsitems->searchResult->items) ) { 
	        sleep(3); 
	        return array('links'=>array(), 'widget'=>array(), 'nrk'=>$nrk, 'nrw'=>$nrw);
	    }
	    
	    $items = $jsitems->searchResult->items;
	    
	    foreach($items as $item) {
	        if($amazondisplaywidget && $nrw<=2 && isset($item->images->primary->medium->url)) {
	            $awidget = new StdClass();
	            $awidget->url = $item->detailPageUrl;
	            $awidget->id = $item->asin;
	            $awidget->image = $item->images->primary->medium->url;
	            $awidget->title = isset($item->itemInfo->title->displayValue) ? $item->itemInfo->title->displayValue : '';
	            $awidget->price = isset($item->offersV2->listings[0]->price->displayAmount) ? $item->offersV2->listings[0]->price->displayAmount : '';
	            
	            $awidgetcode[] = $awidget;
	            $nrw++;
	        }
	
	        if($amazondisplaylinks) {
	            $link = (string) $item->detailPageUrl;
	            $found = 0;
	            foreach($alinks as $aa) {
	                if($link == $aa->link) $found = 1;      
	            }
	            if($found != 1) {
	                $alink = new StdClass();
	                $alink->key = $searchstring;
	                $alink->url = $link;
	                $amazonlinks[] = $alink;
	                break;
	            }
	        }
	    }
	    
	    sleep(2);
	    
	 } //end else for if api key and secret
        
    $nrk++;
    

    return array(
        'links' => $amazonlinks,
        'widget' => $awidgetcode,
        'nrk' => $nrk,
        'nrw' => $nrw
    );
}





add_action( 'admin_init', 'aal_amazon_register_settings' );


function aal_amazon_register_settings() { 
   register_setting( 'aal_amazon_settings', 'aal_amazonid' );
   register_setting( 'aal_amazon_settings', 'aal_amazonapikey' );
   register_setting( 'aal_amazon_settings', 'aal_amazonsecret' );
   register_setting( 'aal_amazon_settings', 'aal_amazoncat' );
   //register_setting( 'aal_amazon_settings', 'aal_amazonactive' );
   register_setting( 'aal_amazon_settings', 'aal_amazonlocal' );
   register_setting( 'aal_amazon_settings', 'aal_amazondisplaylinks' );
   register_setting( 'aal_amazon_settings', 'aal_amazondisplaywidget' );
}


function aalAmazonDisplay() {
	
	$amazoncat = get_option('aal_amazoncat');
	
	if(get_option('aal_amazondisplaylinks') && get_option('aal_amazondisplaylinks') != 1  ) delete_option('aal_amazondisplaylinks');
	if(get_option('aal_amazondisplaywidget') && get_option('aal_amazondisplaywidget') != 1  ) delete_option('aal_amazondisplaywidget');
	
	?>

<script type="text/javascript">

function aal_amazon_validate() {
	
		if(!document.aal_amazonform.aal_amazoncat.value) { alert("Please select a category"); return false; }
		if(!document.aal_amazonform.aal_amazonid.value) { alert("Please add your amazon ID"); return false; }
		//if(!document.aal_amazonform.aal_amazonapikey.value) { alert("Please add your amazon API Key"); return false; }
		//if(!document.aal_amazonform.aal_amazonsecret.value) { alert("Please add your amazon Secret Key"); return false; }
				
	}

jQuery(document).ready(function() {
      jQuery("#aal_amazoncat").val("<?php echo $amazoncat; ?>");	
}); 

	
	</script>
	
	
<div class="wrap">  
    <div class="icon32" id="icon-options-general"></div>  
        
        
                <h2>Amazon Links</h2>
                <br /><br />
                
                         
                
                
                Once you add your affiliate ID and activate amazon links, they will start to appear on your website. The manual links that you add will have priority.<br />
                This feature will only work if you have set the API Key in the "API Key" menu.
                <br /><br />
                
<div class="aal_general_settings">
		<form method="post" action="options.php" name="aal_amazonform" onsubmit="return aal_amazon_validate();"> 
<?php
		settings_fields( 'aal_amazon_settings' );
		do_settings_sections('aal_amazon_settings_display');
		
?>
		<span class="aal_label">Amazon Affiliate ID:</span> <input type="text" name="aal_amazonid" value="<?php echo get_option('aal_amazonid'); ?>" />
		<br /><br />
	<span class="aal_label">Category: </span><select id="aal_amazoncat"  name="aal_amazoncat" ><option value="">-Select a cateogry-	</option>
	   <option value="AmazonVideo">Prime Video</option>
		<option value="Apparel">Apparel & Accessories</option>
		<option value="Appliances">Appliances</option>
		<option value="ArtsAndCrafts">Arts, Crafts & Sewing</option>
		<option value="Automotive">Automotive</option>
		<option value="Baby">Baby</option>
		<option value="Beauty">Beauty</option>
		<option value="Books">Books</option>
		<option value="Classical">Classical</option>
		<option value="Collectibles">Collectibles & Fine Art</option>
		<option value="Computers">Computers</option>
		<option value="DigitalMusic">Digital Music</option>
		<option value="DigitalEducationalResources">Digital Educational Resources</option>
		<option value="Electronics">Electronics</option>
		<option value="EverythingElse">Everything Else</option>
		<option value="Fashion">Clothing, Shoes & Jewelry</option>
		<option value="FashionBaby">Clothing, Shoes & Jewelry Baby</option>
		<option value="FashionBoys">Clothing, Shoes & Jewelry Boys</option>
		<option value="FashionGirls">Clothing, Shoes & Jewelry Girls</option>
		<option value="FashionMen">Clothing, Shoes & Jewelry Men</option>
		<option value="FashionWomen">Clothing, Shoes & Jewelry Women</option>
		<option value="GardenAndOutdoor">Garden & Outdoor</option>
		<option value="GroceryAndGourmetFood">Grocery & Gourmet Food</option>
		<option value="Handmade">Handmade</option>
		<option value="HealthPersonalCare">Health, Household & Baby Care</option>
		<option value="HomeAndKitchen">Home & Kitchen</option>
		<option value="Industrial">Industrial & Scientific</option>
		<option value="Jewelry">Jewelry</option>
		<option value="KindleStore">Kindle Store</option>
		<option value="LocalServices">Home & Business Services</option>
		<option value="Luggage">Luggage & Travel Gear</option>
		<option value="LuxuryBeauty">Luxury Beauty</option>
		<option value="Magazines">Magazine Subscriptions</option>		
		<option value="MobileAndAccessories">Cell Phones & Accessories</option>
		<option value="MoviesAndTV">Movies & TV</option>
		<option value="MobileApps">Apps & Games</option>
		<option value="Music">CDs & Vinyl</option>
		<option value="MusicalInstruments">Musical Instruments</option>	
		<option value="OfficeProducts">Office Products</option>		
		<option value="PetSupplies">PetSupplies</option>
		<option value="Photo">Photo</option>
		<option value="Shoes">Shoes</option>
		<option value="Software">Software</option>
		<option value="SportsAndOutdoors">Sports & Outdoors</option>
		<option value="ToolsAndHomeImprovement">Tools & Home Improvement</option>		
		<option value="ToysAndGames">Toys & Games</option>
		<option value="VHS">VHS</option>
		<option value="VideoGames">Video Games</option>	
		<option value="Watches">Watches</option>
		<option value="All">All Categories</option>
	</select>
	<br /><br />
	<span class="aal_label">Localization: </span><select id="aal_amazonlocal"  name="aal_amazonlocal" >
		<option value="com" <?php if(get_option('aal_amazonlocal')=='com') echo "selected"; ?> >COM</option>
		<option value="com.au" <?php if(get_option('aal_amazonlocal')=='com.au') echo "selected"; ?>>AU</option>
		<option value="com.br" <?php if(get_option('aal_amazonlocal')=='com.br') echo "selected"; ?>>BR</option>
		<option value="ca" <?php if(get_option('aal_amazonlocal')=='ca') echo "selected"; ?>>CA</option>
		<option value="eg" <?php if(get_option('aal_amazonlocal')=='eg') echo "selected"; ?>>EG</option>
		<option value="fr" <?php if(get_option('aal_amazonlocal')=='fr') echo "selected"; ?>>FR</option>
		<option value="de" <?php if(get_option('aal_amazonlocal')=='de') echo "selected"; ?>>DE</option>
		<option value="es" <?php if(get_option('aal_amazonlocal')=='es') echo "selected"; ?>>ES</option>
		<option value="in" <?php if(get_option('aal_amazonlocal')=='in') echo "selected"; ?>>IN</option>
		<option value="it" <?php if(get_option('aal_amazonlocal')=='it') echo "selected"; ?>>IT</option>
		<option value="co.jp" <?php if(get_option('aal_amazonlocal')=='co.jp') echo "selected"; ?>>JP</option>
		<option value="co.uk" <?php if(get_option('aal_amazonlocal')=='co.uk') echo "selected"; ?>>UK</option>
		<option value="com.mx" <?php if(get_option('aal_amazonlocal')=='com.mx') echo "selected"; ?>>MX</option>
		<option value="nl" <?php if(get_option('aal_amazonlocal')=='nl') echo "selected"; ?>>NL</option>
		<option value="pl" <?php if(get_option('aal_amazonlocal')=='pl') echo "selected"; ?>>PL</option>
		<option value="sg" <?php if(get_option('aal_amazonlocal')=='sg') echo "selected"; ?>>SG</option>
		<option value="sa" <?php if(get_option('aal_amazonlocal')=='sa') echo "selected"; ?>>SA</option>
		<option value="se" <?php if(get_option('aal_amazonlocal')=='se') echo "selected"; ?>>SE</option>
		<option value="com.tr" <?php if(get_option('aal_amazonlocal')=='com.tr') echo "selected"; ?>>TR</option>
		<option value="ae" <?php if(get_option('aal_amazonlocal')=='ae') echo "selected"; ?>>AE</option>
		
	</select>
	<br /><br />

	
	<span class="aal_label">Display</span> 
	<input type="checkbox" name="aal_amazondisplaylinks" value="1" <?php checked( '1', get_option('aal_amazondisplaylinks'), 'checked');  ?>  /> Display links in text &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="checkbox" name="aal_amazondisplaywidget" value="1" <?php checked( '1', get_option('aal_amazondisplaywidget'), 'checked');  ?> /> Display product widget at bottom of post

	<!-- <br />
		<span class="aal_label">Status: </span><select name="aal_amazonactive">
			<option value="0" <?php if(get_option('aal_amazonactive')=='0') echo "selected"; ?> > Inactive</option>
			<option value="1" <?php if(get_option('aal_amazonactive')=='1') echo "selected"; ?> >Active</option>
		</select><br /> -->
	<br /><br />
	<h4>Amazon API settings:</h4>
	<br />
	<p>Amazon API credentials are not required. Auto Affiliate Links can display links to amazon without API access, but for best results, if you met the requirements for API access, it is recommended to add them here for best results.</p>
	<br />
	<span class="aal_label">Amazon API key:</span> <input class="aal_big_input" type="text" name="aal_amazonapikey" value="<?php echo get_option('aal_amazonapikey'); ?>" />
		<br /><br />
	<span class="aal_label">Amazon API Secret:</span> <input class="aal_big_input" "aal_big_input" type="text" name="aal_amazonsecret" value="<?php echo get_option('aal_amazonsecret'); ?>" />
		<br /><br />
		<p>You can get your Amazon API key and secret from your Amazon Associates account, from <a href="https://affiliate-program.amazon.com/assoc_credentials/home">Manage Credentials</a>	 page. Check <a href="https://autoaffiliatelinks.com/how-to-obtain-amazon-product-advertising-api-key-and-secret/">this article</a> for instructions.</p>	
		<br /><br />



<?php
	submit_button('Save');
	echo '</form></div>';
	
	update_option('aal_settings_updated',time());	
?>
	<a href="<?php echo admin_url('admin.php?page=aal_apimanagement'); ?>" class="button button-primary">Back to API Management</a>

<?php
	
	echo '</div>';

}




?>