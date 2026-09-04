<?php

$aalUniversalFeed = new aalModule('universalfeed', 'Universal Datafeed', 24);
$aalModules[] = $aalUniversalFeed;

$aalUniversalFeed->aalModuleHook('content', 'aalUniversalFeedDisplay');


//UI, forms and javascript
function aalUniversalFeedDisplay() {
    ?>
    <div class="wrap">  
        <div class="icon32" id="icon-options-general"></div>  
        <h2>Universal Datafeed (Any network)</h2>
        <br />
        <p>Upload any CSV datafeed and map your columns to our system. This processor runs in your browser, meaning it is highly secure and can handle large files by sending data in batches.</p>
        <br />

        <div id="aal_udf_step1" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; max-width: 800px;">
            <h3>Step 1: Select File</h3>
            <p>
                Separator: 
                <select id="aal_udf_separator">
                    <option value=",">, ( comma )</option>
                    <option value="|">| ( vertical line )</option>
                    <option value="tab">Tab</option>
                    <option value=";">; ( semicolon )</option>
                </select>
            </p>
            <p>
                <input type="file" id="aal_udf_file" accept=".csv, text/csv" />
            </p>
            <p>
                <button class="button-primary" id="aal_udf_load_btn" onclick="aalUdfLoadHeaders()">Load Columns</button>
            </p>
            <div id="aal_udf_error" style="color:red; margin-top: 10px;"></div>
        </div>

        <div id="aal_udf_step2" style="display:none; background: #fff; padding: 20px; border: 1px solid #ccd0d4; max-width: 800px; margin-top: 20px;">
            <h3>Step 2: Map Columns</h3>
            <p>Select which column from your CSV corresponds to the required fields.</p>
            
            <table class="form-table">
                <tbody id="aal_udf_mapping_table">
                    </tbody>
            </table>
            
            <br />
            <button class="button-primary" id="aal_udf_process_btn" onclick="aalUdfProcessFile()">Process & Send to Server</button>
            
            <div id="aal_udf_progress_area" style="display:none; margin-top: 20px;">
                <p><strong>Processing... Please do not close this window.</strong></p>
                <p id="aal_udf_status">Preparing data...</p>
                <div style="width: 100%; background: #eee; height: 20px; border-radius: 3px;">
                    <div id="aal_udf_progress_bar" style="width: 0%; background: #0073aa; height: 100%; border-radius: 3px; transition: width 0.3s;"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var aalUdfHeaders = [];
        var aalUdfRawData = "";
        var aalUdfFileName = "";
        
        // Define the fields we accept
        var aalUdfFields = [
            { id: "url", name: "Affiliate URL (Required)" },
            { id: "title", name: "Title / Name (Required)" },
            { id: "description", name: "Description" },
            { id: "merchant", name: "Merchant Name" },
            { id: "affid", name: "Affiliate ID" },
            { id: "category", name: "Category" },
            { id: "price", name: "Price" },
            { id: "image", name: "Image URL" },
            { id: "misc1", name: "Miscellaneous 1" },
            { id: "misc2", name: "Miscellaneous 2" },
            { id: "misc3", name: "Miscellaneous 3" }
        ];

        // Read file and parse headers
        function aalUdfLoadHeaders() {
            var fileInput = document.getElementById('aal_udf_file');
            var errorDiv = document.getElementById('aal_udf_error');
            
            if (!fileInput.files.length) {
                errorDiv.innerText = "Please select a file first.";
                return;
            }

            var file = fileInput.files[0];
            aalUdfFileName = file.name;
            var separator = document.getElementById('aal_udf_separator').value;
            if (separator === 'tab') separator = '\t';

            var reader = new FileReader();
            reader.onload = function(e) {
                aalUdfRawData = e.target.result;
                
                // Extract just the first line for headers
                var firstLine = aalUdfRawData.substring(0, aalUdfRawData.indexOf('\n'));
                aalUdfHeaders = firstLine.split(separator).map(function(item) {
                    return item.replace(/^["']|["']$/g, '').trim(); // Remove quotes
                });

                aalUdfBuildMappingUI();
            };
            reader.readAsText(file);
        }

        // Build the dropdowns
        function aalUdfBuildMappingUI() {
            var tableBody = document.getElementById('aal_udf_mapping_table');
            tableBody.innerHTML = '';

            aalUdfFields.forEach(function(field) {
                var tr = document.createElement('tr');
                var th = document.createElement('th');
                th.innerText = field.name;
                
                var td = document.createElement('td');
                var select = document.createElement('select');
                select.id = 'map_' + field.id;
                
                var defaultOption = document.createElement('option');
                defaultOption.value = "-1";
                defaultOption.innerText = "-- Ignore this field --";
                select.appendChild(defaultOption);

                aalUdfHeaders.forEach(function(header, index) {
                    var option = document.createElement('option');
                    option.value = index;
                    option.innerText = header || ('Column ' + (index + 1));
                    select.appendChild(option);
                });

                td.appendChild(select);
                tr.appendChild(th);
                tr.appendChild(td);
                tableBody.appendChild(tr);
            });

            document.getElementById('aal_udf_step2').style.display = 'block';
            document.getElementById('aal_udf_error').innerText = '';
        }

        // Parse CSV robustly (handles quotes containing commas)
        function aalUdfParseCSV(text, sep) {
            var p = '', row = [''], ret = [row], i = 0, r = 0, s = !0, l;
            for (var j = 0; j < text.length; j++) {
                l = text[j];
                if ('"' === l) {
                    if (s && l === p) row[i] += l;
                    s = !s;
                } else if (sep === l && s) l = row[++i] = '';
                else if ('\n' === l && s) {
                    if ('\r' === p) row[i] = row[i].slice(0, -1);
                    row = ret[++r] = ['']; i = 0;
                } else row[i] += l;
                p = l;
            }
            return ret;
        }

        // Process mapped data and chunk it
        function aalUdfProcessFile() {
            var urlIndex = document.getElementById('map_url').value;
            var titleIndex = document.getElementById('map_title').value;

            if (urlIndex === "-1" || titleIndex === "-1") {
                alert("You must map at least the Affiliate URL and Title fields.");
                return;
            }

            document.getElementById('aal_udf_progress_area').style.display = 'block';
            document.getElementById('aal_udf_process_btn').disabled = true;

            var separator = document.getElementById('aal_udf_separator').value;
            if (separator === 'tab') separator = '\t';

            document.getElementById('aal_udf_status').innerText = "Parsing file...";
            
            // Parse full file
            var parsedRows = aalUdfParseCSV(aalUdfRawData, separator);
            var mappedData = [];

				// Skip row 0 (headers)
            for (var i = 1; i < parsedRows.length; i++) {
                var row = parsedRows[i];
                if (row.length <= 1) continue; // Skip empty rows

                var obj = {};
                var miscData = {}; // Object to hold the custom misc keys
                
                aalUdfFields.forEach(function(field) {
                    var mapIndex = document.getElementById('map_' + field.id).value;
                    if (mapIndex !== "-1" && row[mapIndex] !== undefined) {
                        var val = row[mapIndex].trim();
                        
                        // If it's a misc field, save it with the original header name
                        if (field.id.indexOf('misc') === 0) {
                            var originalHeaderName = aalUdfHeaders[mapIndex] || ('Column ' + (parseInt(mapIndex) + 1));
                            miscData[originalHeaderName] = val;
                        } else {
                            obj[field.id] = val;
                        }
                    }
                });

                // If we mapped any misc fields, attach them as a single JSON object inside the payload
                if (Object.keys(miscData).length > 0) {
                    obj.misc = miscData;
                }

                if (obj.url && obj.title) {
                    mappedData.push(obj);
                }
            }

            // Chunking Logic (1000 items per request)
            var chunkSize = 1000;
            var chunks = [];
            for (var k = 0; k < mappedData.length; k += chunkSize) {
                chunks.push(mappedData.slice(k, k + chunkSize));
            }

            aalUdfSendChunks(chunks, 0);
        }

        // Recursive function to send chunks via AJAX
        function aalUdfSendChunks(chunks, currentIndex) {
            if (currentIndex >= chunks.length) {
                document.getElementById('aal_udf_status').innerText = "Complete! " + (chunks.length > 0 ? (chunks.length-1)*1000 + chunks[chunks.length-1].length : 0) + " links processed.";
                document.getElementById('aal_udf_progress_bar').style.backgroundColor = "#46b450"; // Green
                return;
            }

            var progressPercent = Math.round((currentIndex / chunks.length) * 100);
            document.getElementById('aal_udf_progress_bar').style.width = progressPercent + "%";
            document.getElementById('aal_udf_status').innerText = "Sending batch " + (currentIndex + 1) + " of " + chunks.length + "...";

            var payload = {
                action: 'aal_udf_send_batch',
                filename: aalUdfFileName,
                nonce: '<?php echo wp_create_nonce("aal_udf_nonce"); ?>',
                data: JSON.stringify(chunks[currentIndex])
            };

            jQuery.post(ajaxurl, payload, function(response) {
                // Wait for success, then send next chunk
                aalUdfSendChunks(chunks, currentIndex + 1);
            }).fail(function() {
                document.getElementById('aal_udf_status').innerText = "Error sending batch " + (currentIndex + 1) + ". Process halted.";
                document.getElementById('aal_udf_progress_bar').style.backgroundColor = "red";
                document.getElementById('aal_udf_process_btn').disabled = false;
            });
        }
    </script>
    <?php
}


// 3. WordPress AJAX Handler (Receives chunk from JS, sends to API)
add_action('wp_ajax_aal_udf_send_batch', 'aal_udf_handle_ajax_batch');
function aal_udf_handle_ajax_batch() {
    check_ajax_referer('aal_udf_nonce', 'nonce');
    
    // Ensure user has permissions
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $json_data = stripslashes($_POST['data']);
    $filename = sanitize_text_field($_POST['filename']);
    $apikey = get_option('aal_apikey');

    if (!$apikey) {
        wp_send_json_error("No API key found.");
    }

    // Construct payload for your API
    $api_payload = array(
        'apikey' => $apikey,
        'filename' => $filename,
        'links' => $json_data // JSON string containing max 1000 links
    );

    // Call your centralized API
    $response = aal_post(http_build_query($api_payload), 'http://api.autoaffiliatelinks.com/universalfeed.php');

    wp_send_json_success(array('api_response' => $response));
}

?>