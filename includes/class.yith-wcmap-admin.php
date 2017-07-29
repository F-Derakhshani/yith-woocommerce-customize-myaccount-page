<?php
/**
 * Admin class
 *
 * @author Yithemes
 * @package YITH WooCommerce Customize My Account Page
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCMAP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCMAP_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCMAP_Admin {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCMAP_Admin
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Plugin options
		 *
		 * @var array
		 * @access public
		 * @since 1.0.0
		 */
		public $options = array();

		/**
		 * Add endpoint action
		 *
		 * @var string
		 * @access protected
		 * @since 1.0.0
		 */
		public $add_field_action = 'yith_wcmap_add_field';

		/**
		 * Plugin version
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public $version = YITH_WCMAP_VERSION;

		/**
		 * @var $_panel Panel Object
		 */
		protected $_panel;

		/**
		 * @var string Customize my account panel page
		 */
		protected $_panel_page = 'yith_wcmap_panel';

		/**
		 * Various links
		 *
		 * @var string
		 * @access public
		 * @since 1.0.0
		 */
		public $doc_url = 'https://yithemes.com/docs-plugins/yith-woocommerce-customize-myaccount-page';

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCMAP_Admin
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

			add_action( 'admin_menu', array( $this, 'register_panel' ), 5) ;

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			//Add action links
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_WCMAP_DIR . '/' . basename( YITH_WCMAP_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );

			// endpoints
			add_action( 'woocommerce_admin_field_wcmap_endpoints', array( $this, 'wcmap_endpoints' ), 10, 1 );
			add_filter( 'woocommerce_admin_settings_sanitize_option_yith_wcmap_endpoint', array( $this, 'update_wcmap_fields' ), 10, 3 );

			// add endpoint ajax
			add_action( 'wp_ajax_' . $this->add_field_action, array( $this, 'add_field_ajax' ) );
			add_action( 'wp_ajax_nopriv_' . $this->add_field_action, array( $this, 'add_field_ajax' ) );

			// let's filter the media library
			add_action( 'pre_get_posts', array( $this, 'filter_media_library' ), 10, 1 );

			// Register plugin to licence/update system
			add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
			add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );

			// reset options
			add_action( 'admin_init', array( $this, 'reset_endpoints_options' ), 1 );

		}

		/**
		 * Enqueue scripts
		 *
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function enqueue_scripts() {

			$min        = ( ! defined('SCRIPT_DEBUG') || ! SCRIPT_DEBUG ) ? '.min' : '';

			wp_register_style( 'yith_wcmap', YITH_WCMAP_ASSETS_URL . '/css/ywcmap-admin.css' );
			wp_register_script( 'nestable', YITH_WCMAP_ASSETS_URL . '/js/jquery.nestable.js', array( 'jquery' ), $this->version, true );
			wp_register_script( 'yith_wcmap', YITH_WCMAP_ASSETS_URL . '/js/ywcmap-admin' . $min . '.js', array( 'jquery', 'nestable', 'jquery-ui-dialog' ), $this->version, true );

			// font awesome
			wp_register_style( 'font-awesome', YITH_WCMAP_ASSETS_URL . '/css/font-awesome.min.css' );

			if ( isset( $_GET['page'] ) && $_GET['page'] == 'yith_wcmap_panel' ) {
				wp_enqueue_script( 'jquery-ui' );
				wp_enqueue_script( 'jquery-ui-sortable' );

				wp_enqueue_style( 'font-awesome' );

				wp_enqueue_style( 'yith_wcmap' );
				wp_enqueue_script( 'yith_wcmap' );
				
				// enqueue select2 script registered by WooCommerce
				wp_enqueue_script( 'select2' );

				wp_localize_script( 'yith_wcmap', 'ywcmap', array(
					'ajaxurl'  	    => admin_url( 'admin-ajax.php' ),
					'action_add'    => $this->add_field_action,
					'show_lbl'	    => __( 'Show', 'yith-woocommerce-customize-myaccount-page' ),
					'hide_lbl'	    => __( 'Hide', 'yith-woocommerce-customize-myaccount-page' ),
					'loading'	    => '<img src="' . YITH_WCMAP_ASSETS_URL . '/images/wpspin_light.gif' . '">',
					'checked'	    => '<i class="fa fa-check"></i>',
					'error_icon'    => '<i class="fa fa-times"></i>',
					'remove_alert' 	=> __( 'Are you sure that you want to delete this endpoint?', 'yith-woocommerce-customize-myaccount-page' )
				));
			}
		}

		/**
		 * Action Links
		 *
		 * add the action links to plugin admin page
		 *
		 * @param $links | links plugin array
		 *
		 * @return   mixed Array
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return mixed
		 * @use plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {
			$links[] = '<a href="' . admin_url( "admin.php?page={$this->_panel_page}" ) . '">' . __( 'Settings', 'yith-woocommerce-customize-myaccount-page' ) . '</a>';

			return $links;
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use     /Yit_Plugin_Panel class
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->_panel ) ) {
				return;
			}

			$admin_tabs = array(
				'general' 	=> __( 'Settings', 'yith-woocommerce-customize-myaccount-page' ),
				'endpoints' => __( 'Endpoints', 'yith-woocommerce-customize-myaccount-page' )
			);

			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => __( 'Customize My Account Page', 'yith-woocommerce-customize-myaccount-page' ),
				'menu_title'       => __( 'Customize My Account Page', 'yith-woocommerce-customize-myaccount-page' ),
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yit_plugin_panel',
				'page'             => $this->_panel_page,
				'admin-tabs'       => apply_filters( 'yith_wcmap_admin_tabs', $admin_tabs ),
				'options-path'     => YITH_WCMAP_DIR . '/plugin-options'
			);

			/* === Fixed: not updated theme  === */
			if( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once( YITH_WCMAP_DIR . '/plugin-fw/lib/yit-plugin-panel-wc.php' );
			}

			$this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );

		}

		/**
		 * Register plugins for activation tab
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function register_plugin_for_activation() {
			if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
				require_once( YITH_WCMAP_DIR . 'plugin-fw/licence/lib/yit-licence.php' );
				require_once( YITH_WCMAP_DIR . 'plugin-fw/licence/lib/yit-plugin-licence.php' );
			}

			YIT_Plugin_Licence()->register( YITH_WCMAP_INIT, YITH_WCMAP_SECRET_KEY, YITH_WCMAP_SLUG );
		}

		/**
		 * Register plugins for update tab
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function register_plugin_for_updates() {
			if( ! class_exists( 'YIT_Upgrade' ) ){
				require_once( YITH_WCMAP_DIR . 'plugin-fw/lib/yit-upgrade.php' );
			}

			YIT_Upgrade()->register( YITH_WCMAP_SLUG, YITH_WCMAP_INIT );
		}

		/**
		 * plugin_row_meta
		 *
		 * add the action links to plugin admin page
		 *
		 * @param $plugin_meta
		 * @param $plugin_file
		 * @param $plugin_data
		 * @param $status
		 *
		 * @return   Array
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use plugin_row_meta
		 */
		public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {

			if ( defined( 'YITH_WCMAP_INIT') && YITH_WCMAP_INIT == $plugin_file ) {
				$plugin_meta[] = '<a href="' . $this->doc_url . '" target="_blank">' . __( 'Plugin Documentation', 'yith-woocommerce-customize-myaccount-page' ) . '</a>';
			}

			return $plugin_meta;
		}

		/**
		 * Create new Woocommerce admin field
		 *
		 * @access public
		 * @param array $value
		 * @return void
		 * @since 1.0.0
		 */
		public function wcmap_endpoints( $value ) {

            // get icon list
            if( ! class_exists( 'YIT_Plugin_Common' ) ) {
                require_once( YITH_WCMAP_DIR . '/plugin-fw/lib/yit-plugin-common.php' );
            }

            // get endpoints
			$args = apply_filters( 'yith_wcmap_admin_endpoints_template', array(
				'value'		=> $value,
				'endpoints' => yith_wcmap_get_endpoints(),
			));

			wc_get_template('admin-endpoints.php', $args, '', YITH_WCMAP_TEMPLATE_PATH . '/admin/' );
		}

		/**
		 * Create field key
		 *
		 * @access public
		 * @since 1.0.0
		 * @param string $key
		 * @return string
		 * @author Francesco Licandro
		 */
		public function create_field_key( $key ) {

			// build endpoint key
			$field_key = strtolower( $key );
			$field_key = trim( $field_key );
			// clear from space and add -
			$field_key = sanitize_title( $field_key );

			return $field_key;
		}

		/**
		 * Save the admin field
		 *
		 * @access public
		 * @param mixed $value
		 * @param mixed $option
		 * @param mixed $raw_value
		 * @return mixed
		 * @since 2.0.0
		 */
		public function update_wcmap_fields( $value, $option, $raw_value ) {

		    $decoded_fields = json_decode( $value, true );

            foreach ( $decoded_fields as &$decoded_field ) {

                if( ! isset( $decoded_field['id'] ) ) {
                    continue;
                }

                // check for master key
                $decoded_field['id'] = $this->create_field_key( $decoded_field['id'] );

                // check if is group
                if( isset( $decoded_field['children'] ) ) {
                    // save group options
                    $this->_save_group_options( $decoded_field['id'], $option['id'] );

                    foreach ( $decoded_field['children'] as &$children ) {
                        // check for children key
                        $children['id'] = $this->create_field_key( $children['id'] );
                        // save endpoint
                        $this->_save_endpoint_options( $children['id'], $option['id'] );
                    }
                }
                else {
                    // save endpoint
                    $this->_save_endpoint_options( $decoded_field['id'], $option['id'] );
                }

            }

            // handle also removed field
            $this->_delete_fields( $option['id'] );

            // reset options for rewrite rules
			update_option( 'yith-wcmap-flush-rewrite-rules', 1 );

            return json_encode( $decoded_fields );
		}

        /**
         * Get and save the endpoint options
         *
         * @since 2.0.0
         * @author Francesco Licandro
         * @access protected
         * @param $endpoint
         * @param $option_id
         */
        protected function _save_endpoint_options( $endpoint, $option_id ){

            $options = isset( $_POST[ $option_id . '_' . $endpoint ] ) ? $_POST[ $option_id . '_' . $endpoint ] : yith_wcmap_get_default_endpoint_options( $endpoint );

            $options['label']   = stripslashes( $options['label'] );
            $options['active']  = isset( $options['active'] );
            $options['slug']    = ( isset( $options['slug'] ) && ! empty( $options['slug'] ) ) ? $this->create_field_key( $options['slug'] ) : $endpoint;
            $options['content'] = stripslashes( $options['content'] );

            update_option( $option_id . '_' . $endpoint, $options );

            // synchronize wc options
            update_option( 'woocommerce_myaccount_'. str_replace( '-', '_', $endpoint ) .'_endpoint', $options['slug'] );
        }

        /**
         * Get and save the group options
         *
         * @since 2.0.0
         * @author Francesco Licandro
         * @param $group
         * @param $option_id
         */
        protected function _save_group_options( $group, $option_id ){

            $options = isset( $_POST[ $option_id . '_' . $group ] ) ? $_POST[ $option_id . '_' . $group ] : yith_wcmap_get_default_group_options( $group );

	        $options['active']  = isset( $options['active'] );
	        $options['open']    = isset( $options['open'] );

            update_option( $option_id . '_' . $group, $options );
        }

        /**
         * Delete removed fields
         *
         * @access protected
         * @since 2.0.0
         * @param string $option_id
         * @param array $to_remove
         * @author Francesco Licandro
         */
        protected function _delete_fields( $option_id, $to_remove = array() ) {

            if( empty( $to_remove )  ){
                // get fields removed if any
                $to_remove = isset( $_POST[ $option_id . '_to_remove' ] ) ? $_POST[ $option_id . '_to_remove' ] : '';
                $to_remove = explode( ',', $to_remove );
            }

            if( ! is_array( $to_remove ) ) {
                return;
            }

            foreach( $to_remove as $key ) {
                delete_option( $option_id . '_' . $key );
                // delete wc options if any
                delete_option( 'woocommerce_myaccount_'. str_replace( '-', '_', $key ) .'_endpoint' );
            }
        }

		/**
		 * Add a new field using ajax
		 *
		 * @access public
		 * @since 2.0.0
		 * @author Francesco Licandro
		 */
		public function add_field_ajax(){

			if( ! ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == $this->add_field_action ) || ! isset( $_REQUEST['field_name'] ) || ! isset( $_REQUEST['target'] ) ) {
				die();
			}

			// check if is endpoint
			$is_endpoint = $_REQUEST['target'] == 'endpoint';
			// build field key
            $field = $this->create_field_key( $_REQUEST['field_name'] );

            if( ! $field || yith_wcmap_endpoint_already_exists( $field ) ){
                wp_send_json( array(
                    'error' => __( 'An error has occurred or this endpoint field already exists. Please try again.', 'yith-woocommerce-customize-myaccount-page' ),
                    'field' => false
                ));
            }

            // build args array
            $args = array(
                'endpoint'  => $field,
                'options'   => $is_endpoint ? yith_wcmap_get_default_endpoint_options( $field ) : yith_wcmap_get_default_group_options( $field ),
                'id'        => 'yith_wcmap_endpoint',
                'icon_list' => YIT_Plugin_Common::get_icon_list(),
                'usr_roles'  => yith_wcmap_get_editable_roles()
            );

            ob_start();

            if( ! $is_endpoint ) {
                // print endpoints group
                yith_wcmap_admin_print_endpoints_group( $args );
            }
            else {
                // print single endpoint field.
                yith_wcmap_admin_print_endpoint_field( $args );
            }

            $html = ob_get_clean();

            wp_send_json( array(
                'html'    => $html,
                'field'   => $field
            ) );
		}

		/**
		 * Filter media library query form hide users avatar
		 *
		 * @access public
		 * @since 1.0.0
		 * @param object $q
		 * @author Francesco Licandro
		 */
		public function filter_media_library( $q ){

			$post_ids = get_option( 'yith-wcmap-users-avatar-ids', array() );
			$is_attachment = $q->get( 'post_type' ) == 'attachment';

			if( ! $is_attachment || empty( $post_ids ) || ! is_array( $post_ids )  )
				return;

			$this->_filter_media_library( $q, $post_ids );
		}

		/**
		 * Filter media library query
		 *
		 * @access private
		 * @since 1.0.0
		 * @param object $q
		 * @param array $post_ids Post to filter
		 * @author Francesco Licandro
		 */
		private function _filter_media_library( $q, $post_ids ) {

			$q->set( 'post__not_in', $post_ids );
		}

		/**
		 * Reset endpoints options
		 *
		 * @access public
		 * @since 1.0.0
		 * @author Francesco Licandro
		 */
		public function reset_endpoints_options() {

			if( isset( $_REQUEST['yit-action'] ) && $_REQUEST['yit-action'] == 'wc-options-reset'
				&& isset( $_POST['yith_wc_reset_options_nonce'] ) && wp_verify_nonce( $_POST['yith_wc_reset_options_nonce'], 'yith_wc_reset_options_'. $this->_panel_page )){

			    $fields = yith_wcmap_get_endpoints_keys();
                $this->_delete_fields( 'yith_wcmap_endpoint', $fields );

				// delete main option
                delete_option( 'yith_wcmap_endpoint' );

				// delete also endpoints flush option
				delete_option( 'yith-wcmap-flush-rewrite-rules' );
			}
		}

	}
}

/**
 * Unique access to instance of YITH_WCMAP_Admin class
 *
 * @return \YITH_WCMAP_Admin
 * @since 1.0.0
 */
function YITH_WCMAP_Admin(){
	return YITH_WCMAP_Admin::get_instance();
}