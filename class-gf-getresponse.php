<?php

defined( 'ABSPATH' ) or die();

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms GetResponse Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2020, Rocketgenius
 */
class GFGetResponse extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    GFGetResponse $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the GetResponse Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from getresponse.php
	 */
	protected $_version = GF_GETRESPONSE_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.14.26';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsgetresponse';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsgetresponse/getresponse.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms GetResponse Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'GetResponse';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_getresponse';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_getresponse';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_getresponse_uninstall';

	/**
	 * Defines the capabilities needed for the GetResponse Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_getresponse', 'gravityforms_getresponse_uninstall' );

	/**
	 * Contains an instance of the GetResponse API library, if available.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    GF_GetResponse_API $api If available, contains an instance of the GetResponse API library.
	 */
	protected $api = null;

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return GFGetResponse
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 *
	 * @since  1.1
	 * @access public
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Subscribe contact to GetResponse only when payment is received.', 'gravityformsgetresponse' ),
			)
		);

	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 1.3
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return file_get_contents( $this->get_base_path() . '/images/menu-icon.svg' );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'       => esc_html__( 'GetResponse Account Information', 'gravityformsgetresponse' ),
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'          => 'account_type',
						'label'         => esc_html__( 'Account Type', 'gravityformsgetresponse' ),
						'type'          => 'radio',
						'default_value' => 'standard',
						'onchange'      => "jQuery( this ).parents( 'form' ).submit()",
						'horizontal'    => true,
						'choices'       => array(
							array(
								'label' => esc_html__( 'Standard', 'gravityformsgetresponse' ),
								'value' => 'standard',
							),
							array(
								'label' => esc_html__( 'MAX', 'gravityformsgetresponse' ),
								'value' => '360',
							),
						),
					),
					array(
						'name'              => 'api_key',
						'label'             => esc_html__( 'API Key', 'gravityformsgetresponse' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
					array(
						'name'              => 'domain',
						'label'             => esc_html__( 'Domain', 'gravityformsgetresponse' ),
						'type'              => 'text',
						'class'             => 'medium',
						'dependency'        => array( 'field' => 'account_type', 'values' => array( '360' ) ),
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
					array(
						'name'          => 'max_tld',
						'label'         => esc_html__( 'MAX Endpoint', 'gravityformsgetresponse' ),
						'type'          => 'radio',
						'default_value' => '.com',
						'horizontal'    => true,
						'choices'       => array(
							array(
								'label' => esc_html__( 'Standard (.com)', 'gravityformsgetresponse' ),
								'value' => '.com',
							),
							array(
								'label' => esc_html__( 'Europe (.pl)', 'gravityformsgetresponse' ),
								'value' => '.pl',
							),
						),
						'dependency'    => array( 'field' => 'account_type', 'values' => array( '360' ) ),
					),
					array(
						'type'     => 'save',
						'messages' => array(
							'success' => esc_html__( 'GetResponse settings have been updated.', 'gravityformsgetresponse' ),
						),
					),
				),
			),
		);

	}

	/**
	 * Prepare plugin settings description.
	 *
	 * @since  1.0
	 *
	 * @return string
	 */
	public function plugin_settings_description() {

		// Prepare plugin description.
		$description = sprintf(
			'<p>%s</p>',
			sprintf(
				esc_html__( 'GetResponse makes it easy to send email newsletters to your customers, manage your subscriber lists, and track campaign performance. Use Gravity Forms to collect customer information and automatically add it to your GetResponse subscriber list. If you don\'t have a GetResponse account, you can %1$s sign up for one here.%2$s', 'gravityformsgetresponse' ),
				'<a href="https://www.getresponse.com/" target="_blank">', '</a>'
			)
		);

		// If API is not initialized, add instructions on how to retrieve API key.
		if ( ! $this->initialize_api() ) {

			$description .= sprintf(
				'<p>%s</p>',
				sprintf(
					esc_html__( 'Gravity Forms GetResponse Add-On requires your GetResponse API key, which can be found in the %1$sGetResponse API tab%2$s under your account details.', 'gravityformsgetresponse' ),
					'<a href="https://app.getresponse.com/api" target="_blank">', '</a>'
				)
			);

		}

		return $description;

	}





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the feed edit page.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		return array(
			array(
				'fields' => array(
					array(
						'name'          => 'feed_name',
						'label'         => esc_html__( 'Name', 'gravityformsgetresponse' ),
						'type'          => 'text',
						'class'         => 'medium',
						'required'      => true,
						'default_value' => $this->get_default_feed_name(),
						'tooltip'       => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformsgetresponse' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsgetresponse' )
						),
					),
					array(
						'name'       => 'campaign',
						'label'      => esc_html__( 'GetResponse Campaign', 'gravityformsgetresponse' ),
						'type'       => 'select',
						'required'   => true,
						'choices'    => $this->get_campaigns_for_feed_setting(),
						'onchange'   => "jQuery( this ).parents( 'form' ).submit();",
						'no_choices' => esc_html__( 'Please create a GetResponse Campaign to continue setup.', 'gravityformsgetresponse' ),
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'GetResponse Campaign', 'gravityformsgetresponse' ),
							esc_html__( 'Select which GetResponse campaign this feed will add contacts to.', 'gravityformsgetresponse' )
						),
					),
					array(
						'name'       => 'fields',
						'label'      => esc_html__( 'Map Fields', 'gravityformsgetresponse' ),
						'type'       => 'field_map',
						'dependency' => 'campaign',
						'field_map'  => $this->get_standard_fields_for_field_map(),
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Map Fields', 'gravityformsgetresponse' ),
							esc_html__( 'Select which Gravity Form fields pair with their respective GetResponse field.', 'gravityformsgetresponse' )
						),
					),
					array(
						'name'          => 'custom_fields',
						'label'         => esc_html__( 'Custom Fields', 'gravityformsgetresponse' ),
						'type'          => 'dynamic_field_map',
						'dependency'    => 'campaign',
						'field_map'     => $this->get_custom_fields_for_field_map(),
						'save_callback' => array( $this, 'save_custom_fields' ),
						'tooltip'       => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Custom Fields', 'gravityformsgetresponse' ),
							esc_html__( 'Select or create a new custom GetResponse field to pair with Gravity Forms fields. Custom field names can only contain up to 32 lowercase alphanumeric characters and underscores.', 'gravityformsgetresponse' )
						),
					),
					array(
						'name'           => 'feed_condition',
						'label'          => esc_html__( 'Conditional Logic', 'gravityformsgetresponse' ),
						'type'           => 'feed_condition',
						'dependency'     => 'campaign',
						'checkbox_label' => esc_html__( 'Enable', 'gravityformsgetresponse' ),
						'instructions'   => esc_html__( 'Export to GetResponse if', 'gravityformsgetresponse' ),
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'gravityformsgetresponse' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to GetResponse when the condition is met. When disabled, all form submissions will be exported.', 'gravityformsgetresponse' )
						),
					),
					array(
						'type'       => 'save',
						'dependency' => 'campaign',
					),
				),
			),
		);

	}

	/**
	 * Prepare campaigns for feed field.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function get_campaigns_for_feed_setting() {

		// If GetResponse API instance is not initialized, return choices.
		if ( ! $this->initialize_api() ) {
			return array();
		}

		// Get GetResponse campaigns.
		$campaigns = $this->api->get_campaigns();

		// If campaigns could not be retrieved, return.
		if ( is_wp_error( $campaigns ) || empty( $campaigns ) ) {

			// Log that campaigns could not be retrieved.
			$this->log_error( __METHOD__ . '(): Could not retrieve campaigns;' . ( is_wp_error( $campaigns ) ? ' ' . $campaigns->get_error_message() : '' ) );

			return array();

		}

		// Initialize choices array.
		$choices = array(
			array(
				'label' => esc_html__( 'Select a Campaign', 'gravityformsgetresponse' ),
				'value' => '',
			),
		);

		// Loop through campaigns.
		foreach ( $campaigns as $campaign ) {

			// Add campaign as choice.
			$choices[] = array(
				'label' => esc_html( $campaign['name'] ),
				'value' => esc_attr( $campaign['campaignId'] ),
			);

		}

		return $choices;

	}

	/**
	 * Prepare fields for feed field mapping.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function get_standard_fields_for_field_map() {

		return array(
			array(
				'name'     => 'name',
				'label'    => esc_html__( 'Name', 'gravityformsgetresponse' ),
				'required' => true,
			),
			array(
				'name'       => 'email',
				'label'      => esc_html__( 'Email Address', 'gravityformsgetresponse' ),
				'required'   => true,
				'field_type' => array( 'email' ),
			),
		);

	}

	/**
	 * Renders and initializes a dynamic field map field based on the $field array whose choices are populated by the fields to be mapped.
	 * (Forked to refresh field map.)
	 *
	 * @since  1.3
	 *
	 * @param array $field Field array containing the configuration options of this field.
	 * @param bool  $echo  Determines if field contents should automatically be displayed. Defaults to true.
	 *
	 * @return string
	 */
	public function settings_dynamic_field_map( $field, $echo = true ) {

		// If feed was saved, refresh field map.
		if ( $this->is_save_postback() ) {
			$field['field_map'] = $this->get_custom_fields_for_field_map();
		}

		return parent::settings_dynamic_field_map( $field, $echo );

	}

	/**
	 * Prepare custom fields for feed field mapping.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function get_custom_fields_for_field_map() {

		// Initialize field map array.
		$field_map = array(
			array(
				'label' => esc_html__( 'Select a Custom Field', 'gravityformsgetresponse' ),
				'value' => '',
			),
		);

		// If GetResponse API instance is not initialized, return field map.
		if ( ! $this->initialize_api() ) {
			return $field_map;
		}

		// Get GetResponse custom fields.
		$custom_fields = $this->get_custom_fields();

		// If custom fields could not be retrieved, return.
		if ( is_wp_error( $custom_fields ) ) {

			// Log that custom fields could not be retrieved.
			$this->log_error( __METHOD__ . '(): Could not retrieve custom fields; ' . $custom_fields->get_error_message() );

			return $field_map;

		}

		// Mapped field types.
		$mapped_field_types = array( 'text', 'textarea' );

		// Get mapped fields.
		$mapped_fields = $this->get_setting( 'custom_fields' );

		// If fields are mapped, add currently mapped field types to array.
		if ( $mapped_fields ) {

			// Get the mapped field IDs.
			$field_ids = wp_list_pluck( $mapped_fields, 'key' );
			$field_ids = array_map( function( $key ) { return str_replace( 'custom_', '', $key ); }, $field_ids );

			// Loop through custom fields.
			foreach ( $custom_fields as $custom_field ) {

				// If this is not one of the mapped fields, skip.
				if ( ! in_array( $custom_field['customFieldId'], $field_ids ) ) {
					continue;
				}

				// Add field type.
				if ( ! in_array( $custom_field['type'], $mapped_field_types ) ) {
					$mapped_field_types[] = $custom_field['type'];
				}

			}

		}

		// Loop through custom fields.
		foreach ( $custom_fields as $custom_field ) {

			// Add custom field to field map.
			$field_map[] = array(
				'label' => esc_html( $custom_field['name'] ),
				'value' => 'custom_' . esc_attr( $custom_field['customFieldId'] ),
			);

		}

		return $field_map;

	}

	/**
	 * Create new GetResponse custom fields.
	 *
	 * @since  1.3
	 *
	 * @param array $field       Field settings.
	 * @param array $field_value Field value.
	 *
	 * @return array
	 */
	public function save_custom_fields( $field = array(), $field_value = array() ) {

		global $_gaddon_posted_settings;

		// If API is not initialized, return.
		if ( ! $this->initialize_api() || empty( $field_value ) ) {
			return $field_value;
		}

		// Get existing GetResponse custom fields.
		$custom_fields = $this->get_custom_fields();

		// If custom fields could not be retrieved, return.
		if ( is_wp_error( $custom_fields ) ) {

			// Log that existing fields could not be retrieved.
			$this->log_error( __METHOD__ . '(): Unable to retrieve existing custom fields, not saving new custom fields; ' . $custom_fields->get_error_message() );

			return $field_value;

		}

		// Get existing custom field names.
		$custom_field_names = wp_list_pluck( $custom_fields, 'name' );

		// Loop through custom fields; create new field if using custom key.
		foreach ( $field_value as $i => $custom_field ) {

			// If this is not a new custom field, skip.
			if ( 'gf_custom' !== $custom_field['key'] ) {
				continue;
			}

			// Prepare custom field name.
			$field_name = trim( $custom_field['custom_key'] ); // Set shortcut name to custom key.
			$field_name = str_replace( ' ', '_', $field_name ); // Remove all spaces.
			$field_name = preg_replace( '([^\w\d])', '', $field_name ); // Strip all custom characters.
			$field_name = strtolower( $field_name ); // Set to lowercase.
			$field_name = substr( $field_name, 0, 32 ); // Limit field name to 32 characters.

			// Ensure field name is unique.
			$field_name_i     = 1;
			$start_field_name = $field_name;
			while ( in_array( $field_name, $custom_field_names ) ) {
				$field_name = $start_field_name . $field_name_i;
				$field_name_i++;
			}

			// Prepare custom field object.
			$field_object = array(
				'name'   => $field_name,
				'type'   => 'textarea',
				'hidden' => false,
				'values' => array(),
			);

			// Log field being created.
			$this->log_debug( __METHOD__ . '(): Creating field: ' . print_r( $field_object, true ) );

			// Create custom field.
			$field_object = $this->api->create_custom_field( $field_object );

			// If custom field could not be created, remove from field map.
			if ( is_wp_error( $field_object ) ) {

				// Log that custom field could not be created.
				$this->log_error( __METHOD__ . '(): Unable to create custom field; ' . $field_object->get_error_message() );

				// Remove field.
				unset( $field_value[ $i ], $_gaddon_posted_settings[ $field['name'] ][ $i ] );

				continue;

			}

			// Update field value.
			$field_value[ $i ]['key']        = 'custom_' . $field_object['customFieldId'];
			$field_value[ $i ]['custom_key'] = '';

			// Update posted settings.
			$_gaddon_posted_settings[ $field['name'] ][ $i ]['key']        = 'custom_' . $field_object['customFieldId'];
			$_gaddon_posted_settings[ $field['name'] ][ $i ]['custom_key'] = '';

		}

		GFCache::delete( $this->get_slug() . '_custom_fields_' . $this->get_custom_fields_limit() );

		return $field_value;

	}





	// # FEED LIST -----------------------------------------------------------------------------------------------------

	/**
	 * Set feed creation control.
	 *
	 * @since  1.0
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->initialize_api();

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.1
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {

		return true;

	}

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feed_name' => esc_html__( 'Name', 'gravityformsgetresponse' ),
			'campaign'  => esc_html__( 'GetResponse Campaign', 'gravityformsgetresponse' ),
		);

	}

	/**
	 * Returns the value to be displayed in the campaign name column.
	 *
	 * @since  1.0
	 *
	 * @param array $feed Feed being displayed in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_campaign( $feed ) {

		// Get campaign ID.
		$campaign_id = rgars( $feed, 'meta/campaign' );

		// If GetResponse instance is not initialized, return campaign ID.
		if ( ! $this->initialize_api() ) {
			return $campaign_id;
		}

		// Get campaign.
		$campaign = $this->api->get_campaign( $campaign_id );

		// If campaign could not be retrieved, return campaign ID.
		if ( is_wp_error( $campaign ) ) {

			// Log that could not be found.
			$this->log_error( __METHOD__ . '(): Unable to get campaign for feed list; ' . $campaign->get_error_message() );

			return $campaign_id;

		} else {

			return esc_html( $campaign['name'] );

		}

	}





	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Subscribe the user to the campaign.
	 *
	 * @since  1.0
	 *
	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @return array
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API is not initialized, return error.
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Unable to subscribe user to campaign because API was not initialized.', 'gravityformsgetresponse' ), $feed, $entry, $form );
			return $entry;
		}

		// Initialize contact object.
		$contact = array(
			'name'              => $this->get_field_value( $form, $entry, $feed['meta']['fields_name'] ),
			'email'             => $this->get_field_value( $form, $entry, $feed['meta']['fields_email'] ),
			'campaign'          => array( 'campaignId' => $feed['meta']['campaign'] ),
			'customFieldValues' => array(),
			'ipAddress'         => $this->get_field_value( $form, $entry, 'ip' ),
		);

		// If email address is invalid, return.
		if ( GFCommon::is_invalid_or_empty_email( $contact['email'] ) ) {
			$this->add_feed_error( esc_html__( 'Unable to subscribe user to campaign because an invalid or empty email address was provided.', 'gravityformsgetresponse' ), $feed, $entry, $form );
			return $entry;
		}

		// If no name is provided, return.
		if ( rgblank( $contact['name'] ) ) {
			$this->add_feed_error( esc_html__( 'Unable to subscribe user to campaign because no name was provided.', 'gravityformsgetresponse' ), $feed, $entry, $form );
			return $entry;
		}

		// Set custom field values.
		$contact['customFieldValues'] = $this->prepare_custom_field_values( $feed, $entry, $form );

		// Log that we are checking to see if contact already exists.
		$this->log_debug( __METHOD__ . "(): Checking to see if {$contact['email']} is already on the list." );

		// Get contact.
		$existing_contact = $this->get_contact_by_email( $contact['email'], $contact['campaign']['campaignId'] );

		if ( $existing_contact ) {
			$this->log_debug( __METHOD__ . '(): Found existing contact; updating name, email address, custom fields.' );

			// Set contact ID, custom fields.
			$contact['contactId']         = $existing_contact['contactId'];
			$contact['customFieldValues'] = rgar( $existing_contact, 'customFieldValues' ) && is_array( $existing_contact['customFieldValues'] ) ? array_merge( $existing_contact['customFieldValues'], $contact['customFieldValues'] ) : $contact['customFieldValues'];
		}

		/**
		 * Allows the contact properties to be overridden before they are sent to GetResponse.
		 *
		 * @since 1.4
		 *
		 * @param array      $contact          The contact properties.
		 * @param bool|array $existing_contact False or the existing contact properties.
		 * @param array      $feed             The feed currently being processed.
		 * @param array      $entry            The entry currently being processed.
		 * @param array      $form             The form currently being processed.
		 */
		$contact = gf_apply_filters( array(
			'gform_getresponse_contact',
			$form['id']
		), $contact, $existing_contact, $feed, $entry, $form );

		// If contact exists, updated it. Otherwise, create it.
		if ( $existing_contact ) {

			// Log the contact to be updated.
			$this->log_debug( __METHOD__ . '(): Contact that will be updated => ' . print_r( $contact, true ) );

			// Update contact.
			$updated_contact = $this->api->update_contact( $contact['contactId'], $contact );

			// If contact could not be created, add feed error.
			if ( is_wp_error( $updated_contact ) ) {

				// Log that contact could not be created.
				$this->add_feed_error( sprintf( esc_html__( 'Unable to update existing contact: %s', 'gravityformsgetresponse' ), $updated_contact->get_error_message() ), $feed, $entry, $form );

			} else {

				// Log that contact was created.
				$this->log_debug( __METHOD__ . '(): Contact was created.' );

			}

			return $entry;


		} else {

			// Log the contact to be added.
			$this->log_debug( __METHOD__ . '(): Contact to be added => ' . print_r( $contact, true ) );

			// Add contact.
			$created_contact = $this->api->create_contact( $contact );

			// If contact could not be created, add feed error.
			if ( is_wp_error( $created_contact ) ) {

				// Log that contact could not be created.
				$this->add_feed_error( sprintf( esc_html__( 'Unable to create contact: %s', 'gravityformsgetresponse' ), $created_contact->get_error_message() ), $feed, $entry, $form );

			} else {

				// Log that contact was created.
				$this->log_debug( __METHOD__ . '(): Contact was created.' );

			}

			return $entry;

		}

	}

	/**
	 * Prepare custom field values for contact object.
	 *
	 * @since 1.3
	 *
	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @return array
	 */
	private function prepare_custom_field_values( $feed, $entry, $form ) {

		// Initialize return array.
		$values = array();

		// Get custom fields map.
		$custom_fields_map = self::get_dynamic_field_map_fields( $feed, 'custom_fields' );

		// If no custom fields are mapped, return.
		if ( empty( $custom_fields_map ) ) {
			return $values;
		}

		// Get custom fields.
		$custom_fields = $this->get_custom_fields();

		// If custom fields could not be retrieved, return values array.
		if ( is_wp_error( $custom_fields ) ) {

			// Log that custom fields could not be retrieved.
			$this->add_feed_error( sprintf( 'Unable to get custom fields; %s', $custom_fields->get_error_message() ), $feed, $entry, $form );

			return $values;

		}

		// Update array keys.
		foreach ( $custom_fields as $i => $custom_field ) {
			$custom_fields[ $custom_field['customFieldId'] ] = $custom_field;
			unset( $custom_fields[ $i ] );
		}

		// Loop through custom fields.
		foreach ( $custom_fields_map as $field_name => $field_id ) {

			// If no field is paired to this key, skip it.
			if ( rgblank( $field_name ) || rgblank( $field_id ) ) {
				continue;
			}

			// Get the field value.
			$field_value = $this->get_field_value( $form, $entry, $field_id );

			// If this field value is empty, skip it.
			if ( rgblank( $field_value ) ) {
				continue;
			}

			// Strip "custom_" string from field name, get custom field.
			$custom_field_id = substr( $field_name, 7 );
			$custom_field    = rgar( $custom_fields, $custom_field_id, false );

			// If custom field does not exist, skip.
			if ( ! $custom_field ) {
				continue;
			}

			// Validate field value based on type.
			switch ( $custom_field['type'] ) {

				case 'multi_select':

					$form_field = GFAPI::get_field( $form, $field_id );

					if ( $form_field instanceof GF_Field_MultiSelect ) {
						$field_value = $form_field->to_array( rgar( $entry, $field_id ) );
					} elseif ( ! is_array( $field_value ) ) {
						$field_value = array( $field_value );
					}

					// If choices are not in list of custom field values, skip.
					if ( $invalid = array_diff( $field_value, $custom_field['values'] ) ) {
						$this->log_error( __METHOD__ . '(): Excluding field "' . $custom_field['name'] . '" (' . $custom_field_id . ') from contact because choices (' . implode( ', ', $invalid ) . ') are invalid.' );
						continue 2;
					}

					break;

				case 'checkbox':
				case 'country':
				case 'currency':
				case 'gender':
				case 'radio':
				case 'single_select':

					// If field value is not in list of custom field values, skip.
					if ( ! in_array( $field_value, $custom_field['values'] ) ) {
						$this->log_error( __METHOD__ . '(): Excluding value for field "' . $custom_field['name'] . '" (' . $custom_field_id . ') from contact because value "' . $field_value . '" is invalid.' );
						continue 2;
					}

					break;

				case 'date':
				case 'datetime':

					// Force date format.
					$field_value = date( 'c', strtotime( $field_value ) );

					break;

				case 'ip':

					// If field value is not a valid IP address, skip.
					if ( ! filter_var( $field_value, FILTER_VALIDATE_IP ) ) {
						$this->log_error( __METHOD__ . '(): Excluding value for field "' . $custom_field['name'] . '" (' . $custom_field_id . ') from contact because value "' . $field_value . '" is an invalid IP Address.' );
						continue 2;
					}

					break;

				case 'number':

					// If field value is not numeric, skip.
					if ( ! is_numeric( $field_value ) ) {
						$this->log_error( __METHOD__ . '(): Excluding value for field "' . $custom_field['name'] . '" (' . $custom_field_id . ') from contact because value "' . $field_value . '" is not numeric.' );
						continue 2;
					}

					break;

				case 'phone':

					// Get mapped form field.
					$form_field = GFAPI::get_field( $form, $field_id );

					// If this is not a Phone field or the phone format is not standard, skip.
					if ( ! is_a( $form_field, 'GF_Field_Phone' ) || ( is_a( $form_field, 'GF_Field_Phone' ) && $form_field->phoneFormat !== 'standard' ) ) {
						$this->log_error( __METHOD__ . '(): Excluding value for field "' . $custom_field['name'] . '" (' . $custom_field_id . ') from contact because it is not a Phone field whose format is standard.' );
						continue 2;
					}

					// Reformat field value.
					$field_value = preg_replace( '/[^0-9]/', '', $field_value );
					$field_value = '+1' . $field_value;

					break;

				case 'text':
				case 'textarea':

					// If field value is too long, truncate.
					if ( strlen( $field_value ) > 255 ) {
						$this->log_debug( __METHOD__ . '(): Truncating value for field "' . $custom_field['name'] . '" (' . $custom_field_id . ') because length is more than 255 characters.' );
						$field_value = substr( $field_value, 0, 255 );
					}

					break;

				case 'url':

					// If field value is not a valid URL, skip.
					if ( ! GFCommon::is_valid_url( $field_value ) ) {
						$this->log_error( __METHOD__ . '(): Excluding value for field "' . $custom_field['name'] . '" (' . $custom_field_id . ') from contact because value "' . $field_value . '" is an invalid URL.' );
						continue 2;
					}

					break;

			}

			// Add custom field to contact object.
			$values[] = array(
				'customFieldId' => $custom_field_id,
				'value'         => is_array( $field_value ) ? $field_value : array( $field_value ),
			);

		}

		return $values;

	}




	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes GetResponse API if credentials are valid.
	 *
	 * @since  1.0
	 *
	 * @return bool|null
	 */
	public function initialize_api() {

		// If API object is already setup, return true.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Get the plugin settings.
		$settings = $this->get_plugin_settings();

		// If the API key is empty, return null.
		if ( ! rgar( $settings, 'api_key' ) ) {
			return null;
		}

		// Load the GetResponse API library.
		if ( ! class_exists( 'GF_GetResponse_API' ) ) {
			require_once( 'includes/class-gf-getresponse-api.php' );
		}

		// Log that were testing the API credentials.
		$this->log_debug( __METHOD__ . '(): Validating API credentials.' );

		// Setup a new GetResponse API object.
		$getresponse = new GF_GetResponse_API( $settings['api_key'], rgar( $settings, 'domain' ), rgar( $settings, 'max_tld' ) );

		// Attempt to get account details.
		$accounts = $getresponse->get_accounts();

		if ( is_wp_error( $accounts ) ) {

			// Log that test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; ' . $accounts->get_error_message() );

			return false;

		}

		// Assign the GetResponse API object to this instance.
		$this->api = $getresponse;

		// Log that test passed.
		$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

		return true;

	}

	/**
	 * Find GetResponse contact by email address.
	 *
	 * @since  1.2
	 *
	 * @param string $email       Email address to search for.
	 * @param string $campaign_id Campaign ID to look for the email address in.
	 *
	 * @return array|bool
	 */
	public function get_contact_by_email( $email = '', $campaign_id = '' ) {

		// If API is not initialized, return.
		if ( ! $this->initialize_api() ) {
			return false;
		}

		// Prepare search query.
		$query = array(
			urlencode( 'query[email]' )      => urlencode( $email ),
			urlencode( 'query[campaignId]' ) => urlencode( $campaign_id ),
		);

		// Get contacts.
		$contacts = $this->api->get_contacts( $query );

		// If contacts could not be retrieved, return false.
		if ( is_wp_error( $contacts ) ) {

			// Log that contacts could not be retrieved.
			$this->log_error( __METHOD__ . '(): Unable to get contacts; ' . $contacts->get_error_message() );

			return false;

		}

		// Loop through contacts.
		foreach ( $contacts as $contact ) {

			// If this is the contact we are searching for, return it.
			if ( $email === $contact['email'] ) {
				return $contact;
			}

		}

		return false;

	}

	/**
	 * Gets the GetResponse custom fields.
	 *
	 * @since 1.5
	 *
	 * @return array|WP_Error
	 */
	public function get_custom_fields() {
		$limit     = $this->get_custom_fields_limit();
		$cache_key = $this->get_slug() . '_custom_fields_' . $limit;

		$custom_fields = GFCache::get( $cache_key );

		if ( ! empty( $custom_fields ) ) {
			return $custom_fields;
		}

		$custom_fields = $this->api->get_custom_fields( $limit );

		if ( is_wp_error( $custom_fields ) ) {
			return $custom_fields;
		}

		GFCache::set( $cache_key, $custom_fields, true, HOUR_IN_SECONDS );

		return $custom_fields;
	}

	/**
	 * Gets the maximum number of custom fields which should be retrieved.
	 *
	 * @since 1.5
	 *
	 * @return int
	 */
	public function get_custom_fields_limit() {
		/**
		 * Allows the maximum number of custom fields which are retrieved to be overridden.
		 *
		 * @since 1.5
		 *
		 * @param int $limit The custom fields limit. Defaults to 100.
		 */
		return (int) apply_filters( 'gform_getresponse_limit_pre_get_custom_fields', 100 );
	}



	// # UPGRADES ------------------------------------------------------------------------------------------------------

	/**
	 * Run required routines when upgrading from previous versions of Add-On.
	 *
	 * @since  1.3
	 *
	 * @param string $previous_version Previous version number.
	 */
	public function upgrade( $previous_version ) {

		// Determine if previous version is before API/360 upgrade.
		$previous_is_pre_360 = ! empty( $previous_version ) && version_compare( $previous_version, '1.3', '<' );

		// If previous version is not before the API/360 upgrade, exit.
		if ( ! $previous_is_pre_360 ) {
			return;
		}

		// Get feeds.
		$feeds = $this->get_feeds();

		// If no feeds are configured, exit.
		if ( empty( $feeds ) ) {
			return;
		}

		// If API is not initialize, exit.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to upgrade feeds because API could not be initialized.' );
			return;
		}

		// Get GetResponse custom fields.
		$custom_fields = $this->get_custom_fields();

		// If custom fields could not be retrieved, abort upgrade process.
		if ( is_wp_error( $custom_fields ) ) {

			// Log that custom fields could not be retrieved.
			$this->log_error( __METHOD__ . '(): Could not retrieve custom fields; ' . $custom_fields->get_error_message() );

			return;

		}

		// Loop through feeds, update custom field map.
		foreach ( $feeds as $feed ) {

			// If no custom fields are defined, skip feed.
			if ( ! rgars( $feed, 'meta/custom_fields' ) ) {
				continue;
			}

			// Loop through custom field map, update keys.
			foreach ( $feed['meta']['custom_fields'] as $i => $mapping ) {

				// If mapping is using a custom key, skip.
				if ( 'gf_custom' === rgar( $mapping, 'key' ) ) {
					continue;
				}

				// Loop through custom fields, look for matching key.
				foreach ( $custom_fields as $cf ) {

					// If custom field name does not match key, skip.
					if ( $cf['name'] !== $mapping['key'] ) {
						continue;
					}

					// Update key.
					$feed['meta']['custom_fields'][ $i ]['key'] = 'custom_' . $cf['customFieldId'];

				}

			}

			// Update feed.
			$this->update_feed_meta( $feed['id'], $feed['meta'] );

		}

	}

}
