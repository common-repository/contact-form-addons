<?php

class cfas_back_conditional {

    /**
     * The single instance of the class.
     *
     * @var cfas_back_conditional
     */
    protected static $_instance = null;

    /**
     * Main cfas_back_conditional Instance.
     *
     * Ensures only one instance of cfas_back_conditional is loaded or can be loaded.
     *
     * @static
     * @return cfas_back_conditional - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new cfas_back_conditional();
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
        add_action( 'admin_init', array( $this, 'conditional_tag' ) );
        add_action( 'wpcf7_admin_footer', array( $this, 'create_conditional_variable' ), 99, 1 );
        add_filter( 'wpcf7_editor_panels', array( $this, 'add_conditional_tab' ) );
        add_action( 'wpcf7_save_contact_form', array( $this, 'save_conditional_settings' ), 10, 3 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 99 );
    }

    /**
     *  Register conditional tags in admin.
     */
    public function conditional_tag() {

        // Register conditional tags(group_con and end_con).
        if ( function_exists('wpcf7_add_form_tag' ) ) {
            wpcf7_add_form_tag( array( 'group_con' ), '__return_false', true );
            wpcf7_add_form_tag( array( 'end_con' ), '__return_false', true );
        }

        // Create conditional tags generator(group_con and end_con).
        if ( class_exists( 'WPCF7_TagGenerator' ) ) {
            $tag_generator = WPCF7_TagGenerator::get_instance();
            $tag_generator->add( 'group_con', esc_html( __( 'Conditional Group', CFAS_DOMAIN ) ), array( $this, 'make_con_tag' ) );
        }

    }

    /**
     * Creat conditional tag generator page.
     */
    public function make_con_tag( ) {
        $type = 'group_con';
    
        $description = __( "Generate a group tag to group form elements that can be shown conditionally.", CFAS_DOMAIN );
    
        include CFAS_INC.'backend/con-tag-generator.php';
    }

    /**
     * Add conditional fields.
     *
     * Add current form valid fields to global variable for make conditional rules.
     *
     * @param object $form The current form object.
     */
    public function create_conditional_variable( $form ) {
        $con_field  = $this->get_conditional_field($form);
        $rule_field = $this->get_conditional_rule_field($form);
    
        $script  = "cfas.con_field = new Object();cfas.con_rule = new Object();";
        $script .= "cfas.con_field = JSON.parse( JSON.stringify( $con_field ) );";
        $script .= "cfas.con_rule = JSON.parse( JSON.stringify( $rule_field ) );";
        
        wp_add_inline_script('cfas-con', $script, 'before' );
    }
    
    
    public function get_conditional_field($form, $json = true){
        $fields   = [];
        $inputs   = $this->get_form_input_tag($form);

        foreach ($inputs as $key=>$field){
            $field_type    = $field->type;
            $info['value'] = $field->name;
            $info['index'] = $field->name;

            $info = apply_filters( 'cfas_conditional_field_info', $info, $field_type );
			
            if ( 'group_con' !== $field_type && 'cfas_step' !== $field_type ) {
                continue;
            }

            // If field is invalid dont add its to fields list.
            if( empty( $info['value'] ) ) {
                continue;
            }

            $field_value = $info['value'];
            $field_index = $info['index'];

            $fields[$field_index] = $field_value;
        }

        $fields = apply_filters( 'cfas_conditional_fields', $fields );

        if ( $json ) {
            return json_encode( $fields );
        } else {
            return $fields;
        }
    }
    
    public function get_conditional_rule_field($form, $json = true){
        $fields = [];
        $inputs = $this->get_form_input_tag($form);
    
        foreach ( $inputs as $key=>$field ){
            $field_type = $field->type;
            
            if('submit' === $field_type || 'group_con' === $field_type || 'end_con' === $field_type || 'cfas_step' === $field_type)
                continue;
                
            $fields[$field->name] = $field->name;
        }

        $fields = apply_filters( 'cfas_conditional_rule_fields', $fields );

        if ( $json ) {
            return json_encode( $fields );
        } else {
            return $fields;
        }
    }
    
    public function get_form_input_tag($form){
        return $form->scan_form_tags();
    }
    
    
    public function get_conditional_body( $form, $con_rules ){
        foreach ($con_rules as $info){
            $type      = $info['type'];
            $con_field = $info['field'];
            $con_if    = $info['if'];
            $rules     = $info['rules'];
            $is_valid  = $this->is_conditional_valid( $con_field, $form );

            if( ! $is_valid )
                continue;
            ?>
            <div class="cfas_rules_body p_5">
                <div class="cfas_rule_top">
                    <select class="cfas_con_type brd_r_2 brd_1 brd_b" onchange="cfas_set_conditional_rules()">
                        <?php
                        if( 'show' === $type){
                            ?>
                            <option value="show" selected>Show</option>
                            <option value="hide">Hide</option>
                            <?php
                        }elseif( 'hide' === $type) {
                            ?>
                            <option value="show">Show</option>
                            <option value="hide" selected>Hide</option>
                            <?php
                        }
                        ?>
                    </select>
                    <span class="cfas_field_text txt_b m_r_5 m_l_5">Field</span>
                    <select class="cfas_con_field brd_r_2 brd_1 brd_b m_lr_5" onchange="cfas_set_conditional_rules()">
                        <?php
                        $options = $this->get_conditional_field($form, false);

                        foreach( $options as $field=>$val ){
                            if( $con_field === $val ){
                                ?>
                                <option value="<?php echo $val ?>" selected><?php echo $field ?></option>
                                <?php
                            }else{
                                ?>
                                <option value="<?php echo $val ?>"><?php echo $field ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                    <span class="cfas_field_text txt_b m_r_5 m_l_5">If</span>
                    <select class="cfas_if_type brd_r_2 brd_1 brd_b" onchange="cfas_set_conditional_rules()">
                        <?php
                        if( 'all' === $con_if){
                            ?>
                            <option value="all" selected>All</option>
                            <option value="any">Any</option>
                            <?php
                        }elseif( 'any' === $con_if) {
                            ?>
                            <option value="all">All</option>
                            <option value="any" selected>Any</option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="cfas_rules_container">
                    <?php
                    foreach ($rules as $rule){
                        $rule_field = $rule['field'];
                        $rule_con   = $rule['con'];
                        $rule_val   = $rule['val'];
                        $is_in_form = $this->is_field_in_form( $rule_field, $form );

                        if( ! $is_in_form )
                            continue;
                        ?>
                        <div class="con_rule brd_r_2 brd_1 brd_b p_5 d_flx j_cntr aln_cntr back_b m_tb_5">
                            <span class="cfas_field_text txt_b">Field</span>
                            <select  class="cfas_rule_field brd_r_2 brd_1 brd_b m_lr_5" onchange="cfas_change_auto_value(this)">
                                <?php
                                $options = $this->get_conditional_rule_field($form, false);
                                foreach ( $options as $name){
    
                                    if($rule_field ===  $name){
                                        ?>
                                        <option value="<?php echo $name ?>" selected><?php echo $name ?></option>
                                        <?php
                                    }else{
                                        ?>
                                        <option value="<?php echo $name ?>"><?php echo $name ?></option>
                                        <?php
                                    }
    
                                }
                                ?>
                            </select>
                            <select class="cfas_rule_con brd_r_2 brd_1 brd_b m_lr_5" onchange="cfas_set_conditional_rules()">
                                <option value="==" <?php if($rule_con=='==') echo 'selected'?> >is</option>
                                <option value="!=" <?php if($rule_con=='!=') echo 'selected'?> >is not</option>
                                <option value=">" <?php if($rule_con=='>') echo 'selected'?> >greater than</option>
                                <option value="<" <?php if($rule_con=='<') echo 'selected'?> >less than</option>
                                <option value="contains" <?php if($rule_con=='contains') echo 'selected'?> >contains</option>
                                <option value="starts_with" <?php if($rule_con=='starts_with') echo 'selected'?> >starts with</option>
                                <option value="ends_with" <?php if($rule_con=='ends_with') echo 'selected'?> >ends with</option>
                            </select>
                            <input type="text" class="cfas_rule_val" value="<?php echo $rule_val ?>" onkeyup="cfas_set_conditional_rules()">
                            <div class="rule_btn d_flx aln_cntr">
                                <span class="add_rule m_lr_10" onclick="add_conditional_rule(this);"></span>
                                <span class="del_rule" onclick="del_conditional_rule(this);"></span>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }
    }

    public function is_conditional_valid( $conditional_field, $form ) {
        $fields = $this->get_conditional_field( $form, false );
        return in_array( $conditional_field, $fields );
    }

    public function is_field_in_form( $field, $form ){
        $inputs = $form->scan_form_tags();

        foreach ($inputs as $info){
            $name = $info['name'];

            if($name === $field) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add conditional tab to CF7 tabs.
     *
     * @param array $tabs   The list of exists tabs.
     *
     * @return array
     */
    public function add_conditional_tab($tabs){
        if ( current_user_can( 'wpcf7_edit_contact_form' ) ) {
            $tabs['cfas_conditional'] = array(
                'title'    => __( 'Conditional', CFAS_DOMAIN ),
                'callback' => array( $this, 'create_conditional_panel' )
            );
        }

        return $tabs;
    }

    public function create_conditional_panel($form){
        $form_id     = isset($_GET['post']) ? intval($_GET['post']) : '';

        if ( $form_id === '' ) {
            return false;
        }

        $conditional = get_cfas_settings( $form_id, 'conditional', false );
        $con_rules   = null;

        if( $conditional ) {
            $conditional = stripslashes( $conditional );
            $conditional = substr($conditional, 1);
            $conditional = substr($conditional, 0, -1);
            $con_rules   = json_decode( $conditional, true );
        }

        ?>
        <textarea id="cfas_conditional_rules" name="cfas-conditional" style="display: none !important;"><?php if($conditional) echo $conditional; ?></textarea>
        <h2><?php echo __( 'Conditional', CFAS_DOMAIN )?></h2>
        <div class="cfas_con_body">
            <?php
            if( $con_rules ) {
                $this->get_conditional_body($form, $con_rules);
            }
            ?>
        </div>
        <div class="cfas_add_rule"><input type="button" class="cfas_add_rule_btn" onclick="add_con_rule_box();" value="+<?php echo __( 'Add new rule', CFAS_DOMAIN )?>"></div>
        <?php
    }
    
    public function save_conditional_settings( $contact_form, $args, $context ) {
        $form_id                 = $args['id'];
        $settings                = get_cfas_settings( $form_id );
        $settings['conditional'] = sanitize_textarea_field(stripslashes($_POST['cfas-conditional']));
        set_cfas_settings( $form_id, $settings );
    }
    
    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_script( 'cfas-con', CFAS_JS.'back/conditional'.CFAS_MIN.'.js', array('cfas-back-init'), CFAS_VERSION, true);
    }

}

$cfas_back_conditional = cfas_back_conditional::instance();
$cfas_back_conditional->init();