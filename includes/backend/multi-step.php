<?php

class cfas_back_page {
    
    public $animations = [
        'None'  => 'none',
        'Fade'  => 'fade',
    ];

    /**
     * The single instance of the class.
     *
     * @var cfas_back_page
     */
    protected static $_instance = null;

    /**
     * Main cfas_back_page Instance.
     *
     * Ensures only one instance of cfas_back_page is loaded or can be loaded.
     *
     * @static
     * @return cfas_back_page - Main instance.
     */
    public static function instance() {

        if( is_null( self::$_instance ) ){
            self::$_instance = new cfas_back_page();
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
        add_action( 'admin_init', array( $this, 'multistep_tag' ), 10 );
        add_filter('wpcf7_editor_panels', array( $this, 'add_page_setting_tab' ), 10 );
        add_action( 'wpcf7_save_contact_form', array( $this, 'save_page_settings' ), 10, 3 );
    }
    
    public function multistep_tag() {

        // Register multi-step tag(cfas_step).
        if ( function_exists('wpcf7_add_form_tag' ) ) {
            wpcf7_add_form_tag( array( 'cfas_step' ), '__return_false', true );
        }

        // Create multi-step tag (cfas_step) generator.
        if ( class_exists( 'WPCF7_TagGenerator' ) ) {
            $tag_generator = WPCF7_TagGenerator::get_instance();
            $tag_generator->add( 'cfas_step', esc_html( __( 'page', 'contact-form-7-multi-step-module' ) ), array( $this, 'multistep_tag_generator' ) );
        }
    }
    
    public function multistep_tag_generator( $contact_form, $args = '' ) {
		$type = 'cfas_step';
    
        $description = __( "Make yor forms multi step.", CFAS_DOMAIN );
    
        include CFAS_INC.'backend/page-tag-generator.php';
    }
    
    public function add_page_setting_tab($tabs){
        if ( current_user_can( 'wpcf7_edit_contact_form' ) ) {
            $tabs['cfas_page'] = array(
                'title'    => __( 'Pages Settings', CFAS_DOMAIN ),
                'callback' => array( $this, 'create_page_settings_panel' )
            );
        }
        
        return $tabs;
    }
    
    
    public function create_page_settings_panel( $form ) {
        $form_id      = isset($_GET['post']) && intval($_GET['post']) ? intval($_GET['post']) : false;
        $page         = get_cfas_settings($form_id, 'page' );
        $next_btn     = isset($page['nxt-btn'])     ? $page['nxt-btn']     : __( 'Next', CFAS_DOMAIN);
        $prv_btn      = isset($page['prv-btn'])     ? $page['prv-btn']     : __( 'Previous', CFAS_DOMAIN);
        $animation    = isset($page['animation'])   ? $page['animation']   : 'none';
        $sub_validate = isset($page['sub_validate']) ? $page['sub_validate'] : false;
        
        $page_animations = apply_filters( 'cfas_page_animations', $this->animations );
        
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="cfas-next-btn"><?php echo __( 'Next Button Name', CFAS_DOMAIN)?></label>
                    </th>
                    <td>
                        <input type="text" id="cfas-next-btn" name="cfas-next-btn" class="medium-text code"  value="<?php echo $next_btn ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="cfas-prv-btn"><?php echo __( 'Previous Button Name', CFAS_DOMAIN)?></label>
                    </th>
                    <td>
                        <input type="text" id="cfas-prv-btn" name="cfas-prv-btn" class="medium-text code"  value="<?php echo $prv_btn ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label>Animation</label>
                    </th>
                    <td>
                        <select name="cfas-animation">
                            <?php
                            foreach ( $page_animations as $name=>$val )
                                if($animation == $val){
                                    ?>
                                    <option value="<?php echo $val ?>" selected><?php echo $name ?></option>
                                    <?php
                                }else{
                                    ?>
                                    <option value="<?php echo $val ?>" ><?php echo $name ?></option>
                                    <?php
                                }
                            ?>
                        </select>

                    </td>
                </tr>


                <?php do_action( 'cfas_page_settings', $form_id, $page );  ?>

                <tr>
                    <th scope="row">
                    </th>
                    <td>
                        <?php
                        if($sub_validate){
                            ?>
                            <p><label for="cfas-validate-on-submit"><input type="checkbox" id="cfas-validate-on-submit" name="cfas-validate-on-submit" checked> <?php echo __('Validation form on submit', CFAS_DOMAIN)?></label></p>
                            <?php
                        }else{
                            ?>
                            <p><label for="cfas-validate-on-submit"><input type="checkbox" id="cfas-validate-on-submit" name="cfas-validate-on-submit" > <?php echo __('Validation form on submit', CFAS_DOMAIN)?></label></p>
                            <?php
                        }
                        ?>

                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
    
    public function save_page_settings( $contact_form, $args, $context ) {
        $form_id                           = $args['id'];
        $settings                          = get_cfas_settings($form_id);
        $settings['page']['nxt-btn']       = sanitize_text_field($_POST['cfas-next-btn']);
        $settings['page']['prv-btn']       = sanitize_text_field($_POST['cfas-prv-btn']);
        $settings['page']['animation']     = sanitize_text_field($_POST['cfas-animation']);
        $settings['page']['sub_validate']  = boolval($_POST['cfas-validate-on-submit']);

        $settings = apply_filters( 'cfas_save_page_settings', $settings );
        
        set_cfas_settings($form_id, $settings);
    }
    
}

$cfas_back_page = cfas_back_page::instance();
$cfas_back_page->init();
