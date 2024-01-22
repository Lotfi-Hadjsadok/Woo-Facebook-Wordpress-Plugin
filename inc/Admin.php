<?php

namespace Inc;

use Carbon_Fields\Container;
use Carbon_Fields\Field;


class Admin {
	public static function init() {
		add_action( 'carbon_fields_register_fields', array( new self(), 'facebook_pixel_fields' ) );
	}


	function facebook_pixel_fields() {
		Container::make( 'theme_options', __( 'Woo Facebook' ) )
			->set_icon( 'dashicons-facebook-alt' )
			->set_page_menu_position( 5 )
			->add_fields(
				array(
					Field::make( 'text', 'fb_page_id', 'Facebook Page ID for Messenger Chat' )
					->set_help_text(
						'
                If set you will get messenger button on your website.<br />
                Click  <a href="https://business.facebook.com/latest/inbox/settings/chat_plugin">HERE</a> to configure your button.
                '
					)
					->set_attribute( 'placeholder', '191654990701581' ),
					Field::make( 'complex', 'fb_pixels', 'Facebook Pixels' )
					->set_collapsed( false )
					->add_fields(
						'fb_pixel_creds',
						array(
							Field::make( 'text', 'pixel_id', 'Pixel ID' )
							->set_required( true )
							->set_width( 30 ),
							Field::make( 'text', 'conversion_token', 'Conversion API token' )
							->set_width( 30 ),
							Field::make( 'text', 'conversion_tester', 'Conversion Tester' )
							->set_width( 30 ),
						)
					),
				)
			);
	}
}
