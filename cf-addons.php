<?php
/*
Plugin Name: Contact Form Addons
Plugin URI: https://zinoteam.com/contact-form-essential-addons/
Description: With the help of this plugin, you can add various features such as multi-step, animation, conditional, save form submitted data, etc. to your form.
Version: 1.1.0
Author: Zino Team
Author URI: https://zinoteam.com/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: cfas
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    die();
}

define( 'CFAS_URL', trailingslashit(plugin_dir_url( __FILE__ )));
define( 'CFAS_DIR', trailingslashit(plugin_dir_path( __FILE__ )));
define( 'CFAS_INC', trailingslashit(CFAS_DIR.'includes'));
define( 'CFAS_CSS', trailingslashit(CFAS_URL.'assets/css'));
define( 'CFAS_JS', trailingslashit(CFAS_URL.'assets/js'));
define( 'CFAS_DOMAIN','cfas-addons');
define( 'CFAS_VERSION', '1.1.0' );
define( 'CFAS_DEBUG', false );

if( CFAS_DEBUG ) {
    define( 'CFAS_MIN', '' );
} else {
    define( 'CFAS_MIN', '.min' );
}

load_plugin_textdomain( CFAS_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

if( wpcf7_is_active() ) {
    if(is_admin()){
        require_once CFAS_INC.'backend/back-init.php';
        require_once CFAS_INC.'backend/multi-step.php';
        require_once CFAS_INC.'backend/conditional.php';
        require_once CFAS_INC.'backend/entry-menu.php';
        do_action( 'cfas_admin' );
    }else{
        require_once CFAS_INC.'frontend/front-init.php';
        require_once CFAS_INC.'frontend/add-entry.php';
        require_once CFAS_INC.'frontend/conditional.php';
        require_once CFAS_INC.'frontend/multi-step.php';
        require_once CFAS_INC.'frontend/validation.php';
        do_action( 'cfas_front' );
    }
    
}

function wpcf7_is_active() {
    $active_plugins = get_option('active_plugins');
    
    foreach ( $active_plugins as $plugin ) {
        if ( strpos( $plugin, 'wp-contact-form-7.php' ) ) {
            return true;
        }
    }
    
    return false;
}

/**
 *  Get settings of current form
 *
 * @param integer $form_id The current Form ID.
 * @param null    $key If set return the key of array.
 * @param bool    $is_array Return settings in array or json.
 * @return false|mixed|string|array
 */
function get_cfas_settings($form_id, $key = null, $is_array = true){
    $settings = get_option("cfas-settings-$form_id");

    if (empty($settings) || is_null($settings)){
        return false;
    }


    if($is_array){
        $settings = json_decode($settings, true);
        
        if($key)
            $settings = $settings[$key];
    }elseif ($key){
        $settings = json_decode($settings, true);
        $settings = isset($settings[$key]) && !empty($settings[$key]) ? $settings[$key] : false;
        
        if( $settings ) {
            $settings = json_encode($settings);
        }
    }

    return $settings;
}


/**
 * Set settings of current form
 *
 * @param integer $form_id The current Form ID.
 * @param array   $info    Form settings array.
 */


function set_cfas_settings($form_id, $info){
    $info = json_encode($info);
    update_option("cfas-settings-$form_id", $info);
}

function cfas_get( $name, $default = null ){

    if( ! is_array($_GET) ){
        return false;
    }

    if( isset( $_GET[$name]) && ! empty( $_GET[$name] ) ){
        return sanitize_textarea_field( $_GET[$name] );
    }

    return empty( $default ) ? false : $default;
}

function cfas_post( $name, $default = null, $type = 'string' ) {

    switch ( $type ) {
        case 'bool' :
            return isset( $_POST[$name] ) && ! empty( $_POST[$name] ) ? boolval( $_POST[$name] ) : $default;
            break;

        case 'int' :
            return isset( $_POST[$name] ) && ! empty( $_POST[$name] ) ? intval( $_POST[$name] ) : $default;
            break;

        case 'array' :
            return isset( $_POST[$name] ) && ! empty( $_POST[$name] ) ? cfas_sanitize_array( $_POST[$name] ) : $default;
            break;

        case 'string' :
            return isset( $_POST[$name] ) && ! empty( $_POST[$name] ) ? sanitize_textarea_field( $_POST[$name] ) : $default;
            break;
    }

}

function cfas_is_empty( $string ){
    return empty($string) && !is_array($string);
}


function cfas_get_form_meta( $form_id, $meta) {
    global $wpdb;
    $sql = "SELECT meta_value
            FROM {$wpdb->prefix}cfas_meta
            WHERE form_id=$form_id AND meta_key='$meta'";

    return $wpdb->get_var( $sql );
}

function cfas_set_form_meta( $form_id, $meta, $value ) {
    global $wpdb;
    $sql = "INSERT INTO {$wpdb->prefix}cfas_meta
            (form_id,meta_key,meta_value) VALUES ($form_id,'$meta','$value')";

    return $wpdb->query( $sql );
}

function cfas_update_form_meta( $form_id, $meta, $value ) {
    global $wpdb;
    $sql = "UPDATE {$wpdb->prefix}cfas_meta
            SET meta_value='$value'
            WHERE form_id=$form_id AND meta_key='$meta'";

    return $wpdb->query( $sql );
}

function cfas_delete_form_meta( $form_id, $meta) {
    global $wpdb;
    $sql = "DELETE FROM {$wpdb->prefix}cfas_meta
            WHERE form_id=$form_id AND meta_key='$meta'";

    return $wpdb->query( $sql );
}

function cfas_delete_form_entry_meta( $form_id, $entry_id) {
    global $wpdb;
    $sql = "DELETE FROM {$wpdb->prefix}cfas_meta
            WHERE form_id=$form_id AND entry_id=$entry_id";

    return $wpdb->query( $sql );
}

function cfas_delete_group_form_entry_meta( $form_id, $entry_list ) {
    global $wpdb;
    $where    = '';
    $is_first = true;

    foreach ( $entry_list as $entry_id ) {
        $where .= $is_first ? "entry_id=$entry_id" : " OR entry_id=$entry_id";
        $is_first = false;
    }

    $sql = "DELETE FROM {$wpdb->prefix}cfas_meta
            WHERE form_id=$form_id AND ($where)";

    return $wpdb->query( $sql );
}

function cfas_check_and_insert_meta( $form_id, $meta_key, $value ) {
    $has_meta = cfas_get_form_meta( $form_id, $meta_key );

    if( $has_meta ) {
        cfas_update_form_meta( $form_id, $meta_key, $value );
    } else {
        cfas_set_form_meta( $form_id, $meta_key, $value );
    }
}

function cfas_update_form_entry( $form_id, $entry_id, $type ) {
    global $wpdb;
    $sql = "UPDATE {$wpdb->prefix}cfas_entries
            SET type='$type'
            WHERE form_id=$form_id AND entry_id=$entry_id";

    return $wpdb->query( $sql );
}

function cfas_update_group_form_entry( $form_id, $entry_list , $type ) {
    global $wpdb;
    $where    = '';
    $is_first = true;

    foreach ( $entry_list as $entry_id ) {
        $where .= $is_first ? "entry_id=$entry_id" : " OR entry_id=$entry_id";
        $is_first = false;
    }

    $sql = "UPDATE {$wpdb->prefix}cfas_entries
            SET type='$type'
            WHERE form_id=$form_id AND ($where)";

    return $wpdb->query( $sql );
}

function cfas_delete_form_entry( $form_id, $entry_id) {
    global $wpdb;
    $sql = "DELETE FROM {$wpdb->prefix}cfas_entries
            WHERE form_id=$form_id AND entry_id=$entry_id";

    return $wpdb->query( $sql );
}

function cfas_delete_group_form_entry( $form_id, $entry_list ) {
    global $wpdb;
    $where    = '';
    $is_first = true;

    foreach ( $entry_list as $entry_id ) {
        $where .= $is_first ? "entry_id=$entry_id" : " OR entry_id=$entry_id";
        $is_first = false;
    }

    $sql = "DELETE FROM {$wpdb->prefix}cfas_entries
            WHERE form_id=$form_id AND ($where)";

    return $wpdb->query( $sql );
}


function cfas_sanitize_array( $array ){
    $result = array();
    
    foreach ( $array as $key=>$value ) {
        $key          = sanitize_text_field( $key );
        $value        = sanitize_text_field( $value );
        $result[$key] = $value;
    }
    
    return $result;
}

register_activation_hook( __FILE__, 'cfas_make_entry_table' );

function cfas_make_entry_table() {
    global $wpdb;

    $sql1 = "CREATE TABLE `{$wpdb->prefix}cfas_entries` (
      id        INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
      form_id   INT NOT NULL,
      entry_id  INT NOT NULL,
      entry_key VARCHAR(255) NOT NULL,
      value     TEXT NOT NULL,
      type      VARCHAR(255) NOT NULL,
      input     VARCHAR(255) NOT NULL
    )CHARACTER SET utf8 
    COLLATE utf8_general_ci;";

    $sql2 = "CREATE TABLE `{$wpdb->prefix}cfas_meta` (
      id        INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
      form_id   INT NOT NULL,
      entry_id  INT NOT NULL,
      meta_key  VARCHAR(255) NOT NULL,
      meta_value     TEXT NOT NULL
    )CHARACTER SET utf8 
    COLLATE utf8_general_ci;";

    $wpdb->query( $sql1 );
    $wpdb->query( $sql2 );

}

function cfas_is_json( $entry ) {
    $entry = json_decode( $entry, true );
    
    return is_array( $entry ) ? true : false;
}

function cfas_get_page_numbers() {
    $form_id  = cfas_front::$form_id;
    $form     = WPCF7_ContactForm::get_instance( $form_id );
    $tags     = $form->scan_form_tags(); 
    $page_num = 1;
    
    foreach( $tags as $tag ) {
        if( $tag->type === 'cfas_step' ) {
            $page_num++;
        }
    }
    
    return $page_num;
}

