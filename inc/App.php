<?php
/**
 * Class that initiate the app.
 */

namespace Inc;

use Carbon_Fields\Carbon_Fields;

class App {

	/**
	 * Classes needed to run the app.
	 */
	const CLASSES = array(
		Admin::class,
		FacebookPixelTrigger::class,
		Messenger::class,
	);

	/**
	 * App Runner.
	 */
	public function start() {
		// Init Carbon Fields.
		add_action( 'after_setup_theme', array( Carbon_Fields::class, 'boot' ) );
		// Init Classes
		foreach ( self::CLASSES as $class ) {
			$class = new $class();
			$class->init();
		}
	}
}
