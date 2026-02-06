(function($) {

	$(document).ready(function() { 
	
		//if(document.getElementById('aal_api_data')) {  
		if(!aalInIframe()) {
			
			//Old code for variables 
			/*
			$("div[id*='aal_api_data']").each(function() { 
				//datadiv = document.getElementById('aal_api_data');		
				var datadiv = this;
				var aal_divnumber = datadiv.getAttribute('data-divnumber');
				var aal_target = datadiv.getAttribute('data-target');
				var aal_relation = datadiv.getAttribute('data-relation');
				var aal_postid = datadiv.getAttribute('data-postid');
				var aal_apikey = datadiv.getAttribute('data-apikey');
				var aal_clickbankid = datadiv.getAttribute('data-clickbankid');
				var aal_clickbankgravity = datadiv.getAttribute('data-clickbankgravity');
				var aal_notimes = datadiv.getAttribute('data-notimes');
				var aal_clickbankcat = datadiv.getAttribute('data-clickbankcat');
				var aal_amazonlocal = datadiv.getAttribute('data-amazonlocal');
				var aal_amazonid = datadiv.getAttribute('data-amazonid');
				var aal_amazoncat = datadiv.getAttribute('data-amazoncat');
				var aal_amazondisplaylinks = datadiv.getAttribute('data-amazondisplaylinks');
				var aal_amazondisplaywidget = datadiv.getAttribute('data-amazondisplaywidget');
				var aal_amazonactive = datadiv.getAttribute('data-amazonactive');
				var aal_clickbankactive = datadiv.getAttribute('data-clickbankactive');
				var aal_shareasaleid = datadiv.getAttribute('data-shareasaleid');
				var aal_shareasaleactive = datadiv.getAttribute('data-shareasaleactive');
				var aal_cjactive = datadiv.getAttribute('data-cjactive');
				var aal_ebayactive = datadiv.getAttribute('data-ebayactive');
				var aal_ebayid = datadiv.getAttribute('data-ebayid');
				var aal_bestbuyactive = datadiv.getAttribute('data-bestbuyactive');
				var aal_bestbuyid = datadiv.getAttribute('data-bestbuyid');
				var aal_walmartactive = datadiv.getAttribute('data-walmartactive');
				var aal_walmartid = datadiv.getAttribute('data-walmartid');
				var aal_envatoid = datadiv.getAttribute('data-envatoid');
				var aal_envatosite = datadiv.getAttribute('data-envatosite');
				var aal_envatoactive = datadiv.getAttribute('data-envatoactive');
				var aal_rakutenactive = datadiv.getAttribute('data-rakutenactive');
				var aal_rakutenid = datadiv.getAttribute('data-rakutenid');
				var aal_discoveryjapanactive = datadiv.getAttribute('data-discoveryjapanactive');
				var aal_discoveryjapanid = datadiv.getAttribute('data-discoveryjapanid');
				var aal_discoveryjapanapikey = datadiv.getAttribute('data-discoveryjapanapikey');
				var aal_aurl = datadiv.getAttribute('data-aurl');
				var aal_excludewords = datadiv.getAttribute('data-excludewords');
				var aal_linkcolor = datadiv.getAttribute('data-linkcolor');
				var aal_geminiaion = datadiv.getAttribute('data-geminiaion');
				*/
				
				//new code for variables
		// Check if our data object exists and has items
		if (typeof aal_data !== 'undefined' && aal_data.items && aal_data.items.length > 0) {
		
		    // 1. Get the Global Settings (Config)
		    // These are the same for every post, so we define them once here.
		    var config = aal_data.config;
		    
		    
        //creat variables to use here
		   var aal_target = config.target;
		   var aal_relation = config.relation;
		   var aal_apikey = config.apikey;
		   var aal_geminiaion = config.geminiaion;
		   var aal_linkcolor = config.linkcolor;
		   var aal_excludewords = config.excludewords;
		   
		
		    // 2. Loop through specific Post Items
		    $.each(aal_data.items, function(index, item) {
		        
		        // Extract specific variables for this post
		        var aal_divnumber = item.divnumber;
		        var aal_postid    = item.postid;
		        var aal_aurl      = item.aurl;
		        var aal_notimes   = item.notimes;
		
		        // --- Content Generation Logic (Existing Logic) ---
		        var spydiv = document.getElementById('aalcontent_' + aal_divnumber);
		        
		        // Safety check: if the div is missing, skip this iteration
		        if (!spydiv) return; 
		
		        var parentdiv = spydiv.parentNode;
		        
		        // Check if parent is just a wrapper (using your original logic)
		        // Note: This string comparison is sensitive to spaces/quotes, kept as requested
		        if (parentdiv.innerHTML == '<div id="aalcontent_' + aal_divnumber + '></div>') {
		            parentdiv = parentdiv.parentNode;
		        }
		        
		        var acontent = aalParse(parentdiv, '');
		        if (acontent.length > 10000) {
		            acontent = acontent.substring(0, 10000);
		        }
		
		        var aal_content = encodeURIComponent(acontent);
		
		        // --- Construct the Data Object ---
		        // We combine the 'item' specifics, 'config' globals, and the 'content' we just parsed.
		        
		        var aalapidata = {
		            // Data generated just now
		            content:                aal_content,
		            
		            // Data from the specific Item
		            aal_postid:             aal_postid,
		            aurl:                   aal_aurl,
		            notimes:                aal_notimes,
		
		            // Data from the Global Config
		            apikey:                 config.apikey,
		            clickbankid:            config.clickbankid,
		            clickbankcat:           config.clickbankcat,
		            clickbankgravity:       config.clickbankgravity,
		            amazonid:               config.amazonid,
		            amazoncat:              config.amazoncat,
		            amazonlocal:            config.amazonlocal,
		            amazondisplaylinks:     config.amazondisplaylinks,
		            amazondisplaywidget:    config.amazondisplaywidget,
		            amazonactive:           config.amazonactive,
		            clickbankactive:        config.clickbankactive,
		            shareasaleactive:       config.shareasaleactive,
		            shareasaleid:           config.shareasaleid,
		            awinactive:       config.awinactive,
		            awinid:           config.awinid,
		            cjactive:               config.cjactive,
		            ebayactive:             config.ebayactive,
		            ebayid:                 config.ebayid,
		            bestbuyactive:          config.bestbuyactive,
		            bestbuyid:              config.bestbuyid,
		            walmartactive:          config.walmartactive,
		            walmartid:              config.walmartid,
		            envatoid:               config.envatoid,
		            envatosite:             config.envatosite,
		            envatoactive:           config.envatoactive,
		            rakutenactive:          config.rakutenactive,
		            rakutenid:              config.rakutenid,
		            discoveryjapanactive:   config.discoveryjapanactive,
		            discoveryjapanid:       config.discoveryjapanid,
		            discoveryjapanapikey:   config.discoveryjapanapikey,
		            excludewords:           config.excludewords,
		            geminiaion:             config.geminiaion
		        };
		        

						
				
				//generatecontent
				//var spydiv = document.getElementById('aalcontent_' + aal_divnumber);
				//var parentdiv = spydiv.parentNode;
				//if(parentdiv.innerHTML == '<div id="aalcontent_' + aal_divnumber + '></div>') parentdiv = parentdiv.parentNode;
				
				//var acontent = aalParse(parentdiv, '');
				//if(acontent.length>10000) {
				//	acontent = acontent.substring(0, 10000);
				//}
				//console.log(acontent);							
				
				//var aal_content = encodeURIComponent(acontent);
				//aalapidata = datadiv.getAttribute('data-apidata');	
				//console.log(decodeURIComponent(aal_content));
				
				
				//generate content end
						
				//var aalapidata = {content: aal_content, apikey: aal_apikey, aal_postid: aal_postid, clickbankid: aal_clickbankid, clickbankcat: aal_clickbankcat,  clickbankgravity: aal_clickbankgravity, amazonid: aal_amazonid, amazoncat: aal_amazoncat, amazonlocal: aal_amazonlocal, amazondisplaylinks: aal_amazondisplaylinks, amazondisplaywidget: aal_amazondisplaywidget, amazonactive: aal_amazonactive, clickbankactive: aal_clickbankactive, shareasaleactive: aal_shareasaleactive, shareasaleid: aal_shareasaleid, cjactive: aal_cjactive, ebayactive: aal_ebayactive, ebayid: aal_ebayid, bestbuyactive: aal_bestbuyactive, bestbuyid: aal_bestbuyid, walmartactive: aal_walmartactive, walmartid: aal_walmartid, envatoid: aal_envatoid, envatosite: aal_envatosite, envatoactive: aal_envatoactive, rakutenactive: aal_rakutenactive, rakutenid: aal_rakutenid, discoveryjapanactive: aal_discoveryjapanactive, discoveryjapanid: aal_discoveryjapanid, discoveryjapanapikey: aal_discoveryjapanapikey, aurl: aal_aurl, notimes: aal_notimes, excludewords: aal_excludewords, geminiaion: aal_geminiaion};
	
				//Cache get ajax
				$.ajax({
					type: "post", url: aal_amazon_obj.ajaxurl, data: { action: 'aal_cache_get', cachegetnonce: aal_amazon_obj.cachegetnonce, aalpostid: aal_postid },
		
		
					success: function(html){ 
						 	//console.log(html);
						 	
						 	if(html == '' || html == 'post updated' || html == 'nometa' || html == 'nopost' || html == 'expired' || html == 'failed' || html == 'settings updated') {
						 	
						 			aal_retrievelinks(aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor);
						 	
						 	}
						 	else {
						 		
						 		if(aalIsJSON(html)) {
						 		
					 			    try {
								    	
								    	var cacheresponse = $.parseJSON(html);
								 	
								 		if($.isArray(cacheresponse.links) || $.isArray(cacheresponse.amazonwidget)) {
								 	
											aal_replacement(cacheresponse.links,cacheresponse.amazonwidget,cacheresponse,aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor);		
										}
										else {
									
											aal_retrievelinks(aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor);
									
										}		
								    } catch (e) {
								        aal_retrievelinks(aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor);
								    }
						 		
	
								}
								else {
									aal_retrievelinks(aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor);								
								}		 	
						 	
						 	
						 	}
					 	
							 	
						 	
					
					}
				}); //close Cache get ajax 					
				
				
				//aal_retrievelinks(aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor);
			
		//}
			}); //close each function
		} //close if typeof
		}		//close aalinframe loop
	}); //close document ready function
	
	

	function aal_retrievelinks(aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor) {
			
		//aalapidata = {action: 'aal_update_exclude_posts',aal_exclude_posts:'aaa'};
 		$.ajax({
        type: "POST",
        url: aal_amazon_obj.aal_api_pro_url,
        data: aalapidata,
        cache: false,
        success: function(returned){  
        //console.log(returned);
                 
	      if(returned == 'wrong apikey') {
					return true;        
	      }
	                 
			var response = $.parseJSON(returned);
			var parray = response.links;
		
			var notimes = response.notimes;
			var insertid = response.insertid;
			
			var awidgets;
			if(response.amazonwidget) awidgets = response.amazonwidget;
			
			//Add to cache
			
			if(!response.keywords && aalapidata.aal_postid) {
					
				//Cache set ajax
				$.ajax({
					type: "post", url: aal_amazon_obj.ajaxurl, data: { action: 'aal_cache_set', cachesetnonce: aal_amazon_obj.cachesetnonce, aalpostid: aalapidata.aal_postid, aalcachelinks: parray, aalcacheawidget: awidgets },
	
					success: function(html){ 
	   				 	//console.log(html);
					
					}
				}); //close Cache set ajax 			
			
			
			}
			
			//End add to cache
		
			if(response.keywords) {
					if(aalapidata.amazonactive && aalapidata.amazonid) { 
				//response.keywords.forEach(function(entry) {
						$.ajax({
						type: "post", url: aal_amazon_obj.ajaxurl, data: { action: 'aal_amazon_get', security: aal_amazon_obj.security, keywords: response.keywords, notimes: notimes },
		
						success: function(html){ 
		   				 	//console.log(html);
							try {
		       				var aresults = $.parseJSON(html);
		   				} catch (e) {
		   				 	//console.log(html);
		   				 	//console.log(e);
		   				 	return;
		   				}
							//var aresults = $.parseJSON(html);
							var alinks = aresults.amazonlinks;
							var awidgets;
							if(aresults.amazonwidget) awidgets = aresults.amazonwidget;
						
							
							if(alinks) for(var i=alinks.length-1;i>=0;i--) {
								if(alinks[i].key && alinks[i].url) {
									parray.unshift(alinks[i]);
									if(parray.length>notimes) parray.pop();
								}
									
							
							}	
							//console.log(parray);
							//console.log(awidgets);
							
							//var finalLinks = JSON.stringify(parray);
							if(insertid && (alinks[0] || awidgets[0])) {
								$.ajax({
				                type: "POST",
				                url: "//api.autoaffiliatelinks.com/acache.php",
				                data: { apikey: aalapidata.apikey, insertid: insertid, parray: parray, amazonwidget: awidgets },
				                cache: false,
				                success: function(acacheres){
				                		console.log(acacheres);
				                	}
			               });
							}
								
															
							//salveaza cache si afiseaza link-uri

									if(aalapidata.aal_postid) {
					
											//Cache set ajax
											$.ajax({
												type: "post", url: aal_amazon_obj.ajaxurl, data: { action: 'aal_cache_set', cachesetnonce: aal_amazon_obj.cachesetnonce, aalpostid: aalapidata.aal_postid, aalcachelinks: parray, aalcacheawidget: awidgets },
								
												success: function(html){ 
								   				 	//console.log(html);
												
												}
											}); //close Cache set ajax 			
										
										
										}	
							
							
							
							aal_replacement(parray,awidgets,response,aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor);
						}
					}); //close jQuery.ajax 
			//	});
				}	//end if amazon active and id
				else {
					//save links without keywords
					
						
									if(aalapidata.aal_postid) {
					
											//Cache set ajax
											$.ajax({
												type: "post", url: aal_amazon_obj.ajaxurl, data: { action: 'aal_cache_set', cachesetnonce: aal_amazon_obj.cachesetnonce, aalpostid: aalapidata.aal_postid, aalcachelinks: parray, aalcacheawidget: awidgets },
								
												success: function(html){ 
								   				 	//console.log(html);
												
												}
											}); //close Cache set ajax 			
										
										
										}					
							//daca nu e setat post id, le lasa asa cum sunt si merge mai departe
							aal_replacement(parray,awidgets,response,aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor);
				
				}
			
			} //end if (response.keywords)
			else {
				aal_replacement(parray,awidgets,response,aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor);
		
			} // else if not response.keywords
                 
     		}
     
   	});
   
   
	}

	function aal_replacement(parray,awidgets,response,aalapidata,aal_divnumber,aal_target,aal_relation,aal_linkcolor) {
	
					//var datadiv = document.getElementById('aal_api_data');
					//var cssclass = datadiv.getAttribute('data-cssclass');	
					//var disclosure = datadiv.getAttribute('data-disclosure');	
					
					if (typeof aal_data !== 'undefined' && aal_data.config) {
					    var cssclass = aal_data.config.cssclass;
					    var disclosure = aal_data.config.disclosure;
					}
	
	
					var spydiv = document.getElementById('aalcontent_' + aal_divnumber);
					var parentdiv = spydiv.parentNode;
					if(parentdiv.innerHTML == '<div id="aalcontent_' + aal_divnumber + '></div>') parentdiv = parentdiv.parentNode;
					//var acontent = parentdiv.innerHTML;
					
					var price = '';
					
					
					
					//code for amazon widget
					var amazonWidget = document.createElement("div");
					amazonWidget.className = "aal-amazon-widget";
					var awhtml = '<ul>';
					if(awidgets) { 
						awidgets.forEach(function(entry) {
							if(entry.price) price = entry.price;
							else price = '';
							awhtml += '<li>';
							awhtml += '<a href="'+ entry.url +'" target="_blank"><img src="'+ entry.image +'" /><br /><span>'+ text_truncate(entry.title,45) +'</span><br /><span>'+ price +'</span></a>';
							awhtml += '</li>';
						});
					}
					
					
				
					
					awhtml += '</ul>';
					awhtml += '<div class="aal_clear"></div>';
					amazonWidget.innerHTML = awhtml;				
					
					//end amazon widget
					
					if(parray) parray.forEach(function(entry) {
					
					var aal_lcstyle = '';
					if(aal_linkcolor) aal_lcstyle = 'style="color:' + aal_linkcolor +';"';					
					
					var re2 = new RegExp("(?!(?:[^<\\[]+[>\\]]|[^>\\]]+<\/a>))\\b("+ entry.key +")\\b","i");
					var re = new RegExp("(?!(?:[^<\\[]+[>\\]]|[^>\\]]+<\/a>))(?!(?:[^<\\[]+[>\\]]|[^>\\]]+<\/h.>))(?!(?:[^<\\[]+[>\\]]|[^>\\]]+<\/script.>))\\b("+ entry.key +")\\b","i");
					var mat = '<a title="$1" class="'+ cssclass +' aalauto" target="' + aal_target + '" ' + '" rel="' + aal_relation + '" ' + aal_lcstyle + ' href="'+ entry.url +'">$1</a>' + disclosure;
					//acontent = acontent.replace(re, '<a title="$1" class="'+ cssclass +' aalauto" target="' + aal_target + '" ' + '" rel="' + aal_relation + '" ' + aal_lcstyle + ' href="'+ entry.url +'">$1</a>');	    
				   
				   	rt = 'go';
					   aalTree(parentdiv,re,mat);  
					    
					});
					
				
				  
				   
				   
				   
						
					if(parray) parray.forEach(function(entry) {
						
						$('ul.aal_widget_holder').each(function(i, obj) {
				    		$( this ).append( '<li><a href="' + entry.url + '">' + entry.key + '</a></li>' );
						});    
					    
					    
					    
					    
					});
				
				
					var reg = '/(?!(?:[^<\[]+[>\]]|[^>\]]+<\/a>))\b($name)\b/imsU';
					var rep = '<a title="$1" class="aal" target="$targeto" relation="$relo" href="$url">$1</a>' + disclosure;
				
				
					//parentdiv.innerHTML = acontent;
					
					document.getElementById('aalcontent_' + aal_divnumber).appendChild(amazonWidget);
				
	
	
	
	}
	
 	function aalParse(obj,strcontent){
        //var obj = obj;
        if (obj.hasChildNodes()) {
          var child = obj.firstChild;
          while (child) {
            if ((child.nodeType === 1 || child.nodeType === 3 ) && child.nodeName != 'SCRIPT' && child.nodeName != 'A' && child.nodeName != 'IMG' && child.nodeName[0] != 'H'    ){
              		
              	if(child.nodeType === 3 && child.nodeValue !== null && child.nodeValue.replace(/\s/g, "").length>2 ) {
           			
              		var astr = child.nodeValue;
            		strcontent = strcontent + "." + astr;
              		
              	}
              	strcontent = aalParse(child,strcontent);
            }
            child = child.nextSibling;
          }
        }
        
        return strcontent;
      }	


 	function aalTree(obj,re,mat){
        //var obj = obj;
        if (obj.hasChildNodes()) {
          var child = obj.firstChild;
          var con = 'go';
          while (child && con == 'go') {
          	var p = child.parentNode;
            if ((child.nodeType === 1 || child.nodeType === 3 ) && child.nodeName != 'SCRIPT' && child.nodeName != 'A' && child.nodeName != 'IMG' && child.nodeName[0] != 'H'    ){
              		
              	if(child.nodeType === 3 && child.nodeValue !== null && child.nodeValue.replace(/\s/g, "").length>2 ) {
           			
              		var astr = child.nodeValue;
              		var rstr = astr.replace(re,mat);
              		
              							
						              		
              		if(rstr != astr) {
              			
              			var newel = document.createElement('div');
					   	newel.innerHTML = rstr;
							
					  		var c = newel.firstChild;
					  		while(c) {
					  			
					  			var d = c.cloneNode(true)
								p.insertBefore(d, child);	  		
					  		   
					  		c = c.nextSibling;
					  		}
              			p.removeChild(child);
              			return 'stop';
              		}
              		
              	}
              	con = aalTree(child,re,mat);
            }
            child = child.nextSibling;
          }
        }
        
        return 'go';
      }
	
	
	
	
	
	function aalInIframe () {
    	try {
      	  return window.self !== window.top;
   	 } catch (e) {
    	    return true;
   	 }
	}
	
	function aalIsJSON(str) {
    try {
        $.parseJSON(str);
    } catch (e) {
        return false;
    }
    return true;
}
	
	
	
	text_truncate = function(str, length, ending) {
    if (length == null) {
      length = 100;
    }
    if (ending == null) {
      ending = '...';
    }
    if (str.length > length) {
      return str.substring(0, length - ending.length) + ending;
    } else {
      return str;
    }
  };

})(jQuery);