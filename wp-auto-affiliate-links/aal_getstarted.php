<?php


function wpaal_gettingstarted() {
	?>
	
	
<div class="wrap" >  
    <div class="icon32" id="icon-options-general"></div>  
    <h2>Getting Started with Auto Affiliate Links</h2>
    <div class="aal_leftadmin">	
		<br /><br />
		<p>Thank you for installing WP Auto Affiliate Links. The plugin will replace keywords with affiliate links that you manually add, or automatic trough our supported affiliate networks.</p>
		<p>To get started, go to "WP Auto Affiliate Links" menu item, and add your affiliate links, and keyphrases where you want the links to appear. For example, if you want to add a link to https://amazon.com to each occurences of the keyword "Samsung" in your posts, then enter the link into "Affiliate Links" field, and the keyword into "Keywords" field. You can add more keywords for every affiliate links if you separate them by comma. For example: "samsung, galaxy, galaxy s2, galaxy s3, galaxy s4".</p>
		<p><b>IMPORTANT</b>: Your content is not modified in any way in the database. All the links are added when a post or page is displayed and they are not saved into the database.</p>
		<p>By default the plugin only add links in posts and links are not added to the homepage, only on single post pages. You can change the options in "General Settings" menu under "Wp Auto Affiliate Links" menu.</p>
		<p>To let the plugin to automatically get links and add them to product references or important keywords in your posts, you have to go to our website and <a href="https://autoaffiliatelinks.com/wp-auto-affiliate-links-pro/">get your API key</a>. Then, go to "Upgrade to PRO" menu and add your API key there. On the same page you can activate the affiliate networks you want to get links from: Amazon, Clickbank, Ebay, Shareasale, Walmart, Commission Junction, Bestbuy and Envato Marketplace.</p>
		<p>After you activate the modules you want, a new link for every affiliate network will appear in the Wp Auto Affiliate Links menu. Go to each network menu item and add your affiliate details for each. For example you amazon associates ID, you shareasale affiliate id, etc.</p>
		<p>To set where you want links to be displayed, you should check the <a href="admin.php?page=aal_general_settings">General Settings</a> to select your linking preferences. </p>
		<p>An alternative way to add link in your content is trough a shortcode. Wrap "autolink" shortcode around the text you want to link. Usage: [autolink id=<b>X</b>]Linked text[/autolink] , where X is the link ID. You can see the ID of each inserted link in main plugin admin page, by clicking on "Show advanced options" toggle. </p>
		<p>If you want to display links in a widget you can do so from Appearance -> Widgets menu in your Wordpress admin panel. </p>
	</div>
</div>	
	
<?php	
}


