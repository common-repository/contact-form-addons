
jQuery(document).ready(function () {
    cfas_make_pages_footer();
	cfas_remove_ajax_loder();
	cfas_remove_br_tags();
});


/**
 * Add CF7 page footer for each page.
 */
function cfas_make_pages_footer(){
    $(document).find('.cfas_page_body').each(function(){
        var form_id = get_form_id(this);
        $(this).find('.cfas_page').each(function () {
            var page_num  = get_current_page_num (this, form_id);
            var animation = get_multi_step_animation(form_id);
            cfas_add_page_footer(form_id, page_num, animation);
        })
    })
}

/**
 * Remove CF7 default loader.
 */
function cfas_remove_ajax_loder(){
    // Use delay to ensure that ajax-loader was created.
    setTimeout(function(){
        $(document).find('.cfas_page_body').each(function(){
            $(this).find('span.ajax-loader').remove();
        })
    },10)
}

/**
 * Remove <br> form start and end of pages and group container.
 */
function cfas_remove_br_tags(){
    // Use delay to ensure that cfas_remove_ajax_loder function was executed.
    setTimeout(function(){
        // Remove <br> tag from multi-page
        $(document).find('.cfas_page_content').each(function(){
            first_tag_name = cfas_get_tag_name( this, 'first' )
            last_tag_name  = cfas_get_tag_name( this, 'last' )
            if( 'br' === first_tag_name ) {
                jQuery(this).children().first().remove()
            }
            if( 'br' === last_tag_name ){
                jQuery(this).children().last().remove()
            }
        })

        // Remove <br> tag from conditional group
        $(document).find('.cfas_group_con').each(function(){
            first_tag_name = cfas_get_tag_name( this, 'first' )
            last_tag_name  = cfas_get_tag_name( this, 'last' )
            if( 'br' === first_tag_name ) {
                jQuery(this).children().first().remove()
            }
            if( 'br' === last_tag_name ){
                jQuery(this).children().last().remove()
            }
        })
    },12);
}

/**
 * Get child tag name.
 */
function cfas_get_tag_name( elm, type ){	
	var tag_name;
	
	if( 'first' === type ){ 
		tag_name = jQuery(elm).children().first().prop('tagName');
		return tag_name ? tag_name.toLowerCase() : false;
	}
	
	tag_name = jQuery(elm).children().last().prop('tagName');
	return tag_name ? tag_name.toLowerCase() : false;
	
}

/**
 * Get the current form ID.
 *
 * @param  {object} elm The main pag body element.
 * @return {number}
 */
function get_form_id(elm){
    var form_id = $(elm).attr('id').replace('cfas_page_body_', '');
    return parseInt(form_id);
}


/**
 * Get the current form page number.
 *
 * @param  {object}  elm     The main pag body element.
 * @param  {number}  form_id The current form ID.
 * @param  {boolean} is_page Is elm page selector or not.
 * @return {number}
 */
function get_current_page_num( elm = null, form_id, is_page = true ){
    var page_num = null;
    if ( elm ) {
        if ( is_page ) {
            page_num = $(elm).attr('id').replace('cfas_page_'+form_id+'_', ' ');
        } else {
            page_num = $(elm).parents('.cfas_page').attr('id').replace('cfas_page_'+form_id+'_', ' ')
        }
    } else {
        $(document).find('#cfas_page_body_'+form_id+' .cfas_page').each( function () {
            var display = $(this).css('display');
            if ( display !== 'none' ) {
                page_num = $(this).attr('id').replace('cfas_page_'+form_id+'_', ' ');
                return false;
            }
        })
    }

    return parseInt(page_num);
}

function get_current_page_num1( form_id ){
	var page_num = $(document).find('#cfas_current_page_'+form_id).val();

    return parseInt(page_num);
}

/**
 * Set the current form page number.
 *
 * @param  {number} form_id The current form ID.
 * @param  {number} target  new current page number.
 * @return {number}
 */
function set_current_page_number( form_id, target ) {
	$(document).find('#cfas_current_page_'+form_id).val( target );
}


/**
 * Make CF7 page footer with content.
 *
 * @param {number} form_id   The current form ID.
 * @param {number} page_num  The current page number.
 * @param {string} animation Multi step transition animation name.
 */
function cfas_add_page_footer(form_id, page_num, animation){
    var is_first_page = page_num === 1;
    var is_last_page  = cfas_is_last_page(form_id, page_num);
    var nxt_btn_name  = cfas_get_page_btn_name( form_id, 'nxt-btn', 'nxt' );
    var prv_btn_name  = cfas_get_page_btn_name( form_id, 'prv-btn', 'prv' );
    var nxt_btn_html  = '<input type="button" class="cfas_btn cfas_nxt_btn" id="cfas_nxt_'+form_id+'_'+page_num+'" value="'+nxt_btn_name+'" onclick="cfas_page_transition(\''+animation+'\', '+form_id+', 1)">';
    var prv_btn_html  = '<input type="button" class="cfas_btn cfas_prev_btn" id="cfas_prv_'+form_id+'_'+page_num+'" value="'+prv_btn_name+'" onclick="cfas_page_transition(\''+animation+'\', '+form_id+', -1)">';
    var page_footer   = '<div class="cfas_page_footer" id="cfas_footer_'+form_id+'_'+page_num+'"></div>';

    $(document).find('#cfas_page_'+form_id+'_'+page_num).append(page_footer);
    if(is_first_page){
        $(document).find('#cfas_footer_'+form_id+'_'+page_num).append(nxt_btn_html);
    }else if(is_last_page){
        var submit_btn = $(document).find('#cfas_page_'+form_id+'_'+page_num).find('.wpcf7-submit').clone();
        $(document).find('#cfas_page_'+form_id+'_'+page_num).find('.wpcf7-submit').remove();
        $(document).find('#cfas_footer_'+form_id+'_'+page_num).append(prv_btn_html);
        $(document).find('#cfas_footer_'+form_id+'_'+page_num).append(submit_btn);
    }else {
        $(document).find('#cfas_footer_'+form_id+'_'+page_num).append(prv_btn_html+nxt_btn_html);
    }
    $(document).trigger('cfas_create_page', [form_id,page_num,animation,is_last_page] );


}

/**
 * Get the multi-step footers button name.
 *
 * @param {number} form_id   The current form ID.
 * @param {string} name      Button key name in page setting.
 * @param {string} translate Button key name in translate setting.
 *
 * @return {string}
 */
function cfas_get_page_btn_name( form_id, name , translate ) {
    return window['cfas'][form_id]['page'] && window['cfas'][form_id]['page'][name] ? window['cfas'][form_id]['page'][name] : window['cfas']['translate'][translate];
}

/**
 * If the current page be the last page return true.
 *
 * @param {number} form_id  The current form ID.
 * @param {number} page_num The current page number.
 *
 * @return {boolean}
 */
function cfas_is_last_page(form_id, page_num){
    var page_length = cfas_get_last_page_num(form_id);
    return page_length === page_num;
}

/**
 * Handle the multi step page transition.
 *
 * @param {string} animation  The name of multi step page transition animation.
 * @param {number} form_id   The current form ID.
 * @param {number} curr_page The current page number.
 * @param {number} direction The direction of multi step page transition(1 is next, -1 is previous and 0 is skip).
 */
function cfas_page_transition( animation, form_id, direction ){

	var curr_page          = get_current_page_num1( form_id )
    var target             = cfas_get_transition_target(form_id, curr_page, direction);
    var validate_on_submit = submit_on_last_page( form_id );
    var validate_on_next   = ! validate_on_submit;
	var is_button_disable  = cfas_is_button_disable( form_id );
	
	// If multi-step transition is playing return.
	if( is_button_disable ) {
		return;
	}
	
	cfas_disable_page_buttons( form_id );
	cfas_empty_cf7_response_output( form_id );
	
    if ( target ) {
		if( direction >= 0 ) {
            if ( validate_on_next ) {
                $(document).find('#cfas_btn_type_'+form_id).val('next');
                $(document).find('#cfas_target_page_'+form_id).val(target);
                $(document).find('#cfas_page_body_'+form_id).parents('form.wpcf7-form').find('input.wpcf7-submit[type="submit"]').click();
            } else {
				set_current_page_number( form_id, target );
				cfas_handle_page_transition( animation, form_id, curr_page, target, direction );
            }
        } else {
			set_current_page_number( form_id, target );
            cfas_handle_page_transition( animation, form_id, curr_page, target, direction );
        }
    } else {
        $(document).find('#cfas_btn_type_'+form_id).val('submit');
        $(document).find('#cfas_page_body_'+form_id).parents('form.wpcf7-form').find('input.wpcf7-submit[type="submit"]').click();
    }
	
	
	
}

/**
 * Check that multi-step buttons is disable.
 *
 * @param {number} form_id The current form ID.
 *
 * @return {boolean} 
 */
function cfas_is_button_disable( form_id ) {
	return $(document).find('#cfas_page_body_'+form_id+' .cfas_btn').hasClass('cfas_disabled')
}

$(document).on( 'wpcf7submit', '.wpcf7', function (e) {
    var form_id  = e.detail.contactFormId;
    var success   = e.detail.apiResponse.success;
    var btn_type  = e.detail.apiResponse.clicked;
    var target    = e.detail.apiResponse.target;
    var animation = get_page_animation( form_id );
    var curr_page = get_current_page_num( '', form_id, true);

    if( success ) {
        if ( btn_type === 'next' ) {
			cfas_handle_page_transition( animation, form_id, curr_page, target, 1 );
            $('#cfas_page_body_'+form_id).parents('.wpcf7').find('.wpcf7-response-output').addClass('cfas-none');
        } else if ( 'submit' === btn_type ) {
		 
			cfas_show_cf7_response_output( form_id );
			
            if( curr_page > 1 ) {
				cfas_handle_page_transition( animation, form_id, curr_page, 1, -1 );
            }

        }
    } else {
        if ( btn_type === 'submit' && submit_on_last_page( form_id ) ) {
			
			cfas_show_cf7_response_output( form_id );
			
            var error_page = get_current_page_num( e.detail.apiResponse.invalid_fields[0]['into'], form_id, false );
            if( error_page < curr_page ) {
				set_current_page_number( form_id, error_page );
				cfas_handle_page_transition( animation, form_id, curr_page, error_page, -1 );
            }
        }
		cfas_enable_page_buttons( form_id );
        $('#cfas_page_body_'+form_id).parents('.wpcf7').find('.wpcf7-response-output').removeClass('cfas-none');
    }
    $(document).find('#cfas_btn_type_'+form_id).val('submit');

});

/**
 * Executes the transition of multi-page form pages.
 *
 * @param {string} animation  The name of multi step page transition animation.
 * @param {number} form_id    The current form ID.
 * @param {number} curr_page  The current page number.
 * @param {number} target     The target page number.
 * @param {number} direction  The direction of multi step page transition(1 is next, -1 is previous and 0 is skip).
 *
 * @return {boolean} 
 */
function cfas_handle_page_transition( animation, form_id, curr_page, target, direction ) {
	set_current_page_number( form_id, target );
			
	if ( animation === 'none' ) {
		$(document).find('#cfas_page_'+form_id+'_'+curr_page).css('display', 'none');
		$(document).find('#cfas_page_'+form_id+'_'+target).css('display', '');
		cfas_call_transition_trigger(form_id)
	} else {
		var duration = cfas[form_id]['page'] && cfas[form_id]['page']['time'] ? cfas[form_id]['page']['time'] : 0;
		eval('cfas_'+animation+'_animation('+form_id+', '+curr_page+', '+direction+', '+duration+', '+target+')')
	}
}

/**
 * Empty and hide cf7 response output div element.
 *
 * @param {number} form_id The current form ID.
 */
function cfas_empty_cf7_response_output( form_id ) {
	$('#cfas_page_body_'+form_id).parents('.wpcf7').find('.wpcf7-response-output').empty();
	$('#cfas_page_body_'+form_id).parents('.wpcf7').find('.wpcf7-response-output').css('display', 'none');
	$('#cfas_page_body_'+form_id).parents('.wpcf7').find('.wpcf7-response-output').removeClass('cfas-none');
}

/**
 * Show cf7 response output div element.
 *
 * @param {number} form_id The current form ID.
 */
function cfas_show_cf7_response_output( form_id ){
	$('#cfas_page_body_'+form_id).parents('.wpcf7').find('.wpcf7-response-output').removeClass('cfas-none');
	$('#cfas_page_body_'+form_id).parents('.wpcf7').find('.wpcf7-response-output').css('display', '');
}
	
function submit_on_last_page ( form_id ) {
    return cfas[form_id]['page'] && cfas[form_id]['page']['sub_validate'] ? cfas[form_id]['page']['sub_validate'] : false;
}

function get_page_animation( form_id ) {
    return cfas[form_id]['page'] && cfas[form_id]['page']['animation'] ? cfas[form_id]['page']['animation'] : 'none';
}
/**
 * Get the number of last page.
 *
 * @param {number} form_id The current form ID.
 * @return {number}
 */
function cfas_get_last_page_num(form_id) {
    return $(document).find('#cfas_page_body_'+form_id+' .cfas_page').length;
}

/**
 * Get the name of multi step page transition name.
 *
 * @param  {number} form_id The current form ID.
 * @return {string}
 */
function get_multi_step_animation(form_id){
    var animation, use_pro;
    use_pro   = use_pro_cfas();
    animation = cfas[form_id]['page'] && cfas[form_id]['page']['animation'] ? cfas[form_id]['page']['animation'] : 'none';

    if ( animation === 'fade' ) {
        animation = 'fade';
    } else if ( ! use_pro ) {
        animation = 'none';
    }

    return animation;
}

/**
 * Call multi-step transition trigger.
 *
 * @param {number} form_id The current form ID.
 */
function cfas_call_transition_trigger(form_id) {
    $(document).trigger('cfas_after_animation', form_id);
}

/**
 * Enable or disable multi-step form buttons when footer buttons clicked.
 * @param {number}  form_id    The current form ID.
 * @param {boolean} is_disable Make footer buttons disable or enable.
 */
function cfas_make_page_button_disable_enable(form_id, is_disable){
    if(is_disable){
        $(document).find('#cfas_page_body_'+form_id+' input.cfas_btn').each(function () {
            $(this).prop('disabled', true)
        });
        $(document).find('#cfas_page_body_'+form_id+' .wpcf7-submit').prop('disabled', true);
    }else{
        $(document).find('#cfas_page_body_'+form_id+' input.cfas_btn').each(function () {
            $(this).prop('disabled', false)
        });
        $(document).find('#cfas_page_body_'+form_id+' .wpcf7-submit').prop('disabled', false);
    }
}

/**
 * Get current page animation class name
 *
 * @param {number} form_id        The current form ID.
 * @param {string} animation_name Name of multi-step transition animation.
 * @param {number} direction      Which button clicked? next,previous or skip.
 * @return {string}
 */
function cfas_get_current_page_animation_class(form_id, animation_name, direction) {
    var class_name, use_pro, is_rtl;
    var reverse = false;
    var next    = 'next';
    var prev    = 'prev';

    is_rtl  = cfas_is_site_rtl();
    use_pro = use_pro_cfas();

    if ( is_rtl ) {
        reverse = !reverse;
    }
    if ( use_pro ){
        reverse = cfasp_reverse_statuse( form_id, reverse );
    }
    if ( reverse ) {
        next = 'prev';
        prev = 'next';
    }

    if(direction >= 0){
        class_name = animation_name+'-'+next+'-go-animation';
    }else if(direction === -1){
        class_name = animation_name+'-'+prev+'-go-animation';
    }
    return class_name;
}

/**
 * Get target page animation class name
 *
 * @param {number} form_id        The current form ID.
 * @param {string} animation_name Name of multi-step transition animation.
 * @param {number} direction      Which button clicked? next,previous or skip.
 * @return {string}
 */
function cfas_get_target_page_animation_class(form_id, animation_name, direction) {
    var class_name, use_pro, is_rtl;
    var reverse = false;
    var next    = 'next';
    var prev    = 'prev';

    is_rtl  = cfas_is_site_rtl();
    use_pro = use_pro_cfas();

    if ( is_rtl ) {
        reverse = !reverse;
    }
    if ( use_pro ) {
        reverse = cfasp_reverse_statuse( form_id, reverse);
    }
    if ( reverse ) {
        next = 'prev';
        prev = 'next';
    }

    if(direction >= 0){
        // Next or skip button
        class_name = animation_name+'-'+next+'-come-animation';
    }else if(direction === -1){
        // Previous button
        class_name = animation_name+'-'+prev+'-come-animation';
    }
    return class_name;
}

/**
 * Is site direction rtl?
 *
 * @return {boolean}
 */
function cfas_is_site_rtl() {
    return cfas['is-rtl'] ? cfas['is-rtl'] : false;
}

function cfas_get_transition_target(form_id, curr_page, direction) {
    var target, use_pro;
    
    target  = curr_page;
    use_pro = use_pro_cfas();
    
    if( use_pro ) {
        target = cfasp_get_page_transition_target(form_id, curr_page, direction);
    } else {
		target += direction;
    }
    
    
    return target;
}

/**
 * Disable multi-step buttons in playing transition animation.
 */
function cfas_disable_page_buttons( form_id ) {
	$(document).find('#cfas_page_body_'+form_id).find('.cfas_btn').addClass('cfas_disabled');
	$(document).find('#cfas_page_body_'+form_id).find('.wpcf7-submit').addClass('cfas_disabled');
}

/**
 * Enable multi-step buttons when transition animation finished.
 */
function cfas_enable_page_buttons( form_id ) {
	$(document).find('#cfas_page_body_'+form_id).find('.cfas_btn').removeClass('cfas_disabled');
	$(document).find('#cfas_page_body_'+form_id).find('.wpcf7-submit').removeClass('cfas_disabled');
}

$(document).on('cfas_after_animation', function (e, form_id) {
    cfas_enable_page_buttons( form_id )
});
