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
		private static $form_id = 0;                    // Init
		private static $settings = array();             // Init
		private static $arr_submit = array();           // Init
		private static $arr_mail_sent = array();        // Init
		private static $additional_output = '';


		/**
		 * Static function which is called by the CF7 filter. This starts everything
		 *
		 * @param $output
		 * @param $class
		 * @param $content
		 * @param $instance
		 *
		 * @return string
		 */
		public static function filter( $output, $class, $content, $instance ) {
			// Re-Initialize variables
			self::$additional_output = '';      // Re-initialize this on every call to the filter
			self::$arr_submit = array();        // Re-initialize this on every call to the filter
			self::$arr_mail_sent = array();     // Re-initialize this on every call to the filter

			// Set variables to current values for current filter
			self::$form_id = $instance->id();
			self::$settings = (array) explode( "\n", $instance->prop( 'additional_settings' ) );

			// Handle logic
			self::process_settings();

			// Return the original output, appended with possible additional output
			return $output . self::$additional_output;
		}


		/**
		 * Handle most of the logic to process the settings found
		 *
		 * @return string
		 */
		private static function process_settings() {

			// If there no settings, abort
			if ( empty( self::$settings ) ) {
				return '';
			}

			// Sanitize the strings before we go into the loop
			self::$settings = array_map( 'self::strip_quotes', self::$settings );

			// Call function to prepare our settings arrays
			self::build_settings_arrays();

			// Generate our script tags, if we have anything that made it into our arrays
			self::build_script_output();

		}


		/**
		 * Stolen verbatim from CF7 itself (v4.9.1)
		 * /includes/formatting.php
		 *
		 * @param $text
		 *
		 * @return string
		 */
		private static function strip_quotes( $text ) {
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
		private static function build_settings_arrays() {

			// Loop through our settings to look for `on_sent_ok` or `on_submit`
			foreach ( self::$settings as $setting ) {

				// Define what our new Custom DOM Event should be based on what the current setting was
				$dom_event = self::get_dom_event( $setting );

				// If we have a hit...
				if ( '' != $dom_event ) {

					// Clean up the setting value
					$setting = self::remove_setting( $setting );

					// Add this setting to an array of settings, so we can output them all at the same time below
					if ( 'wpcf7submit' == $dom_event ) {
						self::$arr_submit[] = $setting . '\n';
					} elseif ( 'wpcf7mailsent' == $dom_event ) {
						self::$arr_mail_sent[] = $setting . '\n';
					}

				}

			}

		}


		/**
		 * Build script tag to append to the form rendering
		 *
		 * @return string
		 */
		private static function build_script_output() {

			if ( ! empty( self::$arr_mail_sent ) || ! empty( self::$arr_submit ) ) {
				self::$additional_output .= "<script>alert('mailsent');\nvar wpcf7Elm = document.querySelector( '.wpcf7' );\n";

				if ( ! empty( self::$arr_mail_sent ) ) {
					self::$additional_output .= self::write_js_from_array( 'wpcf7mailsent', self::$arr_mail_sent );
				}

				if ( ! empty( self::$arr_submit ) ) {
					self::$additional_output .= self::write_js_from_array( 'wpcf7submit', self::$arr_submit );
				}

				self::$additional_output .= "</script>\n";
			}

		}


		/**
		 * Find out which of the new DOM events we should use, based on the current custom event
		 *
		 * @param $setting
		 *
		 * @return string
		 */
		private static function get_dom_event( $setting ) {
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
		private static function remove_setting( $str ) {
			$str = trim( str_replace( 'on_sent_ok:', '', $str ) );
			$str = trim( str_replace( 'on_submit:', '', $str ) );

			preg_match( '/"(.*?)"$/', $str, $match );

			return $match[0];
		}


		/**
		 * Write the dom event listener, and make sure it ONLY applies to the current form
		 *
		 * @param $dom_event
		 * @param $arr
		 *
		 * @return string
		 */
		private static function write_js_from_array( $dom_event, $arr ) {
			$script_output = "wpcf7Elm.addEventListener( '" . $dom_event . "', function( event ) {
                if ( '" . self::$form_id . "' == event.detail.contactFormId ) {
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
