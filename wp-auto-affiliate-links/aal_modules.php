<?php

// Classes

$aal_hardcoded_modules = array(
    'amazon/amazon.php',
    'impact/impact.php',
    'awin/awin.php',
    'bestbuy/bestbuy.php',
    'cj/cj.php',
    'clickbank/clickbank.php',
    'customfeed.php',           
    'discoveryjapan/discoveryjapan.php',
    'ebay/ebay.php',
    'envato/envato.php', 
    'rakuten/rakuten.php',
    'shareasale/shareasale.php',
    'universalfeed/universalfeed.php',
    'walmart/walmart.php',
    'temu/temu.php',
    'aliexpress/aliexpress.php',
    'etsy/etsy.php',
    'profitshare/profitshare.php',
    'twoperformant/twoperformant.php',
);

// Define the global array before the loop
global $aalModules; 
$aalModules = array();

$moduledir = plugin_dir_path(__FILE__) . 'modules/';

//Include files for each module
foreach ( $aal_hardcoded_modules as $module_file ) {
    $full_path = $moduledir . $module_file;
    if ( file_exists( $full_path ) ) {
        include_once( $full_path );
    }
}
 
 
 // order modules
 
$mnum = count($aalModules);
$sw = 0;
while($sw == 0) {
	$sw=1;
	for($i=0;$i<$mnum-1;$i++) {
		if($aalModules[$i]->order > $aalModules[$i+1]->order) {
		
			$aux = $aalModules[$i];
			$aalModules[$i] = $aalModules[$i+1];
			$aalModules[$i+1] = $aux;
			$sw=0;	
			
			
		}
		
	}	
	
} 

 
 
 
 //end order modules


class aalModule
{
    public $shortname;
    public $nicename;
    public $hooks = array();
	 public $order;

	function __construct($shortname,$nicename, $order = 99) {
		
		$this->shortname = $shortname;
		$this->nicename = $nicename;
		$this->order = $order;

	}

	function aalModuleHook($hook,$fname) {
		
		$this->hooks[$hook] = $fname;
		
	}


}



function wpaal_modules() {
	global $wpdb;
	$table_name = $wpdb->prefix . "automated_links";	
	if ( !current_user_can("publish_pages") ) return;

	
	?>
	
	
<div class="wrap">  
        <div class="icon32" id="icon-options-general"></div>  
        
        
                <h2>Modules</h2>
                <br /><br /><br />
                
	To add modules, upload the module files into /modules/ subdirectory in wp-auto-affiliate-links/ plugin folder. Once they are uploaded, every module will create a link into the Wp Auto Affiliate Links top menu. 
	<br /><br />
	You can get more modules from <a href="https://autoaffiliatelinks.com">Auto Affiliate Links Modules</a>
	<br /><br />
	<h3>Active modules</h3>

	If a module is causing your problems, just delete the files trough ftp. 
 
	
	<?php
}


function aal_showcustomlinks($network) {

	$content = '<div id="aalshowcustomlinks" data-network="'. $network .'" data-apikey="'. trim(get_option('aal_apikey')) .'" ><span class="spinner .aal_spinner" style="float: left;"></span></div>';

	return $content;
}




?>