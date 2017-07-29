<?php
/**
 * MY ACCOUNT ADMIN ENDPOINTS
 */
if ( ! defined( 'YITH_WCMAP' ) ) {
    exit;
} // Exit if accessed directly

$icon_list = YIT_Plugin_Common::get_icon_list();
$usr_roles = yith_wcmap_get_editable_roles();

?>

<?php do_action( 'yith_wcmap_before_manage_endpoint' ); ?>

<tr valign="top">
    <td class="forminp <?php echo $value['id'] ?>_container">

        <div class="section-title-container">
            <h3 class="section-title"><?php _e( 'Manage Endpoints', 'yith-woocommerce-customize-myaccount-page' ); ?></h3>
            <div class="button-container">
                <button type="button" class="button add_new_field" data-target="group"><?php echo 'Add group' ?></button>
                <button type="button" class="button add_new_field" data-target="endpoint"><?php echo 'Add endpoint' ?></button>
            </div>
        </div>

        <div class="dd endpoints-container">
            <ol class="dd-list endpoints">
                <!-- Endpoints -->
                <?php foreach ( $endpoints as $key => $options ) {
                    // build args array
                    $args = array(
                        'endpoint'  => $key,
                        'options'   => $options,
                        'id'        => $value['id'],
                        'icon_list' => $icon_list,
                        'usr_roles'  => $usr_roles
                    );

                    if( isset( $options['children'] ) ) {
                        // print endpoints group
                        yith_wcmap_admin_print_endpoints_group( $args );
                    }
                    else {
                        // print single endpoint field.
                        yith_wcmap_admin_print_endpoint_field( $args );
                    }
                } ?>
            </ol>
        </div>

        <div class="new-field-form" style="display: none;">
            <label for="yith-wcmap-new-field"><?php _ex( 'Name', 'Label for new endpoint title',
                    'yith-woocommerce-customize-myaccount-page' ); ?>
                <input type="text" id="yith-wcmap-new-field" name="yith-wcmap-new-field" value="">
            </label>
            <div class="loader"></div>
            <p class="error-msg"></p>
        </div>

        <input type="hidden" class="endpoints-order" name="<?php echo $value['id'] ?>" value="" />
        <input type="hidden" class="endpoint-to-remove" name="<?php echo $value['id'] ?>_to_remove" value="" />
    </td>
</tr>

<?php do_action( 'yith_wcmap_after_manage_endpoint' ); ?>