<?php
/**
 * Main class
 *
 * @author Yithemes
 * @package YITH WooCommerce Customize My Account Page
 * @version 1.0.0
 */


if ( ! defined( 'YITH_WCMAP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCMAP' ) ) {
	/**
	 * YITH WooCommerce Customize My Account Page
	 *
	 * @since 1.0.0
	 */
	class YITH_WCMAP {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCMAP
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Plugin version
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public $version = YITH_WCMAP_VERSION;


		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCMAP
		 * @since 1.0.0
		 */
		public static function get_instance(){
			if( is_null( self::$instance ) ){
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @return mixed YITH_WCMAP_Admin | YITH_WCMAP_Frontend
		 * @since 1.0.0
		 */
		public function __construct() {

			// add endpoints
			add_action( 'init', array( $this, 'add_custom_endpoints' ), 5 );

			// rewrite rules
			add_action( 'init', array( $this, 'rewrite_rules' ), 20 );

			// Class admin
			if ( $this->is_admin() ) {
				// Load Plugin Framework
				add_action( 'after_setup_theme', array( $this, 'plugin_fw_loader' ), 1 );

				// require file
				require_once('class.yith-wcmap-admin.php');
				
				YITH_WCMAP_Admin();
			}
			// Class frontend
			else {
				// require file
				require_once('class.yith-wcmap-frontend.php');
				
				YITH_WCMAP_Frontend();
			}

			// filter user avatar
			add_filter( 'get_avatar', array( $this, 'get_avatar' ), 10, 6 );

			self::_update_old_option();
		}

		/**
		 * Update old endpoints option to the new format
		 *
		 * @since 2.0.0
		 * @author Francesco Licandro
		 * @access private
		 */
		private function _update_old_option(){

			if( get_option('yith_wcmap_old_option_updated', 0 ) ) {
				return;
			}

			$fields = get_option( 'yith_wcmap_endpoint', '' );

			if( ! $fields ) {
				// no update required
				update_option( 'yith_wcmap_old_option_updated', 1 );
				return;
			}

			$fields = explode( ',', $fields );
			$updated = array();

			foreach( $fields as $field ) {
				$updated[] = array( 'id' => $field );
			}

			// encode json
			$updated = json_encode( $updated );

			update_option( 'yith_wcmap_endpoint', $updated );
			update_option( 'yith_wcmap_old_option_updated', 1 );
		}

        /**
         * Check if is admin or not and load the correct class
         *
         * @since 1.1.2
         * @author Francesco Licandro
         * @return bool
         */
        public function is_admin(){

            $check_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
            $check_context = isset( $_REQUEST['context'] ) && $_REQUEST['context'] == 'frontend';

            return is_admin() && ! ( $check_ajax && $check_context );
        }

		/**
		 * Load Plugin Framework
		 *
		 * @since  1.0
		 * @access public
		 * @return void
		 * @author Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function plugin_fw_loader() {

			if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if( ! empty( $plugin_fw_data ) ){
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once( $plugin_fw_file );
				}
			}
		}

		/**
		 * Add custom endpoints to main WC array
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function add_custom_endpoints(){

			$keys = yith_wcmap_get_endpoints_slug();

			if( empty( $keys ) || ! is_array( $keys ) ) {
				return;
			}

			foreach( $keys as $key ) {
				if( $key == 'dashboard' || isset( WC()->query->query_vars[$key] ) ){
					continue;
				}

				WC()->query->query_vars[$key] = $key;
			}
		}

		/**
		 * Rewrite rules
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function rewrite_rules(){

			$do_flush = get_option( 'yith-wcmap-flush-rewrite-rules', 1 );

			if( $do_flush ) {
				// change option
				update_option( 'yith-wcmap-flush-rewrite-rules', 0 );
				// the flush rewrite rules
				flush_rewrite_rules();
			}

		}

		/**
		 * Get customer avatar for user
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function get_avatar( $avatar, $id_or_email, $size, $default, $alt, $args = array() ) {

			if( is_admin() ){
				return $avatar;
			}

			if ( empty( $args ) ) {
				$args['size'] = ( int ) $size;
				$args['height'] = $args['size'];
				$args['width'] = $args['size'];
				$args['alt']     = $alt;
				$args['extra_attr'] = '';
			}

			$user = false;

			if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
				$user = get_user_by( 'email', $id_or_email );
			}
			elseif ( $id_or_email instanceof WP_User ) {
				// User Object
				$user = $id_or_email;
			}
			elseif ( $id_or_email instanceof WP_Post ) {
				// Post Object
				$user = get_user_by( 'id', (int) $id_or_email->post_author );
			}
			elseif ( $id_or_email instanceof WP_Comment ) {

				if ( ! empty( $id_or_email->user_id ) ) {
					$user = get_user_by( 'id', (int) $id_or_email->user_id );
				}
				if ( ( ! $user || is_wp_error( $user ) ) && ! empty( $id_or_email->comment_author_email ) ) {
					$email = $id_or_email->comment_author_email;
					$user = get_user_by( 'email', $email );
				}
			}

			// get the user ID
			$user_id = ! $user ? $id_or_email : $user->ID;

			// get custom avatar
			$custom_avatar = get_user_meta( $user_id, 'yith-wcmap-avatar', true );

			if( ! $custom_avatar ){
				return $avatar;
			}

			// maybe resize img
			$resized = yith_wcmap_resize_avatar_url( $custom_avatar, $size );
			// if error occurred return
			if( ! $resized ) {
				return $avatar;
			}

			$src = yith_wcmap_generate_avatar_url( $custom_avatar, $size );
			$class = array( 'avatar', 'avatar-' . (int) $args['size'], 'photo' );

			$avatar = sprintf(
				"<img alt='%s' src='%s' class='%s' height='%d' width='%d' %s/>",
				esc_attr( $args['alt'] ),
				esc_url( $src ),
				esc_attr( join( ' ', $class ) ),
				(int) $args['height'],
				(int) $args['width'],
				$args['extra_attr']
			);

			return $avatar;
		}
	}
}

/**
 * Unique access to instance of YITH_WCMAP class
 *
 * @return \YITH_WCMAP
 * @since 1.0.0
 */
function YITH_WCMAP(){
	return YITH_WCMAP::get_instance();
}