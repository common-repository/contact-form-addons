<?php
class cfas_entry_model {

    /**
     * The single instance of the class.
     *
     * @var cfas_entry_model
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main cfas_entry_model Instance.
     *
     * Ensures only one instance of cfas_entry_model is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return cfas_entry_model - Main instance.
     */
    public static function instance() {

        if( is_null( self::$_instance ) ){
            self::$_instance = new cfas_entry_model();
        }

        return self::$_instance;
    }

    public function init() {
        add_action( 'wp_ajax_change_entry', array( $this, 'ajax_change_entry_status' ) );
        add_action( 'wp_ajax_delete_entry', array( $this, 'ajax_delete_entry' ) );
    }

    public function ajax_change_entry_status() {
        $form_id  = isset( $_POST['form'] ) ? intval( $_POST['form'] ) : '';
        $entry_id = isset( $_POST['entry'] ) ? intval( $_POST['entry'] ) : '';
        $status   = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';
        $action   = $status == 'entry' ? 'cf_restore_entry' : 'cf_trash_entry';
        $change   = self::change_single_entry_status( $action, $status );

        if( $change ) {
            $result = array( 'success' => true);
            do_action( 'cf7_update_entry_property', $form_id, $entry_id, $status );
        } else {
            $result = array( 'success' => false);
        }

        wp_die( json_encode( $result ) );
    }

    public static function change_single_entry_status( $action, $status ) {

        $form_id  = isset( $_POST['form'] ) ? intval( $_POST['form'] ) : '';
        $entry_id = isset( $_POST['entry'] ) ? intval( $_POST['entry'] ) : '';

        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
            $nonce  = sanitize_key($_POST['_wpnonce']);

            if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Nope! Security check failed!' );
        }

        return cfas_update_form_entry( $form_id, $entry_id, $status );
    }

    public static function change_group_entry_status( $form_id, $entry_list, $type) {
        cfas_update_group_form_entry( $form_id, $entry_list, $type);
    }

    public function ajax_delete_entry() {

        $action = 'cf_delete_entry';
        $status = self::delete_single_entry( $action );

        if( $status ) {
            $result = array( 'success' => true);
        } else {
            $result = array( 'success' => false);
        }

        wp_die( json_encode( $result ) );
    }

    public static function delete_single_entry( $action ) {

        $form_id  = isset( $_POST['form'] ) ? intval( $_POST['form'] ) : '';
        $entry_id = isset( $_POST['entry'] ) ? intval( $_POST['entry'] ) : '';

        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
            $nonce  = sanitize_key($_POST['_wpnonce']);
            if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Nope! Security check failed!' );
        }

        $status = cfas_delete_form_entry( $form_id, $entry_id);

        if ( $status ) {
            $status = cfas_delete_form_entry_meta( $form_id, $entry_id );
        }

        return $status;
    }

    public static function delete_group_entry( $form_id, $entry_list) {
        cfas_delete_group_form_entry( $form_id, $entry_list);
        cfas_delete_group_form_entry_meta( $form_id, $entry_list);
    }

}

$cfas_entry_model = cfas_entry_model::instance();
$cfas_entry_model->init();
