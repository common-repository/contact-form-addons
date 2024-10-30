<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class cfas_entries extends WP_List_Table{

    /**
     * Current form id.
     *
     * @var string
     */
    public $form_id = null;

    public $sorting = null;

    /**
     * Which entries type must be shown e.g. trash
     *
     * @var string
     */
    public $page_type;

    /**
     * The single instance of the class.
     *
     * @var cfas_entries
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main cfas_entries Instance.
     *
     * Ensures only one instance of cfas_entries is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return cfas_entries - Main instance.
     */
    public static function instance(){
        if( is_null( self::$_instance ) ){
            self::$_instance = new cfas_entries();
        }

        return self::$_instance;
    }

    public function init(){
        $this->set_current_form_id();
        $this->set_current_page_type();
        $this->check_columns_setting();
        $this->make_entries_top_bar();
        $this->prepare_items();
        $this->views();
        $this->make_search_box();
        echo "<form class='cfas-entries-list' method='post'>";
        echo "<input type='hidden' id='all-entry' name='all-entry'>";
        $this->display();
        echo "</form>";
        echo '</div>';
        $this->columns_setting_form();
        $this->add_scripts();
    }

    public function set_current_form_id() {
        $first_form_id = $this->get_first_form_id();
        $this->form_id = cfas_get( 'form', $first_form_id);
    }

    public function set_current_page_type() {
        $this->page_type = cfas_get( 'type', 'all' );
    }

    public function get_views(){
        $views = array();

		$form_id = $this->form_id;

		$filter_links = $this->get_filter_links();

		$filter = $this->page_type;

		foreach ( $filter_links as $filter_link_index => $filter_link ) {
			$filter_arg = '&type=';
			if ( $filter_link['id'] !== 'all' ) {
				$filter_arg .= $filter_link['id'];
			}
			if ( $filter == '' ) {
				$selected = $filter_link['id'] == 'all' ? 'current' : '';
			} else {
				$selected = ( $filter == $filter_link['id'] ) ? 'current' : '';
			}
			$link = '<a class="' . $selected . '" href="?page=cf_entries&view=entries&form=' . $form_id . esc_attr( $filter_arg ) . '">' . esc_html( $filter_link['label'] ) .
			        '<span class="count"> (<span	id="' . esc_attr( $filter_link['id'] ) . '_count">' . $filter_link['count'] . '</span>)</span></a>';
			$views[ $filter_link['id'] ] = $link;
		}
		return $views;
    }

    public function get_filter_links() {
		$filter_links = array(
			array(
				'id' => 'all',
				'field_filters' => array(),
				'count' => $this->get_entries_count( 'entry' ),
				'label'   => esc_html__( 'All', CFAS_DOMAIN ),
			),
			array(
				'id' => 'trash',
                'field_filters' => array(),
                'count' => $this->get_entries_count( 'trash_entry' ),
                'label'   => esc_html__( 'Trash', CFAS_DOMAIN ),
			),
		);

		$filter_links = apply_filters( 'cfas_type_links_entry_list', $filter_links, $this->form_id );

		return $filter_links;
	}

    public function make_entries_top_bar() {
        $additional_html = apply_filters( 'cfas_entries_top_html', '', $this->form_id );
        echo
            '<div class="wrap">
                <div class="cfas-entries-head">
                    <h2>' . __('Entries List', CFAS_DOMAIN ) . '</h2>
                    <div class="cfas-entries-head-btn">
                        <span id="cfas-entries-setting" class="dashicons dashicons-admin-generic"></span>
                        '.$additional_html.'
                    </div>
            </div>';
    }
	
	public function get_type_links_array() {
		
		$type_links = array(
			array(
				'id' => 'all',
				'field_filters' => array(),
				'count' => $this->get_entries_count( 'entry' ),
				'label'   => esc_html__( 'All', CFAS_DOMAIN ),
			),
			array(
				'id' => 'trash',
				'field_filters' => array(),
				'count' => $this->get_entries_count( 'trash_entry' ),
				'label'   => esc_html__( 'Trash', CFAS_DOMAIN ),
			),
		);
		
		$type_links = apply_filters( 'cfas_type_links_entry_list', $type_links, $this->form_id );

		return $type_links;
	}

    public function get_entries_count( $type ) {
        global $wpdb;

        switch( $type ) {
            case 'trash' :
                $type = 'trash_entry';
                break;

            case 'all' :
                $type = 'entry';
                break;
        }

        $sql = "SELECT COUNT(*) 
                FROM (SELECT entry_id FROM {$wpdb->prefix}cfas_entries WHERE type='$type' AND form_id=$this->form_id GROUP BY entry_id) 
                AS test";

        return $wpdb->get_var( $sql ) ? $wpdb->get_var( $sql ) : 0;
    }

    public function get_first_form_id() {
        global $wpdb;
        $sql = "SELECT ID
                FROM {$wpdb->prefix}posts
                WHERE post_type = 'wpcf7_contact_form'
                LIMIT 1";
        $res = $wpdb->get_row($sql, ARRAY_A);
        if( cfas_is_empty($res) ) {
            die();
        }
        return $res['ID'];
    }

    public function make_search_box(){
        if( ! $this->has_items() ) {
            return;
        }
        ?>
        <div class="cfas-entris-search">
            <form id="form_list_search" method="get">
                <input type="hidden" value="cf_entries" name="page">
                <input type="hidden" value="entries" name="view">
                <input type="hidden" value="<?php echo $this->form_id?>" name="form">
                <input type="hidden" value="<?php echo $this->page_type?>" name="type">
                <select name="s_cat">
                    <?php echo $this->get_search_options() ?>
                </select>
                <?php
                $this->search_box( esc_html__( 'Search Forms', 'CFAS_DOMAIN' ), 'form' );
                ?>
            </form>
        </div>
        <?php
    }

    public function get_search_options() {
        $option     = '';
        $columns    = $this->get_columns();
        $search_cat = cfas_get( 's_cat' );
        foreach ( $columns as $name=>$display ) {
            if( $search_cat == $name) {
                $option .= "<option value='$name' selected>$display</option>";
            }else {
                $option .= "<option value='$name'>$display</option>";
            }
        }
        return $option;
    }

    public function get_columns($has_cb = false){

        if( $has_cb ) {
            $columns = array(
                'cb' => '<input type="checkbox" />',
            );
        } else {
            $columns = array();
        }

        $show_setting  = $this->get_show_setting();
        $entry_columns = $this->get_entry_columns();

        if( ! empty( $show_setting ) ) {
            $columns = array_merge( $columns, $show_setting );
        } elseif ( ! empty( $entry_columns ) ) {
            $columns = array_merge( $columns, $entry_columns );
        } else {
            $columns = array_merge( $columns, $this->get_current_form_columns() );
        }

        $columns = apply_filters( 'cfas_column_display_name', $columns, $this->form_id );

        return $columns;
    }

    public function check_columns_setting() {
		// If user clicked on submit button.
        if( isset( $_POST['sub_settings'] ) ) {
            $show_columns = cfas_sanitize_array( $_POST['show_columns'] );

            if( ! empty($show_columns) ) {
                $show_columns = $this->merging_columns( $show_columns );
                $show_columns = cfas_sanitize_array( $show_columns );
                $show_columns = serialize( $show_columns );
                cfas_check_and_insert_meta( $this->form_id, 'show_columns', $show_columns );
            }

            do_action( 'cfas_submit_columns_setting', $this->form_id, $_POST );
        }elseif ( isset( $_POST['res_settings'] ) ) {
			// If user clicked on reset button.
            $this->reset_columns_settings();
            do_action( 'cfas_reset_columns_setting', $this->form_id );
        }
    }

    public function get_show_setting() {
        $show_columns = cfas_get_form_meta( $this->form_id, 'show_columns');
        return unserialize( $show_columns );
    }

    public function reset_columns_settings() {
        cfas_delete_form_meta( $this->form_id, 'show_columns' );
    }

    public function get_current_form_columns() {
        $form    = WPCF7_ContactForm::get_instance( $this->form_id );
        $tags    = $form->scan_form_tags();
        return $this->merging_columns( $tags, 'name' );
    }

    public function get_sortable_columns(){
        $columns  = $this->get_columns();
        $sortable = array();

        foreach ( $columns as $name=>$display) {
            $sortable[$name] = array( $name, false );
        }
        return $sortable;
    }

    public function prepare_items() {
        $this->process_bulk_action();
        $columns               = $this->get_columns( true );
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $sorting               = empty( $_GET['orderby'] ) ? 'entry_id' : cfas_get('orderby');
        $this->sorting         = $sorting;
        $sort_direction        = empty( $_GET['order'] ) ? 'DESC' : strtoupper(cfas_get('order'));
        $sort_direction        = $sort_direction == 'ASC' ? 'ASC' : 'DESC';
        $search                = cfas_get( 's' );
        $search_cat            = cfas_get( 's_cat' );
        $this->_column_headers = array($columns, $hidden, $sortable);
        $entries_type          = $this->page_type == 'trash' ? 'trash_entry' : 'entry';
        $total_count           = $this->get_entries_count( $this->page_type );

        $entries = $this->get_form_entries( $search, $search_cat, $sorting, $sort_direction, $entries_type );

        $entries = apply_filters( 'cfas_entries_list', $entries, $this->form_id );

        if ( $sorting != 'entry_id' ) {
            usort( $entries, array( $this, 'compare_column_' . $sort_direction ) );
        }

        $show_entries     = $this->check_pagination( $entries );
        $this->set_pagination_args( array(
			'total_items' => $total_count,
			'per_page'    => 20,
		) );

        $this->items = apply_filters( 'cfas_show_entries', $show_entries, $entries, $this->form_id );

    }

    public function check_pagination( $entries ) {
        $per_page              = 20;
        $page_index            = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) - 1 : 0;
        $slice_offset          = $page_index * $per_page;

        return array_slice( $entries, $slice_offset, $per_page );
    }

    public function column_default( $item, $column_name ) {
        if( array_key_exists( $column_name, $item ) ) {
            return $item[ $column_name ];
        } else {
            return '';
        }
    }

    public function single_row( $item ) {
        $class = 'entry_row';
        echo sprintf( '<tr id="entry_row_%d" class="%s" data-id="%d">', $item['entry_id'], $class, $item['entry_id'] );
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    function column_cb( $item ) {
        $entry_id = $item['entry_id'];
        ?>
        <label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $entry_id ); ?>"><?php _e( 'Select entry' ); ?></label>
        <input type="checkbox" class="gform_list_checkbox" name="entry[]" value="<?php echo esc_attr( $entry_id ); ?>" />
        <?php
    }

    public function get_form_entries( $search, $search_cat, $sorting, $sort_direction, $type ) {
        global $wpdb;
        $order_by = $sorting == 'entry_id' ? "ORDER BY entry_id $sort_direction" : '';
        $columns  = $this->get_columns();
        if ( empty( $columns ) ) {
            return '';
        }
        $where = $this->get_sql_conditional( array_keys( $columns ) );
        $sql = "SELECT *
                FROM {$wpdb->prefix}cfas_entries
                WHERE form_id=$this->form_id AND type='$type' AND  ( $where )
                $order_by";
        $res = $wpdb->get_results( $sql, ARRAY_A );
        $res = $this->change_results( $res );
        $res = $this->merge_results( $res );

        if( ! cfas_is_empty( $search ) ) {
            $res = $this->get_searching_result( $res, $search, $search_cat );
        }

        return $res;
    }

    public function get_searching_result( $res, $search, $search_cat ) {
        foreach ( $res as $key=>$info ) {
            $search = strtolower( $search );
            $value  = strtolower( $info[$search_cat] );

            if(  strpos( $value, $search ) === false ) {
                unset( $res[$key] );
            }
        }
        return $res;
    }

    public function change_results( $array ) {
        foreach ( $array as $key=>$row ) {
            $new_name  = $row['entry_key'];
            $new_value = $row['value'];
            $new_value = $this->convert_to_string( $new_value );

            $array[$key][$new_name] = $new_value;
            unset( $array[$key]['entry_key'] );
            unset( $array[$key]['value'] );

        }

        return $array;
    }
    
    public function convert_to_string( $entry ) {
        if( cfas_is_json( $entry ) ) {
            $entry = json_decode( $entry, true);
            $entry = implode( ',' , $entry);
        }
        
        return $entry;
    }

    public function merge_results( $array ) {
        $merge = array();
        $count = 0;
        foreach ($array as &$row ) {
            $entry_id    = $row['entry_id'];
            $merge[$count] = array();
            foreach ( $array as $key=>$info ) {
                if( $info['entry_id'] == $entry_id ) {
                    $merge[$count] = array_merge( $merge[$count], $info );
                    unset( $array[$key] );
                }
            }
          $count++;
        }
        return $merge;
    }

    public function get_sql_conditional( $keys ) {
        $sql      = '';
        $is_first = true;
        foreach ( $keys as &$name ) {
            $where    = $is_first ? " entry_key='$name' " : " OR entry_key='$name' ";
            $sql     .= $where;
            $is_first = false;
        }

        return $sql;
    }

    public function compare_column_asc( $a, $b ) {
        $sorting = $this->sorting;
        return $a[$sorting] > $b[$sorting];
    }

    public function compare_column_desc( $a, $b ) {
        $sorting = $this->sorting;
        return $a[$sorting] < $b[$sorting];
    }

    protected function handle_row_actions( $item, $column_name, $primary )
    {

        if ($primary !== $column_name) {
            return '';
        }

        $form_id  = $this->form_id;
        $field_id = $item['entry_id'];

        ?>
        <div class="row-actions">
            <?php

            switch ($this->page_type) {
                case 'trash' :
                    ?>
                    <span class="edit">
                        <a href="?page=cf_entries&view=details&form=<?php echo $this->form_id?>&entry=<?php echo $field_id?>"><?php esc_html_e('View', CFAS_DOMAIN); ?></a>
                        |
                    </span>
                    <span class="edit">
                        <a class="cfas-restore-entry" href="<?php echo wp_nonce_url("?page=cf_entries&form=$this->form_id&entry=$field_id", 'cf_restore_entry') ?>"><?php esc_html_e('Restore', CFAS_DOMAIN); ?></a>
                        |
                    </span>
                    <span class="delete">
                        <a class="cfas-delete-entry" href="<?php echo wp_nonce_url("?page=cf_entries&form=$this->form_id&entry=$field_id", 'cf_delete_entry') ?>"><?php esc_html_e('Delete Permanently', CFAS_DOMAIN); ?></a>
                    </span>
                    <?php
                    break;

                default:
                    ?>
                    <span class="edit">
                        <a href="?page=cf_entries&view=details&form=<?php echo $this->form_id?>&entry=<?php echo $field_id?>"><?php esc_html_e('View', CFAS_DOMAIN); ?></a>
                        |
                    </span>
                    <span class="trash">
                        <a class="cfas-trash-entry" href=<?php echo wp_nonce_url("?page=cf_entries&form=$this->form_id&entry=$field_id", 'cf_trash_entry') ?>"><?php esc_html_e('Trash', CFAS_DOMAIN); ?></a>
                    </span>
                    <?php
                    break;
            }

            do_action('cfas_entries_row_actions', $form_id, $field_id );

            ?>
        </div>
        <?php
    }

    function get_bulk_actions() {

        $actions = array();

        switch ( $this->page_type ) {
            case 'trash' :
                $actions['restore'] = esc_html__( 'Restore', CFAS_DOMAIN );
                $actions['delete'] = esc_html__( 'Delete', CFAS_DOMAIN );
                break;

            default :
                $actions['trash'] = esc_html__( 'Trash', CFAS_DOMAIN );
                break;
        }

        // Get the current form ID.
        $form_id = $this->form_id;

        /**
         * Modifies available bulk actions for the entries list.
         *
         * @since 1.0.0
         *
         * @param array $actions Bulk actions.
         * @param int   $form_id The ID of the current form.
         */
        return apply_filters( 'cfas_entries_bulk_actions', $actions, $form_id );

    }

    function process_bulk_action() {


        $bulk_action = $this->current_action();

        $form_id = $this->form_id;

        if ( $bulk_action ) {

            $entries_id = $this->get_bulk_action_id();

            if( empty( $entries_id ) ) {
                return;
            }

            switch ( $bulk_action ) {
                case 'trash':
                    cfas_entry_model::change_group_entry_status( $this->form_id, $entries_id, 'trash_entry' );
                    break;

                case 'restore':
                    cfas_entry_model::change_group_entry_status( $this->form_id, $entries_id, 'entry' );
                    break;

                case 'delete':
                    cfas_entry_model::delete_group_entry( $this->form_id, $entries_id );
                    break;
            }

            /**
             * Fires after the default entry list actions have been processed.
             *
             * @param string $bulk_action Action being performed.
             * @param array  $entries_id  The entry IDs the action is being applied to.
             * @param int    $form_id     The current form ID.
             */
            do_action( 'cfas_entry_list_action', $bulk_action, $entries_id, $form_id );

        }
    }

    public function get_bulk_action_id() {
        $id_list = array();

        if( isset( $_POST['all_entry'] ) && ! empty( $_POST['all_entry'] ) ) {
            $id_list = cfas_sanitize_array( $_POST['all_entry'] );
        } elseif ( isset( $_POST['entry'] ) && !empty( $_POST['entry'] ) ) {
            $id_list = cfas_sanitize_array( $_POST['entry'] );
        }

        return $id_list;
    }

    public function columns_setting_form() {
        $all_columns  = $this->get_all_columns();
        $show_columns = $this->get_columns();

        ?>
        <div class="cfas-columns-setting" style="display:none">
            <div class="cfas-settings-container">
                <div class="cfas-setting-header">
                    <button type="button" id="cfas-close-setting" class="cfas-normal-btn"><span class="dashicons dashicons-no"></span></button>
                </div>
                <form method="post" class="cfas-setting-form">
                    <table>
                        <tr>
                            <th><?php echo __('Show', CFAS_DOMAIN)?></th>
                            <th><?php echo __('Column', CFAS_DOMAIN)?></th>
                            <?php do_action( 'cfas_entries_setting_header'); ?>
                        </tr>

                        <?php
                        foreach ($all_columns as $column) {

                            ?>
                            <tr>
                                <?php
                                if( empty( $show_columns) ) {
                                    ?>
                                    <td><input type='checkbox' value='<?php echo $column?>' name='show_columns[]' checked></td>
                                    <?php
                                } else {
                                    ?>
                                    <td><input type='checkbox' value='<?php echo $column?>' name='show_columns[]' <?php if( in_array( $column, array_keys( $show_columns ) ) ) echo 'checked' ?>></td>
                                    <?php
                                }
                                ?>
                                <td><?php echo $column?></td>
                                <?php do_action( 'cfas_entries_setting_body', $this->form_id, $column ); ?>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                    <div class="cfas-setting-footer">
                        <input type='submit' name='res_settings' class='cfas-normal-btn' value='<?php echo __( 'Reset', CFAS_DOMAIN )?>'>
                        <input type='submit' name='sub_settings' class='cfas-normal-btn' value='<?php echo __( 'Submit', CFAS_DOMAIN )?>'>
                    </div>
                </form>
            </div>
        </div>

        <?php
    }

    public function get_all_columns() {
        $entry_columns = $this->get_entry_columns();
        $form_columns  = $this->get_current_form_columns();

        return array_merge( $form_columns, $entry_columns );
    }

    public function get_entry_columns() {
        global $wpdb;
        $sql = "SELECT entry_key 
                FROM {$wpdb->prefix}cfas_entries
                WHERE form_id=$this->form_id
                GROUP BY entry_key";
        $result = $wpdb->get_results($sql);
        return $this->merging_columns( $result, 'entry_key' );
    }

    public function merging_columns( $object, $index = null ) {
        $columns = array();
        foreach ( $object as $item) {
            if ( $index ) {
                $label = $item->{$index};
            } else {
                $label = $item;
            }

            if( ! empty( $label ) ) {

                $columns[$label] = $label;
            }
        }
        return $columns;
    }

    public function add_scripts() {
        ?>
        <script>
            var cfas_ajax_url  = "<?php echo get_admin_url('', 'admin-ajax.php', ''); ?>";
            var cfas_page_type = "<?php echo $this->page_type?>";
            function get_parameter_by_name(name, url) {
                name = name.replace(/[\[\]]/g, '\\$&');
                var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
                    results = regex.exec(url);
                if (!results) return null;
                if (!results[2]) return '';
                return decodeURIComponent(results[2].replace(/\+/g, ' '));
            }
            function cfas_toggle_fade( $elm ) {
                jQuery(document).find( $elm ).fadeToggle()
            }

            jQuery(document).on('click', '#cfas-entries-setting', function () {
                cfas_toggle_fade( '.cfas-columns-setting' );
            }).on('click', '#cfas-close-setting', function () {
                cfas_toggle_fade( '.cfas-columns-setting' );
            }).on('click', '.cfas-columns-setting', function (e) {
                var sort = jQuery(document).find('.cfas-settings-container');
                if (!sort.is(e.target) && sort.has(e.target).length === 0) {
                    jQuery(document).find( '.cfas-columns-setting' ).fadeOut();
                }
            });

            jQuery(document).on( 'click', '.cfas-trash-entry', function (e) {

                e.preventDefault();
                var elm = this;
                var url = jQuery(this).attr('href'),
                    nonce = get_parameter_by_name('_wpnonce', url), // MUST for security checks
                    form  = get_parameter_by_name('form', url),
                    entry = get_parameter_by_name('entry', url);
                jQuery.ajax({
                    type     : 'POST',
                    dataType : 'json',
                    url      : cfas_ajax_url,
                    data     :{
                        'action'   : 'change_entry',
                        '_wpnonce' : nonce,
                        'form'     : form,
                        'entry'    : entry,
                        'status'   : 'trash_entry'
                    },
                    success: function(response){
                        if(response.success) {
                            jQuery(elm).parents('.entry_row').fadeOut(200, function() {
                                jQuery(this).remove()
                                var trash_num = parseInt(jQuery(document).find('.subsubsub #trash_count').html()) + 1;
                                var all_num   = parseInt(jQuery(document).find('.subsubsub #all_count').html()) - 1;
                                jQuery(document).find('.subsubsub #trash_count').html(trash_num);
                                jQuery(document).find('.subsubsub #all_count').html(all_num);

                                if ( 'all' !== cfas_page_type ) {
                                    var link_count = parseInt(jQuery(document).find('.subsubsub #'+cfas_page_type+'_count').html()) - 1;
                                    jQuery(document).find('.subsubsub #'+cfas_page_type+'_count').html(link_count);
                                }
                            })
                        }
                    }
                });
            }).on( 'click', '.cfas-restore-entry', function (e) {
                e.preventDefault();
                var elm = this;
                var url = jQuery(this).attr('href'),
                    nonce = get_parameter_by_name('_wpnonce', url), // MUST for security checks
                    form  = get_parameter_by_name('form', url),
                    entry = get_parameter_by_name('entry', url);
                jQuery.ajax({
                    type     : 'POST',
                    dataType : 'json',
                    url      : cfas_ajax_url,
                    data     :{
                        'action'   : 'change_entry',
                        '_wpnonce' : nonce,
                        'form'     : form,
                        'entry'    : entry,
                        'status'   : 'entry'
                    },
                    success: function(response){
                        if(response.success) {
                            jQuery(elm).parents('.entry_row').fadeOut(200, function() {
                                jQuery(this).remove();
                                var trash_num = parseInt(jQuery(document).find('.subsubsub #trash_count').html()) - 1;
                                var all_num   = parseInt(jQuery(document).find('.subsubsub #all_count').html()) + 1;
                                jQuery(document).find('.subsubsub #trash_count').html(trash_num);
                                jQuery(document).find('.subsubsub #all_count').html(all_num);
                            })
                        }
                    }
                });
            }).on( 'click', '.cfas-delete-entry', function (e) {
                e.preventDefault();
                var elm = this;
                var url = jQuery(this).attr('href'),
                    nonce = get_parameter_by_name('_wpnonce', url), // MUST for security checks
                    form  = get_parameter_by_name('form', url),
                    entry = get_parameter_by_name('entry', url);
                jQuery.ajax({
                    type     : 'POST',
                    dataType : 'json',
                    url      : cfas_ajax_url,
                    data     :{
                        'action'   : 'delete_entry',
                        '_wpnonce' : nonce,
                        'form'     : form,
                        'entry'    : entry,
                    },
                    success: function(response){
                        if(response.success) {
                            jQuery(elm).parents('.entry_row').fadeOut(200, function() {
                                jQuery(this).remove()
                                var trash_num = parseInt(jQuery(document).find('.cfas-entries-type .trash .cfas-count').html()) - 1;
                                jQuery(document).find('.cfas-entries-type .trash .cfas-count').html(trash_num);
                            })
                        }
                    }
                });
            });

            jQuery(document).on( 'click', '.column-cb input', function() {
                var list = new Array();
                if(jQuery(this).is(':checked')){
                    jQuery('.entry_row').each(function() {
                        var res = jQuery(this).attr('data-id');
                        list.push(res)
                    });
                }
                jQuery('#all-entry').val(list);
            })
        </script>
        <?php
    }



}

$cfas_entries = cfas_entries::instance();
$cfas_entries->init();