jQuery(document).ready(function() { 


	var apikey = document.getElementById('aal_apikey').getAttribute('data-apikey');
	//alert(apikey);

	jQuery('.gl_spinner').css('visibility', 'visible');
	
	
	var table = document.getElementById("aal_gltable");
	
	aalapidata = { apikey: apikey };
  jQuery.ajax({
                    type: "GET",
                    url: "//autoaffiliatelinks.com/api/getlinks.php",
                    data: aalapidata,
                    cache: false,
                    success: function(returned){
                    	
                    	jQuery('.gl_spinner').css('visibility', 'hidden');
                   // console.log('succes');         
                   
                  //console.log(returned);
                    
						var larray = jQuery.parseJSON(returned);
						//console.log(larray);
					if (typeof larray.links !== 'undefined') {
						larray.links.forEach(function(entry) {
							
							
							var urldomain = new URL(entry.url);
							
							if(urldomain.hostname.replace('www.','') == window.location.hostname.replace('www.','')) {
														var kwlist = '';
												if (typeof entry.keywords !== 'undefined' || typeof entry.keywords !== 'null') {
							   
												var keywords = jQuery.parseJSON(entry.keywords);
														if(keywords.constructor !== Array)  keywords = keywords.links;
														if(keywords.constructor === Array) 
															keywords.forEach(function(keyword) {
															
															//console.log('aaa');
															
															kwlist = kwlist + '<a href="' + keyword.url + '">'+ keyword.key +'</a>,&nbsp;';
														
														
														});
														
													}
													if(kwlist=='') kwlist = 'No links displayed for this post';
							
								    
												var tr = document.createElement('tr');
												var td1 = document.createElement('td'); td1.innerHTML = '<a href="' + entry.url + '">' + entry.url + '</a>'; tr.appendChild(td1);	
												var td2 = document.createElement('td'); td2.innerHTML = kwlist; tr.appendChild(td2);
												var td3 = document.createElement('td'); td3.innerHTML = ''; tr.appendChild(td3);
										
												
												table.appendChild(tr);	    
	    
								}    
						});
						
					}
					else {
						var tr = document.createElement('tr');	
						var td1 = document.createElement('td'); td1.innerHTML = 'There are no links generated to show here. Please let us know on <a href="https://autoaffiliatelinks.com/support-help/support-contact/">Support page</a>'; 
						td1.colSpan = "3";						
						tr.appendChild(td1);
						table.appendChild(tr);				
					
					}

                    
     }
     
   });



});