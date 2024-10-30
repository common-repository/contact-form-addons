<?php

class cfas_validation {

    public $conditional = array();
    public $success = false;
    public $clicked_btn;
    public $target;
    public $on_page = 1;
    
    /**
     * The single instance of the class.
     *
     * @var cfas_validation
     */
    protected static $_instance = null;

    /**
     * Main cfas_validation Instance.
     *
     * Ensures only one instance of cfas_validation is loaded or can be loaded.
     *
     * @static
     * @return cfas_validation - Main instance.
     */
    public static function instance() {

        if( is_null( self::$_instance ) ){
            self::$_instance = new cfas_validation();
        }

        return self::$_instance;
    }

    public function init() {
        add_filter( 'wpcf7_validate', array( $this, 'validate' ), 10, 2 );
        add_filter( 'wpcf7_feedback_response', array( $this, 'add_data_to_json_result' ), 10, 2 );
    }

    public function validate( $result, $tags ) {
        $this->set_conditional();
        
        require_once CFAS_DIR . '../contact-form-7/includes/validation.php';
    
        $new_fields           = new WPCF7_Validation();
        $validation_on_submit = cfas_post( 'cfas_submit_validate', false, 'bool' );
        $current_page         = cfas_post( 'cfas_curr_page', 1, 'int' );
        $this->clicked_btn    = cfas_post( 'cfas_clicked_btn', 'submit' );
        $this->target         = $this->clicked_btn === 'next' ? cfas_post( 'cfas_target_page', false, 'int' ) : false;
        $invalid_fields       = $result->get_invalid_fields();

        $this->make_page_invalid_fields( $new_fields, $invalid_fields, $tags, $current_page, $validation_on_submit );

        $new_invalid_fields = $new_fields->get_invalid_fields();

        if ( empty ( $new_invalid_fields ) ) {
            $this->success = true;

            if( 'next' === $this->clicked_btn ) {
                // Make new invalid field when current page hasn't no invalid field to prevent from submit form and send mail.
                $new_fields = new WPCF7_Validation();
                $new_fields->invalidate( array( 'name' => 'page-valid'), 'success');
            }
        } else {
            $this->success = false;
        }
        
        do_action( 'cfas_new_invalid_fields', $new_invalid_fields );
        
        return $new_fields;
    }

    public function set_conditional() {
        $form_id           = cfas_front::$form_id;
        $this->conditional = json_decode( get_cfas_settings($form_id, 'conditional',true), true );
    }

    public function make_page_invalid_fields( $new_fields, $invalid_fields, $tags, $current, $validate_on_submit ) {

        $group_conditional = $this->get_page_group_conditional( $tags, $current, $validate_on_submit );

        foreach ( $tags as $tag ) {

            if( $validate_on_submit ) {
                $this->on_submit_validate( $tag, $group_conditional, $invalid_fields, $new_fields );
            } else {
                $this->on_next_validate( $tag, $current, $group_conditional, $invalid_fields, $new_fields );
            }

        }

    }

    public function get_page_group_conditional( $tags, $current, $validate_on_submit ) {
        $start_page     = 1;
        $allow_to_add   = false;
        $display_status = null;
        $group_fields   = array();
        $visible_pages  = array();

        // For nested group conditional
        $group_depth    = array();

        if( $validate_on_submit ) {
            $visible_pages = $this->get_visible_page_number();
        } else {
            $visible_pages[] = $current;
        }
        
        

        foreach ( $tags as $tag ) {
            $type = $tag->type;
            $name = $tag->name;

            if ( $type === 'cfas_step' ) {
                $start_page++;
            }

            if( ! in_array( $start_page, $visible_pages ) ) {
                continue;
            }

            if ( $type === 'group_con' && empty( $group_depth ) ) {

                $display_status = $this->group_con_visible_status ( $name );
                $group_depth[1] = $display_status;
                
                if( 'show' === $display_status ) {
                    $allow_to_add  = true;
                }
                
                continue;
            } elseif ( $type === 'group_con' && ! empty( $group_depth ) ) {
                $parent_depth   = $this->get_parent_group_depth( $group_depth );
                $parent_display = $this->get_parent_group_display( $group_depth, $parent_depth );
                $current_depth  = $parent_depth + 1;
                $display_status = $parent_display === 'show' ? $this->group_con_visible_status ( $name ) : 'hide';
                
                $group_depth[$current_depth] = $display_status;
                
                if( 'show' === $display_status ) {
                    $allow_to_add  = true;
                } else {
                    $allow_to_add  = false;
                }
                
                continue;
            } elseif ( $type === 'end_con') {
                array_pop( $group_depth );
                if ( empty( $group_depth ) ) {
                    $allow_to_add = false;
                } else {
                    $parent_depth   = $this->get_parent_group_depth( $group_depth );
                    $parent_display = $this->get_parent_group_display( $group_depth, $parent_depth );
                    $allow_to_add   = $parent_display === 'show' ? true : false;    
                }
                
                continue;
            }
            
            if ( $allow_to_add ) {
                //When fields in group conditional is visible
                $conditional    = $this->get_field_conditional( $name );
                $display_status = 'show';
                
                if ( $conditional ){
                    $is_field_visible = $this->is_field_visible( $conditional );
                    $display_status   = $is_field_visible ? 'show' : 'hide';
                }
                
                $group_fields[$name] = $display_status;
            } elseif ( ! empty( $group_depth ) ) {
                //When group conditional and its fields is hide
                $group_fields[$name] = 'hide';
            }
            
            
            
            
        }


        return $group_fields;
    }

    public function on_submit_validate( $tag, $group_conditional, $invalid_fields, $new_fields ) {
        $visible_pages = $this->get_visible_page_number();

        if( in_array( $this->on_page, $visible_pages ) ) {
            $this->on_next_validate( $tag, $this->on_page, $group_conditional, $invalid_fields, $new_fields );
        }

    }

    public function get_visible_page_number() {
        
        $valid_pages = array();
        $pages_count = cfas_get_page_numbers();

        for( $start_page = 1 ; $start_page <= $pages_count ; $start_page++ ) {
            if (  $this->is_page_visible( $start_page ) ) {
                $valid_pages[] = $start_page;
            }
        }
        return $valid_pages;
    }

    public  function is_page_visible( $page_num ) {
        $is_visible = true;
        return apply_filters( 'cfas_visible_page', $is_visible, $page_num );
    }

    public function group_con_visible_status ( $name ) {
        $conditionals = $this->get_field_conditional( $name );

        if( $conditionals ) {
            
            $is_field_visible = $this->is_field_visible( $conditionals );

            return $is_field_visible ? 'show' : 'hide';
        }
        
        return 'show';
    }

    public function on_next_validate( $tag, $current, $group_conditional, $invalid_fields, $new_fields ) {
        $type       = $tag->type;
        $name       = $tag->name;

        if ( $type === 'cfas_step' ) {
            $this->on_page++;
        }
        if ( $current !== $this->on_page ) {
            // If user clicked on next button just return the current page invalid fields
            return '';
        }
        if ( isset( $invalid_fields[$name] ) ) {

            if ( $this->is_field_in_group_conditional( $group_conditional, $name ) ) {
                $display_status = $group_conditional[$name];
                
                if( 'hide' === $display_status) {
                    return '';
                }
            } else {
                $conditional = $this->get_field_conditional( $name );

                if ( $conditional ){
                    $is_field_visible = $this->is_field_visible( $conditional );
                    $is_field_valid   = !$is_field_visible;
                    if( $is_field_valid ) {
                        return '';
                    }
                }
            }

            $reason = $invalid_fields[$name]['reason'];
            $new_fields->invalidate( $tag, $reason );
        }
    }

    public function get_field_conditional( $name ) {
        $conditionals = $this->conditional ? $this->conditional : array();

        foreach ( $conditionals as $key=>$rule ) {
            $field = $rule['field'];

            if ( $name !== $field || $this->is_page( $field ) ) {
                unset( $conditionals[$key] );
            }
        }

        return ! empty( $conditionals ) ? $conditionals : false;
    }

    public function is_page( $field ) {
        return  !! strpos('cfaspage', $field );
    }


    public function is_field_visible( $conditional ) {
        $is_visible = true;

        foreach ( $conditional as $rule_body ) {
            $con_type       = $rule_body['type'];
            $con_if         = $rule_body['if'];
            $con_rules      = $rule_body['rules'];
            $is_rules_valid = $this->check_rules_validation( $con_if, $con_rules );

            if( $is_rules_valid ) {
                if($con_type === 'show'){
                    $is_visible = true;
                    break;
                }else if($con_type === 'hide'){
                    $is_visible = false;
                    break;
                }
            } else {
                if($con_type === 'show'){
                    $is_visible = false;
                    break;
                }else if($con_type === 'hide'){
                    $is_visible = true;
                    break;
                }
            }
        }

        return $is_visible;

    }

    public function get_parent_group_depth( $depth ) {
        $end = null; 
        
        foreach ( $depth as $key=>$val ) {
            $end = $key;
        }
        
        return $key;
    }
    
    public function get_parent_group_display( $depth, $key ) {
        return $depth[$key];
    }
    
    public function is_field_in_group_conditional( $group_conditional, $name ) {
        return isset( $group_conditional[$name] ) ? true : false;
    }

    public function check_rules_validation( $if, $rules ) {
        $is_valid = false;

        foreach ( $rules as $rule ) {
            $field     = $rule['field'];
            $operator  = $rule['con'];
            $value     = $rule['val'];
            $field_val = is_array($_POST[$field]) ? cfas_post( $field, '', 'array' ) : cfas_post( $field);
            $is_valid  = $this->single_rule_validation( $operator, $value, $field_val );

            if ( $if === 'all' ) {
                if( ! $is_valid ) {
                    $is_valid = false;
                    break;
                }
            } elseif ( $if === 'any' ) {
                if ( $is_valid ) {
                    $is_valid = true;
                    break;
                }
            }
        }

        return $is_valid;
    }

    public function single_rule_validation( $operator, $value, $field_val ) {
        $is_valid = false;
        $is_array = is_array( $field_val );

        switch ( $operator ) {
            case '==' :
                if( $is_array ) {
                    if( in_array( $value, $field_val ) )
                        $is_valid = true;
                } else {
                    if( $field_val === $value )
                        $is_valid = true;
                }
                break;

            case '!=' :
                if( $is_array ) {
                    if( ! in_array( $value, $field_val ) )
                        $is_valid = true;
                } else {
                    if ( $field_val !== $value )
                        $is_valid = true;
                }
                break;
        }

        $is_valid = apply_filters( 'cfas_single_rule_validate', $is_valid, $operator, $value, $field_val );

        return $is_valid;
    }

    public function add_data_to_json_result( $response, $result ) {
		
		if ( $this->success ) {
			$response['invalid_fields'] = null;
		}
		
        $response['success'] = $this->success;
        $response['target']  = ! empty( $this->target )      ? $this->target      : false ;
        $response['clicked'] = ! empty( $this->clicked_btn ) ? $this->clicked_btn : 'submit' ;

        return $response;
    }

}

$cfas_validation = cfas_validation::instance();
$cfas_validation->init();
