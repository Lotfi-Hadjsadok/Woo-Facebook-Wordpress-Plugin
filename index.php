<?php
/**
 * Plugin Name: Woo Facebook Pixels & Messenger
 * Description: With this plugin you will be able to create multiple facebook pixels in one website and use the messenger chat functionality.
 * Author: L.Hadjsadok
 * Author URI: https://www.facebook.com/lotfihadjsadok.dev
 * Version : 1.0.1
 */

if ( ! ( defined( 'ABSPATH' ) ) ) {
	exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

use Inc\App;
( new App() )->start();
