<?php
/**
 * Plugins Functions and Hooks
 *
 * @author Yithemes
 * @package YITH WooCommerce Customize My Account Page
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCMAP' ) ) {
	exit;
} // Exit if accessed directly

/*######################################
 ADMIN FUNCTION
 ######################################*/

if( ! function_exists( 'yith_wcmap_admin_print_endpoint_field' ) ) {
	/**
	 * Print endpoint field options
	 *
	 * @since 1.0.0
	 * @param array $args Template args array
	 * @author Francesco Licandro
	 */
	function yith_wcmap_admin_print_endpoint_field( $args ) {

	    // let third part filter template args
	    $args = apply_filters( 'yith_wcmap_admin_print_endpoint_field', $args );

		wc_get_template( 'endpoint-field.php', $args, '', YITH_WCMAP_TEMPLATE_PATH . '/admin/' );
	}
}

if( ! function_exists( 'yith_wcmap_admin_print_endpoints_group' ) ) {
	/**
	 * Print endpoints group field options
	 *
	 * @since 1.0.0
	 * @param array $args Template args array
	 * @author Francesco Licandro
	 */
	function yith_wcmap_admin_print_endpoints_group( $args ) {

	    // let third part filter template args
	    $args = apply_filters( 'yith_wcmap_admin_print_endpoints_group', $args );

		wc_get_template( 'endpoints-group.php', $args, '', YITH_WCMAP_TEMPLATE_PATH . '/admin/' );
	}
}

/*####################################
 COMMON FUNCTION
#####################################*/

if( ! function_exists( 'yith_wcmap_get_editable_roles' ) ) {
    /**
     * Get editable roles for endpoints
     *
     * @since 2.0.0
     * @author Francesco Licandro
     * @return array
     */
    function yith_wcmap_get_editable_roles(){
        // get user role
        $roles = get_editable_roles();
        $usr_roles = array();
        foreach ( $roles as $key => $role ) {
            if( empty( $role['capabilities'] ) ) {
                continue;
            }
            $usr_roles[ $key ] = $role['name'];
        }

        return $usr_roles;
    }
}

if( ! function_exists( 'yith_wcmap_build_label') ) {
    /**
     * Build endpoint label by name
     *
     * @since 2.0.0
     * @author Francesco Licandro
     * @param string $name
     * @return string
     */
    function yith_wcmap_build_label( $name ) {

        $label = preg_replace('/[^a-z]/', ' ', $name);
        $label = trim($label);
        $label = ucfirst($label);

        return $label;
    }
}

if( ! function_exists( 'yith_wcmap_get_default_endpoint_options' ) ) {
    /**
     * Get default options for new endpoints
     *
     * @since 2.0.0
     * @param string $endpoint
     * @return array
     * @author Francesco Licandro
     */
    function yith_wcmap_get_default_endpoint_options( $endpoint ) {

        $endpoint_name = yith_wcmap_build_label( $endpoint );

        // build endpoint options
        $options = array(
            'slug' => $endpoint,
            'active' => true,
            'label' => $endpoint_name,
            'icon' => '',
            'content' => '',
            'usr_roles' => ''
        );

        return apply_filters( 'yith_wcmap_get_default_endpoint_options', $options );
    }
}

if( ! function_exists( 'yith_wcmap_get_default_group_options' ) ) {
    /**
     * Get default options for new group
     *
     * @since 2.0.0
     * @param string $group
     * @return array
     * @author Francesco Licandro
     */
    function yith_wcmap_get_default_group_options( $group ) {

        $group_name = yith_wcmap_build_label($group);

        // build endpoint options
        $options = array(
	        'active'    => true,
            'label'     => $group_name,
	        'usr_roles' => '',
            'open'      => true,
            'children'  => array()
        );

        return apply_filters( 'yith_wcmap_get_default_group_options', $options );
    }
}

if( ! function_exists( 'yith_wcmap_get_endpoints' ) ) {
    /**
     * Get ordered endpoints based on plugin option
     *
     * @since 1.0.0
     * @return array
     * @author Francesco Licandro
     */
    function yith_wcmap_get_endpoints() {

        // get saved endpoints order
        $fields = get_option( 'yith_wcmap_endpoint', '' );
        $fields = json_decode( $fields, true );
        // set empty array is false or null
        ( ! $fields || is_null( $fields ) ) && $fields = array();

	    // initialize return array
        $return = array();

        // get default endpoints
        $default = yith_wcmap_get_default_endpoints();
        $default_plugin = yith_wcmap_get_plugins_endpoints();
        // merge default and plugin default
        $all_default = array_merge( $default, $default_plugin );

        foreach ( $fields as $field ) {

            // build return array
            $id = $field['id'];
            $return[ $id ] = array();

            // check child on default plugin
            if( array_key_exists( $id, $default_plugin ) ) {
                unset( $default_plugin[ $id ] );
            }

            $options = get_option( 'yith_wcmap_endpoint_' . $id, array() );
            // is empty check on default endpoint otherwise get default value
            if( empty( $options ) ) {
                if( isset( $all_default[ $id ] ) ) {
                    $options = $all_default[ $id ];
                }
                else {
                    $options = isset( $field['children'] ) ? yith_wcmap_get_default_group_options( $id ) : yith_wcmap_get_default_endpoint_options( $id );
                }
            }

            if( isset( $field['children'] ) ) {

                $children = array();

                foreach ( $field['children'] as $child ) {
                    $child_id = $child['id'];
                    $child_options = get_option( 'yith_wcmap_endpoint_' . $child_id, array() );
                    $children[ $child_id ] = empty( $child_options ) ? array() : $child_options;

                    // check child on default plugin
                    if( array_key_exists( $child_id, $default_plugin ) ) {
                        unset( $default_plugin[ $child_id ] );
                    }
                }

                $options['children'] = $children;
            }

            $return[ $id ] = $options;
        }

        if( empty( $return ) ) {
            return $all_default;
        }

        // merge with new plugin then return
        $return = array_merge( $return, $default_plugin );

        return apply_filters( 'yith_wcmap_get_endpoints', $return );
    }
}

if( ! function_exists( 'yith_wcmap_get_endpoints_keys' ) ) {
    /**
     * Get all endpoints keys
     *
     * @since 2.0.0
     * @return array
     * @author Francesco Licandro
     */
    function yith_wcmap_get_endpoints_keys() {

        $fields = yith_wcmap_get_endpoints();

        if( empty( $fields ) ) {
            return array();
        }

        $keys = array();
        foreach( $fields as $field_key => $field ) {
            $keys[] = $field_key;
            if( isset( $field['children'] ) ) {
                foreach ( $field['children'] as $child_key => $child ) {
                    $keys[] = $child_key;
                }
            }
        }

        return $keys;
    }
}

if( ! function_exists( 'yith_wcmap_get_default_endpoints_keys' ) ) {
	/**
	 * Get default endpoints key
	 *
	 * @since 1.0.0
	 * @author Francesco Licandro
	 */
	function yith_wcmap_get_default_endpoints_keys() {
		return apply_filters( 'yith_wcmap_get_default_endpoints_keys_array',
			array(
				'dashboard',
				'my-downloads',
				'view-order',
				'edit-account',
				'edit-address'
			) );
	}
}

if( ! function_exists( 'yith_wcmap_get_default_endpoints' ) ) {
	/**
	 * Get default endpoints and options
	 *
	 * @since 1.0.0
	 * @return array
	 * @author Francesco Licandro
	 */
	function yith_wcmap_get_default_endpoints(){

		$endpoints_keys = yith_wcmap_get_default_endpoints_keys();
		$endpoints_keys = array_unique( $endpoints_keys );

		if( empty( $endpoints_keys ) || ! is_array( $endpoints_keys ) ) {
			return array();
		}

		$endpoints = array();

		// populate endpoints array with options
		foreach ( $endpoints_keys as $endpoint ) {

			$label = $icon = $content = $slug = '';

			switch( $endpoint ) {
				case 'view-order':
					$label 		= __( 'My Orders', 'yith-woocommerce-customize-myaccount-page' );
					$icon 		= 'file-text-o';
					$content 	= '[view_order_content]';
					$slug 		=  get_option( 'woocommerce_myaccount_view_order_endpoint', 'view-order' );
					break;
				case 'edit-account':
					$label 		= __( 'Edit Account', 'yith-woocommerce-customize-myaccount-page' );
					$icon 		= 'pencil-square-o';
					$slug 		=  get_option( 'woocommerce_myaccount_edit_account_endpoint', 'edit-account' );
					break;
				case 'edit-address':
					$label 		= __( 'Edit Address', 'yith-woocommerce-customize-myaccount-page' );
					$icon 		= 'pencil-square-o';
					$slug 		=  get_option( 'woocommerce_myaccount_edit_address_endpoint', 'edit-address' );
					break;
				case 'my-downloads':
					$label 		= __( 'My Downloads', 'yith-woocommerce-customize-myaccount-page' );
					$icon 		= 'download';
					$content 	= '[my_downloads_content]';
					$slug 		=  'my-downloads';
					break;
				case 'dashboard':
					$label 		= __( 'Dashboard', 'yith-woocommerce-customize-myaccount-page' );
					$icon 		= 'tachometer';
					$slug 		= 'dashboard';
					break;
				case 'payment-methods' :
					$label 		= __( 'Payment Methods', 'yith-woocommerce-customize-myaccount-page' );
					$icon 		= 'money';
					$content 	= '';
					$slug 		= get_option( 'woocommerce_myaccount_payment_methods_endpoint', 'payment-methods' );
					break;
			}

			$endpoints[ $endpoint ]['slug'] = $slug;
			$endpoints[ $endpoint ]['active'] = true;
			$endpoints[ $endpoint ]['label'] = $label;
			$endpoints[ $endpoint ]['icon'] = $icon;
			$endpoints[ $endpoint ]['content'] = $content;
            $endpoints[ $endpoint ]['usr_roles'] = '';
		}

		// lets filter endpoints array
		return apply_filters( 'yith_wcmap_get_default_endpoints_array', $endpoints );
	}
}

if( ! function_exists( 'yith_wcmap_get_endpoints_slug' ) ) {
    /**
     * Get endpoints slugs for register endpoints
     *
     * @since 1.0.0
     * @author Francesco Licandro
     * @return array
     */
    function yith_wcmap_get_endpoints_slug(){

        $slugs = array();
        $fields = yith_wcmap_get_endpoints();

        foreach( $fields as $field ) {

            isset( $field['slug'] ) && $slugs[] = $field['slug'];

            if( isset( $field['children'] ) ) {
                foreach ( $field['children'] as $child ) {
                    isset( $child['slug'] ) && $slugs[] = $child['slug'];
                }
            }
        }

        return $slugs;
    }
}

if( ! function_exists( 'yith_wcmap_endpoint_already_exists' ) ) {
    /**
     * Check if endpoints already exists
     *
     * @since 1.0.0
     * @param string $endpoint
     * @return boolean
     * @author Francesco Licandro
     */
    function yith_wcmap_endpoint_already_exists( $endpoint ) {

        // check first in key
        $field_key  = yith_wcmap_get_endpoints_keys();
        $exists     = in_array( $endpoint, $field_key );

        // check also in slug
        if( ! $exists ) {
            $endpoint_slug = yith_wcmap_get_endpoints_slug();
            $exists = in_array( $endpoint, $endpoint_slug );
        }

        return $exists;
    }
}

if( ! function_exists( 'yith_wcmap_check_endpoint_active' ) ) {
    /**
     * Check if and endpoint is active on frontend. Used for add class 'active' on account menu in frontend
     *
     * @since 1.1.0
     * @param string $endpoint
     * @author Francesco Licandro
     * @return boolean
     */
    function yith_wcmap_check_endpoint_active( $endpoint ) {
        _deprecated_function( __FUNCTION__, '2.0.0', 'yith_wcmap_get_current_endpoint' );

        $current = yith_wcmap_get_current_endpoint();

        return $endpoint === $current;
    }
}

if( ! function_exists( 'yith_wcmap_get_current_endpoint' ) ) {
    /**
     * Check if and endpoint is active on frontend. Used for add class 'active' on account menu in frontend
     *
     * @since 2.0.0
     * @author Francesco Licandro
     * @return string
     */
    function yith_wcmap_get_current_endpoint(){

        global $wp;

        $current = 'dashboard';
        foreach( WC()->query->get_query_vars() as $key => $value ) {
            if ( isset( $wp->query_vars[ $key ] ) ) {
                $current = $value;
            }
        }
        return apply_filters( 'yith_wcmap_get_current_endpoint', $current );
    }
}

if( ! function_exists( 'yith_wcmap_endpoints_option_default' ) ) {
    /**
     * Get endpoints slugs for register endpoints
     *
     * @since 2.0.0
     * @author Francesco Licandro
     * @return array
     */
    function yith_wcmap_endpoints_option_default(){

        $return = array();
        $fields = yith_wcmap_get_endpoints();

        foreach( $fields as $field ) {
            if( isset( $field['children'] ) ) {
                foreach ( $field['children'] as $child ) {
                    $return[ $child['slug'] ] = $child['label'];
                }

                continue;
            }
            $return[ $field['slug'] ] = $field['label'];
        }

        return $return;
    }
}

if( ! function_exists( 'yith_wcmap_get_endpoint_by' ) ) {
	/**
	 * Get endpoint by a specified key
	 *
	 * @since 2.0.0
	 * @author Francesco Licandro
	 * @param string $value
	 * @param string $key Can be key or slug
	 * @param array $fields Endpoint array
	 * @return array
	 */
	function yith_wcmap_get_endpoint_by( $value, $key = 'key', $fields = array() ) {

		$accepted = apply_filters( 'yith_wcmap_get_endpoint_by_accepted_key', array( 'key', 'slug' ) );

		if( ! in_array( $key, $accepted ) ) {
			return array();
		}

		empty( $fields ) && $fields = yith_wcmap_get_endpoints();
		$find   = array();

		foreach( $fields as $id => $field ) {
			if( ( $key == 'key' && $id == $value ) || ( isset( $field[ $key ] ) && $field[ $key ] == $value ) ) {
				$find[ $id ] = $field;
				continue;
			}
			elseif( isset( $field['children'] ) ) {
				foreach( $field['children'] as $child_id => $child ) {
					if( ( $key == 'key' && $child_id == $value ) || ( isset( $child[ $key ] ) && $child[ $key ] == $value ) ) {
						$find[ $child_id ] = $child;
						continue;
					}
				}
				continue;
			}
		}
		return apply_filters( 'yith_wcmap_get_endpoint_by_result', $find );
	}
}

/*#####################################
 PRINT ENDPOINT FRONTEND
######################################*/

add_action( 'yith_wcmap_print_single_endpoint', 'yith_wcmap_print_single_endpoint', 10, 2 );
add_action( 'yith_wcmap_print_endpoints_group', 'yith_wcmap_print_endpoints_group', 10, 2 );

if( ! function_exists('yith_wcmap_print_single_endpoint') ) {
    /**
     * Print single endpoint on front menu
     *
     * @since 2.0.0
     * @author Francesco Licandro
     * @param string $endpoint
     * @param array $options
     */
    function yith_wcmap_print_single_endpoint( $endpoint, $options ) {

        $url = get_permalink( wc_get_page_id( 'myaccount' ) );
        $endpoint != 'dashboard' && $url = wc_get_endpoint_url( $options['slug'], '', $url );

        $classes = array();
        // check if endpoint is active
        $current = yith_wcmap_get_current_endpoint();
        if( $options['slug'] == $current ) {
            $classes[] = 'active';
        }

        $classes = apply_filters( 'yith_wcmap_endpoint_menu_class', $classes, $endpoint, $options );

        // build args array
        $args = apply_filters( 'yith_wcmap_print_single_endpoint_args', array(
            'url'       => $url,
            'endpoint'  => $endpoint,
            'options'   => $options,
            'classes'   => $classes
        ));

        wc_get_template( 'ywcmap-myaccount-menu-item.php', $args, '', YITH_WCMAP_DIR . 'templates/' );
    }
}

if( ! function_exists('yith_wcmap_print_endpoints_group') ) {
    /**
     * Print endpoints group on front menu
     *
     * @since 2.0.0
     * @author Francesco Licandro
     * @param string $endpoint
     * @param array $options
     */
    function yith_wcmap_print_endpoints_group( $endpoint, $options ) {

        $classes = array( 'group-' . $endpoint );
        $current = yith_wcmap_get_current_endpoint();
        // check in child
        foreach( $options['children'] as $child ) {
            if( $child['slug'] == $current ) {
                $classes[] = 'active';
                break;
            }
        }

        $istab = get_option( 'yith-wcmap-menu-style', 'sidebar' ) == 'tab';
        // options for style tab
	    if( $istab ) {
		    // force option open to true
		    $options['open'] = true;
	        $class_icon = 'fa-chevron-down';
		    $classes[] = 'is-tab';
        }
	    else {
		    $class_icon = $options['open'] ? 'fa-chevron-up' : 'fa-chevron-down';
	    }
        
        $classes = apply_filters( 'yith_wcmap_endpoints_group_class', $classes, $endpoint, $options );

        // build args array
        $args = apply_filters( 'yith_wcmap_print_endpoints_group_group', array(
            'options'       => $options,
            'classes'       => $classes,
            'class_icon'    => $class_icon
        ));

        wc_get_template( 'ywcmap-myaccount-menu-group.php', $args, '', YITH_WCMAP_DIR . 'templates/' );
    }
}

/*#####################################
 AVATAR FUNCTION
#####################################*/

if( ! function_exists( 'yith_wcmap_generate_avatar_path' ) ){
	/**
	 * Generate avatar path
	 *
	 * @param $attachment_id
	 * @param $size
	 * @return string
	 */
	function  yith_wcmap_generate_avatar_path( $attachment_id, $size ) {
		// Retrieves attached file path based on attachment ID.
		$filename = get_attached_file( $attachment_id );

		$pathinfo  = pathinfo( $filename );
		$dirname   = $pathinfo['dirname'];
		$extension = $pathinfo['extension'];

		// i18n friendly version of basename().
		$basename = wp_basename( $filename, '.' . $extension );

		$suffix    = $size . 'x' . $size;
		$dest_path = $dirname . '/' . $basename . '-' . $suffix . '.' . $extension;

		return $dest_path;
	}
}

if( ! function_exists( 'yith_wcmap_generate_avatar_url' ) ) {
	/**
	 * Generate avatar url
	 *
	 * @param $attachment_id
	 * @param $size
	 * @return mixed
	 */
	function yith_wcmap_generate_avatar_url( $attachment_id, $size ) {
		// Retrieves path information on the currently configured uploads directory.
		$upload_dir = wp_upload_dir();

		// Generates a file path of an avatar image based on attachment ID and size.
		$path = yith_wcmap_generate_avatar_path( $attachment_id, $size );

		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $path );
	}
}

if( ! function_exists( 'yith_wcmap_resize_avatar_url' ) ) {
	/**
	 * Resize avatar
	 *
	 * @param $attachment_id
	 * @param $size
	 * @return boolean
	 */
	function yith_wcmap_resize_avatar_url( $attachment_id, $size ){

		$dest_path = yith_wcmap_generate_avatar_path( $attachment_id, $size );

		if ( file_exists( $dest_path ) ) {
			$resize = true;
		} else {
			// Retrieves attached file path based on attachment ID.
			$path = get_attached_file( $attachment_id );

			// Retrieves a WP_Image_Editor instance and loads a file into it.
			$image = wp_get_image_editor( $path );

			if ( ! is_wp_error( $image ) ) {

				// Resizes current image.
				$image->resize( $size, $size, true );

				// Saves current image to file.
				$image->save( $dest_path );

				$resize = true;

			}
			else {
				$resize = false;
			}
		}

		return $resize;
	}
}

/*#########################################
 CUSTOM PLUGINS ENDPOINTS
###########################################*/

if( ! function_exists( 'yith_wcmap_get_plugins_endpoints' ) ) {
	/**
	 * Get plugins endpoints
	 *
	 * @since 1.0.0
	 * @param string $key
	 * @return array
	 * @author Francesco Licandro
	 */
	function yith_wcmap_get_plugins_endpoints( $key = '' ) {

		$endpoints = array();

		if( defined( 'YITH_WCWL' ) && YITH_WCWL ) {
			$endpoints['my-wishlist'] = array(
				'slug'		=> 'my-wishlist',
				'active' 	=> true,
				'label'  	=> __( 'My Wishlist', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'	 	=> 'heart',
				'content' 	=> '[yith_wcwl_wishlist]'
			);
		}
		if( defined( 'YITH_WOCC_PREMIUM' ) && YITH_WOCC_PREMIUM ) {
			$endpoints['one-click'] = array(
				'slug'		=> 'one-click',
				'active' 	=> true,
				'label'  	=> __( 'One click checkout', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'	 	=> 'hand-o-up',
				'content' 	=> '[yith_wocc_myaccount]'
			);
		}
		if( defined( 'YITH_WCSTRIPE_PREMIUM' ) && YITH_WCSTRIPE_PREMIUM ) {
			$endpoints['stripe'] = array(
				'slug'		=> 'saved-cards',
				'active' 	=> true,
				'label'  	=> __( 'Saved Cards', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'	 	=> 'cc-stripe',
				'content' 	=> ''
			);
		}
		if( defined( 'YITH_YWRAQ_PREMIUM' ) && YITH_YWRAQ_PREMIUM ) {
			$endpoints['view-quote'] = array(
				'slug'		=> 'view-quote',
				'active' 	=> true,
				'label'  	=> __( 'My Quotes', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'	 	=> 'pencil',
				'content' 	=> '[yith_ywraq_myaccount_quote]'
			);
		}
		if( defined( 'YITH_WCWTL_PREMIUM' ) && YITH_WCWTL_PREMIUM ) {
			$endpoints['waiting-list'] = array(
				'slug'		=> 'my-waiting-list',
				'active' 	=> true,
				'label'  	=> __( 'My Waiting List', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'	 	=> 'clock-o',
				'content' 	=> '[ywcwtl_waitlist_table]'
			);
		}
		if( defined( 'YITH_WCMBS_PREMIUM' ) && YITH_WCMBS_PREMIUM ) {

            $title     = __( 'Membership Plans:', 'yith-woocommerce-membership' );
            $shortcode = '[membership_history title="' . $title . '"]';
            $shortcode = apply_filters( 'yith_wcmbs_membership_history_shortcode_in_my_account', $shortcode, $title );

			$endpoints['yith-membership'] = array(
				'slug'		=> 'membership-plans',
				'active' 	=> true,
				'label'  	=> __( 'Membership Plans', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'	 	=> 'list',
				'content' 	=> $shortcode
			);
		}
		if( defined( 'YITH_YWSBS_PREMIUM' ) && YITH_YWSBS_PREMIUM ) {
			$endpoints['yith-subscription'] = array(
				'slug'		=> 'my-subscription',
				'active' 	=> true,
				'label'  	=> __( 'My Subscriptions', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'	 	=> 'pencil',
				'content' 	=> '[ywsbs_my_account_subscriptions]'
			);
		}
		if( class_exists( 'WC_Memberships' ) ) {
			$endpoints['view-membership'] = array(
				'slug'		=> 'view-membership',
				'active' 	=> true,
				'label'  	=> __( 'My Membership', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'	 	=> 'list',
				'content' 	=> '[ywcmap_woocommerce_membership]'
			);
		}
		if( class_exists( 'WC_Subscriptions' ) ) {
			$endpoints['view-subscription'] = array(
				'slug'		=> 'view-subscription',
				'active' 	=> true,
				'label'  	=> __( 'My Subscription', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'	 	=> 'pencil',
				'content' 	=> '[ywcmap_woocommerce_subscription]'
			);
		}

        if( defined('YITH_FUNDS_PREMIUM') && YITH_FUNDS_PREMIUM ){

            $endpoints['make-a-deposit'] = array(
                'slug' => 'make-a-deposit',
                'active' => true,
                'label' => __('Make a Deposit', 'yith-woocommerce-customize-my-account-page'),
                'icon' => 'money',
                'content' => '[yith_ywf_make_a_deposit_endpoint]'
            );

            $endpoints['income-expenditure-history'] = array(
                'slug'=> 'income-expenditure-history',
                'active' => true,
                'label' => __('Income/Expenditure History', 'yith-woocommerce-customize-my-account-page' ),
                'icon' => 'list-ol',
                'content' => '[yith_ywf_show_history pagination="yes"]'

            );
        }

		return ( $key && isset( $endpoints[$key] ) ) ? $endpoints[$key] : $endpoints;
	}
}

if( ! function_exists( 'yith_wcmap_is_plugin_endpoint' ) ) {
	/**
	 * Check if an endpoint is a plugin
	 *
	 * @since 1.0.4
	 * @author Francesco Licandro
	 */
	function yith_wcmap_is_plugin_endpoint( $endpoint ) {
		$plugin_endpoints = yith_wcmap_get_plugins_endpoints();
		return array_key_exists( $endpoint, $plugin_endpoints );
	}
}

/*####################################
* YITH WOOCOMMERCE ONE CLICK CHECKOUT
######################################*/

if( defined( 'YITH_WOCC_PREMIUM' ) && YITH_WOCC_PREMIUM ) {
	/**
	 * Add One Click Checkout compatibility
	 *
	 * @author Francesco Licandro
	 */
	function yith_wocc_one_click_compatibility(){

		if( class_exists( 'YITH_WOCC_User_Account' ) ) {
			// remove content in my account
			remove_action( 'woocommerce_after_my_account', array( YITH_WOCC_User_Account(), 'my_account_options' ) );
		}

		add_filter( 'yith_wcmap_endpoint_menu_class', 'yith_wocc_set_active_one_click', 10, 3 );
	}

	/**
	 * Assign active class to endpoint one-click
	 *
	 * @since 1.1.0
	 * @author Francesco Licandro
	 * @param array $classes
	 * @param string $endpoint
	 * @param array $options
	 * @return array
	 */
	function yith_wocc_set_active_one_click( $classes, $endpoint, $options ) {

		global $wp;

		if( $endpoint == 'one-click' && ! in_array( 'active', $classes ) && isset( $wp->query_vars['custom-address'] ) ) {
			$classes[] = 'active';
		}

		return $classes;
	}

	add_action( 'template_redirect', 'yith_wocc_one_click_compatibility', 5 );
}

/*####################################
* YITH WOOCOMMERCE STRIPE
######################################*/

if( defined( 'YITH_WCSTRIPE_PREMIUM' ) && YITH_WCSTRIPE_PREMIUM && ! yith_wcmap_wc26() ) {
	/**
	 * Add Stripe compatibility
	 *
	 * @author Francesco Licandro
	 */
	function yith_wcmap_stripe_compatibility(){

		global $wp;

		if( ! class_exists( 'YITH_WCStripe_Premium' ) ) {
			return;
		}

		$endpoints = yith_wcmap_get_plugins_endpoints( 'stripe' );
		$options = get_option( 'yith_wcmap_endpoint_stripe', array() );
		$slug = isset( $options['slug'] ) ? $options['slug'] : $endpoints['slug'];

		// remove content in my account
		remove_action( 'woocommerce_after_my_account', array( YITH_WCStripe_Premium::get_instance(), 'saved_cards_box' ) );
		if( isset( $wp->query_vars[ $slug ] ) ) {
			add_filter( 'yith_savedcards_page', '__return_true' );
		}
	}

	add_action( 'template_redirect', 'yith_wcmap_stripe_compatibility', 5 );
}

/*####################################
* YITH WOOCOMMERCE REQUEST A QUOTE
######################################*/

if( defined( 'YITH_YWRAQ_PREMIUM' ) && YITH_YWRAQ_PREMIUM ) {
	/**
	 * Add Request Quote compatibility
	 *
	 * @author Francesco Licandro
	 */
	function yith_wcmap_request_quote_compatibility(){

		if( class_exists( 'YITH_YWRAQ_Order_Request' ) ) {
			// remove content in my account
			remove_action( 'woocommerce_before_my_account', array( YITH_YWRAQ_Order_Request(), 'my_account_my_quotes' ) );
			remove_action( 'template_redirect', array( YITH_YWRAQ_Order_Request(), 'load_view_quote_page' ) );
		}
	}

	add_action( 'template_redirect', 'yith_wcmap_request_quote_compatibility', 5 );
}

/*####################################
* YITH WOOCOMMERCE WAITING LIST
######################################*/

if( defined( 'YITH_WCWTL_PREMIUM' ) && YITH_WCWTL_PREMIUM ) {
	/**
	 * Add Request Quote compatibility
	 *
	 * @author Francesco Licandro
	 */
	function yith_wcmap_waiting_list_compatibility(){

		if( class_exists( 'YITH_WCWTL_Frontend' ) ) {
			// remove content in my account
			remove_action( 'woocommerce_before_my_account', array( YITH_WCWTL_Frontend(), 'add_waitlist_my_account' ) );
		}
	}

	add_action( 'template_redirect', 'yith_wcmap_waiting_list_compatibility', 5 );
}

if( defined( 'YITH_WCMBS_PREMIUM' ) && YITH_WCMBS_PREMIUM ) {
	/**
	 * Add Request Quote compatibility
	 *
	 * @author Francesco Licandro
	 */
	function yith_membership_compatibility(){

		if( class_exists( 'YITH_WCMBS_Frontend_Premium' ) ) {
			// remove content in my account
            remove_action( 'woocommerce_after_my_account', array( YITH_WCMBS_Frontend(), 'print_membership_history' ), 10 );
            remove_action( 'woocommerce_account_dashboard', array( YITH_WCMBS_Frontend(), 'print_membership_history' ), 10 );
		}
	}

	add_action( 'template_redirect', 'yith_membership_compatibility', 5 );
}

if( defined( 'YITH_YWSBS_PREMIUM' ) && YITH_YWSBS_PREMIUM ) {
	/**
	 * Add Request Quote compatibility
	 *
	 * @author Francesco Licandro
	 */
	function yith_subscription_compatibility(){

		if( class_exists( 'YITH_WC_Subscription' ) ) {
			// remove content in my account
            remove_action( 'woocommerce_before_my_account', array( YITH_WC_Subscription(), 'my_account_subscriptions' ), 10);
		}
	}

	add_action( 'template_redirect', 'yith_subscription_compatibility', 5 );
}

if( ! function_exists( 'yith_wcmap_woocommerce_subscription_compatibility' ) ) {
	/**
	 * Add Request Quote compatibility
	 *
	 * @author Francesco Licandro
	 */
	function yith_wcmap_woocommerce_subscription_compatibility(){

		if( ! class_exists( 'WC_Subscriptions' ) ) {
			return;
		}

		// remove content in my account
		remove_action( 'woocommerce_before_my_account', array( 'WC_Subscriptions', 'get_my_subscriptions_template' ) );
		add_shortcode( 'ywcmap_woocommerce_subscription', 'ywcmap_woocommerce_subscription' );
	}

	function ywcmap_woocommerce_subscription( $args ){
		
		global $wp;

		if( ! class_exists( 'WC_Subscriptions' ) ) {
			return '';
		}

		ob_start();
		if( ! empty( $wp->query_vars['view-subscription'] ) ) {
			wc_get_template( 'myaccount/view-subscription.php', array(), '', plugin_dir_path( WC_Subscriptions::$plugin_file ) . 'templates/' );
		}
		else {
			WC_Subscriptions::get_my_subscriptions_template();
		}

		return ob_get_clean();
	}
}
add_action( 'template_redirect', 'yith_wcmap_woocommerce_subscription_compatibility', 5 );

if( ! function_exists( 'yith_wcmap_woocommerce_membership_compatibility' ) ) {
    /**
     * Add Request Quote compatibility
     *
     * @author Francesco Licandro
     */
    function yith_wcmap_woocommerce_membership_compatibility(){

        if( ! class_exists( 'WC_Memberships' ) ) {
            return;
        }

        $class = wc_memberships();

        // remove content in my account
        remove_action( 'woocommerce_before_my_account', array( $class->frontend, 'my_account_memberships' ), 10 );
        add_shortcode( 'ywcmap_woocommerce_membership', 'ywcmap_woocommerce_membership' );
    }

    function ywcmap_woocommerce_membership( $args ){

	    if( ! class_exists( 'WC_Memberships' ) ) {
		    return '';
	    }

        $class = wc_memberships();

        ob_start();
        $class->frontend->my_account_memberships();
        return ob_get_clean();
    }
}
add_action( 'template_redirect', 'yith_wcmap_woocommerce_membership_compatibility', 5 );


/*###########################
* COMPATIBILITY WITH WC 2.6
#############################*/

/**
 * Check if WC version is 2.6
 *
 * @author Francesco Licandro
 * @return mixed
 */
function yith_wcmap_wc26(){
	return version_compare( WC()->version, '2.6', '>=' );
}

if( yith_wcmap_wc26() ) {

	// remove standard woocommerce sidebar;
	if( $priority = has_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation' ) ) {
		remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation', $priority );
	}

	// Add endpoint WC 2.6
	add_filter( 'yith_wcmap_get_default_endpoints_keys_array', 'yith_wcmap_default_endpoints_keys_wc26', 10, 1 );
	// Assign active class to endpoint payment-methods for other payment methods endpoints
	add_filter( 'yith_wcmap_endpoint_menu_class', 'yith_wcmap_set_active_payment_methods', 10, 3 );

	/**
	 * Add endpoint WC 2.6
	 *
	 * @param array $endpoints
	 * @return array
	 * @author Francesco Licandro
	 */
	function yith_wcmap_default_endpoints_keys_wc26( $endpoints ) {
		// add wc 2.6 endpoints
		$endpoints[] = 'payment-methods';
		return $endpoints;
	}

	/**
	 * Assign active class to endpoint payment-methods for other payment methods endpoints
	 *
	 * @since 1.1.0
	 * @author Francesco Licandro
	 * @param array $classes
	 * @param string $endpoint
	 * @param array $options
	 * @return array
	 */
	function yith_wcmap_set_active_payment_methods( $classes, $endpoint, $options ) {

		if( $endpoint == 'payment-methods' ) {

			$current = WC()->query->get_current_endpoint();
			if( ! in_array( 'active', $classes ) &&
			    in_array( $current, array( 'add-payment-method', 'delete-payment-method', 'set-default-payment-method' ) ) ) {
				$classes[] = 'active';
			}
		}

		return $classes;
	}
}