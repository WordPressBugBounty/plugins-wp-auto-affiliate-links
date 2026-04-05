 (function($) {
    $(document).ready(function() { 
    
    

//exclude post
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
//exclude post end


// AJAX search for authors to exclude
$('#aal_trigger_author_search_btn').on('click', function(e) {
    e.preventDefault();
    var searchTerm = $('#aal_search_author_input').val();

    if (searchTerm.length < 3) {
        alert('Please enter at least 3 characters to search for an author.');
        return;
    }

    $('#aal_author_search_spinner').show();

    $.ajax({
        url: ajax_script.ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'aal_search_authors_to_exclude',
            search_term: searchTerm
        },
        success: function(response) {
            $('#aal_author_search_spinner').hide();
            var $select = $('#aal_search_author_results');
            $select.empty();

            if (response && response.length > 0) {
                $.each(response, function(index, user) {
                    $select.append($('<option>', {
                        value: user.id,
                        text: user.name + ' (' + user.login + ')'
                    }));
                });
            } else {
                $select.append($('<option disabled>', {text: 'No authors found matching that term.'}));
            }
        },
        error: function() {
            $('#aal_author_search_spinner').hide();
            alert('An error occurred while searching. Check console for details.');
        }
    });
});




//end ajax search for authors to exclude




//delete excluded link
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
//end delete excluded link






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

    // Selectăm direct elementele <option> selectate
    var $selectedOptions = $('#aal_search_results option:selected');
    
    // Extragem ID-urile într-un array
    var selectedIds = [];
    $selectedOptions.each(function() {
        selectedIds.push($(this).val());
    });
    
    var nonce_val = $('#aal_excludepostbyid_nonce_search').val();

    if (selectedIds.length === 0) {
        alert('Please select at least one post from the list.');
        return;
    }

    // Unim toate ID-urile cu virgulă pentru request
    var idsString = selectedIds.join(',');

    $(".aal_exclude_status").empty();

    var data = {
        action: 'aal_add_exclude_posts',
        aal_post: idsString,
        aal_excludepostbyid_nonce: nonce_val
    };

    $.ajax({
        type: "POST",
        url: ajax_script.ajaxurl,
        data: data,
        cache: false,
        success: function(response){
            if(response == 'nopost') { 
                alert('One or more post IDs do not correspond with any post or page.'); 
            }
            else {  
                // Aici iterăm prin opțiunile pe care user-ul le-a selectat în listă
                // IGNORĂM răspunsul PHP și construim interfața dinamic.
                $selectedOptions.each(function() {
                    var single_id = $(this).val();
                    var rawText = $(this).text();
                    
                    // În search, textul era "Titlu Postare (ID: 42)". Extragem doar titlul curat.
                    var postTitle = rawText.replace(/ \(ID: \d+\)$/, '');

                    $(".aal_exclude_posts").append(
                        '<div class="aal_excludeditem">' +
                            '<div class="aal_excludedcol aal_excludedidcol">' + single_id + '</div>' + 
                            '<div class="aal_excludedcol aal_excludedtitle">' + postTitle + '</div>' + 
                            '<div class="aal_excludedcol">Added</div>' + 
                            '<div class="aal_excludedcol">' +
                                '<a href="javascript:;" class="aal_delete_exclude_link">' +
                                    '<img src="' + ajax_script.aal_plugin_url + 'images/delete.png"/>' +
                                '</a>' +
                            '</div><br/>' +
                        '</div>' +
                        '<div style="clear: both;"></div>'
                    );
                });
                
                $(".aal_exclude_status").append('<p><i>Selected posts excluded!</i></p>');
                
                // Cleanup interfață
                $('#aal_search_results_container').hide();
                $('#aal_search_results').empty();
                $('#aal_search_post_input').val('');
            }
        }
    });
});

//End exclude posts by search



//Code for reset exclude rules
    $('#aal_reset_date_rules').on('click', function() {
        if(confirm('This will clear both "Before" and "After" date exclusion rules. Continue?')) {
            $('#aal_excluderulesdatebefore').val('');
            $('#aal_excluderulesdateafter').val('');
            $('#aal_excluderules').submit();
        }
    });




//document ready end

    });
})(jQuery);


