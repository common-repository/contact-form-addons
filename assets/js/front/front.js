var $ = jQuery;

function cfas_calc_form_id( elm ){
    var form_id = $(elm).parents('.wpcf7').attr('id')
    form_id     = form_id.split('-')
    form_id     = form_id[1].replace( 'f', '' )
    return form_id
}

function use_pro_cfas(){
    return cfas['use-pro'];
}

$(document).on( 'change', '.wpcf7-form select', function () {
    var form_id = cfas_calc_form_id( $(this) );
    $(document).trigger( 'cfas_change', [$(this), form_id] );
    $(document).trigger( 'cfas_conditional', [$(this), form_id] );
});

$(document).on( 'change', '.wpcf7-form input[type=radio]', function () {
    var form_id = cfas_calc_form_id( $(this) );
    $(document).trigger( 'cfas_change', [$(this), form_id] );
    $(document).trigger( 'cfas_conditional', [$(this), form_id] );
});

$(document).on( 'change', '.wpcf7-form input[type=checkbox]', function () {
    var form_id = cfas_calc_form_id( $(this) );
    $(document).trigger( 'cfas_conditional', [$(this), form_id] );
});

$(document).on( 'keyup', '.wpcf7-form input[type=text], .wpcf7-form input[type=textarea]', function () {
    var form_id = cfas_calc_form_id( $(this) );
    $(document).trigger( 'cfas_conditional', [$(this), form_id] );
});

$(document).ready(function() {
    $(document).find('.wpcf7-form').each(function () {
        var form_id = cfas_calc_form_id( $(this) );
        $(document).trigger( 'cfas_conditional', [$(this), form_id] );
    })
});