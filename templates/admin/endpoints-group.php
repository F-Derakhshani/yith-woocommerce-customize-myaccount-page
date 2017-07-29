<?php
/**
 * MY ACCOUNT ENDPOINTS GROUP TEMPLATE
 */
if ( ! defined( 'YITH_WCMAP' ) ) {
    exit;
} // Exit if accessed directly

?>

<li class="dd-item endpoint group" data-id="<?php echo $endpoint ?>">

    <label class="on-off-endpoint" for="<?php echo $id . '_' . $endpoint ?>_active">
        <input type="checkbox" class="hide-show-check" name="<?php echo $id . '_' . $endpoint ?>[active]" id="<?php echo $id . '_' . $endpoint ?>_active" value="<?php echo $endpoint ?>" <?php checked( $options['active'] ) ?>/>
        <i class="fa fa-power-off"></i>
    </label>

    <div class="open-options field-type">
        <span><?php _e( 'Group', 'yith-woocommerce-customize-myaccount-page' ) ?></span>
        <i class="fa fa-chevron-down"></i>
    </div>

    <div class="dd-handle endpoint-content">

        <!-- Header -->
        <div class="endpoint-header">
            <?php echo $options['label'] ?>
        </div>

        <div class="endpoint-options" style="display: none;">

            <div class="options-row">
                <span class="hide-show-trigger"><?php echo $options['active'] ? __( 'Hide', 'yith-woocommerce-customize-myaccount-page') : __( 'Show', 'yith-woocommerce-customize-myaccount-page' ); ?></span>
                <span class="sep">|</span>
                <span class="remove-trigger" data-endpoint="<?php echo $endpoint ?>"><?php _e( 'Remove', 'yith-woocommerce-customize-myaccount-page'); ?></span>
            </div>

            <table class="options-table form-table">
                <tbody>

                <tr>
                    <th>
                        <label for="<?php echo $id . '_' . $endpoint ?>_label"><?php echo __( 'Group label', 'yith-woocommerce-customize-myaccount-page' ); ?></label>
                        <img class="help_tip" data-tip='<?php _e( 'Menu item for this endpoint in "My Account".',
                            'yith-woocommerce-customize-myaccount-page' ) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </th>
                    <td>
                        <input type="text" name="<?php echo $id . '_' . $endpoint ?>[label]" id="<?php echo $id . '_' . $endpoint ?>_label" value="<?php echo $options['label'] ?>">
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="<?php echo $id . '_' . $endpoint ?>_usr_roles"><?php echo __( 'User roles',
                                'yith-woocommerce-customize-myaccount-page' ); ?></label>
                        <img class="help_tip" data-tip='<?php _e( 'Restrict endpoint visibility to the following user role(s).',
                            'yith-woocommerce-customize-myaccount-page' ) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </th>
                    <td>
                        <select name="<?php echo $id . '_' . $endpoint ?>[usr_roles][]" id="<?php echo $id . '_' . $endpoint ?>_usr_roles" multiple="multiple">
                            <?php foreach( $usr_roles as $role => $role_name ) :
                                ! isset( $options['usr_roles'] ) && $options['usr_roles'] = array();
                                ?>
                                <option value="<?php echo $role ?>" <?php selected( in_array( $role, (array) $options['usr_roles'] ), true ); ?>><?php echo $role_name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="<?php echo $id . '_' . $endpoint ?>_open"><?php echo __( 'Show open', 'yith-woocommerce-customize-myaccount-page' ); ?></label>
                        <img class="help_tip" data-tip='<?php _e( 'Show the group open by default. (Please note: this option is valid only for "Sidebar" style)', 'yith-woocommerce-customize-myaccount-page' ) ?>'
                             src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </th>
                    <td>
                        <input type="checkbox" name="<?php echo $id . '_' . $endpoint ?>[open]" id="<?php echo $id . '_' . $endpoint ?>_open" value="yes" <?php checked( $options['open'] ) ?>>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>

    </div>

    <?php if( ! empty( $options['children'] ) ) : ?>
        <ol class="dd-list endpoints">
        <?php foreach ( (array) $options['children'] as $key => $single_options ) {
            $args = array(
                'endpoint'  => $key,
                'options'   => $single_options,
                'id'        => $id,
                'icon_list' => $icon_list,
                'usr_roles'  => $usr_roles
            );

            // print single endpoint field.
            yith_wcmap_admin_print_endpoint_field( $args );
        } ?>
        </ol>
    <?php endif; ?>
</li>