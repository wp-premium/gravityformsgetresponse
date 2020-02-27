<?php

/**
 * Gravity Forms GetResponse API Library.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2020, Rocketgenius
 */
class GF_GetResponse_API {

	/**
	 * GetResponse API key.
	 *
	 * @since  1.3
	 * @access protected
	 * @var    string $api_key GetResponse API key.
	 */
	protected $api_key;

	/**
	 * GetResponse API URL.
	 *
	 * @since  1.3
	 * @access protected
	 * @var    string $api_url GetResponse API URL.
	 */
	protected $api_url = 'https://api.getresponse.com/v3/';

	/**
	 * GetResponse 360 API URL.
	 *
	 * @since  1.3
	 * @access protected
	 * @var    string $api_url_360 GetResponse 360 API URL.
	 */
	protected $api_url_360 = 'https://api3.getresponse360.com/v3/';

	/**
	 * GetResponse account domain (for 360 customers).
	 *
	 * @since  1.3
	 * @access protected
	 * @var    string $domain GetResponse account domain.
	 */
	protected $domain;

	/**
	 * Initialize GetResponse API library.
	 *
	 * @since  1.3
	 *
	 * @param string $api_key GetResponse API key.
	 * @param string $domain  GetResponse account domain.
	 */
	public function __construct( $api_key, $domain = null ) {

		$this->api_key = $api_key;
		$this->domain  = $domain;

	}





	// # ACCOUNT METHODS -----------------------------------------------------------------------------------------------

	/**
	 * Get account details.
	 *
	 * @since  1.3
	 *
	 * @return array|WP_Error
	 */
	public function get_accounts() {

		return $this->make_request( 'accounts' );

	}





	// # CAMPAIGN METHODS ----------------------------------------------------------------------------------------------

	/**
	 * Get individual campaign.
	 *
	 * @since  1.3
	 *
	 * @param string $campaign_id Campaign ID.
	 *
	 * @return array|WP_Error
	 */
	public function get_campaign( $campaign_id = '' ) {

		return $this->make_request( 'campaigns/' . $campaign_id );

	}

	/**
	 * Get campaigns for account.
	 *
	 * @since  1.3
	 *
	 * @return array|WP_Error
	 */
	public function get_campaigns() {

		return $this->make_request( 'campaigns' );

	}





	// # CONTACT METHODS -----------------------------------------------------------------------------------------------

	/**
	 * Create contact.
	 *
	 * @since  1.3
	 *
	 * @param array $contact Contact object.
	 *
	 * @return array|WP_Error
	 */
	public function create_contact( $contact ) {

		return $this->make_request( 'contacts', $contact, 'POST', 202 );

	}

	/**
	 * Get contacts.
	 *
	 * @since  1.3
	 *
	 * @param array $query Search query.
	 *
	 * @return array|WP_Error
	 */
	public function get_contacts( $query ) {

		return $this->make_request( 'contacts', $query );

	}

	/**
	 * Create contact.
	 *
	 * @since  1.3
	 *
	 * @param string $contact_id Contact ID.
	 * @param array  $contact    Contact object.
	 *
	 * @return array|WP_Error
	 */
	public function update_contact( $contact_id, $contact ) {

		return $this->make_request( 'contacts/' . $contact_id, $contact, 'POST' );

	}





	// # CUSTOM FIELDS METHODS -----------------------------------------------------------------------------------------

	/**
	 * Create custom field.
	 *
	 * @since  1.3
	 *
	 * @param array $custom_field Custom field object.
	 *
	 * @return array|WP_Error
	 */
	public function create_custom_field( $custom_field ) {

		return $this->make_request( 'custom-fields', $custom_field, 'POST', 201 );

	}

	/**
	 * Get custom fields for account.
	 *
	 * @since  1.3
	 *
	 * @return array|WP_Error
	 */
	public function get_custom_fields() {

		return $this->make_request( 'custom-fields' );

	}





	// # REQUEST METHODS -----------------------------------------------------------------------------------------------

	/**
	 * Make API request.
	 *
	 * @since  1.3
	 * @access private
	 *
	 * @param string $action        Request action.
	 * @param array  $options       Request options.
	 * @param string $method        HTTP method. Defaults to GET.
	 * @param int    $response_code Expected HTTP response code. Defaults to 200.
	 *
	 * @return array|string|WP_Error
	 */
	private function make_request( $action, $options = array(), $method = 'GET', $response_code = 200 ) {

		// Prepare request URL.
		$request_url = ( $this->domain ? $this->api_url_360 : $this->api_url ) . $action;

		// Add query parameters.
		if ( 'GET' === $method ) {
			$request_url = add_query_arg( $options, $request_url );
		}

		// Build request arguments.
		$args = array(
			'method'    => $method,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			'headers'   => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
				'X-Auth-Token' => 'api-key ' . $this->api_key,
			),
		);

		// Add account domain to request headers.
		if ( $this->domain ) {
			$args['headers']['X-Domain'] = $this->domain;
		}

		// Add body to non-GET requests.
		if ( 'GET' !== $method ) {
			$args['body'] = json_encode( $options );
		}

		// Execute API request.
		$result = wp_remote_request( $request_url, $args );

		// If API request returns a WordPress error, return.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Convert JSON response to array.
		$response = wp_remote_retrieve_body( $result );
		$response = gf_getresponse()->maybe_decode_json( $response );

		// If result response code is not the expected response code, return error.
		if ( wp_remote_retrieve_response_code( $result ) !== $response_code && is_array( $response ) ) {
			return new WP_Error( $response['code'], $response['codeDescription'] );
		}

		return $response;

	}

}
