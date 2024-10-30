function make_conditional_box() {
    var html =
        '<div class="cfas_rules_body cfas_added_new p_5"  style="display: none;">'+
        '    <div class="cfas_rule_top">'+
        '        <select class="cfas_con_type brd_r_2 brd_1 brd_b" onchange="cfas_set_conditional_rules()">'+
        '            <option value="show">Show</option>'+
        '            <option value="hide">Hide</option>'+
        '        </select>'+
        '        <span class="cfas_field_text txt_b m_r_5 m_l_5">Field</span>'+
                cfas_get_conditional_fields()+
        '        <span class="cfas_field_text txt_b m_r_5 m_l_5">If</span>'+
        '        <select class="cfas_if_type brd_r_2 brd_1 brd_b" onchange="cfas_set_conditional_rules()">'+
        '            <option value="all">All</option>'+
        '            <option value="any">Any</option>'+
        '        </select>'+
        '    </div>'+
        '    <div class="cfas_rules_container">'+
                make_conditional_rule( false )
        '    </div>'+
        '</div>';
    return html;
}


function make_conditional_rule( is_hidden = false ){
    var cls   = '';
    var style = '';
    if( is_hidden ){
        cls   = 'cfas_added_new';
        style = 'style="display:none;"';
    }
    var html =
        '<div class="con_rule brd_r_2 brd_1 brd_b p_5 d_flx j_cntr aln_cntr back_b m_tb_5 '+cls+'" '+style+'>'+
        '   <span class="cfas_field_text txt_b">Field</span>'+
            cfas_get_conditional_rule_fields() +
        '   <select  class="cfas_rule_con brd_r_2 brd_1 brd_b m_lr_5" onchange="cfas_set_conditional_rules()">'+
        '        <option value="==" selected="selected">is</option>'+
        '        <option value="!=">is not</option>'+
        '        <option value=">">greater than</option>'+
        '        <option value="<">less than</option>'+
        '        <option value="contains">contains</option>'+
        '        <option value="starts_with">starts with</option>'+
        '        <option value="ends_with">ends with</option>'+
        '   </select>'+
        '   <input type="text" class="cfas_rule_val" onkeyup="cfas_set_conditional_rules()">'+
        '   <div class="rule_btn d_flx aln_cntr">'+
        '       <span class="add_rule m_lr_10" onclick="add_conditional_rule(this);"></span>'+
        '       <span class="del_rule" onclick="del_conditional_rule(this);"></span>'+
        '   </div>'+
        '</div>';

    return html;
}


function add_con_rule_box() {
    var has_con_fields = cfas.con_rule && cfas.con_rule && cfas.con_rule!=='' ? true : false;
    if(!has_con_fields)
        return false;
    $(document).find('.cfas_con_body').append( make_conditional_box() );
    cfas_set_conditional_rules()
    cfas_fade_in_element('.cfas_con_body', '.cfas_rules_body');
}

function cfas_get_conditional_fields(){
    var con_fields = cfas && cfas.con_field ? cfas.con_field : '';
    var options    = '';
    var html;
    $.each( con_fields, function(field, name) {
        options += '<option value="'+name+'">'+field+'</option>';
    });
    html =
        '<select class="cfas_con_field brd_r_2 brd_1 brd_b m_lr_5" onchange="cfas_set_conditional_rules()">'+
            options +
        '</select>';

    return html;
}

function cfas_get_conditional_rule_fields() {
    var con_fields = cfas && cfas.con_rule ? cfas.con_rule : '';
    var html, options;
    $.each( con_fields, function( field ) {
        options += '<option value="'+field+'">'+field+'</option>';
    });
    html =
        '<select class="cfas_rule_field brd_r_2 brd_1 brd_b m_lr_5" onchange="cfas_set_conditional_rules()">'+
            options +
        '</select>';

    return html;
}

function add_conditional_rule( elm ){
    var has_conditional_fields = cfas.con_rule && cfas.con_rule && cfas.con_rule!=='' ? true : false;
    if(!has_conditional_fields)
        return false;
    $(elm).parents('.con_rule').after( make_conditional_rule( true ) );
    cfas_set_conditional_rules()
    cfas_fade_in_element('.cfas_con_body', '.con_rule');
}

function del_conditional_rule( elm ){
    var rule_number = cfas_get_con_rule_num(elm);
    var parent      = '';
    if ( rule_number > 1 ) {
        parent = '.con_rule';
    }else {
        parent = '.cfas_rules_body';
    }
    var target = $(elm).parents(parent);
    cfas_fade_out_remove(target, 'cfas_set_conditional_rules()');
}

function cfas_get_con_rule_num( elm ){
    return $(elm).parents('.cfas_rules_container').find('.con_rule').length;
}


function cfas_set_conditional_rules(){
    var conditional    = new Object();
    $(document).find('.cfas_con_body .cfas_rules_body').each( function (rule_key) {
        var con_type  = $(this).find('.cfas_con_type').val();
        var con_field = $(this).find('.cfas_con_field').val();
        var con_if    = $(this).find('.cfas_if_type').val();
        conditional[rule_key]          = new Object();
        conditional[rule_key]['type']  = con_type;
        conditional[rule_key]['field'] = con_field;
        conditional[rule_key]['if']    = con_if;
        conditional[rule_key]['rules'] = new Object();
        $(this).find('.con_rule').each(function(field_key){
            var rule_field = $(this).find('.cfas_rule_field').val();
            var con        = $(this).find('.cfas_rule_con').val();
            var val        = $(this).find('.cfas_rule_val').val();
            conditional[rule_key]['rules'][field_key]          = new Object();
            conditional[rule_key]['rules'][field_key]['field'] = rule_field;
            conditional[rule_key]['rules'][field_key]['con']   = con;
            conditional[rule_key]['rules'][field_key]['val']   = val;
        })
    });
    if( $.isEmptyObject( conditional ) ) {
        $(document).find('#cfas_conditional_rules').text( '' );
    } else {
        $(document).find('#cfas_conditional_rules').text( JSON.stringify(conditional) );
    }

}







$(document).ready(function () {
    $(document).find('.cfas_rules_container').each( function () {
        var html = $.trim($(this).html());
        if ( html === '' ) {
            $(this).parents('.cfas_rules_body').remove();
        }
    })
})

















if(typeof wpcf7 !== 'undefined'){
    var old_tag = wpcf7.taggen.compose;
    wpcf7.taggen.compose = function(tagType, $form)
    {
        var tag = old_tag.apply(this, arguments);
        if (tagType== 'group_con') tag += "[end_con]";

        return tag;
    };

}
