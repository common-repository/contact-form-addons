function cfas_fade_animation(form_id, current, direction, duration, target){
    var curr_height, target_height;

    if(duration === 0){
        duration = 300;
    }
    $(document).find('#cfas_page_body_'+form_id+'_'+target).css('display', '')

    curr_height   = $(document).find('#cfas_page_'+form_id+'_'+current).height()
    target_height = $(document).find('#cfas_page_'+form_id+'_'+target).height()

    jQuery("#cfas_page_body_"+form_id).css( 'height', curr_height)
    $(document).find('#cfas_page_'+form_id+'_'+target).css('display', 'none')



    if (curr_height >= target_height) {
        setTimeout(function () {
            jQuery("#cfas_page_body_"+form_id).css({ height: target_height,transition:"height "+duration*0.8+"ms ease-in-out" })
        },  duration)
    } else {
        setTimeout(function () {
            jQuery("#cfas_page_body_"+form_id).css({ height: target_height,transition:"height "+duration+"ms ease-in-out"})
        },1)
    }


    $(document).find('#cfas_page_'+form_id+'_'+current).addClass('fade-go-animation');
    $(document).find('#cfas_page_'+form_id+'_'+current).css('animation-duration', duration+'ms');




    setTimeout(function () {
        $(document).find('#cfas_page_'+form_id+'_'+target).css({display: '', position:'absolute', top:0, left:0, width:'100%'});
        $(document).find('#cfas_page_'+form_id+'_'+target).addClass('fade-come-animation');
        $(document).find('#cfas_page_'+form_id+'_'+target).css('animation-duration', duration+'ms');
        $(document).find('#cfas_page_'+form_id+'_'+current).css('display', 'none');
    },duration+10)

    setTimeout(function () {
        $(document).find('#cfas_page_'+form_id+'_'+current).removeAttr('style');
        $(document).find('#cfas_page_'+form_id+'_'+current).css('display', 'none');
        $(document).find('#cfas_page_'+form_id+'_'+current).removeClass('fade-go-animation');
        $(document).find('#cfas_page_'+form_id+'_'+target).removeClass('fade-come-animation');
        $(document).find('#cfas_page_'+form_id+'_'+target).removeAttr('style');
        $(document).find('#cfas_page_body_'+form_id).removeAttr('style');
        cfas_call_transition_trigger(form_id)
    },(duration*2) * 1.01)
}