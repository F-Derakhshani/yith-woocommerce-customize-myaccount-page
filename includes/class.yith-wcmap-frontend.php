<?php
/**
 * Frontend class
 *
 * @author Yithemes
 * @package YITH WooCommerce Customize My Account Page
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCMAP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCMAP_Frontend' ) ) {
	/**
	 * Frontend class.
	 * The class manage all the frontend behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCMAP_Frontend {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCMAP_Frontend
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
		 * Page templates
		 *
		 * @var string
		 * @since 1.0.0
		 */
		protected $_is_myaccount = false;

		/**
		 * Menu Shortcode
		 *
		 * @access protected
		 * @var string
		 */
		protected $_shortcode_name = 'yith-wcmap-menubar';

		/**
		 * My account endpoint
		 *
		 * @var string
		 * @since 1.0.0
		 */
		protected $_menu_endpoints = array();

		/**
		 * Action print avatar form
		 * 
		 * @since 2.2.0
		 * @var string
		 */
		public $action_print = 'ywcmap_print_avatar_form';

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCMAP_Frontend
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
		 * @access public
		 * @since 1.0.0
		 */
		public function __construct() {

			// plugin init
			add_action( 'init', array( $this, 'init' ) );

			// enqueue scripts and styles
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 15 );

			// check if is shortcode my-account
			add_action( 'template_redirect', array( $this, 'check_myaccount' ), 1 );
			// redirect to the default endpoint
			add_action( 'template_redirect', array( $this, 'redirect_to_default' ), 150 );
            // add account menu
            add_action( 'template_redirect', array( $this, 'add_account_menu' ), 151 );
            // add custom endpoints content
            add_action( 'template_redirect', array( $this, 'add_custom_endpoint_content' ), 152 );

			// shortcode for print my account sidebar
			add_shortcode( $this->_shortcode_name, array( $this, 'my_account_menu' ) );

            // add avatar
			add_action( 'init', array( $this, 'add_avatar' ) );

			// shortcodes for my-downloads and view order content
			add_shortcode( 'my_downloads_content', array( $this, 'my_downloads_content' ) );
			add_shortcode( 'view_order_content', array( $this, 'view_order_content' ) );

			// mem if is my account page
			add_action( 'shutdown', array( $this, 'save_is_my_account' ) );

            // reset default avatar
            add_action( 'init', array( $this, 'reset_default_avatar' ) );

			// AJAX Avatar
			add_action( 'wc_ajax_'.$this->action_print, array( $this, 'get_avatar_form_ajax' ) );
		}

		/**
		 * Init plugins variable
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function init() {

			$this->_menu_endpoints = yith_wcmap_get_endpoints();

            // get current user and set user role
            $current_user = wp_get_current_user();
            $user_role = (array) $current_user->roles;

			// first register string for translations then remove disable
			foreach( $this->_menu_endpoints as $endpoint => &$options ) {

			    // first register label for wpml
                $this->_register_string_wpml( $endpoint, $options['label'] );

                // check if master is active
                if( isset( $options['active'] ) && ! $options['active'] ){
                    unset( $this->_menu_endpoints[$endpoint] );
                    continue;
                }

                // check master by user roles
                if( isset( $options['usr_roles'] ) && $this->_hide_by_usr_roles( $options['usr_roles'], $user_role ) ) {
                    unset( $this->_menu_endpoints[$endpoint] );
                    continue;
                }

			    // check if child is active
                if( isset( $options['children'] ) ) {
                    foreach ( $options['children'] as $child_endpoint => $child_options ) {

                        // first register label for wpml
                        $this->_register_string_wpml( $child_endpoint, $child_options['label'] );

                        if( ! $child_options['active'] ){
                            unset( $options['children'][$child_endpoint] );
                            continue;
                        }
                        if( isset( $child_options['usr_roles'] ) && $this->_hide_by_usr_roles( $child_options['usr_roles'], $user_role ) ) {
                            unset( $options['children'][$child_endpoint] );
                            continue;
                        }

                        // get wpml translated label
                        $options['children'][$child_endpoint]['label'] = $this->_get_string_wpml( $child_endpoint, $child_options['label'] );
                    }
                }

                // get wpml translated label
                $options['label'] = $this->_get_string_wpml( $endpoint, $options['label'] );
			}

			// remove theme sidebar
			if( defined('YIT') && YIT ) {
				remove_action( 'yit_content_loop', 'yit_my_account_template', 5 );
				// also remove the my-account template
				$my_account_id = wc_get_page_id( 'myaccount' );
				if ( 'my-account.php' == get_post_meta( $my_account_id, '_wp_page_template', true ) ) {
					update_post_meta( $my_account_id, '_wp_page_template', 'default' );
				}
			}
		}

        /**
         * Register a WPML string
         *
         * @access protected
         * @since 2.0.0
         * @author Francesco Licandro
         * @param string $key
         * @param string $value
         */
        protected function _register_string_wpml( $key, $value ){
            do_action( 'wpml_register_single_string', 'yith-woocommerce-customize-myaccount-page', 'plugin_yit_wcmap_' . $key, $value );
        }

        /**
         * Get a WPML translated string
         *
         * @access protected
         * @since 2.0.0
         * @author Francesco Licandro
         * @param string $key
         * @param string $value
         * @return string
         */
        protected function _get_string_wpml( $key, $value ){
            $localized_label = apply_filters( 'wpml_translate_single_string', $value, 'yith-woocommerce-customize-myaccount-page', 'plugin_yit_wcmap_' . $key );
            if( $localized_label == $value ) {
                // search in old domain
                $localized_label = apply_filters( 'wpml_translate_single_string', $value, 'Plugin', 'plugin_yit_wcmap_' . $key );
            }

            return $localized_label;
        }

        /**
         * Hide field based on current user role
         *
         * @access protected
         * @since 2.0.0
         * @author Francesco Licandro
         * @param array $roles
         * @param array $current_user_role
         * @return boolean
         */
        protected function _hide_by_usr_roles( $roles, $current_user_role ){
            // return if $roles is empty
            if( empty( $roles ) || current_user_can( 'administrator' ) ) {
                return false;
            }

            // check if current user can
            $intersect = array_intersect( $roles, $current_user_role );
            if( ! empty( $intersect ) ){
                return false;
            }

            return true;
        }

        /**
		 * Enqueue scripts and styles
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function enqueue_scripts(){

			if( ! $this->_is_myaccount ){
				return;
			};

			$paths          = apply_filters( 'yith_wcmap_stylesheet_paths', array( WC()->template_path() . 'yith-customize-myaccount.css', 'yith-customize-myaccount.css' ) );
			$located        = locate_template( $paths, false, false );
			$search         = array( get_stylesheet_directory(), get_template_directory() );
			$replace        = array( get_stylesheet_directory_uri(), get_template_directory_uri() );
			$stylesheet     = ! empty( $located ) ? str_replace( $search, $replace, $located ) : YITH_WCMAP_ASSETS_URL . '/css/ywcmap-frontend.css';
            $suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';

			wp_register_style( 'ywcmap-frontend', $stylesheet );
            wp_register_script( 'ywcmap-frontend', YITH_WCMAP_ASSETS_URL . '/js/ywcmap-frontend'. $suffix . '.js', array( 'jquery' ), false, true );
			// font awesome
			wp_register_style( 'font-awesome', YITH_WCMAP_ASSETS_URL . '/css/font-awesome.min.css' );

			// ENQUEUE STYLE
			wp_enqueue_style( 'ywcmap-frontend' );
            wp_enqueue_style( 'font-awesome' );

			$inline_css = '
				#my-account-menu .logout a, #my-account-menu-tab .logout a {
					color:' . get_option('yith-wcmap-logout-color') . ';
					background-color:' . get_option('yith-wcmap-logout-background') . ';
				}
				#my-account-menu .logout:hover a, #my-account-menu-tab .logout:hover a {
					color:' . get_option('yith-wcmap-logout-color-hover') . ';
					background-color:' . get_option('yith-wcmap-logout-background-hover') . ';
				}
				.myaccount-menu li a {
					color:' . get_option( 'yith-wcmap-menu-item-color' ). ';
				}
				.myaccount-menu li a:hover, .myaccount-menu li.active > a {
					color:' . get_option( 'yith-wcmap-menu-item-color-hover' ). ';
				}';

			wp_add_inline_style( 'ywcmap-frontend', $inline_css );

			// ENQUEUE SCRIPTS
			wp_enqueue_script( 'ywcmap-frontend' );
			wp_localize_script( 'ywcmap-frontend', 'yith_wcmap', array(
				'ajaxurl'           => WC_AJAX::get_endpoint( "%%endpoint%%" ),
				'actionPrint'       => $this->action_print
			) );
		}

		/**
		 * Check if is page my-account and set class variable
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function check_myaccount() {

			global $post;

			if( ! is_null( $post ) && strpos( $post->post_content, '[woocommerce_my_account' ) !== false && is_user_logged_in() ) {
				$this->_is_myaccount = true;
			}
		}

		/**
		 * Redirect to default endpoint
		 *
		 * @access public
		 * @since 1.0.4
		 * @author Francesco Licandro
		 */
		public function redirect_to_default(){

			// exit if not my account
			if( ! $this->_is_myaccount || ! is_array( $this->_menu_endpoints ) ) {
				return;
			}

			$current_endpoint = yith_wcmap_get_current_endpoint();
			// if a specific endpoint is required return
            if( $current_endpoint != 'dashboard' || apply_filters( 'yith_wcmap_no_redirect_to_default', false ) ) {
                return;
            }

			$default_endpoint = get_option( 'yith-wcmap-default-endpoint', 'dashboard' );
			// let's third part filter default endpoint
			$default_endpoint = apply_filters( 'yith_wcmap_default_endpoint', $default_endpoint );
			$url = wc_get_page_permalink( 'myaccount' );

            // otherwise if I'm not in my account yet redirect to default
            if( ! get_option( 'yith_wcmap_is_my_account', true ) ) {
				$default_endpoint != 'dashboard' && $url = wc_get_endpoint_url( $default_endpoint, '', $url );
				wp_safe_redirect( $url );
				exit;
			}
		}

		/**
		 * Add custom endpoints content
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function add_custom_endpoint_content() {

			if( ! $this->_is_myaccount || empty( $this->_menu_endpoints ) ) {
				return;
			}

			global $wp, $post;

			// search for active endpoints
			$active = yith_wcmap_get_current_endpoint();
			// get active endpoint options by slug
			$endpoint = yith_wcmap_get_endpoint_by( $active, 'slug', $this->_menu_endpoints );

			if( empty( $endpoint ) || ! is_array( $endpoint ) ){
				return;
			}

			// get key
			$key = key( $endpoint );

			// set endpoint title
			if( isset( $endpoint['view-quote'] ) && ! empty( $wp->query_vars[$active] ) ) {
				$order_id           = $wp->query_vars[$active];
				$post->post_title   = sprintf( __( 'Quote #%s', 'yith-woocommerce-request-a-quote' ), $order_id );
			}
			elseif( ! empty( $endpoint[$key]['label'] ) && $active != 'dashboard' ) {
				$post->post_title = stripslashes( $endpoint[$key]['label'] );
			}

			// first check in custom content
			if( ! empty( $endpoint[$key]['content'] ) ) {
				$this->vc_compatibility( 'set', stripslashes( $endpoint[$key]['content'] ) );

				if( isset( $endpoint['my-wishlist'] ) ) {
					add_filter( 'yith_wcwl_current_wishlist_view_params', array( $this, 'change_wishlist_view_params' ), 10, 1 );
				}
			}
		}

		/**
		 * Change view params for wishlist shortcode
		 *
		 * @since 1.0.6
		 * @param $params
		 * @author Francesco Licandro
		 * @return mixed
		 */
		public function change_wishlist_view_params( $params ) {

			$endpoint = yith_wcmap_get_endpoint_by( 'my-wishlist', 'key', $this->_menu_endpoints );

			$params = get_query_var( $endpoint['my-wishlist']['slug'], false );

			return $params;
		}

		/**
		 * If is my account add menu to content
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function add_account_menu() {

			if( $this->_is_myaccount ) {

				// remove filter for plugin WooComposer
				remove_filter( 'the_content','ninja_get_template_myaccount_belongs');

				$post_content = $this->vc_compatibility('get');

				$position = get_option( 'yith-wcmap-menu-position', 'left' );
				$tab = get_option( 'yith-wcmap-menu-style', 'sidebar' ) == 'tab' ? '-tab' : '';
				$menu = '<div id="my-account-menu' . $tab . '" class="yith-wcmap position-' . $position .'">[' . $this->_shortcode_name . ']</div>';
				$post_content = '<div id="my-account-content" class="woocommerce woocommerce-MyAccount-content">' . $post_content . '</div>';

				$content = ( $position == 'right' && $tab == '' ) ? $post_content . $menu : $menu . $post_content;
				// set new post content
				$this->vc_compatibility( 'set', $content );
			}
		}

		/**
		 * Output my-account shortcode
		 *
		 * @since 1.0.0
		 * @author Frnacesco Licandro
		 */
		public function my_account_menu() {

			$args = apply_filters( 'yith-wcmap-myaccount-menu-template-args', array(
				'endpoints' => $this->_menu_endpoints,
				'my_account_url' => get_permalink( wc_get_page_id( 'myaccount' ) ),
				'avatar'	=> get_option( 'yith-wcmap-custom-avatar' ) == 'yes'
			));

			ob_start();

			wc_get_template( 'ywcmap-myaccount-menu.php', $args, '', YITH_WCMAP_DIR . 'templates/' );

			return ob_get_clean();

		}

		/**
		 * Add user avatar
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function add_avatar(){

			if( ! isset( $_FILES['ywcmap_user_avatar'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'wp_handle_upload' ) )
				return;

			// required file
			if ( ! function_exists( 'media_handle_upload' )  ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}
			if( ! function_exists( 'wp_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
			if( ! function_exists('wp_generate_attachment_metadata' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
			}

			$media_id = media_handle_upload( 'ywcmap_user_avatar', 0 );

			if( is_wp_error( $media_id ) ) {
				return;
			}

			// save media id for filter query in media library
			$medias = get_option('yith-wcmap-users-avatar-ids', array() );
			$medias[] = $media_id;
			// then save
			update_option( 'yith-wcmap-users-avatar-ids', $medias );


			// save user meta
			$user = get_current_user_id();
			update_user_meta( $user, 'yith-wcmap-avatar', $media_id );

		}

		/**
		 * Print my-downloads endpoint content
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function my_downloads_content( $atts ) {

			$content = '';
			$template_name = version_compare( WC()->version, '3.0', '>=' ) ? 'myaccount/downloads.php' : 'myaccount/my-downloads.php';
			$template = apply_filters( 'yith_wcmap_downloads_shortcode_template', $template_name );

			ob_start();
					wc_get_template( $template );
			$content = ob_get_clean();

			// print message if no downloads
			if( ! $content ){
				$content = '<p>' . __( 'There are no available downloads yet.', 'yith-woocommerce-customize-myaccount-page' ) . '</p>';
			}

			return $content;
		}

		/**
		 * Print view-order endpoint content, if view-order is not empty print order details
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function view_order_content( $atts ) {

			global $wp;

			$content = '';
			$endpoint = yith_wcmap_get_endpoint_by( 'view-order', 'key', $this->_menu_endpoints );

			if( empty( $endpoint ) ) {
				return '';
			}

			$slug = $endpoint['view-order']['slug'];

			if ( ! empty( $wp->query_vars[$slug] ) ) {

				$order_id = absint( $wp->query_vars[$slug] );
				$order    = wc_get_order( $order_id );

				if ( ! current_user_can( 'view_order', $order_id ) ) {
					$content = '<div class="woocommerce-error">' . __( 'Invalid order.', 'woocommerce' ) . ' <a href="' . wc_get_page_permalink( 'myaccount' ) . '" class="wc-forward">' . __( 'My Account', 'woocommerce' ) . '</a>' . '</div>';

				}
				else {
					// Backwards compatibility
					$status       = new stdClass();
					$status->name = wc_get_order_status_name( $order->get_status() );

					ob_start();
					wc_get_template( 'myaccount/view-order.php', array(
							'status'   => $status, // @deprecated 2.2
							'order'    => wc_get_order( $order_id ),
							'order_id' => $order_id
					) );
					$content = ob_get_clean();
				}
			}
			else {

				if( version_compare( WC()->version, '3.0', '>=' ) ) {
					$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
					ob_start();
					woocommerce_account_orders( $paged );
					$content = ob_get_clean();
				}
				else {
					// backward compatibility	
					extract( shortcode_atts( array(
						'order_count' => 15
					), $atts ) );
					
					$order_count = $order_count == 'all' ? -1 : $order_count;

					ob_start();
					wc_get_template( 'myaccount/my-orders.php', array( 'order_count' => $order_count ) );
					$content = ob_get_clean();

					// print message if no orders
					if( ! $content ){
						$content = '<p>' . __( 'There are no orders yet.', 'yith-woocommerce-customize-myaccount-page' ) . '</p>';
					}	
				}				
			}


			return $content;
		}

		/**
		 * Save an option to check if the page is myaccount
		 *
		 * @access public
		 * @since 1.0.4
		 * @author Francesco Licandro
		 */
		public function save_is_my_account(){
			update_option( 'yith_wcmap_is_my_account', $this->_is_myaccount );
		}

		/**
		 * Compatibility with visual composer plugin.
		 *
		 * @since 1.0.7
		 * @author Francesco Licandro
		 * @param string $action
		 * @param string $content
		 * @return string
		 */
		public function vc_compatibility( $action = 'get', $content = '' ){

			global $post;

			// extract from post content the my-account shortcode
			preg_match( '/\[woocommerce_my_account[^\]]*\]/', $post->post_content, $shortcode );
			// get content
			$shortcode = isset( $shortcode[0] ) ? $shortcode[0] : $post->post_content;

			if( $action == 'get' ) {
				return $shortcode;
			}
			elseif( $action = 'set' && $content ) {
				$post->post_content = str_replace( $shortcode, $content, $post->post_content );
				return true;
			}
		}

		/**
         * Reset standard WordPress avatar for customer
         *
         * @since 1.1.2
         * @author Francesco Licandro
         */
		public function reset_default_avatar(){

		    if( ! isset( $_POST['action'] ) || $_POST['action'] != 'ywcmap_reset_avatar' ) {
		        return;
            }

            // get user id
            $user = get_current_user_id();
            $media_id = get_user_meta( $user, 'yith-wcmap-avatar', true );

            if( ! $media_id ) {
                return;
            }

            // remove id from global list
            $medias = get_option('yith-wcmap-users-avatar-ids', array() );
            foreach ( $medias as $key => $media ) {
                if( $media == $media_id ) {
                    unset( $media[ $key ] );
                    continue;
                }
            }

            // then save
            update_option( 'yith-wcmap-users-avatar-ids', $medias );

            // then delete user meta
            delete_user_meta( $user, 'yith-wcmap-avatar' );

            // then delete media attachment
            wp_delete_attachment( $media_id );

        }

		/**
		 * Get avatar upload form
		 *
		 * @since 2.2.0
		 * @author Francesco Licandro
		 * @access public
		 * @param boolean $print Print or return avatar form
		 * @param array $args Array of argument for the template
		 * @return string
		 */
		public function get_avatar_form( $print = false, $args = array() ){
			ob_start();
			wc_get_template( 'ywcmap-myaccount-avatar-form.php', $args, '', YITH_WCMAP_DIR . 'templates/' );
			$form = ob_get_clean();

			if( $print ) {
				echo $form;
				return '';
			}

			return $form;
		}

		/**
		 * Get avatar upload form using Ajax
		 *
		 * @since 2.2.0
		 * @author Francesco Licandro
		 * @access public
		 * @return void
		 */
		public function get_avatar_form_ajax(){

			if( ! is_ajax() ) {
				return;
			}
			
			echo $this->get_avatar_form();
			die();
		}

	}
}
/**
 * Unique access to instance of YITH_WCMAP_Frontend class
 *
 * @return \YITH_WCMAP_Frontend
 * @since 1.0.0
 */
function YITH_WCMAP_Frontend(){
	return YITH_WCMAP_Frontend::get_instance();
}