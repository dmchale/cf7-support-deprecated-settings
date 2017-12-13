<?php
if ( ! class_exists( 'CF7_Support_Deprecated_Settings' ) ) {

	/**
	 * CF7_Support_Deprecated_Settings class
	 */
	class CF7_Support_Deprecated_Settings {


		/**
		 * Initialize class variables
		 *
		 * @var int
		 */
		private $additional_output;
		private $arr_submit;
		private $arr_mail_sent;
		private $form_id;
		private $settings;


		/**
		 * CF7_Support_Deprecated_Settings constructor.
		 */
		public function __construct() {
			add_filter( 'wpcf7_form_response_output', array( &$this, 'filter' ), 10, 4 );

			// Disable the admin actions that normally handle the Additional Settings
			add_action( 'wpcf7_submit', array( &$this, 'prevent_admin_actions_post' ) );
			add_filter( 'wpcf7_ajax_json_echo', array( &$this, 'prevent_admin_actions_rest' ) );
		}


		/**
		 * Primary function
		 *
		 * @param $output
		 * @param $class
		 * @param $content
		 * @param $instance
		 *
		 * @return string
		 */
		public function filter( $output, $class, $content, $instance ) {
			// Re-Initialize variables
			$this->additional_output = '';      // Re-initialize this on every call to the filter
			$this->arr_submit        = array();        // Re-initialize this on every call to the filter
			$this->arr_mail_sent     = array();     // Re-initialize this on every call to the filter

			// Set variables to current values for current filter
			$this->form_id  = $instance->id();
			$this->settings = (array) explode( "\n", $instance->prop( 'additional_settings' ) );

			// Handle logic
			$this->process_settings();

			// Return the original output, appended with possible additional output
			return $output . $this->additional_output;
		}


		/**
		 * If this plugin is installed, we do NOT want the admin form processing to ALSO run the Additional Settings
		 * Use this function to empty the $result variables as necessary to make sure things don't fire twice
		 *
		 * @param $form
		 * @param $result
		 *
		 * @return mixed
		 */
		public function prevent_admin_actions_post( $form, $result ) {
			unset( $result['scripts_on_sent_ok'] );
			unset( $result['scripts_on_submit'] );

			return $result;
		}


		/**
		 * If this plugin is installed, we do NOT want the admin form processing to ALSO run the Additional Settings
		 * Use this function to empty the $result variables as necessary to make sure things don't fire twice
		 *
		 * @param $response
		 * @param $result
		 *
		 * @return mixed
		 */
		public function prevent_admin_actions_rest( $response, $result ) {
			unset( $response['onSentOk'] );
			unset( $response['onSubmit'] );

			return $response;
		}


		/**
		 * Handle most of the logic to process the settings found
		 *
		 * @return string
		 */
		private function process_settings() {

			// If there no settings, abort
			if ( empty( $this->settings ) ) {
				return '';
			}

			// Sanitize the strings before we go into the loop
			$this->settings = array_map( array( &$this, 'strip_quote' ), $this->settings );

			// Call function to prepare our settings arrays
			$this->build_settings_arrays();

			// Generate our script tags, if we have anything that made it into our arrays
			$this->build_script_output();

		}


		/**
		 * Stolen verbatim from CF7 itself (v4.9.1)
		 * wpcf7_strip_quote() in /includes/formatting.php
		 *
		 * @param $text
		 *
		 * @return string
		 */
		private function strip_quote( $text ) {
			$text = trim( $text );

			if ( preg_match( '/^"(.*)"$/s', $text, $matches ) ) {
				$text = $matches[1];
			} elseif ( preg_match( "/^'(.*)'$/s", $text, $matches ) ) {
				$text = $matches[1];
			}

			return $text;
		}

		/**
		 * Loop through all of our settings, see if they are one of the two we care about
		 * Add them to the appropriate array, if so
		 */
		private function build_settings_arrays() {

			// Loop through our settings to look for `on_sent_ok` or `on_submit`
			foreach ( $this->settings as $setting ) {

				// Define what our new Custom DOM Event should be based on what the current setting was
				$dom_event = $this->get_dom_event( $setting );

				// If we have a hit...
				if ( '' != $dom_event ) {

					// Clean up the setting value
					$setting = $this->remove_setting( $setting );

					// Add this setting to an array of settings, so we can output them all at the same time below
					if ( 'wpcf7submit' == $dom_event ) {
						$this->arr_submit[] = $setting . "\n";
					} elseif ( 'wpcf7mailsent' == $dom_event ) {
						$this->arr_mail_sent[] = $setting . "\n";
					}

				}

			}

		}


		/**
		 * Build script tag to append to the form rendering
		 *
		 * @return string
		 */
		private function build_script_output() {

			if ( ! empty( $this->arr_mail_sent ) || ! empty( $this->arr_submit ) ) {
				$this->additional_output .= "<script>\n";

				if ( ! empty( $this->arr_mail_sent ) ) {
					$this->additional_output .= $this->write_js_from_array( 'wpcf7mailsent', $this->arr_mail_sent );
				}

				if ( ! empty( $this->arr_submit ) ) {
					$this->additional_output .= $this->write_js_from_array( 'wpcf7submit', $this->arr_submit );
				}

				$this->additional_output .= "</script>\n";
			}

		}


		/**
		 * Find out which of the new DOM events we should use, based on the current custom event
		 *
		 * @param $setting
		 *
		 * @return string
		 */
		private function get_dom_event( $setting ) {
			$dom_event = '';

			if ( false !== strpos( $setting, 'on_sent_ok' ) ) {
				$dom_event = 'wpcf7mailsent';
			} elseif ( false !== strpos( $setting, 'on_submit' ) ) {
				$dom_event = 'wpcf7submit';
			}

			return $dom_event;
		}


		/**
		 * Clean up our string. Remove the deprecated action, AND strip the enclosing double-quotes
		 *
		 * @param $str
		 *
		 * @return mixed
		 */
		private function remove_setting( $str ) {
			$str = trim( str_replace( 'on_sent_ok:', '', $str ) );
			$str = trim( str_replace( 'on_submit:', '', $str ) );

			$first_quote = strpos( $str, "\"" );
			$last_quote  = strrpos( $str, "\"" );

			// Ensure we're looking for at least one character
			if ( $first_quote < $last_quote ) {
				$str = substr( $str, $first_quote + 1, $last_quote - 1 );
			}

			return $str;
		}


		/**
		 * Write the dom event listener, and make sure it ONLY applies to the current form
		 *
		 * @param $dom_event
		 * @param $arr
		 *
		 * @return string
		 */
		private function write_js_from_array( $dom_event, $arr ) {
			$script_output = "document.addEventListener( '" . $dom_event . "', function( event ) {
                if ( '" . $this->form_id . "' == event.detail.contactFormId ) {
                    ";

			foreach ( $arr as $setting ) {
				$script_output .= $setting;
			}

			$script_output .= "
                }
			}, false );
            ";

			return $script_output;
		}

	}   // end class

}   // end if
