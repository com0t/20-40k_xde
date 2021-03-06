<?php
/**
 * Formidable class
 *
 * @package ZeroSpam
 */

namespace ZeroSpam\Modules\Formidable;

// Security Note: Blocks direct access to the plugin PHP files.
defined( 'ABSPATH' ) || die();

/**
 * Formidable
 */
class Formidable {
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
		add_filter( 'zerospam_types', array( $this, 'types' ), 10, 1 );

		if (
			'enabled' === \ZeroSpam\Core\Settings::get_settings( 'verify_formidable' ) &&
			\ZeroSpam\Core\Access::process()
		) {
			add_action( 'frm_entry_form', array( $this, 'honeypot' ), 10, 1 );
			add_filter( 'frm_validate_entry', array( $this, 'preprocess_submission' ), 10, 2 );
		}
	}

	/**
	 * Add to the types array
	 *
	 * @param array $types Array of available detection types.
	 */
	public function types( $types ) {
		$types['formidable'] = __( 'Formidable', 'zerospam' );

		return $types;
	}

	/**
	 * Formidable sections
	 *
	 * @param array $sections Array of available setting sections.
	 */
	public function sections( $sections ) {
		$sections['formidable'] = array(
			'title' => __( 'Formidable Integration', 'zerospam' ),
		);

		return $sections;
	}

	/**
	 * Formidable settings
	 *
	 * @param array $settings Array of available settings.
	 * @param array $options  Array of saved database options.
	 */
	public function settings( $settings, $options ) {
		$settings['verify_formidable'] = array(
			'title'       => __( 'Protect Formidable Submissions', 'zerospam' ),
			'section'     => 'formidable',
			'type'        => 'checkbox',
			'options'     => array(
				'enabled' => __( 'Monitor Formidable submissions for malicious or automated spambots.', 'zerospam' ),
			),
			'value'       => ! empty( $options['verify_formidable'] ) ? $options['verify_formidable'] : false,
			'recommended' => 'enabled',
		);

		$message = __( 'Your IP has been flagged as spam/malicious.', 'zerospam' );

		$settings['formidable_spam_message'] = array(
			'title'       => __( 'Spam/Malicious Message', 'zerospam' ),
			'desc'        => __( 'When Formidable protection is enabled, the message displayed to the user when a submission has been detected as spam/malicious.', 'zerospam' ),
			'section'     => 'formidable',
			'type'        => 'text',
			'field_class' => 'large-text',
			'placeholder' => $message,
			'value'       => ! empty( $options['formidable_spam_message'] ) ? $options['formidable_spam_message'] : $message,
			'recommended' => $message,
		);

		$settings['log_blocked_formidable'] = array(
			'title'       => __( 'Log Blocked Formidable Submissions', 'zerospam' ),
			'section'     => 'formidable',
			'type'        => 'checkbox',
			'desc'        => wp_kses(
				__( 'Enables logging blocked Formidable submissions. <strong>Recommended for enhanced protection.</strong>', 'zerospam' ),
				array( 'strong' => array() )
			),
			'options'     => array(
				'enabled' => __( 'Enabled', 'zerospam' ),
			),
			'value'       => ! empty( $options['log_blocked_formidable'] ) ? $options['log_blocked_formidable'] : false,
			'recommended' => 'enabled',
		);

		return $settings;
	}

	/**
	 * Add a 'honeypot' field to the form
	 *
	 * @param array $form_data Form data and settings.
	 */
	public function honeypot( $form_data ) {
		// @codingStandardsIgnoreLine
		echo \ZeroSpam\Core\Utilities::honeypot_field();
	}

	/**
	 * Preprocess submission
	 *
	 * @param array $errors Array of errors.
	 * @param array $values Array of values.
	 */
	public function preprocess_submission( $errors, $values ) {
		// @codingStandardsIgnoreLine
		$post = \ZeroSpam\Core\Utilities::sanitize_array( $_POST );

		// Get the error message.
		$error_message = \ZeroSpam\Core\Utilities::detection_message( 'formidable_spam_message' );

		// Create the details array for logging & sharing data.
		$details = $values;

		$details['type'] = 'formidable';

		// Begin validation checks.
		$validation_errors = array();

		// Check Zero Spam's honeypot field.
		$honeypot_field_name = \ZeroSpam\Core\Utilities::get_honeypot();
		// @codingStandardsIgnoreLine
		if ( isset( $post[ $honeypot_field_name ] ) && ! empty( $post[ $honeypot_field_name ] ) ) {
			// Failed the honeypot check.
			$details['failed'] = 'honeypot';

			$validation_errors[] = 'honeypot';
		}

		if ( ! empty( $validation_errors ) ) {
			// Failed validations, log & send details if enabled.
			foreach ( $validation_errors as $key => $fail ) {
				$details['failed'] = $fail;

				// Log the detection if enabled.
				if ( 'enabled' === \ZeroSpam\Core\Settings::get_settings( 'log_blocked_formidable' ) ) {
					\ZeroSpam\Includes\DB::log( 'formidable', $details );
				}

				// Share the detection if enabled.
				if ( 'enabled' === \ZeroSpam\Core\Settings::get_settings( 'share_data' ) ) {
					do_action( 'zerospam_share_detection', $details );
				}
			}

			$errors['zerospam_honeypot'] = $error_message;
		}

		return $errors;
	}
}
