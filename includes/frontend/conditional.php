<?php

class cfas_front_conditional {

    /**
     * The single instance of the class.
     *
     * @var cfas_front_conditional
     */
    protected static $_instance = null;

    /**
     * Main cfas_front_conditional Instance.
     *
     * Ensures only one instance of cfas_front_conditional is loaded or can be loaded.
     *
     * @static
     * @return cfas_front_conditional - Main instance.
     */
    public static function instance() {

        if( is_null( self::$_instance ) ){
            self::$_instance = new cfas_front_conditional();
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
        add_action( 'wpcf7_init', array( $this, 'group_conditional_tag_handler' ) );
        add_action( 'wpcf7_contact_form', array( $this, 'add_conditional_settings' ), 11, 1 );
        add_action( 'wpcf7_contact_form', array( $this, 'enqueue_assets' ), 10 );
    }

    /**
     * Register group conditional tags.
     *
     * If used from group_con tag, to creat group field function is called.
     */
    public function group_conditional_tag_handler(){
        if ( function_exists('wpcf7_add_form_tag' ) ) {
            wpcf7_add_form_tag( 'group_con', array( $this, 'group_conditional_start_html' ), true );
            wpcf7_add_form_tag( 'end_con' , array( $this, 'group_conditional_end_html' ), true );
        }
    }

    /**
     * Add start html for group conditional.
     *
     * @param object $tag The current tag object.
     *
     * @return string
     */
    public function group_conditional_start_html( $tag ){
        return "<div class='{$tag->name} cfas_group_con'>";
    }

    /**
     * Add end html for group conditional.
     *
     * @param object $tag The current tag object.
     *
     * @return string
     */
    public function group_conditional_end_html( $tag ){
        return "</div>";
    }

    /**
     * Add conditional settings to front.
     *
     * Add conditional information to variable(cfas).
     *
     * @param object $form The current form object.
     */
    public function add_conditional_settings( $form ) {
        $form_id     = $form->id();
        $conditional = get_cfas_settings($form_id, 'conditional',false);
        $script      = '';

        if ( $conditional ) {
            $script  = "var cfas_con = new Object();cfas_con = JSON.parse($conditional);";
            $script .= "window['cfas']['$form_id']['conditional']= cfas_con;";
        }

        wp_add_inline_script( 'cfas-conditional', $script, 'before' );
    }

    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_assets( $form ) {
        $form_id     = $form->id();
        $conditional = get_cfas_settings($form_id, 'conditional',false);

        if ( $conditional ) {
            wp_enqueue_script( 'cfas-conditional', CFAS_JS.'front/conditional'.CFAS_MIN.'.js', array('cfas-front-init'), CFAS_VERSION, true);
        }
    }

}

$cfas_front_conditional = cfas_front_conditional::instance();
$cfas_front_conditional->init();






