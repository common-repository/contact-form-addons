<?php

class cfas_entry_menu {

    /**
     * The single instance of the class.
     *
     * @var cfas_entry_menu
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main cfas_entries Instance.
     *
     * Ensures only one instance of cfas_entry_menu is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return cfas_entry_menu - Main instance.
     */
    public static function instance(){
        if( is_null( self::$_instance ) ){
            self::$_instance = new cfas_entry_menu();
        }

        return self::$_instance;
    }

    public function init(){
        add_action( 'admin_menu', array($this, 'add_entries_menu'), 10 );
        require_once CFAS_INC.'backend/entry-model.php';
    }

    public function add_entries_menu(){
        add_submenu_page( 'wpcf7',
            'Entries',
            'Entries',
            'wpcf7_edit_contact_forms',
            'cf_entries',
            array($this, 'handle_entries_url')
        );
    }


    public function handle_entries_url(){
        $view = cfas_get( 'view', 'forms' );

        switch ( $view ){
            case 'entries':
                require_once CFAS_INC.'backend/entries.php';
                break;

            case 'details':
                require_once CFAS_INC.'backend/entry-detail.php';
                break;

            case 'forms':
            default:
                require_once CFAS_INC.'backend/form-list.php';
                break;
        }
    }

}

$cfas_entry_menu = cfas_entry_menu::instance();
$cfas_entry_menu->init();
