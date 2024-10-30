$(document).on('cfas_conditional', function ($event, $element, $form_id) {
    var $has_con      = !!cfas[$form_id]['conditional'];
    var $conditionals, $is_pag_conditional, $is_rule_valid;
    if($has_con){
        $conditionals = cfas[$form_id]['conditional'];
        $.each($conditionals, function ($rule_key, $rule_body) {
            $con_type  = $rule_body['type'];
            $con_if    = $rule_body['if'];
            $con_field = $rule_body['field'];
            $con_rules = $rule_body['rules'];
            $is_pag_conditional = is_conditional_for_page($con_field);
            if($is_pag_conditional){
                return;
            }

            $is_rule_valid = check_rules_validation($con_if, $con_rules);
            if($is_rule_valid){
                if($con_type === 'show'){
                    cfas_show_conditional_field( $form_id, $con_field);
                }else if($con_type === 'hide'){
                    cfas_hide_conditional_field( $form_id, $con_field);
                }
            }else{
                if($con_type === 'show'){
                    cfas_hide_conditional_field( $form_id, $con_field);
                }else if($con_type === 'hide'){
                    cfas_show_conditional_field( $form_id, $con_field);
                }
            }
        })
    }

});

function is_conditional_for_page($field) {
    $field = $field.split('_')[0];
    return $field === 'cfaspage';
}

function check_rules_validation($if, $rules) {
    var $field, $operator, $value, $field_val;
    var $is_valid = false;
    $.each($rules, function ($key, $rule) {
        $field     = $rule['field'];
        $operator  = $rule['con'];
        $value     = $rule['val'];
        $field_val = cfas_get_conditional_field_value($field);

        if( $field_val !== null ) {
            $is_valid  = cfas_check_rule_validation($operator, $value, $field_val);
        } else {
            $is_valid = true;
        }

        if($if === 'all'){
            if(!$is_valid){
                $is_valid = false;
                return false;
            }
        }else if($if === 'any'){
            if($is_valid){
                $is_valid = true;
                return false;
            }
        }

    });
    return $is_valid;
}

function cfas_get_conditional_field_value($field){
    var $is_exist = $(document).find('[name^="'+$field+'"]').length;
    var $input_type,$value;
    if( $is_exist ) {
        $input_type = $(document).find('[name^="'+$field+'"]')[0].type;
    } else {
        return null;
    }

    switch ($input_type) {
        case 'radio':
            $value = $(document).find('[name^="'+$field+'"]:checked').val();
            break;
        case 'checkbox':
            $value = [];
            var checkbox = $(document).find('[name^="'+$field+'"]');
            $.each( checkbox , function(){
                if ( $(this)[0].checked ) {
                    $value.push( $(this).val() );
                }
            });
            break;
        default:
            $value = $(document).find('[name^="'+$field+'"]').val();
    }

    return $value;
}

function cfas_check_rule_validation($operator, $value, $field_val){
    var $is_valid = false;
    var $is_array = $.isArray( $field_val );
    var $use_pro  = use_pro_cfas();

    switch ($operator) {
        case '==':
            if( $is_array ) {
                if( $.inArray( $value, $field_val ) > -1 ) {
                    $is_valid = true;
                }
            } else {
                if( $field_val === $value ) {
                    $is_valid = true;
                }
            }
            break;

        case '!=':
            if( $is_array ) {
                if( $.inArray( $value, $field_val ) < 0 ) {
                    $is_valid = true;
                }
            } else {
                if($field_val !== $value) {
                    $is_valid = true;
                }
            }
            break;

        default :
            if( $use_pro ) {
                $is_valid = cfasp_check_rule_validation( $operator, $value, $field_val );
            }
            break;
    }

    return $is_valid;
}

function cfas_show_conditional_field( $form_id, $con_field){
    show_conditional_field_error( $form_id, $con_field );
	$(document).find('.wpcf7[id^="wpcf7-f'+$form_id+'"]').find('.'+$con_field).slideDown(200)
}

function cfas_hide_conditional_field( $form_id, $con_field){
    hide_conditional_field_error( $form_id, $con_field );
	$(document).find('.wpcf7[id^="wpcf7-f'+$form_id+'"]').find('.'+$con_field).slideUp(200) 
}

function show_conditional_field_error( $form_id, $con_field ) {
	$(document).find('.wpcf7[id^="wpcf7-f'+$form_id+'"]').find('.'+$con_field).find('.wpcf7-not-valid-tip').removeClass('cfas-none');
}

function hide_conditional_field_error( $form_id, $con_field ) {
	$(document).find('.wpcf7[id^="wpcf7-f'+$form_id+'"]').find('.'+$con_field).find('.wpcf7-not-valid-tip').addClass('cfas-none'); 
}

$(document).on( 'wpcf7submit', '.wpcf7', function (e) {
    setTimeout(function () {
        var $form_id = e.detail.contactFormId;
        $(document).trigger( 'cfas_conditional', [$(this), $form_id] );
    }, 10)
});