<?php
/*
Plugin Name: SMTP Mailer Override
Plugin URI: https://proxymis.com/
Description: Overrides wp_mail to use SMTP for sending emails.
Version: 1.0
Author: Proxymis
Author URI: https://www.proxymis.com
*/
if ( ! defined( 'ABSPATH' ) ) exit;

// Add settings page to the WordPress admin menu
add_action('admin_menu', 'smtp_mailer_override_add_admin_menu');
add_action('admin_init', 'smtp_mailer_override_settings_init');

function smtp_mailer_override_add_admin_menu() {
	add_options_page('SMTP Mailer Override', 'SMTP Mailer', 'manage_options', 'smtp-mailer-override', 'smtp_mailer_override_options_page');
}

function smtp_mailer_override_settings_init() {
	// Register SMTP settings
	register_setting('smtp_mailer_override_plugin_settings', 'smtp_mailer_override_smtp_host');
	register_setting('smtp_mailer_override_plugin_settings', 'smtp_mailer_override_smtp_port');
	register_setting('smtp_mailer_override_plugin_settings', 'smtp_mailer_override_smtp_username');
	register_setting('smtp_mailer_override_plugin_settings', 'smtp_mailer_override_smtp_password');
	register_setting('smtp_mailer_override_plugin_settings', 'smtp_mailer_override_smtp_secure');

	// Register logging setting
	register_setting('smtp_mailer_override_plugin_settings', 'smtp_mailer_override_enable_logging');

	// Add sections and fields
	add_settings_section('smtp_mailer_override_plugin_settings_section', 'SMTP Settings', 'smtp_mailer_override_settings_section_callback', 'smtp-mailer-override');
	add_settings_field('smtp_mailer_override_smtp_host', 'SMTP Host', 'smtp_mailer_override_smtp_host_render', 'smtp-mailer-override', 'smtp_mailer_override_plugin_settings_section');
	add_settings_field('smtp_mailer_override_smtp_port', 'SMTP Port', 'smtp_mailer_override_smtp_port_render', 'smtp-mailer-override', 'smtp_mailer_override_plugin_settings_section');
	add_settings_field('smtp_mailer_override_smtp_username', 'SMTP Username', 'smtp_mailer_override_smtp_username_render', 'smtp-mailer-override', 'smtp_mailer_override_plugin_settings_section');
	add_settings_field('smtp_mailer_override_smtp_password', 'SMTP Password', 'smtp_mailer_override_smtp_password_render', 'smtp-mailer-override', 'smtp_mailer_override_plugin_settings_section');
	add_settings_field('smtp_mailer_override_smtp_secure', 'SMTP Secure', 'smtp_mailer_override_smtp_secure_render', 'smtp-mailer-override', 'smtp_mailer_override_plugin_settings_section');
	add_settings_field('smtp_mailer_override_enable_logging', 'Enable Logging', 'smtp_mailer_override_enable_logging_render', 'smtp-mailer-override', 'smtp_mailer_override_plugin_settings_section');

}

function smtp_mailer_override_settings_section_callback() {
	echo 'Enter your SMTP settings below:';
}

function smtp_mailer_override_smtp_host_render() {
	$smtp_host = esc_attr(get_option('smtp_mailer_override_smtp_host'));
	echo "<input required type='text' name='smtp_mailer_override_smtp_host' value='".esc_html($smtp_host)."' />";
}

function smtp_mailer_override_smtp_port_render() {
	$smtp_port = esc_attr(get_option('smtp_mailer_override_smtp_port'));
	echo "<input required type='number' name='smtp_mailer_override_smtp_port' value='".esc_html($smtp_port)."' />";
}

function smtp_mailer_override_smtp_username_render() {
	$smtp_username = esc_attr(get_option('smtp_mailer_override_smtp_username'));
	echo "<input type='text' name='smtp_mailer_override_smtp_username' value='".esc_html($smtp_username)."' />";
}

function smtp_mailer_override_smtp_secure_render() {
	$smtp_secure = esc_attr(get_option('smtp_mailer_override_smtp_secure'));
	echo "<input type='text' name='smtp_mailer_override_smtp_secure' value='".esc_html($smtp_secure)."' />";
}

function smtp_mailer_override_smtp_password_render() {
	$smtp_password = esc_attr(get_option('smtp_mailer_override_smtp_password'));
	echo "<input type='password' name='smtp_mailer_override_smtp_password' value='".esc_html($smtp_password)."' />";
}

function smtp_mailer_override_enable_logging_render() {
	$enable_logging = get_option('smtp_mailer_override_enable_logging');
	echo "<input type='checkbox' name='smtp_mailer_override_enable_logging' value='1' " . esc_html(checked(1, $enable_logging, false)) . " />";
}

function smtp_mailer_override_options_page() {
	?>
	<div class="wrap">
		<h1>SMTP Mailer Override</h1>
		<form method="post" action="options.php">
			<?php
			settings_fields('smtp_mailer_override_plugin_settings');
			do_settings_sections('smtp-mailer-override');
			submit_button();
			?>
		</form>
		<br />
		<h2>Test Email</h2>
		<form method="post" action="">
			<?php wp_nonce_field('smtp_mailer_test_email_nonce', 'smtp_mailer_test_email_nonce');?>
			<input required type="email" name="test_email" placeholder="Enter email address" />
			<input type="submit" name="test_email_submit" value="Send Test Email" class="button-primary" />
		</form>
	</div>
	<?php
}


// Override wp_mail function
add_action('phpmailer_init', 'smtp_mailer_override_configure_smtp');

function smtp_mailer_override_configure_smtp($phpmailer) {
	$smtp_host = get_option('smtp_mailer_override_smtp_host');
	$smtp_port = get_option('smtp_mailer_override_smtp_port');
	$smtp_username = get_option('smtp_mailer_override_smtp_username');
	$smtp_password = get_option('smtp_mailer_override_smtp_password');
	$smtp_secure = get_option('smtp_mailer_override_smtp_secure');

	$phpmailer->isSMTP();
	$phpmailer->Host = $smtp_host;
	$phpmailer->Port = $smtp_port;

	if ($smtp_username && $smtp_password) {
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $smtp_username;
		$phpmailer->Password = $smtp_password;
		$phpmailer->SMTPSecure = $smtp_secure;
	}

	$enable_logging = get_option('smtp_mailer_override_enable_logging');
	if ($enable_logging) {
		ob_start();
		$phpmailer->SMTPDebug = 2;
		$phpmailer->Debugoutput = function($str, $level) {
			echo esc_html($str);
		};
	}
}

// Handle test email submission
add_action('admin_init', 'smtp_mailer_override_test_email');

function smtp_mailer_override_test_email() {
	if (isset($_POST['test_email_submit'])) {
		$test_email = sanitize_email($_POST['test_email']);
		if (is_email($test_email)) {
			if (!isset($_POST['smtp_mailer_test_email_nonce']) || !wp_verify_nonce($_POST['smtp_mailer_test_email_nonce'], 'smtp_mailer_test_email_nonce')) {
				return;
			}
			$subject = 'SMTP Mailer Test Email';
			$message = 'This is a test email sent via SMTP Mailer Override plugin at:'.date('H:i');
			$result = wp_mail($test_email, $subject, $message);
			$res = ob_get_clean();

			if ($result) {
				add_action('admin_notices', function() use ($res) {
					echo '<div class="notice notice-success"><p>Test email sent successfully!</p><p>'.esc_html($res).'</p></div>';
				});
			} else {
				add_action('admin_notices',  function() use ($res) {
					echo '<div class="notice notice-error"><p>Failed to send test email. Please check your SMTP settings.</p><p>'.esc_html($res).'</p></div>';
				});
			}
		} else {
			add_action('admin_notices', function() {
				echo '<div class="notice notice-error"><p>Invalid email address.</p></div>';
			});
		}
	}
}
