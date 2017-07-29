<?php
/**
 * MY ACCOUNT TEMPLATE MENU ITEM
 *
 * @since 2.0.0
 */
if ( ! defined( 'YITH_WCMAP' ) ) {
    exit;
} // Exit if accessed directly

?>

<li class="<?php echo implode( ' ', $classes ) ?>">

    <a href="#" class="group-opener">
        <?php echo $options['label'] ?>
        <i class="fa <?php echo $class_icon ?>"></i>
    </a>

    <ul class="myaccount-submenu" <?php echo $options['open'] ? '' : 'style="display:none"'; ?>>
        <?php foreach( $options['children'] as $child => $child_options ) {
            /**
             * Print single endpoint
             */
            do_action('yith_wcmap_print_single_endpoint', $child, $child_options );
        } ?>
    </ul>
</li>