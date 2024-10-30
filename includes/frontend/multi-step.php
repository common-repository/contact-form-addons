<?php


class cfas_front_page {
    
    /**
     * The current page number.
     *
     * A counter that save current page number and uses to craet page.
     *
     * @var int
     */
    public $page_num = 1;
    
    /**
     * The single instance of the class.
     *
     * @var cfas_front_page
     */
    protected static $_instance = null;

    /**
     * Main cfas_front_page Instance.
     *
     * Ensures only one instance of cfas_front_page is loaded or can be loaded.
     *
     * @static
     * @return cfas_front_page - Main instance.
     */
    public static function instance() {

        if( is_null( self::$_instance ) ){
            self::$_instance = new cfas_front_page();
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
        add_action( 'wpcf7_init', array( $this, 'cfas_page_add_shortcode' ) );
        add_filter( 'wpcf7_form_elements', array( $this, 'cfas_make_page' ), 10 );
        add_action( 'wpcf7_contact_form', array( $this, 'add_page_settings' ), 11 );
        add_action( 'wpcf7_contact_form', array( $this, 'enqueue_animation_assets'), 10 );
        add_action( 'wpcf7_contact_form', array( $this, 'enqueue_assets' ), 10 );
    }

    /**
     * Register cfas_step tag
     *
     * If used from cfas_step tag, to creat page function is called.
     *
     */
    public function cfas_page_add_shortcode(){
        if ( function_exists('wpcf7_add_form_tag' ) ) {
            wpcf7_add_form_tag( array( 'cfas_step' ), array( $this, 'cfas_page_shortcode_handler' ), true );
        }
    }

    /**
     * Create multi-step.
     * 
     * Add multi-step html elements for each cfas_step tag.
     * 
     * @param object $tag The current tag(cfas_step) info.
     *
     * @return string
     */
    public function cfas_page_shortcode_handler( $tag ){
        $form_id  = cfas_front::$form_id;
        $page_num = ++$this->page_num;

        return "</div></div><div class='cfas_page' id='cfas_page_{$form_id}_{$page_num}' style='display:none;'><div class='cfas_page_content' id='cfas_page_content_{$form_id}_{$page_num}'>";
    }

    /**
     * Add multi-step html.
     *
     * Add necessary html elements to start and end of form to create page.
     *
     * @param object $form The current form object.
     *
     * @return string
     */
    public function cfas_make_page( $form ) {
        $form_id  = cfas_front::$form_id;
        $has_page = $this->has_page( $form );
    
        if ( $has_page ) {
			$this->reset_page_counter();
			
            $hidden = $this->get_page_hidden_input( $form_id );
			
            $form   = substr_replace( $form, "$hidden<div class='cfas_page_body' id='cfas_page_body_$form_id'><div class='cfas_page' id='cfas_page_{$form_id}_1'><div class='cfas_page_content' id='cfas_page_content_{$form_id}_1'>", 3 ,0 );
            $length = strlen( $form );
            $form   = substr_replace( $form, '</div></div></div>', $length-5 ,0 );
        }
    
        return $form;
    }
	
	/**
     * Reset page counter for make new form pages.
     */
	public function reset_page_counter() {
		$this->page_num = 1;
	}

    /**
     * Multi-step hidden input.
     *
     * Add hidden inputs for multi-step forms to post necessary multi-step information.
     *
     * @param integer $form_id The current form object.
     *
     * @return string
     */
    public function get_page_hidden_input( $form_id ) {
        $submit_validate = $this->is_validate_on_submit( $form_id );

        $hidden  = "<input id='cfas_btn_type_$form_id' type='hidden' name='cfas_clicked_btn' value='submit'>";
        $hidden .= "<input id='cfas_current_page_$form_id' type='hidden' name='cfas_curr_page' value='1'>";
        $hidden .= "<input id='cfas_target_page_$form_id' type='hidden' name='cfas_target_page'>";
        $hidden .= "<input id='cfas_validate_type_$form_id' type='hidden' name='cfas_submit_validate' value='$submit_validate'>";

        return $hidden;
    }

    /**
     * Get type of multi-step validate.
     *
     * Validate form on each page or validate form on submit.
     *
     * @param integer $form_id The current form id.
     *
     * @return bool
     */
    public function is_validate_on_submit( $form_id ) {
        $page_settings = get_cfas_settings( $form_id, 'page');
        return isset( $page_settings['sub_validate'] ) ? $page_settings['sub_validate'] : false;
    }

    /**
     * Has form multi-step?
     *
     * If the current form use multi-step return true.
     *
     * @param string $form The current form html.
     *
     * @return bool
     */
    public function has_page( $form ){
        return strpos( $form, 'cfas_page' ) > 0;
    }

    /**
     * Add multi-step settings to front.
     *
     * Add multi-step information to variable(cfas).
     *
     * @param object $form The current form object.
     */
    public function add_page_settings( $form ) {
        $form_id       = $form->id();
        $page_settings = get_cfas_settings($form_id, 'page',false);
        $script        = null;

        if ($page_settings){
            $script  = "var cfas_page = (JSON.parse(JSON.stringify($page_settings)));";
            $script .= "window['cfas']['$form_id']['page']= cfas_page";
        }

        wp_add_inline_script('cfas-page', $script, 'before');
    }

    /**
     * Enqueue multi step scripts and styles for each form.
     *
     * @param object $form The current form object.
     */
    public function enqueue_animation_assets( $form ){
        $form_id      = $form->id();
        $page_setting = get_cfas_settings($form_id, 'page');
        $animation    = $page_setting['animation'] ? $page_setting['animation'] : 'none';

        if($animation === 'fade'){
            wp_enqueue_script('cfas-page-'.$animation.'-animation', CFAS_JS.'front/animations/'.$animation.CFAS_MIN.'.js', array('cfas-page'), CFAS_VERSION, true);
            wp_enqueue_style('cfas-animation-style', CFAS_CSS.'front/animations'.CFAS_MIN.'.css', '', CFAS_VERSION);
        }

        do_action( 'cfas_enqueue_animation', $animation );
    }

    /**
     * Enqueue scripts and styles.
     *
     * @param object $form The current form object.
     */
    public function enqueue_assets( $form ) {
        $has_page_tag = $this->check_for_page_tag( $form );
        if( $has_page_tag ) {
            wp_enqueue_script('cfas-page', CFAS_JS.'front/multi-step'.CFAS_MIN.'.js', array('cfas-front-init'), CFAS_VERSION, true);
            wp_enqueue_style('cfas-page-style', CFAS_CSS.'front/front'.CFAS_MIN.'.css', '', CFAS_VERSION);

            do_action( 'cfas_enqueue_page_assets' );
        }
    }

    public function check_for_page_tag( $form ) {
        $tags = $form->scan_form_tags();

        foreach ( $tags as $tag ) {
            $type = $tag->type;
            if( 'cfas_step' === $type ) {
                return true;
            }
        }

        return false;
    }
}

$cfas_front_page = cfas_front_page::instance();
$cfas_front_page->init();
