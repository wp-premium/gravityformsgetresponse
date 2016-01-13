<?php

GFForms::include_feed_addon_framework();

class GFGetResponse extends GFFeedAddOn {

	protected $_version = GF_GETRESPONSE_VERSION;
	protected $_min_gravityforms_version = '1.9.5.1';
	protected $_slug = 'gravityformsgetresponse';
	protected $_path = 'gravityformsgetresponse/getresponse.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms GetResponse Add-On';
	protected $_short_title = 'GetResponse';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_getresponse', 'gravityforms_getresponse_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_getresponse';
	protected $_capabilities_form_settings = 'gravityforms_getresponse';
	protected $_capabilities_uninstall = 'gravityforms_getresponse_uninstall';
	protected $_enable_rg_autoupgrade = true;
	
	protected $api = null;
	private static $_instance = null;

	public static function get_instance() {
		
		if ( self::$_instance == null )
			self::$_instance = new GFGetResponse();

		return self::$_instance;
		
	}

	/* Settings Page */
	public function plugin_settings_fields() {
		
		return array(
			array(
				'title'       => __( 'GetResponse Account Information', 'gravityformsgetresponse' ),
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'api_key',
						'label'             => __( 'GetResponse API Key', 'gravityformsgetresponse' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => __( 'GetResponse settings have been updated.', 'gravityformsgetresponse' )
						),
					),
				),
			),
		);
		
	}

	/* Prepare plugin settings description */
	public function plugin_settings_description() {
		
		$description  = '<p>';
		$description .= sprintf(
			__( 'GetResponse makes it easy to send email newsletters to your customers, manage your subscriber lists, and track campaign performance. Use Gravity Forms to collect customer information and automatically add them to your GetResponse subscriber list. If you don\'t have a GetResponse account, you can %1$s sign up for one here.%2$s', 'gravityformsgetresponse' ),
			'<a href="http://www.getresponse.com/" target="_blank">', '</a>'
		);
		$description .= '</p>';
		
		if ( ! $this->initialize_api() ) {
			
			$description .= '<p>';
			$description .= sprintf(
				__( 'Gravity Forms GetResponse Add-On requires your GetResponse API key, which can be found in the %1$sGetResponse API tab%2$s under your account details.', 'gravityformsgetresponse' ),
				'<a href="https://app.getresponse.com/account.html#api" target="_blank">', '</a>'
			);
			
			$description .= '</p>';
			
		}
				
		return $description;
		
	}

	/* Setup feed settings fields */
	public function feed_settings_fields() {	        

		$settings = array(
			array(
				'title' =>	'',
				'fields' =>	array(
					array(
						'name'           => 'feed_name',
						'label'          => __( 'Name', 'gravityformsgetresponse' ),
						'type'           => 'text',
						'required'       => true,
						'tooltip'        => '<h6>'. __( 'Name', 'gravityformsgetresponse' ) .'</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformsgetresponse' )
					),
					array(
						'name'           => 'campaign',
						'label'          => __( 'GetResponse Campaign', 'gravityformsgetresponse' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->campaigns_for_feed_setting(),
						'tooltip'        => '<h6>'. __( 'GetResponse Campaign', 'gravityformsgetresponse' ) .'</h6>' . __( 'Select which GetResponse campaign this feed will add contacts to.', 'gravityformsgetresponse' )
					),
					array(
						'name'           => 'fields',
						'label'          => __( 'Map Fields', 'gravityformsgetresponse' ),
						'type'           => 'field_map',
						'field_map'      => $this->fields_for_feed_mapping(),
						'tooltip'        => '<h6>'. __( 'Map Fields', 'gravityformsgetresponse' ) .'</h6>' . __( 'Select which Gravity Form fields pair with their respective GetResponse field.', 'gravityformsgetresponse' )
					),
					array(
						'name'           => 'custom_fields',
						'label'          => __( 'Custom Fields', 'gravityformsgetresponse' ),
						'type'           => 'dynamic_field_map',
						'field_map'      => $this->custom_fields_for_feed_mapping(),
						'tooltip'        => '<h6>'. __( 'Custom Fields', 'gravityformsgetresponse' ) .'</h6>' . __( 'Select or create a new custom GetResponse field to pair with Gravity Forms fields. Custom field names can only contain up to 32 lowercase alphanumeric characters and underscores.', 'gravityformsgetresponse' )
					),
					array(
						'name'           => 'feed_condition',
						'label'          => __( 'Opt-In Condition', 'gravityformsgetresponse' ),
						'type'           => 'feed_condition',
						'checkbox_label' => __( 'Enable', 'gravityformsgetresponse' ),
						'instructions'   => __( 'Export to GetResponse if', 'gravityformsgetresponse' ),
						'tooltip'        => '<h6>'. __( 'Opt-In Condition', 'gravityformsgetresponse' ) .'</h6>' . __( 'When the opt-in condition is enabled, form submissions will only be exported to GetResponse when the condition is met. When disabled, all form submissions will be exported.', 'gravityformsgetresponse' )

					)
				)
			)
		);

		return $settings;
	
	}
	
	/* Prepare campaigns for feed field */
	public function campaigns_for_feed_setting() {
			
		/* If GetResponse API instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() )
			return array();
		
		/* Setup choices array */
		$choices = array();
		
		/* Get the campaigns */
		$campaigns = $this->api->getCampaigns();
		
		/* Add campaigns to the choices array */
		if ( ! empty( $campaigns ) ) {
			
			foreach ( $campaigns as $campaign_id => $campaign ) {
				
				$choices[] = array(
					'label'		=>	$campaign->name,
					'value'		=>	$campaign_id
				);
				
			}
			
		}
		
		return $choices;
		
	}

	/* Prepare fields for feed field mapping */
	public function fields_for_feed_mapping() {
		
		/* Setup initial field map */
		$field_map = array(
			array(	
				'name'       => 'name',
				'label'      => __( 'Name', 'gravityformsgetresponse' ),
				'required'   => false
			),
			array(	
				'name'       => 'email',
				'label'      => __( 'Email Address', 'gravityformsgetresponse' ),
				'required'   => true,
				'field_type' => array( 'email' )
			)
		);
		
		return $field_map;
		
	}

	/* Prepare custom fields for feed field mapping */
	public function custom_fields_for_feed_mapping() {
		
		/* Setup initial field map */
		$field_map = array();
		
		/* If GetResponse instance is not initialized, return initial field map. */
		if ( ! $this->initialize_api() )
			return $field_map;
		
		/* Get GetResponse account's custom fields and add to field map array */
		$custom_fields = $this->get_custom_fields();
		
		/* If custom fields exist, add them to the field map. */
		if ( ! empty( $custom_fields ) ) {
			
			foreach ( $custom_fields as $custom_field ) {
				
				$field_map[] = array(	
					'name'     => 'custom_'. $custom_field->name,
					'label'    => $custom_field->name,
				);
				
			}
			
		}
		
		return $field_map;
		
	}
	
	/* Setup feed list columns */
	public function feed_list_columns() {
		
		return array(
			'feed_name' => 'Name',
			'campaign'  => 'GetResponse Campaign'
		);
		
	}
	
	/* Change value of campaign feed column to campaign name */
	public function get_column_value_campaign( $item ) {
			
		/* If GetResponse instance is not initialized, return campaign ID. */
		if ( ! $this->initialize_api() )
			return $item['meta']['campaign'];
		
		/* Get campaign and return name */
		$campaign = $this->api->getCampaignByID( $item['meta']['campaign'] );		
		return isset( $campaign->{$item['meta']['campaign']} ) ? $campaign->{$item['meta']['campaign']}->name : $item['meta']['campaign'] ;
		
	}

	/* Hide "Add New" feed button if API credentials are invalid */		
	public function feed_list_title() {
		
		if ( $this->initialize_api() )
			return parent::feed_list_title();
			
		return sprintf( __( '%s Feeds', 'gravityforms' ), $this->get_short_title() );
		
	}

	/* Notify user to configure add-on before setting up feeds */
	public function feed_list_message() {

		$message = parent::feed_list_message();
		
		if ( $message !== false )
			return $message;

		if ( ! $this->initialize_api() )
			return $this->configure_addon_message();

		return false;
		
	}
	
	/* Feed list message for user to configure add-on */
	public function configure_addon_message() {
		
		$settings_label = sprintf( __( '%s Settings', 'gravityforms' ), $this->get_short_title() );
		$settings_link  = sprintf( '<a href="%s">%s</a>', esc_url( $this->get_plugin_settings_url() ), $settings_label );

		return sprintf( __( 'To get started, please configure your %s.', 'gravityformsgetresponse' ), $settings_link );
		
	}

	/* Process feed */
	public function process_feed( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Processing feed.' );
		
		/* If GetResponse instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			
			$this->log_debug( __METHOD__ . '(): Failed to set up the API.' );
			return;
			
		}
		
		/* Prepare new contact array */
		$contact = array(
			'name'          => $this->get_field_value( $form, $entry, $feed['meta']['fields_name'] ),
			'email'         => $this->get_field_value( $form, $entry, $feed['meta']['fields_email'] ),
			'custom_fields' => array()
		);

		/* If email address is empty, exit. */
		if ( rgblank( $contact['email'] ) ) {
			
			$this->log_error( __METHOD__ . '(): Email address not provided.' );
			return;			
			
		}
				
		/* Find any custom fields mapped and push them to the new contact array. */
		if ( ! empty( $feed['meta']['custom_fields'] ) ) {
			
			foreach ( $feed['meta']['custom_fields'] as $custom_field ) {
				
				/* If no field is paired to this key, skip field. */
				if ( rgblank( $custom_field['value'] ) )
					continue;
										
				/* Get the field value. */
				$field_value = $this->get_field_value( $form, $entry, $custom_field['value'] );
				
				/* If this field is empty, skip field. */
				if ( rgblank( $field_value ) )
					continue;
				
				/* Get the custom field name */
				if ( $custom_field['key'] == 'gf_custom' ) {
					
					$custom_field_name = trim( $custom_field['custom_key'] ); // Set shortcut name to custom key
					$custom_field_name = str_replace( ' ', '_', $custom_field_name ); // Remove all spaces
					$custom_field_name = preg_replace( '([^\w\d])', '', $custom_field_name ); // Strip all custom characters
					$custom_field_name = strtolower( $custom_field_name ); // Set to lowercase
					$custom_field_name = substr( $custom_field_name, 0, 32 );
					
				} else {
					
					$custom_field_name = $custom_field['key'];
					
				}
				
				/* Trim field value to max length. */
				$field_value = substr( $field_value, 0, 255 );
				
				$contact['custom_fields'][$custom_field_name] = $field_value;
				
			}
			
		}

		/* Check if email address is already on this campaign list. */
		$this->log_debug( __METHOD__ . "(): Checking to see if {$contact['email']} is already on the list." );
		$email_in_campaign = get_object_vars( $this->api->getContactsByEmail( $contact['email'], array( $feed['meta']['campaign'] ), 'CONTAINS' ) );
		
		/* If email address is not in campaign, add. Otherwise, update. */
		if ( empty( $email_in_campaign ) ) {
			
			$add_contact_response = $this->api->addContact( $feed['meta']['campaign'], $contact['name'], $contact['email'], 'standard', 0, $contact['custom_fields'] );
							
			if ( is_null( $add_contact_response ) ) {
				
				$this->log_debug( __METHOD__ . "(): {$contact['email']} is on campaign list, but unconfirmed." );
				return;
				
			} else {
				
				$this->log_debug( __METHOD__ . "(): {$contact['email']} is not on campaign list; added info." );
				return;
				
			}
						
		} else {
			
			$this->log_debug( __METHOD__ . "(): {$contact['email']} is already on campaign list; updating info." );
			
			$contact_id = key( $email_in_campaign );
			
			if ( ! empty( $contact['name'] ) ) {
				
				$contact_name_response = $this->api->setContactName( $contact_id, $contact['name'] );
			
				if ( isset( $contact_name_response->updated ) )
					$this->log_debug( __METHOD__ . "(): Name for {$contact['email']} have been updated." );
	
			}
			
			if ( ! empty( $contact['custom_fields'] ) ) {
				
				$contact_customs_response = $this->api->setContactCustoms( $contact_id, $contact['custom_fields'] );
			
				if ( isset( $contact_customs_response->updated ) )
					$this->log_debug( __METHOD__ . "(): Custom fields for {$contact['email']} have been updated." );
				
			}
			
		}
							
	}
		
	/* Checks validity of GetResponse API key and initializes API if valid. */
	public function initialize_api() {

		if ( ! is_null( $this->api ) )
			return true;

		/* Load the GetResponse API library. Class has been modified to make some functions public. */
		require_once 'api/GetResponseAPI.class.php';
		
		/* Get the plugin settings */
		$settings = $this->get_plugin_settings();
		
		/* If the API key is empty, return null. */
		if ( rgblank( $settings['api_key'] ) )
			return null;
			
		$this->log_debug( __METHOD__ . "(): Validating login for API Info for key {$settings['api_key']}." );

		$getresponse = new GetResponse( $settings['api_key'] );
		
		/* Run authentication test request. */
		if ( $getresponse->ping() == 'pong' ) {
			
			/* Log that test passed. */
			$this->log_debug( __METHOD__ . '(): API Key is valid.' );
			
			/* Assign GetResponse object to the class. */
			$this->api = $getresponse;
			
			return true;
			
		} else {
			
			$this->log_error( __METHOD__ . '(): Invalid API Key.' );
			return false;			
			
		}
					
	}
	
	/* GetResponse: Get Custom Fields */
	public function get_custom_fields() {
			
		/* If GetResponse instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() )
			return array();
		
		return $this->api->execute( $this->api->prepRequest( 'get_account_customs' ) );
		
	}
	
}