<?php
class cfas_back{

    public static $form_id;

    /**
     * The single instance of the class.
     *
     * @var cfas_back
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main cfas_back Instance.
     *
     * Ensures only one instance of cfas_back is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return cfas_back - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new cfas_back();
        }
        return self::$_instance;
    }

    public function init() {
        add_action( 'wpcf7_admin_footer', array($this, 'set_initial'), 10, 1 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 99);
    }

    public function set_initial( $instance ) {
        $this->make_global_variable();
    }

    public function set_form_id( $instance ) {
        self::$form_id = $instance->id();
    }

    public function make_global_variable(){
        $script  = "if(!cfas){var cfas = new Object();};";
        wp_add_inline_script('cfas-back-init', $script, 'before');
    }
    
    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_style( 'cfas-backend-style', CFAS_CSS.'back/backend'.CFAS_MIN.'.css', '', CFAS_VERSION);
        wp_enqueue_script( 'cfas-back-init', CFAS_JS.'back/admin'.CFAS_MIN.'.js', array('jquery'), CFAS_VERSION, true);
    }

}

$cfas_back = cfas_back::instance();
$cfas_back->init();