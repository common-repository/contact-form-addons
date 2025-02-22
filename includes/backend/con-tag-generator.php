
<div class="control-box">
    <fieldset>
        <legend><?php echo sprintf( esc_html( $description ) ); ?></legend>

<table class="form-table">
    <tbody>

    <tr>
        <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
        <td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
    </tr>

    <tr>
        <th scope="row"><label for="clear_on_hide"><?php echo esc_html( __( 'Clear on hide', 'contact-form-7' ) ); ?></label></th>
        <td><input type="checkbox" name="clear_on_hide" class="option" id="clear_on_hide" /></td>
    </tr>

    <tr>
        <th scope="row"><label for="inline"><?php echo esc_html( __( 'Inline', 'contact-form-7' ) ); ?></label></th>
        <td><input type="checkbox" name="inline" class="option" id="inline" /></td>
    </tr>

    </tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
    <input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

    <div class="submitbox">
        <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
    </div>

    <br class="clear" />
</div>