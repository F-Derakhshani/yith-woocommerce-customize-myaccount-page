<?php
/**
 * MY ACCOUNT ENDPOINT FIELDS
 */
if ( ! defined( 'YITH_WCMAP' ) ) {
    exit;
} // Exit if accessed directly

$editor_args = array(
    'wpautop'       => true, // use wpautop?
    'media_buttons' => true, // show insert/upload button(s)
    'textarea_name' => $id . '_' . $endpoint . '[content]', // set the textarea name to something different, square brackets [] can be used here
    'textarea_rows' => 15, // rows="..."
    'tabindex'      => '',
    'editor_css'    => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
    'editor_class'  => '', // add extra class(es) to the editor textarea
    'teeny'         => false, // output the minimal editor config used in Press This
    'dfw'           => false, // replace the default fullscreen with DFW (needs specific DOM elements and css)
    'tinymce'       => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
    'quicktags'     => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
);

$default_endpoints = yith_wcmap_get_default_endpoints_keys();

?>

<li class="dd-item endpoint" data-id="<?php echo $endpoint ?>">

    <label class="on-off-endpoint" for="<?php echo $id . '_' . $endpoint ?>_active">
        <input type="checkbox" class="hide-show-check" name="<?php echo $id . '_' . $endpoint ?>[active]" id="<?php echo $id . '_' . $endpoint ?>_active" value="<?php echo $endpoint ?>" <?php checked( $options['active'] ) ?>/>
        <i class="fa fa-power-off"></i>
    </label>

    <div class="open-options field-type">
        <span><?php _e( 'Endpoint', 'yith-woocommerce-customize-myaccount-page' ) ?></span>
        <i class="fa fa-chevron-down"></i>
    </div>

    <div class="dd-handle endpoint-content">

        <!-- Header -->
        <div class="endpoint-header">
            <?php echo $options['label'] ?>
            <span class="sub-item-label"><i><?php _e( 'sub item', 'yith-woocommerce-customize-myaccount-page' ); ?></i></span>
        </div>

        <!-- Content -->
        <div class="endpoint-options" style="display: none;">

            <div class="options-row">
                <span class="hide-show-trigger"><?php echo $options['active'] ? __( 'Hide', 'yith-woocommerce-customize-myaccount-page') : __( 'Show', 'yith-woocommerce-customize-myaccount-page' ); ?></span>
                <?php if( ! in_array( $endpoint, $default_endpoints ) && ! yith_wcmap_is_plugin_endpoint( $endpoint ) ) : ?>
                    <span class="sep">|</span>
                    <span class="remove-trigger" data-endpoint="<?php echo $endpoint ?>"><?php _e( 'Remove', 'yith-woocommerce-customize-myaccount-page'); ?></span>
                <?php endif; ?>
            </div>

            <table class="options-table form-table">
            <tbody>

                <?php if( $endpoint != 'dashboard' ) : ?>
                <tr>
                    <th>
                        <label for="<?php echo $id . '_' . $endpoint ?>_slug"><?php echo __( 'Endpoint slug', 'yith-woocommerce-customize-myaccount-page' ); ?></label>
                        <img class="help_tip" data-tip='<?php _e( 'Text appended to your page URLs to manage new contents in account pages. It must be unique for every page.', 'yith-woocommerce-customize-myaccount-page' ) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </th>
                    <td>
                        <input type="text" name="<?php echo $id . '_' . $endpoint ?>[slug]" id="<?php echo $id . '_' . $endpoint ?>_slug" value="<?php echo $options['slug'] ?>">
                    </td>
                </tr>
                <?php endif; ?>

                <tr>
                    <th>
                        <label for="<?php echo $id . '_' . $endpoint ?>_label"><?php echo __( 'Endpoint label', 'yith-woocommerce-customize-myaccount-page' ); ?></label>
                        <img class="help_tip" data-tip='<?php _e( 'Menu item for this endpoint in "My Account".',
                            'yith-woocommerce-customize-myaccount-page' ) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </th>
                    <td>
                        <input type="text" name="<?php echo $id . '_' . $endpoint ?>[label]" id="<?php echo $id . '_' . $endpoint ?>_label" value="<?php echo $options['label'] ?>">
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="<?php echo $id . '_' . $endpoint ?>_icon"><?php echo __( 'Endpoint icon', 'yith-woocommerce-customize-myaccount-page' ); ?></label>
                        <img class="help_tip" data-tip='<?php _e( 'Endpoint icon for "My Account" menu option', 'yith-woocommerce-customize-myaccount-page' ) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </th>
                    <td>
                        <select name="<?php echo $id . '_' . $endpoint ?>[icon]" id="<?php echo $id . '_' . $endpoint ?>_label" class="icon-select">
                            <option value=""><?php _e( 'No icon', 'yith-woocommerce-customize-myaccount-page' ) ?></option>
                            <?php foreach( $icon_list['FontAwesome'] as $icon => $label ) : ?>
                                <option value="<?php echo $label ?>" <?php selected( $options['icon'], $label ); ?>><?php echo $label ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <label><?php echo __( 'Endpoint custom content', 'yith-woocommerce-customize-myaccount-page' ); ?></label>
                        <img class="help_tip" data-tip='<?php _e( 'Custom endpoint content. Leave it black to use default content.',
                            'yith-woocommerce-customize-myaccount-page' ) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </th>
                    <td>
                        <div class="editor"><?php wp_editor( stripslashes( $options['content'] ), $id . '_' . $endpoint . '_content', $editor_args ); ?></div>
                    </td>
                </tr>

            </tbody>
        </table>
        </div>

    </div>
</li>