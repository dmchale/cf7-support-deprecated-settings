# Contact Form 7: Support Deprecated Settings

[![Code Climate](https://api.codeclimate.com/v1/badges/27f6d9f01370338430cc/maintainability.svg)](cf7-support-deprecated-settings) [![Codacy Badge](https://api.codacy.com/project/badge/Grade/4864f5cb70f340a9b04068454b6b39c7)](https://www.codacy.com/app/dmchale/cf7-support-deprecated-settings?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=dmchale/cf7-support-deprecated-settings&amp;utm_campaign=Badge_Grade)

Provide continued support for `on_sent_ok` and `on_submit` within Contact Form 7's Additional Settings

Per the developer, Contact Form 7 will no longer support the `on_sent_ok` and `on_submit` settings available on the `Additional Settings` screen. Both settings are currently deprecated, with plans to be completely unsupported before the end of 2017. https://contactform7.com/additional-settings/

This plugin is meant to be a band-aid to quickly get your `Additional Settings` working. It reads the `Additional Settings` data and adds a javascript block when the form is output on the page, using DOM Events as suggested by the CF7 author. https://contactform7.com/dom-events/

## Installation
1. Install to WordPress plugins as normal and activate.

## Usage
1. Basic usage of the plugin requires no configuration.

## Credits
Authored by Dave McHale

## License
As with all WordPress projects, this plugin is released under the GPL 
