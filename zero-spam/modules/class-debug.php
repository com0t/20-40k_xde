<?php
/**
 * Debug class.
 *
 * @package ZeroSpam
 */

namespace ZeroSpam\Modules;

// Security Note: Blocks direct access to the plugin PHP files.
defined( 'ABSPATH' ) || die();

/**
 * Debug
 */
class Debug {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Fires after WordPress has finished loading but before any headers are sent.
	 */
	public function init() {
		add_filter( 'zerospam_setting_sections', array( $this, 'sections' ) );
		add_filter( 'zerospam_settings', array( $this, 'settings' ), 10, 2 );
		add_filter( 'zerospam_get_ip', array( $this, 'debug_ip' ), 10, 1 );
	}

	/**
	 * Updates the visitor IP to the debug IP
	 *
	 * @param string $ip IP address.
	 */
	public function debug_ip( $ip ) {
		$debug_ip = \ZeroSpam\Core\Settings::get_settings( 'debug_ip' );

		if (
			'enabled' === \ZeroSpam\Core\Settings::get_settings( 'debug' ) &&
			! empty( $debug_ip )
		) {
			return $debug_ip;
		}

		return $ip;
	}

	/**
	 * Admin setting sections
	 *
	 * @param array $sections Array of admin setting sections.
	 */
	public function sections( $sections ) {
		$sections['debug'] = array(
			'title' => __( 'Debug', 'zerospam' ),
		);

		return $sections;
	}

	/**
	 * Admin settings
	 *
	 * @param array $settings Array of available settings.
	 * @param array $options  Array of saved database options.
	 */
	public function settings( $settings, $options ) {
		$settings['debug'] = array(
			'title'   => __( 'Debug', 'zerospam' ),
			'desc'    => __( 'For troubleshooting site issues.', 'zerospam' ),
			'section' => 'debug',
			'type'    => 'checkbox',
			'options' => array(
				'enabled' => __( 'Enabled', 'zerospam' ),
			),
			'value'   => ! empty( $options['debug'] ) ? $options['debug'] : false,
		);

		$settings['debug_ip'] = array(
			'title'       => __( 'Debug IP', 'zerospam' ),
			'desc'        => wp_kses(
				/* translators: %s: url */
				__( 'Mock a IP address for debugging. <strong>WARNING: This overrides all visitor IP addresses and while enabled could block legit visitors from accessing the site.</strong>', 'zerospam' ),
				array(
					'strong' => array(),
				)
			),
			'section'     => 'debug',
			'type'        => 'text',
			'placeholder' => '127.0.0.1',
			'value'       => ! empty( $options['debug_ip'] ) ? $options['debug_ip'] : false,
		);

		return $settings;
	}
}
