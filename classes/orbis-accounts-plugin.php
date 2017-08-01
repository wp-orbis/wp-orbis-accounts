<?php

class Orbis_Accounts_Plugin extends Orbis_Plugin {
	public function __construct( $file ) {
		parent::__construct( $file );

		$this->set_name( 'orbis_accounts' );
		$this->set_db_version( '1.0.0' );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'p2p_init', array( $this, 'p2p_init' ) );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

		$post_type = 'orbis_account';
	}


	public function loaded() {
		$this->load_textdomain( 'orbis_accounts', '/languages/' );
	}

	public function init() {
		register_post_type( 'orbis_account', array(
			'labels'             =>  array(
				'name'               => _x( 'Accounts', 'post type general name', 'orbis_accounts' ),
				'singular_name'      => _x( 'Account', 'post type singular name', 'orbis_accounts' ),
				'menu_name'          => _x( 'Accounts', 'admin menu', 'orbis_accounts' ),
				'name_admin_bar'     => _x( 'Account', 'add new on admin bar', 'orbis_accounts' ),
				'add_new'            => _x( 'Add New', 'account', 'orbis_accounts' ),
				'add_new_item'       => __( 'Add New Account', 'orbis_accounts' ),
				'new_item'           => __( 'New Account', 'orbis_accounts' ),
				'edit_item'          => __( 'Edit Account', 'orbis_accounts' ),
				'view_item'          => __( 'View Account', 'orbis_accounts' ),
				'all_items'          => __( 'All Accounts', 'orbis_accounts' ),
				'search_items'       => __( 'Search Accounts', 'orbis_accounts' ),
				'parent_item_colon'  => __( 'Parent Account:', 'orbis_accounts' ),
				'not_found'          => __( 'No accounts found.', 'orbis_accounts' ),
				'not_found_in_trash' => __( 'No accounts found in Trash.', 'orbis_accounts' )
			),
			'description'        => __( 'Description.', 'orbis_accounts' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'accounts' ),
			'menu_icon'          => 'dashicons-universal-access',
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array(
				'title',
				'author',
			),
			'show_in_rest'       => true,
			'rest_base'          => 'orbis-accounts',
		) );
	}

	/**
	 * Posts 2 Posts init
	 *
	 * @see https://github.com/scribu/wp-posts-to-posts/wiki/Basic-usage
	 */
	public function p2p_init() {
		if ( post_type_exists( 'orbis_company' ) ) {
			p2p_register_connection_type( array(
				'name' => 'orbis_accounts_to_companies',
				'from' => 'orbis_account',
				'to'   => 'orbis_company',
			) );
		}
	}

	public function rest_api_init() {
		register_rest_field( 'orbis_account', 'companies', array(
			'get_callback' => array( $this, 'rest_field_companies' ),
		) );

		register_rest_field( 'orbis_account', 'subscriptions', array(
			'get_callback' => array( $this, 'rest_field_subscriptions' ),
		) );
	}

	public function rest_field_companies( $data ) {
		$post_id = $data['id'];

		$query = new WP_Query( array(
			'connected_type'  => 'orbis_accounts_to_companies',
			'connected_items' => $post_id,
			'nopaging'        => true,
		) );

		$companies = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$company = new stdClass();

				$company->id    = get_the_ID();
				$company->title = get_the_title();

				$companies[] = $company;
			}
		}

		return $companies;
	}

	public function rest_field_subscriptions( $data ) {
		if ( ! isset( $data['companies'] ) ) {
			return;
		}

		$companies = $data['companies'];

		if ( ! is_array( $companies ) ) {
			return;
		}

		$ids = wp_list_pluck( $companies, 'id' );
		$ids = wp_parse_id_list( $ids );

		if ( empty( $ids ) ) {
			return;
		}

		$list = implode( ',', $ids );

		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT
				subscription.id, 
				subscription.type_id,
				company.name AS company_name,
				product.name AS product_name,
				product.price,
				subscription.name,
				subscription.activation_date,
				subscription.cancel_date IS NOT NULL AS canceled,
				subscription.post_id
			FROM
				$wpdb->orbis_subscriptions AS subscription
					LEFT JOIN
				$wpdb->orbis_subscription_products AS product
						ON subscription.type_id = product.id
					LEFT JOIN
				$wpdb->orbis_companies as company
						ON subscription.company_id = company.id
			WHERE
				company.post_id IN ( $list )
			ORDER BY
				activation_date ASC
			;",
			$id
		);

		$subscriptions = $wpdb->get_results( $query );

		return $subscriptions;
	}
}
