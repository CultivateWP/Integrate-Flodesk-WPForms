<?php
/**
 * Flodesk WPForms class.
 *
 * @package Integrate_Flodesk_WPForms
 * @author CultivateWP
 */

/**
 * Class Flodesk WPForms
 *
 * @package Integrate_Flodesk_WPForms
 * @author CultivateWP
 */
class Integrate_Flodesk_WPForms extends WPForms_Provider {

    /**
	 * Holds API connections to Flodesk.
	 *
	 * @since   1.1.0
	 *
	 * @var     array
	 */
	public $api = array();

    /**
     * API URL
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    private $api_url = 'https://api.flodesk.com/v1/';

    /**
     * API Args
     */
    public function api_args( $api_key ) {
        $auth = base64_encode( $api_key . ': ' );
        $args = [
            'headers' => [
				'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Basic ' . $auth,
                'User-Agent' => 'Integrate Flodesk & WPForms (https://cultivatewp.com)',
            ]
        ];
        return $args;
    }

    /**
	 * Holds the Flodesk account URL.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	private $api_key_url = 'https://app.flodesk.com/account/integration/api'; // @phpstan-ignore-line

    /**
     * Holds the Flodesk Register URL
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    private $register_url = 'https://flodesk.com/c/63SZN2'; // @phpstan-ignore-line

    /**
     * Initialize
     *
     * @since 1.0.0
     */
    public function init() {

		$this->version  = INTEGRATE_FLODESK_WPFORMS_VERSION;
		$this->name     = 'Flodesk';
		$this->slug     = 'flodesk';
		$this->priority = 14;
		$this->icon     = plugins_url( 'assets/flodesk-icon.png', INTEGRATE_FLODESK_WPFORMS_FILE );

		if ( is_admin() ) {
			add_filter( "wpforms_providers_provider_settings_formbuilder_display_content_default_screen_{$this->slug}", array( $this, 'builder_settings_default_content' ) );

			// Updater
			require INTEGRATE_FLODESK_WPFORMS_PATH . 'includes/updater/plugin-update-checker.php';
			$myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
				'https://github.com/cultivatewp/integrate-flodesk-wpforms/',
				INTEGRATE_FLODESK_WPFORMS_FILE, //Full path to the main plugin file or functions.php.
				'integrate-flodesk-wpforms'
			);
			$myUpdateChecker->setBranch('master');
		}

    }

	/**
	 * Processes and submits a WPForms Form entry to Flodesk,
	 * based on the Form's settings at Marketing > Flodesk.
	 *
	 * @since   1.5.0
	 *
	 * @param   array $fields    List of fields with their data and settings.
	 * @param   array $entry     Submitted entry values.
	 * @param   array $form_data Form data and settings.
	 * @param   int   $entry_id  Saved entry ID.
	 */
	public function process_entry( $fields, $entry, $form_data, $entry_id = 0 ) {

		// Only run if this form has a connection for this provider.
		if ( empty( $form_data['providers'][ $this->slug ] ) ) {
			return;
		}

        $flodesk_fields = [ 'first_name', 'last_name', 'name', 'email' ];

		// Iterate through each Flodesk connection.  A WPForms Form can have one or more
		// connection to a provider (i.e. Flodesk) configured at Marketing > Flodesk.
		foreach ( $form_data['providers'][ $this->slug ] as $connection ) {

			// Iterate through the WPForms Form field to Flodesk field mappings, to build
			// the API query to subscribe to the Flodesk Form.
			foreach ( $connection['fields'] as $flodesk_field => $wpforms_field ) {
                // Skip if no Flodesk mapping specified for this WPForms Form field.
				if ( empty( $wpforms_field ) ) {
					continue;
				}

				// Fetch the field's value from the WPForms Form entry.
				$value = $this->get_entry_field_value( $wpforms_field, $fields );

                // Handle any forms set to custom fields in Flodesk
                if( str_starts_with( $flodesk_field, "custom_" ) ) {
                    if( empty( $value ) ) continue;
                    if( empty( $body['custom_fields'] ) ) $body['custom_fields'] = [];

                    $property_name = substr( $flodesk_field, 7 );
                    $body['custom_fields'][$property_name] = $value;
                }

				// Depending on the field name, store the value in the $args array.
                if( in_array( $flodesk_field, $flodesk_fields ) ) {
                    $body[ $flodesk_field ] = $value;
                }
			}

            // Skip if no email address field was mapped.
			if ( ! array_key_exists( 'email', $body ) ) {
				continue;
			}

			// Skip if the email address field is blank.
			if ( empty( $body['email'] ) ) {
				continue;
			}

			// Send data to Flodesk to subscribe the email address.
			$providers = get_option( 'wpforms_providers', [] );
			if ( ! empty( $providers[ $this->slug ][ $connection['account_id'] ]['api'] ) ) {
				$args = $this->api_args( $providers[ $this->slug ][ $connection['account_id'] ]['api'] );
				$args['body'] = json_encode( $body );

                $response = wp_remote_post( $this->api_url . 'subscribers', $args );

                // If the API response is an error, log it as an error.
				if ( is_wp_error( $response ) ) {
					wpforms_log(
						'Flodesk',
						sprintf(
							'API Error: %s',
							$response->get_error_message()
						),
						array(
							'type'    => array( 'provider', 'error' ),
							'parent'  => $entry_id,
							'form_id' => $form_data['id'],
						)
					);

					return;
				}

                // Add segments to the user.
                if( ! empty( $connection['groups'] ) ) {
                    $response_array = json_decode( $response['body'] );
                    $subscriber_id = $response_array->id;
                    $this->add_user_to_segments(
                        $providers[ $this->slug ][ $connection['account_id'] ]['api'],
                        $subscriber_id,
                        $connection['groups'],
                        $form_data['id'] );
                }

				// Log successful API response.
				wpforms_log(
					'Flodesk',
					$response,
					array(
						'type'    => array( 'provider', 'log' ),
						'parent'  => $entry_id,
						'form_id' => $form_data['id'],
					)
				);
			}
		}        
    }

    /**
	 * Returns the value for the given field in a WPForms form entry.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $field      Field Name.
	 * @param   array  $fields     Fields and their submitted form values.
	 * @return  bool|string             Field Value
	 */
	private function get_entry_field_value( $field, $fields ) {

		$field = explode( '.', $field );
		$id    = $field[0];

		// Determine the field ID's key that stores the submitted value for this field.
		$key = 'value';
		if ( ! empty( $field[1] ) ) {
			$key = $field[1];
		} elseif ( array_key_exists( 'value_raw', $fields[ $id ] ) ) {
			// Some fields, such as checkboxes, radio buttons and select fields, may
			// have a different value defined vs. the label. Using 'value_raw' will
			// always fetch the value, if "Show Values" is enabled in WPForms,
			// falling back to the label if "Show Values" is disabled.
			$key = 'value_raw';
		}

		// Check if mapped form field has a value.
		if ( empty( $fields[ $id ][ $key ] ) ) {
			return false;
		}

		return $fields[ $id ][ $key ];

	}

    /**
	 * Output fields at WPForms > Settings > Integrations > Flodesk,
	 * allowing the user to enter their Flodesk API Key.
	 *
	 * @since   1.5.0
	 */
	public function integrations_tab_new_form() {

		printf(
			'<input type="text" name="apikey" placeholder="%s">',
			sprintf(
				/* translators: %s - current provider name. */
				esc_html__( '%s API Key', 'integrate-flodesk-wpforms' ),
				$this->name
			)
		);

		printf(
			'<input type="text" name="label" placeholder="%s">',
			sprintf(
				/* translators: %s - current provider name. */
				esc_html__( '%s Account Nickname', 'wpforms-campaign-monitor' ),
				$this->name
			)
		);        
	}

	/**
	 * Default Content
	 */
	public function builder_settings_default_content( $content ) {
		ob_start();
		?>
		<p>
			<a href="<?php echo esc_url( $this->register_url ); ?>" class="wpforms-btn wpforms-btn-md wpforms-btn-orange" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Try Flodesk for Free', 'integrate-flodesk-wpforms' ); ?>
			</a>
		</p>
		<?php

		return $content . ob_get_clean();

	}

    /**
	 * Authenticate with the provider API.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data
	 * @param string $form_id
	 *
	 * @return mixed id or error object
	 */
	public function api_auth( $data = [], $form_id = '' ) {

        $request = wp_remote_get( $this->api_url . 'subscribers', $this->api_args( $data['apikey'] ) );

        if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
            wpforms_log(
				'Flodesk API error',
				wp_remote_retrieve_response_message( $request ),
				[
					'type'    => [ 'provider', 'error' ],
					'form_id' => isset( $form_id['id'] ) ? $form_id['id'] : 'Form ID not set',
				]
			);

			return $this->error( 'API authorization error: ' . wp_remote_retrieve_response_message( $request ) );
        } else {
            $id                              = uniqid();
            $providers                       = get_option( 'wpforms_providers', array() );
            $providers[ $this->slug ][ $id ] = array(
                'api'       => trim( $data['apikey'] ),
                'label'     => sanitize_text_field( $data['label'] ),
                //'client_id' => sanitize_text_field( $data['client_id'] ),
                'date'      => time(),
            );
            update_option( 'wpforms_providers', $providers );
    
            return $id;            
        }

    }

	/**
	 * Retrieve provider account list fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id
	 * @param string $account_id
	 * @param string $list_id
	 *
	 * @return mixed array or error object
	 */
	public function api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {

        $provider_fields = array(
			array(
				'name'       => __( 'Flodesk: Email', 'integrate-flodesk-wpforms' ),
				'field_type' => 'email',
				'req'        => '1',
				'tag'        => 'email',
			),
			array(
				'name'       => __( 'Flodesk: First Name', 'integrate-flodesk-wpforms' ),
				'field_type' => 'text',
				'tag'        => 'first_name',
			),
			array(
				'name'       => __( 'Flodesk: Last Name', 'integrate-flodesk-wpforms' ),
				'field_type' => 'text',
				'tag'        => 'last_name',
			),
		);

        $custom_fields = $this->api_custom_fields( $connection_id, $account_id, $list_id );
        if( ! empty( $custom_fields ) ) {
            foreach( $custom_fields as $custom_field ) {
	            $provider_fields[] = [
		            'name'       => __( "Custom: {$custom_field['label']}", 'integrate-flodesk-wpforms' ),
		            'field_type' => 'text',
		            'tag'        => $custom_field['key']
	            ];
            }
        }

        return $provider_fields;    
	}

    /**
	 * Retrieve provider account lists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id
	 * @param string $account_id
	 *
	 * @return mixed array or WP_Error object.
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
        return [
            [
                'id' => $account_id,
                'name' => 'Your Flodesk Account'
            ]
        ];
    }

    /**
	 * Retrieve provider account list groups.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id
	 * @param string $account_id
	 * @param string $list_id
	 *
	 * @return mixed array or error object.
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		$groupsets = [
			'groupset' => [
				'name'   => "Groupset_{$list_id}",
				'id'     => "ID_{$list_id}",
				'groups' => []
			]
		];
        if( empty( $account_id ) ) return $this->error( 'Problem retrieving groups. No account ID set.' );

        $providers = get_option( 'wpforms_providers', [] );
        $args = $this->api_args( $providers[ $this->slug ][ $account_id ]['api'] );
        $response = wp_remote_get( "{$this->api_url}segments", $args );
        if( is_wp_error( $response ) ) {
            wpforms_log(
                'Flodesk',
                sprintf(
                    'API Error: %s',
                    $response->get_error_message()
                ),
                array(
                    'type'    => array( 'group', 'error' )
                )
            );

            return [];
        }
        if( empty( $response['body'] ) ) return [];
        if( 'string' !== gettype( $response['body'] ) ) return [];
        $group_results = json_decode( $response['body'] );
        if( empty( $group_results->data ) ) return [];
        if( 'array' !== gettype( $group_results->data ) ) return [];

        foreach( $group_results->data as $group_row ) {
	        $groupsets['groupset']['groups'][] = [
		        'id'   => $group_row->id,
		        'name' => $group_row->name
	        ];
        }

        return $groupsets;
	}

	/**
     * Retrieve custom fields for a provider account.
     *
     * @since 1.1.0
     *
	 * @param $connection_id
	 * @param $account_id
	 * @param $list_id
	 *
	 * @return array|WP_Error
	 */
    public function api_custom_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
        $custom_fields = [];
        if( empty( $account_id ) ) return $this->error( 'Problem retrieving custom fields. No account ID set.' );

        $providers = get_option( 'wpforms_providers', [] );
        $args = $this->api_args( $providers[ $this->slug ][ $account_id ]['api'] );
        $response = wp_remote_get( "{$this->api_url}custom-fields", $args );
        if( is_wp_error( $response ) ) {
            wpforms_log(
                'Flodesk',
                sprintf(
                    'API Error: %s',
                    $response->get_error_message()
                ),
                array(
                    'type'    => array( 'custom-fields', 'error' )
                )
            );

            return [];
        }

        if( empty( $response['body'] ) ) return [];
        if( 'string' !== gettype( $response['body'] ) ) return [];
        $custom_field_results = json_decode( $response['body'] );
        if( empty( $custom_field_results->data ) ) return [];
        if( 'array' !== gettype( $custom_field_results->data ) ) return [];

        foreach ( $custom_field_results->data as $custom_field_row ) {
		    $custom_fields[] = [
			    'key'   => "custom_{$custom_field_row->key}",
			    'label' => $custom_field_row->label
		    ];
	    }

        return $custom_fields;
    }

	/**
	 * Add a segment to a user
     *
     * @since 1.1.0
     *
	 * @param string $api_key
	 * @param string $id
	 * @param array $segments
	 * @param string $form_id
	 *
	 * @return void
	 */
    private function add_user_to_segments( $api_key = '', $id = '', $segments = [], $form_id = '' ) {
        if( empty( $api_key ) ) return;
        if( empty( $id ) ) return;
        if( empty( $segments ) ) return;
        if( 'array' !== gettype( $segments ) ) return;

        $api_url = "{$this->api_url}subscribers/{$id}/segments";
        $args = $this->api_args( $api_key );
        $body['segment_ids'] = [];

	    foreach ( $segments as $segment ) {
		    foreach ( $segment as $group_id => $group_name ) {
                $body['segment_ids'][] = $group_id;
		    }
	    }
        $args['body'] = json_encode( $body );

        $response = wp_remote_post( $api_url, $args );

        // If the API response is an error, log it as an error.
        if ( is_wp_error( $response ) ) {
            wpforms_log(
                'Flodesk - Add segments to user',
                sprintf(
                    'API Error: %s',
                    $response->get_error_message()
                ),
                array(
                    'type'    => array( 'provider', 'error' ),
                    'parent'  => $id,
                    'form_id' => $form_id,
                )
            );

            return;
        }
	    // Log successful API response.
	    wpforms_log(
		    'Flodesk',
		    $response,
		    array(
			    'type'    => array( 'provider', 'log' ),
			    'parent'  => $id,
			    'form_id' => $form_id,
		    )
	    );
    }
}
new Integrate_Flodesk_WPForms();