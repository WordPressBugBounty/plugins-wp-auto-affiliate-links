<?php

add_action('admin_init', 'aal_exclude_authors_logic');

function aal_exclude_authors_logic() {
    // Verificăm permisiunile (folosim același nivel ca la restul pluginului)
    if ( !current_user_can("publish_pages") ) return;

    $option_name = 'aal_exclude_authors';

    // --- 1. LOGICA DE ADĂUGARE ---
    if ( isset($_POST['aal_exclude_authors_submit']) && $_POST['aal_exclude_authors_submit'] == 'ok' ) {
        
        check_admin_referer( 'aal_exclude_authors_action', 'aal_exclude_authors_nonce' );

        // Preluăm ID-urile selectate (fiind multiple select, primim un array)
        $new_ids = isset($_POST['aal_exclude_author_ids']) ? array_map('intval', $_POST['aal_exclude_author_ids']) : array();

        if ( !empty($new_ids) ) {
            $existing_raw = get_option($option_name, '');
            $existing_ids = !empty($existing_raw) ? explode(',', $existing_raw) : array();

            // Combinăm listele și eliminăm duplicatele
            $final_ids = array_unique(array_merge($existing_ids, $new_ids));
            
            // Salvăm înapoi ca string separat prin virgulă
            update_option($option_name, implode(',', $final_ids));
        }
    }

    // --- 2. LOGICA DE ȘTERGERE ---
    if ( isset($_POST['aal_delete_exclude_author_id']) ) {
        
        check_admin_referer( 'aal_exclude_authors_action', 'aal_exclude_authors_nonce' );

        $id_to_delete = intval($_POST['aal_delete_exclude_author_id']);
        $existing_raw = get_option($option_name, '');

        if ( !empty($existing_raw) ) {
            $existing_ids = explode(',', $existing_raw);
            
            // Căutăm ID-ul și îl eliminăm
            if ( ($key = array_search($id_to_delete, $existing_ids)) !== false ) {
                unset($existing_ids[$key]);
            }

            // Dacă mai avem ID-uri, facem update, altfel ștergem opțiunea de tot
            if ( !empty($existing_ids) ) {
                update_option($option_name, implode(',', $existing_ids));
            } else {
                delete_option($option_name);
            }
        }
    }
}

function wpaal_exclude_authors_ui() {

    $excluded_authors_raw = get_option('aal_exclude_authors');
    $excluded_authors = $excluded_authors_raw ? explode(',', $excluded_authors_raw) : array();

    $args = array(
        'orderby' => 'ID',
        'order'   => 'DESC',
        'number'  => 10,
        'exclude' => $excluded_authors 
    );
    $recent_users = get_users( $args );
    ?>

    <h3>Exclude content by Author</h3>
    
    <form name="aal_add_exclude_author_form" id="aal_add_exclude_author_form" method="post">
        <?php wp_nonce_field( 'aal_exclude_authors_action', 'aal_exclude_authors_nonce' ); ?>
        <input type="hidden" name="aal_exclude_authors_submit" value="ok" />
        
        <b>Search for author:</b><br />
        <input type="text" id="aal_search_author_input" placeholder="Type username or email..." size="30" autocomplete="off" />
        <button type="button" id="aal_trigger_author_search_btn" class="button">Search</button>
        <span id="aal_author_search_spinner" style="display:none; color: #666;"><i> Searching...</i></span>
        
        <br /><br />
        
        <b>Select authors to exclude:</b><br />
        <select name="aal_exclude_author_ids[]" id="aal_search_author_results" multiple="multiple" style="width: 100%; max-width: 400px; height: 150px;">
            <?php 
            if ( !empty($recent_users) ) {
                foreach ( $recent_users as $user ) {
                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name . ' (' . $user->user_login . ') (' . count_user_posts($user->ID) . ')') . '</option>';
                }
            } else {
                echo '<option value="" disabled>No authors available.</option>';
            }
            ?>
        </select>
        
        <br /><br />
        <input class="button-primary" type="submit" value="Exclude content from this author"/>
    </form>

    <br /><br />

    <h4>Excluded Authors:</h4>
    <table class="widefat fixed" style="max-width: 600px;"> 
        <thead>
            <tr>
                <th>Excluded Author</th>
                <th style="width: 100px;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        if ( !empty($excluded_authors) && $excluded_authors[0] !== '' ) {
            foreach ( $excluded_authors as $author_id ) { 
                $user_info = get_userdata($author_id);
                $display_name = $user_info ? $user_info->display_name . ' (' . $user_info->user_login . ') (' . count_user_posts($author_id) . ')' : 'Deleted/Unknown User (ID: ' . $author_id . ')';
                ?>
                <tr>
                    <td><?php echo esc_html($display_name); ?></td>
                    <td>
                        <form name="aal_delete_exclude_author_form" method="post">
                            <?php wp_nonce_field( 'aal_exclude_authors_action', 'aal_exclude_authors_nonce' ); ?>
                            <input type="hidden" name="aal_delete_exclude_author_id" value="<?php echo esc_attr($author_id); ?>" />
                            <input class="button-primary" type="submit" value="Delete" onclick="return confirm('Are you sure you want to remove this author from the exclusion list?');" />
                        </form>
                    </td>
                </tr>   
                <?php   
            }
        } else {
            echo '<tr><td colspan="2">No authors are currently excluded.</td></tr>';
        }
        ?>
        </tbody>
    </table>
    <br /><hr />
    <?php
}











add_action('wp_ajax_aal_search_authors_to_exclude', 'aal_search_authors_to_exclude_callback');

function aal_search_authors_to_exclude_callback() {
    if ( !current_user_can('publish_pages') ) {
        wp_send_json(array());
    }

    $search_term = sanitize_text_field( $_POST['search_term'] );
    
    // Nu vrem să returnăm userii pe care i-am exclus deja
    $excluded_authors_raw = get_option('aal_exclude_authors');
    $excluded_authors = $excluded_authors_raw ? explode(',', $excluded_authors_raw) : array();

    $args = array(
        'search'         => '*' . $search_term . '*', // Caută oriunde în nume/email
        'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
        'number'         => 15,
        'exclude'        => $excluded_authors
    );

    $user_query = new WP_User_Query( $args );
    $results = array();

    if ( ! empty( $user_query->get_results() ) ) {
        foreach ( $user_query->get_results() as $user ) {
            $results[] = array(
                'id'    => $user->ID,
                'name'  => $user->display_name . ' (' . count_user_posts($user->ID) . ')',
                'login' => $user->user_login
            );
        }
    }

    wp_send_json( $results );
}