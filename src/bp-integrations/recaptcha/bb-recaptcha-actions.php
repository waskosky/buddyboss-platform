<?php
/**
 * Recaptcha integration actions
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_bb_recaptcha_verification_admin_settings', 'bb_recaptcha_verification_admin_settings' );
add_action( 'login_form', 'bb_recaptcha_login', 99 );
add_action( 'lostpassword_form', 'bb_recaptcha_lost_password' );
add_action( 'lostpassword_post', 'bb_recaptcha_validate_lost_password', 10, 1 );
add_action( 'bp_before_registration_submit_buttons', 'bb_recaptcha_registration' );
add_action( 'bp_signup_validate', 'bb_recaptcha_validate_registration' );
add_action( 'bb_before_activate_submit_buttons', 'bb_recaptcha_activate_form' );

/**
 * Handles AJAX request for reCAPTCHA verification in admin settings.
 *
 * @sicne BuddyBoss [BBVERSION]
 */
function bb_recaptcha_verification_admin_settings() {

	$nonce = bb_filter_input_string( INPUT_POST, 'nonce' );
	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bb-recaptcha-verification' ) ) {
		wp_send_json_error(
			array(
				'code'    => 403,
				'message' => esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' ),
			)
		);
	}

	$selected_version = bb_filter_input_string( INPUT_POST, 'selected_version' );
	$site_key         = bb_filter_input_string( INPUT_POST, 'site_key' );
	$secret_key       = bb_filter_input_string( INPUT_POST, 'secret_key' );
	$captcha_response = bb_filter_input_string( INPUT_POST, 'captcha_response' );
	$v2_option        = bb_filter_input_string( INPUT_POST, 'v2_option' );

	// Fetch settings data.
	$settings = bb_recaptcha_options();
	$settings = ! empty( $settings ) ? $settings : array();

	$connection_status = 'not_connected';
	if (
		'recaptcha_v3' === $selected_version ||
		(
			'recaptcha_v2' === $selected_version &&
			'v2_invisible_badge' === $v2_option
		)
	) {
		if ( empty( $captcha_response ) ) {
			$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
		} else {
			$response = bb_get_google_recaptcha_api_response( $secret_key, $captcha_response );
			if ( ! empty( $response ) && $response['success'] ) {
				$connection_status = 'connected';
				$data              = '<img src="' . bb_recaptcha_integration_url( 'assets/images/success.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification was successful', 'buddyboss' ) . '</p>';
			} else {
				$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
			}
		}
	} elseif ( 'recaptcha_v2' === $selected_version ) {
		if ( empty( $captcha_response ) ) {
			$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
		} else {
			$response = bb_get_google_recaptcha_api_response( $secret_key, $captcha_response );
			if ( ! empty( $response ) && $response['success'] ) {
				$connection_status = 'connected';
				$data              = '<img src="' . bb_recaptcha_integration_url( 'assets/images/success.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification was successful', 'buddyboss' ) . '</p>';
			} else {
				$data = '<img src="' . bb_recaptcha_integration_url( 'assets/images/error.png' ) . '" />
					<p>' . __( 'reCAPTCHA verification failed, please try again', 'buddyboss' ) . '</p>';
			}
		}
	}

	// Store verification data.
	$settings['recaptcha_version'] = $selected_version;
	$settings['site_key']          = $site_key;
	$settings['secret_key']        = $secret_key;
	$settings['connection_status'] = $connection_status;
	$settings['v2_option']         = $v2_option;
	bp_update_option( 'bb_recaptcha', $settings );
	if ( 'not_connected' === $connection_status ) {
		wp_send_json_error( $data );
	}
	wp_send_json_success( $data );
	exit();
}

/**
 * Displays the reCAPTCHA on the login form.
 *
 * @sicne BuddyBoss [BBVERSION]
 */
function bb_recaptcha_login() {
	$enable_for_login = bb_recaptcha_is_enabled( 'bb_login' );
	if ( $enable_for_login ) {
		bb_recaptcha_display( 'bb_login' );

		add_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Displays reCAPTCHA on the lost password form if enabled.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_recaptcha_lost_password() {
	$enable_for_lost_password = bb_recaptcha_is_enabled( 'bb_lost_password' );
	if ( $enable_for_lost_password ) {
		bb_recaptcha_display( 'bb_lost_password' );

		add_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Validate recaptcha for lost password form.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_Error $errors A WP_Error object containing any errors generated
 *                         by using invalid credentials.
 */
function bb_recaptcha_validate_lost_password( $errors ) {
	if ( ! bb_recaptcha_is_enabled( 'bb_lost_password' ) ) {
		return $errors;
	}

	$captcha = bb_recaptcha_verification_front();
	if ( is_wp_error( $captcha ) ) {
		$errors->add( 'bb_recaptcha', $captcha->get_error_message() );
	}

	return $errors;
}

/**
 * Displays reCAPTCHA on the registration form if enabled.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_recaptcha_registration() {
	$enable_for_lost_password = bb_recaptcha_is_enabled( 'bb_register' );
	if ( $enable_for_lost_password ) {
		bb_recaptcha_display( 'bb_register' );

		add_action( 'wp_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Validate recaptcha for registration form.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_recaptcha_validate_registration() {
	if ( bb_recaptcha_is_enabled( 'bb_lost_password' ) ) {
		$captcha = bb_recaptcha_verification_front();
		if ( is_wp_error( $captcha ) ) {
			wp_die(
				$captcha->get_error_message(),
				'reCAPTCHA',
				array(
					'response'  => 403,
					'back_link' => 1,
				)
			);
		}
	}
}

/**
 * Displays reCAPTCHA on the activate form if enabled.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_recaptcha_activate_form() {
	$enable_for_bb_activate = bb_recaptcha_is_enabled( 'bb_activate' );
	if ( $enable_for_bb_activate ) {
		bb_recaptcha_display( 'bb_activate' );

		add_action( 'wp_footer', 'bb_recaptcha_add_scripts_login_footer' );
	}
}

/**
 * Enqueue scripts for recaptcha.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_recaptcha_add_scripts_login_footer() {
	wp_enqueue_script( 'bb-recaptcha' );
}
