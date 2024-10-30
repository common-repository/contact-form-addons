<?php

class cfas_entry_detail {

    public $form_id  = null;
    public $entry_id = null;
    public $nonce    = '';
    public $mode     = 'view';
    public $action   = '';

    /**
     * The single instance of the class.
     *
     * @var cfas_entry_detail
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main cfas_entry_detail Instance.
     *
     * Ensures only one instance of cfas_entry_list is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return cfas_entry_detail - Main instance.
     */
    public static function instance(){
        if( is_null( self::$_instance ) ){
            self::$_instance = new cfas_entry_detail();
        }

        return self::$_instance;
    }

    public function init(){
        $this->set_entry_id();
        $this->set_form_id();
        $this->set_page_info();
        $this->handle_page_changes();
        $this->make_entry_page();
    }

    public function set_entry_id() {
        $id = cfas_get( 'entry');
        if( ! empty( $id ) ) {
            $this->entry_id = $id;
        } else {
            die();
        }
    }

    public function set_form_id() {
        global $wpdb;
        $form_id = cfas_get( 'form' );
        if( ! $form_id ) {
            $sql = "SELECT form_id
            FROM {$wpdb->prefix}cfas_entries
            WHERE entry_id=$this->entry_id";

            $form_id = $wpdb->get_var( $sql );
        }
        $this->form_id = $form_id;
    }

    public function set_page_info() {
        $this->nonce  = wp_create_nonce('cf_detail_entry');
        $this->mode   = isset( $_POST['mode'] )   ? sanitize_key( $_POST['mode'] )   : '';
        $this->action = isset( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : '';
    }

    public function handle_page_changes() {

        switch ($this->action) {
            case 'trash' :
                cfas_entry_model::change_single_entry_status( 'cf_detail_entry', 'trash_entry' );
                break;
            case 'restore' :
                cfas_entry_model::change_single_entry_status( 'cf_detail_entry', 'entry' );
                break;
            case 'delete' :
                cfas_entry_model::delete_single_entry( 'cf_detail_entry' );
                $entry_type = $this->get_entry_type() == 'trash_entry' ? 'trash' : 'all';
                ?>
                <script>
                    window.location.href = "?page=cf_entries&view=entries&form=<?php echo $this->form_id?>&type=<?php echo $entry_type?>";
                </script>
                <?php
                break;
        }

    }

    public function make_entry_page() {
        $entry_type = $this->get_entry_type();

        // If entry eas deleted redirect to entries list page
        if( empty($entry_type) ) {
            ?>
            <script>
                window.location.href = "?page=cf_entries&view=entries&form=<?php echo $this->form_id?>";
            </script>
            <?php
        }
        ?>
        <div class="wrap">
            <div class="cfas-entry-header">
                <h2><?php echo __( 'Entry Detail', CFAS_DOMAIN ) ?></h2>
                <?php

                if( $entry_type == 'entry' ) {
                    echo "<a href='?page=cf_entries&view=entries&form=$this->form_id' class='cfas-back'>" . __("Back", CFAS_DOMAIN) . " ></a>";
                } elseif ( $entry_type == 'trash_entry' ) {
                    echo "<a href='?page=cf_entries&view=entries&form=$this->form_id&type=trash' class='cfas-back'>" . __("Back", CFAS_DOMAIN) . " ></a>";
                }
                ?>
            </div>
            <form method="post" id="entry-form">
                <input type="hidden" id="form_entry_nonce" name="nonce" value="<?php echo $this->nonce?>">
                <input type="hidden" id="form_entry_mode" name="mode">
                <input type="hidden" id="form_entry_action" name="action">
                <input type="hidden" id="form_id" name="form" value="<?php echo $this->form_id?>">
                <input type="hidden" id="entry_id" name="entry" value="<?php echo $this->entry_id?>">
                <div class="entry-detail-body">
                        <div class="entry-main-section">
                            <?php $this->make_detail_section(); ?>
                        </div>
                        <div class="entry-sidebar-section">
                            <?php $this->make_detail_sidebar(); ?>
                        </div>
                </div>
            </form>
        </div>
        <?php
    }

    public function make_detail_section() {
        $entry = $this->get_entry_detail();

        ?>
        <table class="cfas-entry-detail-table">
            <thead>
            <tr>
                <th id="cfas-entry-number">
                    <?php
                    echo sprintf( '%s %s', esc_html__( 'Entry # ', CFAS_DOMAIN ), absint( $this->entry_id ) );
                    ?>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php
            $columns_names = $this->get_columns_display_name();
            foreach ( $entry as $field ) {

                $entry_name  = $field->entry_key;
                $entry_value = $field->value;
                $entry_input = $field->input;
                $is_json     = cfas_is_json( $entry_value );
                ?>
                <tr>
                    <?php
                    if( is_array($columns_names) && in_array( $entry_name, array_keys( $columns_names ) ) ) {
                        if ( $entry_name == $columns_names[$entry_name] ) {
                            $name = $entry_name;
                        } else {
                            $name = $columns_names[$entry_name] . ' (' . $entry_name . ')';
                        }
                    } else {
                        $name = $entry_name;
                    }
                    ?>
                    <td class="cfas-entry-field-name"><?php echo $name ?></td>
                </tr>
                <tr>

                    <?php
                    if ( $is_json ) {
                        $entry_value = json_decode( $entry_value, true );
                        ?>
                        <td class="cfas-entry-field-value">
                        <?php
                        foreach ( $entry_value as $value) {
                            echo '<div class="cfas-single-entry"><span class="cfas-dot"></span>' . $value . '</div>';
                        }
                        ?>
                        </td>
                        <?php
                    } else {
                        if ( 'file_upload' === $entry_input ) {
                            ?>
                            <td class="cfas-entry-field-value"><a href="<?php echo $entry_value?>" target="_blank"><?php echo $entry_value?></a></td>
                            <?php
                        } else {
                            ?>
                            <td class="cfas-entry-field-value"><?php echo $entry_value ?></td>
                            <?php
                        }

                    }
                    ?>

                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }

    public function get_entry_detail() {
        global $wpdb;
        $sql = "SELECT entry_key,value,input 
                FROM {$wpdb->prefix}cfas_entries
                WHERE entry_id={$this->entry_id}";

        return $wpdb->get_results( $sql );
    }

    public function make_detail_sidebar() {
        $entry_type  = $this->get_entry_type();
        $entry_meta  = $this->get_entry_meta( $this->form_id, $this->entry_id );
        $submit_date = isset( $entry_meta['submit_date'] ) ? $entry_meta['submit_date'] : false;
        $submit_time = isset( $entry_meta['submit_time'] ) ? $entry_meta['submit_time'] : '00:00';
        $user_ip     = isset( $entry_meta['user_ip'] )     ? $entry_meta['user_ip']     : false;
        $user_id     = isset( $entry_meta['user_id'] )     ? $entry_meta['user_id']     : false;

        ?>
        <div id="cfas-entry-info" class="cfas-sidebar-box">
            <div class="cfas-box-header"><h2><?php echo __( 'Information', CFAS_DOMAIN )?></h2></div>
            <div id="cfas-entry-info-box">
                <div id="cfas-entry-info-body">
                    <div class="cfas-info-row"><?php echo __( 'Entry Id: ', CFAS_DOMAIN ) . $this->entry_id?></div>
                    <?php
                    if ( $submit_date ) {
                        ?>
                        <div class="cfas-info-row"><?php echo __( 'Submitted on: ', CFAS_DOMAIN ) . $submit_date . __( ' at ', CFAS_DOMAIN ) . $submit_time ?></div>
                        <?php
                    }
                    if ( $user_ip ) {
                        ?>
                        <div class="cfas-info-row"><?php echo __( 'User IP: ', CFAS_DOMAIN ) . $user_ip ?></div>
                        <?php
                    }
                    if ( $user_id ) {
                        $user_info    = get_userdata( $user_id );
                        $display_name = $user_info->display_name;

                        ?>
                        <div class="cfas-info-row"><?php echo __( 'User: ', CFAS_DOMAIN )?><a target="_blank" href="user-edit.php?user_id=<?php echo $user_id?>"><?php echo $display_name?></a></div>
                        <?php
                    }
                    ?>


                </div>
                <div id="cfas-publishing-actions" class="submitbox">
                    <?php
                        switch ( $entry_type ) {
                            case 'trash_entry':
                                ?>
                                <div id="delete-action">
                                    <a href="javascript:void(0);" onclick="jQuery('#form_entry_action').val('restore');jQuery('#entry-form').submit()"><?php echo __( 'Restore', CFAS_DOMAIN )?></a>
                                    <span class="cfas-seperator"> | </span>
                                    <a class="submitdelete" href="javascript:void(0);" onclick="jQuery('#form_entry_action').val('delete');jQuery('#entry-form').submit()"><?php echo __( 'Delete Permanently', CFAS_DOMAIN )?></a>
                                </div>
                                <?php
                                break;
                            default:
                                ?>
                                <div id="delete-action">
                                    <a class="submitdelete" href="javascript:void(0);" onclick="jQuery('#form_entry_action').val('trash');jQuery('#entry-form').submit()"><?php echo __( 'Move to Trash', CFAS_DOMAIN )?></a>
                                </div>

                                <?php
                                break;
                        }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function get_entry_meta( $form_id, $entry_id ) {
        global $wpdb;

        $meta = array();

        $sql = "SELECT meta_key,meta_value
                FROM {$wpdb->prefix}cfas_meta
                WHERE form_id=$form_id AND entry_id=$entry_id";

        $result = $wpdb->get_results( $sql, ARRAY_A );

        foreach ( $result as $data ) {
            $meta_name        = $data['meta_key'];
            $meta_value       = $data['meta_value'];
            $meta[$meta_name] = $meta_value;
        }

        return $meta;
    }

    public function get_entry_type() {
        global $wpdb;
        $entry_id = $this->entry_id;
        $sql = "SELECT type 
                FROM {$wpdb->prefix}cfas_entries
                WHERE entry_id=$entry_id";

        return $wpdb->get_var( $sql );
    }

    public function get_columns_display_name() {
        $display_name = cfas_get_form_meta( $this->form_id, 'columns_name');
        return unserialize( $display_name );
    }

}


$cfas_entry_detail = cfas_entry_detail::instance();
$cfas_entry_detail->init();