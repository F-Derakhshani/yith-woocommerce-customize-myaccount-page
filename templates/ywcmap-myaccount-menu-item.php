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
    <a class="<?php echo $endpoint ?>" href="<?php echo esc_url( $url ) ?>" title="<?php echo esc_attr( $options['label'] ) ?>">
        <?php if( ! empty( $options['icon'] ) ) :
            // prevent double fa-
            $icon = strpos( $options['icon'], 'fa-' ) === false ? 'fa-' . $options['icon'] : $options['icon']; ?>
            <i class="fa <?php echo $icon; ?>"></i>
        <?php endif; ?>
        <span><?php echo $options['label'] ?></span>
    </a>
</li>