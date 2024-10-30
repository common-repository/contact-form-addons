<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class cfas_form_list extends WP_List_Table {

    /**
     * The single instance of the class.
     *
     * @var cfas_form_list
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main cfas_entries Instance.
     *
     * Ensures only one instance of cfas_form_list is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return cfas_form_list - Main instance.
     */
    public static function instance(){
        if( is_null( self::$_instance ) ){
            self::$_instance = new cfas_form_list();
        }

        return self::$_instance;
    }

    public function init(){
        echo '<div class="wrap"><h2>'.__( 'Contact Forms list', CFAS_DOMAIN ).'</h2>';
        $this->prepare_items();
        $this->make_search_box();
        $this->display();
        echo '</div>';
    }

    public function make_search_box(){
        ?>
        <form id="form_list_search" method="get">
            <input type="hidden" value="cf_entries" name="page">
            <?php
                $this->search_box( esc_html__( 'Search Forms', 'CFAS_DOMAIN' ), 'form' );
            ?>
        </form>
        <?php
    }

    public function get_columns(){
        return array(
            'post_title'  => 'Title',
            'ID'          => 'ID',
            'entry_count' => 'Entries',
            'trash_count' => 'Trash'
        );
    }

    public function get_sortable_columns(){
        return array(
            'post_title'   => array( 'post_title', false ),
            'ID'           => array( 'ID', false ),
            'entry_count'  => array( 'entry_count', false ),
            'trash_count'  => array( 'view_count', false ),
        );
    }

    public function prepare_items() {
        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $sorting               = empty( $_GET['orderby'] ) ? 'ID' : cfas_get('orderby');
        $sort_direction        = empty( $_GET['order'] ) ? 'ASC' : strtoupper(cfas_get('order'));
        $sort_direction        = $sort_direction == 'ASC' ? 'ASC' : 'DESC';
        $search                = cfas_get( 's' );
        $this->_column_headers = array($columns, $hidden, $sortable);

        if ( ! in_array( strtolower( $sorting ), $sortable ) ) {
            $sorting = 'ID';
        }

        $result = $this->get_forms_list($search, $sorting, $sort_direction);


        $this->items = $result;
    }

    public function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'post_title':
            case 'ID':
            case 'entry_count':
            case 'trash_count':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

    public function single_row_columns( $item ) {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        foreach ( $columns as $column_name => $column_display_name ) {
            $classes = "$column_name column-$column_name";
            if ( $primary === $column_name ) {
                $classes .= ' has-row-actions column-primary';
            }

            if ( in_array( $column_name, $hidden ) ) {
                $classes .= ' hidden';
            }


            // Comments column uses HTML in the display name with screen reader text.
            // Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
            $data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

            $attributes = "class='$classes' $data";

            if ( 'post_title' === $column_name ) {
                ?>
                    <th scope="row">
                        <strong>
                            <a href="?page=cf_entries&view=entries&form=<?php echo $item->ID?>"><?php echo $item->post_title ?></a>
                        </strong>
                    </th>
                <?php
            } else {
                ?>
                    <td <?php echo $attributes ?>>
                        <?php echo $item->$column_name ?>
                    </td>
                <?php
            }
        }
    }

    /**
     * Gets all forms.
     *
     * @since  Unknown
     * @access public
     * @global $wpdb
     *
     * @uses GFFormsModel::get_form_table_name()
     * @uses GFFormsModel::get_form_db_columns()
     * @uses GFFormsModel::get_entry_count_per_form()
     * @uses GFFormsModel::get_view_count_per_form()
     *
     * @param bool   $is_active   Optional. Defines if inactive forms should be displayed. Defaults to null.
     * @param string $sort_column Optional. The column to be used for sorting the forms. Defaults to 'title'.
     * @param string $sort_dir    Optional. Defines the direction that sorting should occur. Defaults to 'ASC' (ascending). Use 'DESC' for descending.
     * @param bool   $is_trash    Optional. Defines if forms within the trash should be displayed. Defaults to false.
     *
     * @return array $forms All forms found.
     */
    public function get_forms_list($search, $sorting, $sort_direction) {
        global $wpdb;
        $columns = $this->get_available_columns();
        if(!in_array($sorting, $columns)){
            $sorting = 'ID';
        }
        $where    = cfas_is_empty($search) ? '' : "AND post_title LIKE '%{$search}%' ";
        $order_by = !empty($sorting) ? "ORDER BY $sorting $sort_direction" : '';
        $query = "SELECT ID,post_title,0 entry_count, 0 trash_count 
                  FROM {$wpdb->prefix}posts 
                  WHERE post_type='wpcf7_contact_form' $where
                  $order_by";

        // Getting all contact forms
        $forms = $wpdb->get_results($query);

        //Adding entry counts and trash counts to form array
        foreach ($forms as &$form){
            $form->entry_count = $this->get_entries_count( $form->ID );
            $form->trash_count = $this->get_trash_entries_count( $form->ID );
        }

        return $forms;
    }

    public function get_available_columns() {
        return array('ID', 'post_title');
    }

    public function get_entries_count( $form_id ){
        global $wpdb;

        $sql = "SELECT count(*) as entry FROM 
                  (
                  SELECT * FROM {$wpdb->prefix}cfas_entries
                  WHERE form_id=$form_id AND type='entry'
                  GROUP BY entry_id
                  )
                AS entry";

        $result = $wpdb->get_row($sql);
        return $result->entry ? $result->entry : 0;
    }

    public function get_trash_entries_count( $form_id ){
        global $wpdb;

        $sql = "SELECT count(*) as trash FROM 
                  (
                  SELECT * FROM {$wpdb->prefix}cfas_entries
                  WHERE form_id=$form_id AND type='trash_entry'
                  GROUP BY entry_id
                  )
                AS trash";

        $result = $wpdb->get_row($sql);
        return $result->trash ? $result->trash : 0;
    }

}

$cfas_form_list = cfas_form_list::instance();
$cfas_form_list->init();

