<?php
/*
Plugin Name: Contact Form 7: Support Deprecated Settings
Plugin URI: http://www.binarytemplar.com
Description: Provide continued support for `on_sent_ok` and `on_submit` within Contact Form 7's Additional Settings
Version: 0.2
Author: Dave McHale
Author URI: http://www.binarytemplar.com
License: GPL2
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include our required class
require_once('classes/cf7-support-deprecated-settings.php');

// Run the filter
add_filter( 'wpcf7_form_response_output', 'CF7_Support_Deprecated_Settings::filter', 10, 4 );
