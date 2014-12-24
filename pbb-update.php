<?php
 
/**
* Run the incremental updates one by one.
* 
* For example, if the current DB version is 3, and the target DB version is 6,
* this function will execute update routines if they exist:
* Based on http://solislab.com/blog/plugin-activation-checklist/#update-routines
*/
function pbb_smooth_update($db_setting_name, $target_db_ver) {

    $current_db_ver = get_option($db_setting_name, 0);

    while ( $current_db_ver < $target_db_ver ) {
        // increment the current db_ver by one
        $current_db_ver ++;
 
        // each db version will require a separate update function
        // for example, for db_ver 3, the function name should be pbb_update_routine_3
        $func = "pbb_update_routine_{$current_db_ver}";
        if ( function_exists( $func ) ) {
            call_user_func( $func );
        }
 
        // update the option in the database, so that this process can always
        // pick up where it left off
        update_option( $db_setting_name, $current_db_ver );
    }
}
function pbb_update_routine_1(){
    global $wp_roles;
    if ( ! isset( $wp_roles ) ){
        $wp_roles = new WP_Roles(); 
    }  
    $wp_roles->add_cap('editor','manage_pbb');
    $wp_roles->add_cap('administrator','manage_pbb');
    $wp_roles->add_cap('super admin','manage_pbb');
}