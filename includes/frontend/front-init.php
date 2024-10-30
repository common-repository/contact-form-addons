<?php
class cfas_front{

    public $add_assets = false;
    /**
     * The current form id.
     *
     * Set Current form id to access from another classes.
     *
     * @var
     * @static
     */
    public static $form_id;

    /**
     * The single instance of the class.
     *
     * @var cfas_front
     */
    protected static $_instance = null;

    /**
     * Main cfas_front Instance.
     *
     * Ensures only one instance of cfas_front is loaded or can be loaded.
     *
     * @static
     * @return cfas_front - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new cfas_front();
        }
        return self::$_instance;
    }

    /**
     * Initial function
     *
     * Call necessary hooks (actions and filters).
     *
     */
    public function init() {
        add_action( 'wpcf7_init', array( $this, 'allow_add_assets' ), 10, 1 );
        add_action( 'wpcf7_contact_form', array( $this, 'set_initial' ), 10, 1 );
        add_filter( 'wpcf7_form_hidden_fields', array( $this, 'add_user_id' ), 10, 1 );
        add_action( 'wp_enqueue_scripts', array( $this, 'cfas_enqueue_scripts' ) );
    }

    public function allow_add_assets() {
        $this->add_assets = true;
    }

    public function set_initial( $instance ) {
        $this->set_form_id($instance);
        $this->make_global_variable();
    }

    /**
     * Set current form id.
     *
     * Save current form id for access from another classes.
     *
     * @param object $form The current form object.
     */
    public function set_form_id( $form ) {
        self::$form_id = $form->id();
    }

    /**
     * Create variable for settings.
     *
     * Create global variable(cfas) in front to add settings to it.
     */
    public function make_global_variable(){
        $form_id = self::$form_id;
        $is_rtl  = is_rtl() ? 'true' : 'false';
        
        if( defined( 'CFASP_USE_PRO' ) ) {
            $use_pro = 'true';
        } else {
            $use_pro = 'false';
        }

        $script  = "if(!window['cfas']){window['cfas']=new Object()};";
        $script .= "window['cfas']['$form_id']=new Object();";
        $script .= "window['cfas']['is-rtl']=new Object();";
        $script .= "window['cfas']['is-rtl']=$is_rtl;";
        $script .= "window['cfas']['use-pro']=new Object();";
        $script .= "window['cfas']['use-pro']=$use_pro;";
        $script .= "window['cfas']['translate']=new Object();";
        $script .= "window['cfas']['translate']['nxt']=new Object();";
        $script .= "window['cfas']['translate']['nxt']='".__( 'Next', CFAS_DOMAIN)."';";
        $script .= "window['cfas']['translate']['prv']=new Object();";
        $script .= "window['cfas']['translate']['prv']='".__( 'Previous', CFAS_DOMAIN)."';";

        $script = apply_filters( 'cfas_window_variable', $script );

        wp_add_inline_script('cfas-front-init', $script, 'before');
    }

    /**
     * Add user id to hidden inputs.
     *
     * For save user id for use in entry detail when form submit.
     *
     * @return array
     */
    public function add_user_id() {
            $hidden  = array();
            $user_id = get_current_user_id();

            $hidden['cfas_user']  = $user_id;

            return $hidden;
    }

    /**
     * Enqueue scripts and styles.
     */
    public function cfas_enqueue_scripts() {
        if ( $this->add_assets ) {
            ?>
            <style>.cfas-none{display:none !important}</style>
            <?php
            wp_enqueue_script('cfas-front-init', CFAS_JS.'front/front'.CFAS_MIN.'.js', array('jquery'), CFAS_VERSION, true);
        }
    }

}

$cfas_front = cfas_front::instance();
$cfas_front->init();
