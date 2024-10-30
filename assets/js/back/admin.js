var $ = jQuery;

function cfas_fade_in_element(parent, find){
    var elm = $(parent).find(find+'.cfas_added_new');
    $(elm).removeClass( 'cfas_added_new' );
    $(elm).fadeIn( 400 );
}

function cfas_fade_out_remove( elm, callback ){
    $(elm).fadeOut( 350, function () {
        $(this).remove();
        eval(callback)
    });
}