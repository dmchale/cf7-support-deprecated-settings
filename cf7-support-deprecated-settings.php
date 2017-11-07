<?php
/*
Plugin Name: Contact Form 7: Support Deprecated Settings
Plugin URI: http://www.binarytemplar.com
Description: Provide continued support for `on_sent_ok` and `on_submit` within Contact Form 7's Additional Settings
Version: 0.1
Author: Dave McHale
Author URI: http://www.binarytemplar.com
License: GPL2
*/


/**
 * Define the wpcf7_form_response_output callback
 *
 * @param $output
 * @param $class
 * @param $content
 * @param $instance
 *
 * @return string
 */
function cf7sds_filter_wpcf7_form_response_output( $output, $class, $content, $instance ) {

	// Initialize variables
	$settings      = (array) explode( "\n", $instance->additional_settings );        // Grab the "Additional Settings" from the form
	$script_output = '';            // Initialize blank value

	// If there are settings, do some work
	if ( ! empty( $settings ) ) {
		// Form ID of the form being rendered
		$form_id = $instance->id;

		// Sanitize the string, just like CF7 itself does
		$settings = array_map( 'cf7sds_wpcf7_strip_quote', $settings );

		// Handle most of the logic to process the settings found
		$script_output = cf7sds_process_settings( $form_id, $settings );
	}

	// Return the original output, appended with potential output
	return $output . $script_output;

}

add_filter( 'wpcf7_form_response_output', 'cf7sds_filter_wpcf7_form_response_output', 10, 4 );


/**
 * Handle most of the logic to process the settings found
 *
 * @param $form_id
 * @param $settings
 *
 * @return string
 */
function cf7sds_process_settings( $form_id, $settings ) {
	$arr_submit    = array();       // Init
	$arr_mail_sent = array();       // Init

	// Loop through our settings to look for `on_sent_ok` or `on_submit`
	foreach ( $settings as $setting ) {

		// Define what our new Custom DOM Event should be based on what the current setting was
		$dom_event = cf7sds_get_dom_event( $setting );

		// If we have a hit...
		if ( '' != $dom_event ) {

			// Clean up the setting value
			$setting = cf7sds_remove_setting( $setting );

			// Add this setting to an array of settings, so we can output them all at the same time below
			if ( 'wpcf7submit' == $dom_event ) {
				$arr_submit[] = $setting . '\n';
			} elseif ( 'wpcf7mailsent' == $dom_event ) {
				$arr_mail_sent[] = $setting . '\n';
			}

		}

	}

	// Generate our script tags, if we have anything that made it into our arrays
	$script_output = cf7sds_build_script_output( $form_id, $arr_submit, $arr_mail_sent );

	return $script_output;
}

/**
 * Build script tag to append to the form rendering
 *
 * @param $form_id
 * @param $arr_submit
 * @param $arr_mail_sent
 *
 * @return string
 */
function cf7sds_build_script_output( $form_id, $arr_submit, $arr_mail_sent ) {
	$script_output = '';

	if ( ! empty( $arr_mail_sent ) || ! empty( $arr_submit ) ) {
		$script_output .= "<script>alert('mailsent');\nvar wpcf7Elm = document.querySelector( '.wpcf7' );\n";

		if ( ! empty( $arr_mail_sent ) ) {
			$script_output .= cf7sds_write_js_from_array( 'wpcf7mailsent', $form_id, $arr_mail_sent );
		}

		if ( ! empty( $arr_submit ) ) {
			$script_output .= cf7sds_write_js_from_array( 'wpcf7submit', $form_id, $arr_submit );
		}

		$script_output .= "</script>\n";
	}

	return $script_output;

}


/**
 * Write the dom event listener, and make sure it ONLY applies to the current form
 *
 * @param $dom_event
 * @param $form_id
 * @param $arr
 *
 * @return string
 */
function cf7sds_write_js_from_array( $dom_event, $form_id, $arr ) {
	$script_output = "wpcf7Elm.addEventListener( '$dom_event', function( event ) {
                if ( '$form_id' == event.detail.contactFormId ) {
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


/**
 * Find out which of the new DOM events we should use, based on the current custom event
 *
 * @param $setting
 *
 * @return string
 */
function cf7sds_get_dom_event( $setting ) {
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
function cf7sds_remove_setting( $str ) {
	$str = trim( str_replace( 'on_sent_ok:', '', $str ) );
	$str = trim( str_replace( 'on_submit:', '', $str ) );

	preg_match( '/"(.*?)"$/', $str, $match );

	return $match[0];
}


/**
 * Stolen verbatim from CF7 itself (v4.9.1)
 * /includes/formatting.php
 *
 * @param $text
 *
 * @return string
 */
function cf7sds_wpcf7_strip_quote( $text ) {
	$text = trim( $text );

	if ( preg_match( '/^"(.*)"$/s', $text, $matches ) ) {
		$text = $matches[1];
	} elseif ( preg_match( "/^'(.*)'$/s", $text, $matches ) ) {
		$text = $matches[1];
	}

	return $text;
}