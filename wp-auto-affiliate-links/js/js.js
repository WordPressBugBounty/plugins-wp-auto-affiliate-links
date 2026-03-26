(function($) {

$(document).ready(function() { 


/* Sorting lists */

$( "a.aal-sort-asc" ).hover(function() {
  $( this ).find(".aal-sort-span").css('visibility', 'visible');
}, function() {
  $( this ).find(".aal-sort-span").css('visibility', 'hidden');
});

$( "a.aal-sorted-asc" ).hover(function() {
  $( this ).find(".aal-sort-span").addClass('aal-sort-desc');
}, function() {
  $( this ).find(".aal-sort-span").removeClass('aal-sort-desc');
});

$( "a.aal-sorted-desc" ).hover(function() {
  $( this ).find(".aal-sort-span").removeClass('aal-sort-desc');
}, function() {
  $( this ).find(".aal-sort-span").addClass('aal-sort-desc');
});

	//Open link color picker in general settings page
	$('#aal_linkcolor').wpColorPicker();


	//Dismiss button for aal notification
	$('#aal_dismiss_link').click(function() {
	    aalDismiss();
	});

	//Check if the URL is valid
    function isValidURL(url){
    var RegExp = /(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;

    if(RegExp.test(url)){
        return true;
    }else{
        return false;
    }

    }

	//Show advanced options
		$("a.aal_form_toggle_advanced").on('click', function() {
              

			$(".aal_form_advanced_options").toggle();
			$("a.aal_form_toggle_advanced").toggle();

                return false;
        }); 
        
//Show advanced options on link edit screen    
$("a.aal_edit_show_advanced").on('click', function() {
              

			$(this).toggle();
			$(this).parent().find("a.aal_edit_hide_advanced").toggle();
			$(this).parent().find(".aal_edit_advanced").toggle();

                return false;
});  


//Hide advanced options on link edit screen
$("a.aal_edit_hide_advanced").on('click', function() {
              

			$(this).toggle();
			$(this).parent().find("a.aal_edit_show_advanced").toggle();
			$(this).parent().find(".aal_edit_advanced").toggle();

                return false;
});           



//Delete Link called through AJAX
$(".aalDeleteLink").on('click', function() {
              
    var answer = confirm("Are you sure you want to delete this automated link?");
    
        if (answer){
        
        var linkContainer = $(this).parent().parent();
        var id = $(this).attr("id");
        var data = {action: 'aal_delete_link', id: id, aal_nonce: ajax_script.aal_nonce};
        
            $.ajax({
                    type: "POST",
                    url: ajax_script.ajaxurl,
                    data: data,
                    cache: false,
                    success: function(){
                    linkContainer.slideUp('slow', function() {$(this).remove();});
                                        }
                });
        }

                return false;
        }); 
        
        
//Update link called trough ajax  ( 25.04.2022 )
$(".aalUpdateLink").on('click', function() {
              
        
        var linkContainer = $(this).parent().parent();
        var id = $(this).attr("id");
        
        $(this).parent().find(".aal_spinner").css("background-image", "url(images/spinner.gif)");
        $(this).parent().find(".aal_spinner").css('visibility', 'visible');
    
        var linkval = {};
		  $.each(linkContainer.serializeArray(), function(i, field) {
   		 linkval[field.name] = field.value;
		  });
		  
        var data = {action: 'aal_update_link', id: id, aal_nonce: ajax_script.aal_nonce, aal_link: linkval.aal_link, aal_keywords: linkval.aal_keywords, aal_disabled: linkval.aal_disabled, aal_title: linkval.aal_title, aal_samelinkmeta: linkval.aal_samelinkmeta, aal_disclosureoff: linkval.aal_disclosureoff  };
    
        
        
            $.ajax({
                    type: "POST",
                    url: ajax_script.ajaxurl,
                    data: data,
                    cache: false,
                    success: function(){
                    /* link updated */
                    		//console.log( data );
                                        }
                })
                .done(function( returned ) {
  						  //console.log(  returned );
  						  linkContainer.find(".aal_spinner").css("background-image", "url(images/yes.png)");
  						  //linkContainer.find(".aal_spinner").css('visibility', 'hidden');
 					 })
 					 .fail(function() {
 					 		linkContainer.find(".aal_spinner").css("background-image", "url(images/no.png)");
    						//alert( "error" );
  					 });
                

		

                return false;
        }); 


        
        


// Add Link (Called through AJAX)

    $("#aal_add_new_link_form").submit(function() {
        
        var aal_keywords = $("#aal_formkeywords").val();
        var aal_link = $("#aal_formlink").val();
         var aal_title = $("#aal_formtitle").val();
        
        if(isValidURL(aal_link)){
        
        if(aal_keywords!=''){
        
				return true;        
        
        	
         /* $("#aal_formlink").val("");
            $("#aal_formkeywords").val("");
            $("#aal_formtitle").val("");
            
            

            var data = {
                        action: 'aal_add_link',
                        aal_link: aal_link,
                        aal_keywords:aal_keywords,
                        aal_title:aal_title
                       };

            $.ajax({
                    type: "POST",
                    url: ajax_script.ajaxurl,
                    data: data,
                    dataType: "json",
                    cache: false,
                    success: function(data){
                        
                    $(".aal_links").append('<li class="aal_links_box aal_new_link"><input type="checkbox" name="aal_massids[]" value="' + data['aal_delete_id'] + '" /> Link: <input style="margin: 5px 10px;width: 32%;" type="text" name="aal_link" value="'+ data['aal_new_url'] +'" />\
                                                  Keywords: <input style="margin: 5px 10px;width: 15%;" type="text" name="aal_keywords" value="'+aal_keywords+'" /> \
                                                   Title: <input style="margin: 5px 10px;width: 10%;" type="text" name="aal_title" value="'+aal_title+'" /> \
                       										<a href="javascript:;" id="' + data['aal_delete_id'] + '" class="aalDeleteLink button-primary" onclick="aalOnClickDeleteLink(this,' + data['aal_delete_id'] + ')" >Delete</a> \
                                                </li>');
                        //scrool to the added element
                    		// $('li.aal_new_link')[0].scrollIntoView( true );                        
                        //show pop-up with confirmation
                     	$("#aal_addlink_confirmation").show();

                    }

               }); */
            
            }else {
            	alert('Keyword must not be empty');
            	return false;
            }
       
        }else {
        		alert('Link entered is not valid, it should contain http:// or https:// and cannot be blank');
        		return false;
        	}
    
        return false;
     }); 
     
     
// Reset stats

    $("#aal_statsresetform").submit(function() {
        
        var aal_resetstats = $("#aal_statsreset").val();

        
        if(aal_resetstats == 'yes'){
        	
        		return confirm("Are you sure want to reset all stats? This action can't be undone");
        
        	}
        	else {
        		return false;
        	}
    
        return false;
     }); 
     
//General Options CHANGE

$("#aal_changeOptions").submit(function() {
        
      
        
            var aal_iscloacked = $("#aal_iscloacked").is(":checked");
            var aal_cloakurl = $("#aal_cloakurl").val();
            var aal_langsupport = $("#aal_langsupport").is(":checked");
            var aal_showhome= $("#aal_showhome").is(":checked");
            var aal_showlist= $("#aal_showlist").is(":checked");
            var aal_showwidget= $("#aal_showwidget").is(":checked");
            var aal_showexcerpt= $("#aal_showexcerpt").is(":checked");
            var aal_showhtags= $("#aal_showhtags").is(":checked");
            var aal_notimes= $("#aal_notimes").val();
            var aal_pluginstatus= $("#aal_pluginstatus").val();
            var aal_notimescustom= $("#aal_notimescustom").val();
            var aal_samekeyword= $("#aal_samekeyword").val();
            var aal_samelink= $("#aal_samelink").val();
            var aal_linkdistribution = $("#aal_linkdistribution").val();
            var aal_display= $("#aal_display").val();
            var aal_cssclass= $("#aal_cssclass").val();
            var aal_target= $('#aal_changeOptions input[type=radio][name=aal_target]:checked').val();
            var aal_relation= $('#aal_changeOptions input[type=radio][name=aal_relation]:checked').val();
            
 
        
            
            var aal_displayca = [];
            
            
           $("#aal_changeOptions input#aal_displayc:checkbox:checked").each(function(){
    			 aal_displayca.push($(this).val());
    			//return aal_displayca;
  			  });
  			  
  			 var aal_displayc = JSON.stringify(aal_displayca);
            
           // console.log(aal_displayc);
            //return false;
            
            var data = {
                        action: 'aal_change_options',
                        aal_iscloacked: aal_iscloacked,
                        aal_cloakurl: aal_cloakurl,
                        aal_langsupport: aal_langsupport,
                        aal_showhome:aal_showhome,
                        aal_showlist:aal_showlist,
                        aal_showwidget:aal_showwidget,
                        aal_showexcerpt:aal_showexcerpt,
                        aal_showhtags:aal_showhtags,
                        aal_notimes:aal_notimes,
                        aal_notimescustom:aal_notimescustom,
                        aal_samekeyword:aal_samekeyword,
                        aal_samelink:aal_samelink,
                        aal_linkdistribution:aal_linkdistribution,
                        aal_target:aal_target,
                        aal_relation:aal_relation,
                        aal_display:aal_display,
                        aal_cssclass:aal_cssclass,
                        aal_pluginstatus:aal_pluginstatus,
                        aal_displayc:aal_displayc
                       };
                       
                      //console.log(data);

            $.ajax({
                    type: "POST",
                    url: ajax_script.ajaxurl,
                    data: data,
                    cache: false,
                    success: function(){

                     //$(".aal_add_link_status").text('Options Saved');
							//alert("Settings saved");
							$("#aal_settings_confirmation").show();
                    }

               });
            
       
    
        return false;
     }); 
 
 
$("#aal_add_exclude_posts_form").submit(function() {
    
    var id_input = $("#aal_add_exclude_post_id").val();
    var nonce_val = this.aal_excludepostbyid_nonce.value; // Capture nonce outside the loop
    
    // Split the string by comma, trim spaces, and remove empty entries
    var idsArray = id_input.split(',').map(function(item) {
        return $.trim(item);
    }).filter(function(item) {
        return item !== '';
    });

    // Prevent submission if the field was empty
    if (idsArray.length === 0) return false;
    
    // Clear the status area before adding new statuses
    $(".aal_exclude_status").empty();

    // Loop through each ID and make a separate AJAX call
    $.each(idsArray, function(index, single_id) {
        
        var data = {
            action: 'aal_add_exclude_posts',
            aal_post: single_id, // Send just the single ID for this loop iteration
            aal_excludepostbyid_nonce: nonce_val
        };

        $.ajax({
            type: "POST",
            url: ajax_script.ajaxurl,
            data: data,
            cache: false,
            success: function(response){
                
                if(response == 'nopost') { 
                    alert('The post ID ' + single_id + ' does not correspond with any post or page.'); 
                }
                else if(response == 'duplicate') { 
                    alert('A post with the ID ' + single_id + ' is already excluded.'); 
                }
                else {  
                    // Append the unique row for this ID and its specific title/response
                    $(".aal_exclude_posts").append(
                        '<div class="aal_excludeditem">' +
                            '<div class="aal_excludedcol aal_excludedidcol">' + single_id + '</div>   ' + 
                            response + 
                            '<div class="aal_excludedcol">' +
                                '<a href="javascript:;" class="aal_delete_exclude_link">' +
                                    '<img src="' + ajax_script.aal_plugin_url + 'images/delete.png"/>' +
                                '</a>' +
                            '</div><br/>' +
                        '</div>' +
                        '<div style="clear: both;"></div>'
                    );
                    
                    // Append a success message for this specific ID
                    $(".aal_exclude_status").append('<p><i>Exclude ID ' + single_id + ' added!</i></p>');
                }
            }
        });
    });

    // Clear the input field after the requests are fired off
    $("#aal_add_exclude_post_id").val('');
    
    return false;
});


$(".aal_exclude_posts").on('click', '.aal_delete_exclude_link', function(e) {
    e.preventDefault();
        
    var answer = confirm("Are you sure you want to delete this excluded link?");
    
    if (answer) {
        // Find the exact row container
        var linkContainer = $(this).closest('.aal_excludeditem');
        
  
        var removeItem = linkContainer.children(".aal_excludedcol:first-child").text().trim();
        
 
        linkContainer.slideUp('slow', function() { $(this).remove(); });
        

        var posts = [];
        $(".aal_excludeditem").each(function() {
            var currentItem = $(this).children(".aal_excludedcol:first-child").text().trim();
            if (currentItem !== removeItem && currentItem !== "") {
                posts.push(currentItem);
            }
        });
        
        // Prepare data with the GLOBAL nonce
        var data = {
            action: 'aal_update_exclude_posts',
            aal_exclude_posts: posts.join(','), 
            aal_global_nonce: $('#aal_global_exclude_nonce').val() 
        };
          
        $.ajax({
            type: "POST",
            url: ajax_script.ajaxurl,
            data: data,
            cache: false,
            success: function() {

            }
        });
    }
});



// --- 1. SEARCH LISTENER ---
// 1. Trigger the search when the button is clicked
$('#aal_trigger_search_btn').on('click', function(e) {
    e.preventDefault();
    var searchTerm = $('#aal_search_post_input').val();

    if (searchTerm.length < 3) {
        alert('Please enter at least 3 characters to search.');
        return;
    }

    $('#aal_search_spinner').show();
    $('#aal_search_results_container').hide(); // Hide previous results

    $.ajax({
        url: ajax_script.ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'aal_search_posts_to_exclude',
            search_term: searchTerm
        },
        success: function(response) {
            $('#aal_search_spinner').hide();
            var $select = $('#aal_search_results');
            $select.empty();

            if (response && response.length > 0) {
                // Populate the multi-select box
                $.each(response, function(index, post) {
                    $select.append($('<option>', {
                        value: post.id,
                        text: post.title + ' (ID: ' + post.id + ')'
                    }));
                });
                $('#aal_search_results_container').show();
            } else {
                alert('No posts found matching that title (or they are already excluded).');
            }
        },
        error: function() {
            $('#aal_search_spinner').hide();
            alert('An error occurred while searching. Check console for details.');
        }
    });
});

// 2. "Select All" Button Logic
$('#aal_select_all_btn').on('click', function(e) {
    e.preventDefault();
    $('#aal_search_results option').prop('selected', true);
});

// 3. Submit selected posts to be excluded
$('#aal_submit_search_exclude').on('click', function(e) {
    e.preventDefault();

    var selectedIds = $('#aal_search_results').val(); 
    var nonce_val = $('#aal_excludepostbyid_nonce_search').val();

    if (!selectedIds || selectedIds.length === 0) {
        alert('Please select at least one post from the list.');
        return;
    }

    $(".aal_exclude_status").empty();

    // Loop through the selected IDs and fire the AJAX call
    $.each(selectedIds, function(index, single_id) {
        var data = {
            action: 'aal_add_exclude_posts',
            aal_post: single_id,
            aal_excludepostbyid_nonce: nonce_val
        };

        $.ajax({
            type: "POST",
            url: ajax_script.ajaxurl,
            data: data,
            cache: false,
            success: function(response){
                if(response == 'nopost') { 
                    alert('The post ID ' + single_id + ' does not correspond with any post or page.'); 
                }
                else if(response == 'duplicate') { 
                    // Do nothing, or you can log it to console
                }
                else {  
                    $(".aal_exclude_posts").append(
                        '<div class="aal_excludeditem">' +
                            '<div class="aal_excludedcol aal_excludedidcol">' + single_id + '</div>   ' + 
                            response + 
                            '<div class="aal_excludedcol">' +
                                '<a href="javascript:;" class="aal_delete_exclude_link">' +
                                    '<img src="' + ajax_script.aal_plugin_url + 'images/delete.png"/>' +
                                '</a>' +
                            '</div><br/>' +
                        '</div>' +
                        '<div style="clear: both;"></div>'
                    );
                    
                    $(".aal_exclude_status").append('<p><i>Exclude ID ' + single_id + ' added!</i></p>');
                }
            }
        });
    });

    // Cleanup interface after submission
    $('#aal_search_results_container').hide();
    $('#aal_search_results').empty();
    $('#aal_search_post_input').val('');
});

//End exclude posts by search





}); 



var aal_selectclicked = false;

$( document ).ready(function() {
 $('#aal_selectall').click( function () {
 	
 	if(aal_selectclicked) {
		aal_selectclicked = false; 	
		$('#aal_panel3 :checkbox').each(function() {
          this.checked = false;
     	 });
     	 $(this).val('Select all');
     }
     else {
     	
 		 aal_selectclicked = true;
   		 $('#aal_panel3 :checkbox').each(function() {
          this.checked = true;
     	 });
     	 $(this).val('Deselect all');
     }
  });

	return false;
});





//show custom links

$(document).ready(function() {
      canvas = document.getElementById('aalshowcustomlinks');
      if(canvas) { 
      	apikey = canvas.getAttribute('data-apikey');
      	network = canvas.getAttribute('data-network');
      	
      	$('#aalshowcustomlinks').find(".spinner").css('visibility', 'visible');
      
		apidata = { network: network, apikey: apikey };

	$.ajax({
         type: "GET",
         url: "//api.autoaffiliatelinks.com/getcustomlinks.php",
         data: apidata,
         cache: false,
         success: function(returned){
         	
         	//console.log('succes');   
         	//console.log(returned); 
         	
         	
         	canvas = document.getElementById('aalshowcustomlinks');
         	
         	
         	if(!returned || returned == 'there was an error' ) {
					canvas.innerHTML = 'No links added yet';	         		
         		return false;
				}	                    
                  
				var farray = $.parseJSON(returned);
				//console.log(farray.list.q);
				//console.log(farray);

				if(!farray.number) {
					canvas.innerHTML = 'There are no links to be displayed';	         		
         		return false;			
				}

					
					while (canvas.firstChild) {
					    canvas.removeChild(canvas.firstChild);
					}
					
					
					 var div = document.createElement('div');
					div.className = "aalcustomlinkdeleteall";
					var htmltext = '<span class=""><a href="" onclick="aalCustomLinkDeleteAll();" >Delete All Links</a></span><div style="clear: both;"></div>';
					div.innerHTML = htmltext;

	    			canvas.appendChild(div);


				farray.links.forEach(function(entry) {
	    
					//console.log(entry.title + entry.link);	 
					
					var deletelink = '';   
					
					 var div = document.createElement('div');
					div.className = "aalcustomlink_item";
					var htmltext = '<span class="aalcustomlink_url"><a href="' + entry.link +'">Link</a>&nbsp;&nbsp;&nbsp;</span><span class="aalcustomlink_url" ><a onclick="return aalCustomLinkDelete('+ entry.id +');" style="color: #ff0000;" href="' + deletelink +'">Delete</a>&nbsp;&nbsp;&nbsp;</span><span class="aalcustomlink_title">' + entry.title + '</span><span class="aalcustomlink_merchant">' + entry.merchant +'      </span><div style="clear: both;"></div>';
					div.innerHTML = htmltext;

	    			canvas.appendChild(div);
	    			
				});
	
                   
     		}
     
   });      
      
      
      
      } 
      else {  }
});


//AAL javascript code for keyword suggestions

$(document).ready(function() {
	$(".aal_sugkey").click(function() { 
 		if($("#aal_formkeywords").val())  {
 				$("#aal_formkeywords").val($("#aal_formkeywords").val() + ", " + $(this).attr("title"));
 			}
 			else { 
 				$("#aal_formkeywords").val($(this).attr("title"));
 		}
 		$(window).scrollTop(0);
		$("#aal_formkeywords").addClass( "yellowhighlight" );
 		$(this).hide();
	});


	$("#aal_moresug").click(function() {
 		$("#aal_extended").toggle();
 		
 		
         if ($("#aal_extended").is(":visible")) {
            $("#aal_extended").html(''); 
            $("#aal_extended").append('<div class="aal_loader_spinner"></div>'); 


 		
 			
        
            $.ajax({
                    type: "POST",
                    url: ajax_script.ajaxurl,
                    data: {action: 'aal_kw_suggestion', aal_kw_nonce: ajax_script.aal_kw_nonce},
                    cache: false,
                    success: function(returned){
                    		//console.log(returned);
                    		$("#aal_extended").find(".aal_loader_spinner").remove();
                    		$("#aal_extended").html(returned);
                    		
                    		
	$(".aal_sugkey").click(function() { 
 		if($("#aal_formkeywords").val())  {
 				$("#aal_formkeywords").val($("#aal_formkeywords").val() + ", " + $(this).attr("title"));
 			}
 			else { 
 				$("#aal_formkeywords").val($(this).attr("title"));
 		}
 		$(window).scrollTop(0);
		$("#aal_formkeywords").addClass( "yellowhighlight" );
 		$(this).hide();
	});                    		
                    		
                    		
                    		
                    		
                                        }
                });
		 		
 		}
 		
 		
	});

});



//Aal notice dismiss function
	function aalDismiss() {


        var data = {action: 'aal_dismiss_notice'};
        
            $.ajax({
                    type: "POST",
                    url: ajax_script.ajaxurl,
                    data: data,
                    cache: false,
                    success: function(){
                    $("#aal_notice_div").slideUp('slow', function() {$("#aal_notice_div").remove();});
                                        }
                });
        	
		
		
	}
	
	


//Show-hide add link toast
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('aal_add_link_success')) {
        const $notice = $('#aal-add-link-inline-notice');
        
        // Slide down the notice
        $notice.slideDown(400);
        
        // Optional: Auto-hide after a few seconds, or keep it visible
        setTimeout(function() {
            $notice.slideUp(800);
            
            // Clean the URL without reloading
            const cleanUrl = window.location.href.split('&aal_add_link_success')[0].split('?aal_add_link_success')[0];
            window.history.replaceState({}, document.title, cleanUrl);
        }, 5000);
    }
});



//Send keyword suggestions to API
jQuery(document).ready(function($) {
    $('#aal_get_ai_suggestions').on('click', function(e) {
        e.preventDefault();
        
        const affiliateUrl = $('#aal_formlink').val();
        if (!affiliateUrl || affiliateUrl.length < 10) {
            alert('Please enter a valid affiliate link first.');
            return;
        }

        const $btn = $(this);
        const $spinner = $('#aal_ai_spinner');

        $btn.prop('disabled', true);
        $spinner.css('visibility', 'visible');

        // Tell WordPress to scrape the URL locally
        $.post(ajax_script.ajaxurl, {
            action: 'aal_get_ai_keywords',
            affiliate_url: affiliateUrl,
            aal_nonce: ajax_script.aal_nonce
        }, function(response) {
            if (response.success) {
                // Append generated keywords to the input field
                const currentKeywords = $('#aal_formkeywords').val();
                const newKeywords = response.data.keywords.join(', ');
                
                $('#aal_formkeywords').val(currentKeywords ? currentKeywords + ', ' + newKeywords : newKeywords);
                
                // Trigger the 'inline toast' 
                $('#aal-inline-notice').find('span:last').text('AI Keywords added!');
                $('#aal-inline-notice').slideDown().delay(3000).slideUp();
            } else {
            	if (response.data && response.data.code === 'scrape_failed') {
            			    $('#aal_ai_fallback_container').slideDown();
    							 $('#aal_ai_spinner').css('visibility', 'hidden');
    							 $('#aal_get_ai_suggestions').prop('disabled', false);
            	}
            	else {
                alert('AI Error: ' + (response.data || 'Could not generate suggestions.'));
             }
            }
              
            
        }).always(function() {
            $btn.prop('disabled', false);
            $spinner.css('visibility', 'hidden');
        });
    });
    
    
	$('#aal_generate_from_hint').on('click', function(e) {
        e.preventDefault();
        const hint = $('#aal_product_hint').val();
        const $btn = $(this);
        
        if (!hint) { alert('Please enter a hint.'); return; }

        $btn.prop('disabled', true);
        $('#aal_ai_spinner').css('visibility', 'visible');

        $.post(ajax_script.ajaxurl, {
            action: 'aal_get_ai_keywords', 
            product_hint: hint,           
            aal_nonce: ajax_script.aal_nonce
        }, function(response) {
        		if (response.success) {
               // Append generated keywords to the input field
                const currentKeywords = $('#aal_formkeywords').val();
                const newKeywords = response.data.keywords.join(', ');
                
                $('#aal_formkeywords').val(currentKeywords ? currentKeywords + ', ' + newKeywords : newKeywords);
                $('#aal_ai_fallback_container').slideUp();
                
                // Trigger the 'inline toast' 
                $('#aal-inline-notice').find('span:last').text('AI Keywords added!');
                $('#aal-inline-notice').slideDown().delay(3000).slideUp();
             }
             else {
             		alert('AI Error: ' + (response.data || 'Could not generate suggestions.'));      
             }
        }).always(function() {
            $btn.prop('disabled', false);
            $('#aal_ai_spinner').css('visibility', 'hidden');
        });
    });    
    
    
    
    
    
});

	
})(jQuery);



        
//Onclick delete function
function aalOnClickDeleteLink(el,linkid) {
              
    var answer = confirm("Are you sure you want to delete this automated link?");
    
        if (answer){
        
        var linkContainer = jQuery(el.parentNode);
        var id = linkid
        var data = {action: 'aal_delete_link',id: id};
        
            jQuery.ajax({
                    type: "POST",
                    url: ajax_script.ajaxurl,
                    data: data,
                    cache: false,
                    success: function(){
                    linkContainer.slideUp('slow', function() {jQuery(this).remove();});
                                        }
                });
        }

                return false;
        }

function aal_masscomplete() {

		var checkboxes = document.getElementsByName('aal_massids[]');
		var vals = "";
		for (var i=0, n=checkboxes.length;i<n;i++) {
		  if (checkboxes[i].checked) 
		  {
		  vals += ","+checkboxes[i].value;
		  }
		}
		if (vals) vals = vals.substring(1);
	
	
		document.getElementById('aal_massstring').value = vals;

		return confirm('Are you sure you want to delete all selected links ?');
}


//Frequency selector, display custom value
function aalFrequencySelector() {
	
	var es = document.getElementById('aal_notimes');
	

	if(es.options[es.selectedIndex].value == 'custom') { 
	
		document.getElementById('aal_custom_frequency').style.display = 'block';
		
	}
	else {
		document.getElementById('aal_custom_frequency').style.display = 'none';
	}
	

}



function aalCustomLinkDelete(linkid) {
	var answer = confirm("Are you sure you want to delete this link  ?")
	if (answer){
		
		
      var canvas = document.getElementById('aalshowcustomlinks');
      if(canvas) { 
      	apikey = canvas.getAttribute('data-apikey');
      	network = canvas.getAttribute('data-network');
		
		var apidata = { network: network, apikey: apikey, linkid: linkid };

	jQuery.ajax({
         type: "GET",
         url: "//api.autoaffiliatelinks.com/deletecustomlinks.php",
         data: apidata,
         cache: false,
         success: function(returned){
         	
         	//console.log('succes');   
         	console.log(returned); 
         	
         	
         	var canvas = document.getElementById('aalshowcustomlinks');
         	
         	
         	if(!returned || returned == 'there was an error' ) {
					alert("There was a problem completing your action, please refresh the page and try again");	         		
         		return false;
				}	                    
                  
				//var farray = $.parseJSON(returned);


					
					while (canvas.firstChild) {
					    canvas.removeChild(canvas.firstChild);
					}
	
                   
     		}
     
   });     		
		
		
		
	}	
	
	//return false;
		
	}
	else{
		return false;
	}
	
	//return false;
}



function aalCustomLinkDeleteAll() {

var answer = confirm("Are you sure you want to delete all the links below  ?")
	if (answer){
		
		
      var canvas = document.getElementById('aalshowcustomlinks');
      if(canvas) { 
      	apikey = canvas.getAttribute('data-apikey');
      	network = canvas.getAttribute('data-network');
		
		var apidata = { network: network, apikey: apikey, massdelete: 'all' };

	jQuery.ajax({
         type: "GET",
         url: "//api.autoaffiliatelinks.com/deletecustomlinks.php",
         data: apidata,
         cache: false,
         success: function(returned){
         	
         	//console.log('succes');   
         	console.log(returned); 
         	
         	
         	var canvas = document.getElementById('aalshowcustomlinks');
         	
         	
         	if(!returned || returned == 'there was an error' ) {
					alert("There was a problem completing your action, please refresh the page and try again");	         		
         		return false;
				}	                    
                  
				//var farray = $.parseJSON(returned);


					
					while (canvas.firstChild) {
					    canvas.removeChild(canvas.firstChild);
					}
	
                   
     		}
     
   });     		
		
		
		
	}	
	
	//return false;
		
	}
	else{
		return false;
	}




//return false;
}



function aalActivateModule(name) {

	document.forms["aal_apikey_form"][name].selectedIndex = 1;
	document.createElement('form').submit.call(document.forms["aal_apikey_form"]);
	return false;

}


	
function aalCopyCloak(el) {
 
 
  var id = el.getAttribute('data-id');
  document.getElementById("edit_advanced_" + id).style.display = "block";
  var cloak = document.getElementById("aal-cloak-" + id);

  /* Select the text field */
  cloak.select();
  cloak.setSelectionRange(0, 99999); /*For mobile devices*/

  /* Copy the text inside the text field */
  document.execCommand("copy");

  /* Alert the copied text */
  alert("Copied the link: " + cloak.value);
}


//Code for notification


jQuery(document).ready(function($) {
    $(document).on('click', '.aal-notice-dismiss-link', function(e) {
        e.preventDefault();
        
        $(this).closest('.notice').find('.notice-dismiss').trigger('click');
    });

    // Existing dismissal logic (make sure it handles the versioning correctly)
    $(document).on('click', '.aal-notice-pro .notice-dismiss', function() {
        var version = $(this).closest('.notice').data('notice-ver');

        $.post(ajaxurl, {
            action: 'aal_dismiss_notice',
            version: version,
            security: ajax_script.aal_nonce
        });
    });
});

