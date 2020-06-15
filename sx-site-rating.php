<?php
/**
 * Plugin Name: Sx Site Rating
 * Plugin URI: 'https://github.com/manolis-sx/sx-site-rating'
 * Description: A simple rating plugin for your wordpress site
 * Version: 0.9.2
 * Author: Manolis Schizakis
 * Author URI: 'https://schizakis.wordpress.com/'
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
	return;
}

require_once plugin_dir_path(__FILE__) . 'sx-site-rating-widget.php';

//install
function activate_sx_site_ratings() {
	$sdefault_rating  = [0, 0, 0, 0, 0];
	$default_settings = ["voting" => true, "keep" => true, "expire" => 5];
	$ratings          = get_option('sx_ratings', false);
	$settings         = get_option('sx_rating_settings', false);

	if (!$ratings) {
		add_option('sx_ratings', $sdefault_rating);
	}
	if ($settings == array()) {
		add_option('sx_rating_settings', $default_settings);
	}

}
register_activation_hook(__FILE__, 'activate_sx_site_ratings');

//destroy
function deactivate_sx_site_ratings() {
	$settings = get_option('sx_rating_settings', false);
	if (!$settings['keep']) {
		delete_option('sx_ratings');
		delete_option('sx_rating_settings');
		delete_option('sx_reset_rating_settings');
	}

}
register_deactivation_hook(__FILE__, 'deactivate_sx_site_ratings');

// Top Level Menu and submenu
add_action('admin_menu', 'sx_site_rating_menu');
function sx_site_rating_menu() {
	add_menu_page(
		__('Sx Site Rating', 'sx'),
		__('Sx Site Rating', 'sx'),
		'manage_options',
		'sx-rating',
		'',
		'dashicons-star-filled',
		30
	);
	add_submenu_page('sx-rating', 'View Ratings', 'View Ratings', 'manage_options', 'sx-rating', 'sx_rating_view_ratings');
	add_submenu_page('sx-rating', 'Settings', 'Settings', 'manage_options', 'sx-rating-settings', 'sx_rating_settings');
}

// View Ratings Page
function sx_rating_view_ratings() {
	$ratings = get_option('sx_ratings', array()); ?>
	<div class="wrap">
		<h1><?php echo __(get_admin_page_title(), 'sx') ?></h1>
	<?php

	$total_rate   = 0; //sum of star ratings
	$total_number = 0; //number of votes
	$max_number   = 0; //which star has most votes (we draw the bars based on that)
	$mo           = 0; //the average rating

	foreach ($ratings as $rate => $number) {
		$star         = $rate + 1;
		$total_rate   = $total_rate + ($star * $number);
		$total_number = $total_number + $number;
		if ($number > $max_number) {
			$max_number = $number;
		}
	}
	if ($max_number == 0) { // later we divide with $max_number so make sure its not 0
		$max_number = 1;
	}

	if ($total_number != 0) { // if 0 votes, 0 average
		$mo = $total_rate / $total_number;
	}
	?>
    	<div class='sx-rating-info'>
     		<div class="sx-mo"> <?php echo number_format($mo, 1, '.', ''); ?> </div>
      		<div class="sx-star-wrapper">
    <?php

	foreach ($ratings as $rate => $number) {

		if ($rate + 1 <= $mo) {
			echo '<span class="sx-star"><i class="dashicons dashicons-star-filled active"></i></span>';
		} elseif ($rate + 1 - $mo > 0 && $rate + 1 - $mo < 1) { //doto: simplify that
			$p = round(($mo - $rate), 2) * 100;
			echo '<span class="sx-star"><i class="dashicons dashicons-star-filled active" style="width:' . $p . '%;"></i><i class="dashicons dashicons-star-filled inactive"></i></span>';
		} else {
			echo '<span class="sx-star"><i class="dashicons dashicons-star-filled inactive"></i></span>';
		}
	}
	?>
    		</div>
    		<span class="sx-rating-total"><?php echo '<strong>' . $total_number . '</strong> total votes'; ?></span>
    	</div>
    	<div class="sx-rating-bars">
    <?php

	$i = count($ratings);
	while ($i) {
		$p = round(($ratings[$i - 1] / $max_number), 2) * 100;
		echo "<div class='sx-bar-wrapper'>";
		echo "<span class='sx-star-number'>" . $i . "</span>";
		echo "<span class='sx-bar bar-" . $i . "' style='width:" . $p . "%;'>" . $ratings[$i - 1] . "</span>";
		echo "</div>";
		$i--;
	} ?>


    	</div>
  	</div>
<?php
}

// View Settings Page
function sx_rating_settings() {
	// check user capabilities
	if (!current_user_can('manage_options')) {
		return;
	} ?>
	<div class="wrap">
    	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    	<form action="options.php" method="post">
    <?php

	//general settings section
	settings_errors('sx_rating_settings');
	settings_fields('sx_rating_settings');
	do_settings_sections('sx_rating_settings');
	submit_button(__('Save Settings', 'sx'), 'primary large', );
	?>

		</form>
		<form action="options.php" method="post">
	<?php

	//reset rating  section
	settings_errors('sx_reset_rating_settings');
	settings_fields('sx_reset_rating_settings');
	do_settings_sections('sx_reset_rating_settings');
	?>
		</form>
	</div>
<?php
}
function sx_rating_register_settings() {
	//general settings
	register_setting('sx_rating_settings', 'sx_rating_settings', 'sx_rating_settings_validate');
	add_settings_section('general_settings', 'General Settings', '', 'sx_rating_settings');

	add_settings_field('sx_rating_settings_voting', 'Allow Voting:', 'sx_rating_settings_voting', 'sx_rating_settings', 'general_settings');
	add_settings_field('sx_rating_settings_keep', 'Keep Rating after Deactivation:', 'sx_rating_settings_keep', 'sx_rating_settings', 'general_settings');
	add_settings_field('sx_rating_settings_expire', 'Cookie Expire time:', 'sx_rating_settings_expire', 'sx_rating_settings', 'general_settings');
	add_settings_field('sx_rating_settings_reset', 'Reset to Default:', 'sx_rating_settings_reset', 'sx_rating_settings', 'general_settings');

	////rest ratings section
	register_setting('sx_reset_rating_settings', 'sx_reset_rating_settings', 'sx_reset_rating_settings_validate');
	add_settings_section('reset_ratings', 'Reset Ratings', '', 'sx_reset_rating_settings');
	add_settings_field('sx_rating_reset_ratings', 'Reset Ratings:', 'sx_rating_reset_ratings', 'sx_reset_rating_settings', 'reset_ratings');
	add_settings_field('sx_rating_reset_message', '', 'sx_rating_reset_message', 'sx_reset_rating_settings', 'reset_ratings');
}
add_action('admin_init', 'sx_rating_register_settings');

//validate settings and save settings
function sx_rating_settings_validate($input) {
	if (isset($_POST['reset'])) {
		add_settings_error('sx_rating_settings', 'sx_rating_settings', __('Your settings have been changed to default.', 'sx'), 'updated');
		$newinput['voting'] = true;
		$newinput['keep']   = true;
		$newinput['expire'] = 5;
		return $newinput;
	}
	$newinput['voting'] = (!empty($input['voting']) && $input['voting'] == 'yes') ? true : false;
	$newinput['keep']   = (!empty($input['keep']) && $input['keep'] == 'yes') ? true : false;

	$expire_allowed = [0, 1, 5]; //
	if (in_array((int)$input['expire'], $expire_allowed)) {
		$newinput['expire'] = (int)$input['expire'];
	} else {
		$newinput['expire'] = 5;
	}
	add_settings_error('sx_rating_settings', 'sx_rating_settings', __('Your settings have been saved.', 'sx'), 'updated');
	return $newinput;
}

function sx_rating_settings_voting() {
	$options = get_option('sx_rating_settings');
	$checked = "";

	(isset($options['voting']) && $options['voting']) ? $checked = 'checked' : $checked = '';
	echo "<input id='sx_rating_settings_voting' name='sx_rating_settings[voting]' type='checkbox' value='yes' $checked />";
	echo "<span><em> Uncheck this if you want to disable voting for all widgets added.</em></span>";
}

function sx_rating_settings_keep() {
	$options = get_option('sx_rating_settings');
	$checked = "";

	(isset($options['keep']) && $options['keep']) ? $checked = 'checked' : $checked = '';
	echo "<input id='sx_rating_settings_keep' name='sx_rating_settings[keep]' type='checkbox' value='yes' $checked />";
	echo "<span><em> By default when deactivating or uninstalling the plugin the rating will be kept and restored if the plugin is activated again. Uncheck this box if you want to permantly remove all data.</em></span>";
}

function sx_rating_settings_expire() {
	$options = get_option('sx_rating_settings');
	$v       = $options['expire'];

	echo "<select id='dbi_plugin_setting_start_date' name='sx_rating_settings[expire]' >";
	echo "<option value='5'" . (($v == 5) ? 'selected' : '') . ">Five years (default) </option>";
	echo "<option value='1'" . (($v == 1) ? 'selected' : '') . ">One year</option>";
	echo "<option value='0'" . (($v == 0) ? 'selected' : '') . ">End of session</option>";
	echo "</select>";
	echo "<span><em> Every user's rating is saved in a cookie. When this cookie expires or is deleted the user can vote again. Before that, he/she can only change the rate. Default is five years. Choosing 'End of session' means that the user can rate your site again on next visit after his/hers browser is restated.</em></span>";
}
function sx_rating_settings_reset() {
	//reset
	submit_button(__('Reset Settings', 'sx'), 'secondary', 'reset', false);
}

function sx_reset_rating_settings_validate() {
	if (isset($_POST['reset-message'])) { //maybe check $input[]?
		$message=sanitize_text_field( $_POST['reset-message'] );
		add_settings_error('sx_reset_rating_settings', 'sx_reset_rating_settings', $message, 'updated');
	}
	return;
}

function sx_rating_reset_ratings() {
	submit_button(__('Reset Ratings', 'sx'), 'primary large', 'reset-ratings', false, array('id' => 'sx-reset-ratings-button'));
	echo "<span><em> <strong>You cant undo this</strong>. Clicking the button will reset your rating and delete all votes. </em></span>";
}
function sx_rating_reset_message() {
	echo '<input type="hidden" id="reset-message" name="reset-message" value="">';
}

//ajax call to reset settings
function sx_reset_rating() {
	check_ajax_referer('sx_rating_admin', '_wpnonce', true);

	$result  = array('success' => 1, 'message' => '');
	$ratings = get_option('sx_ratings', array());
	if ($ratings == array()) {
		$result['success'] = 0;
		$result['message'] = __('Something went wrong.', 'sx');
	} else {
		$ratings = [0, 0, 0, 0, 0];
		$r       = update_option('sx_ratings', $ratings);
		if ($r) {
			$result['message'] = __('Your ratings have been reset', 'sx');
		} else {
			$result['success'] = 0;
			$result['message'] = __('Something went wrong.', 'sx');
		}
	}
	echo json_encode($result);
	wp_die();
}
add_action('wp_ajax_sx_reset_rating', 'sx_reset_rating');

//load script and styles
function sx_rating_load_plugin_assets() {

	wp_enqueue_style('sx-site-rating-css', plugin_dir_url(__FILE__) . 'assets/css/sx-site-rating.css', array(), '', 'screen');
	wp_register_script('sx-site-rating-js', plugin_dir_url(__FILE__) . 'assets/js/sx-site-rating.js', array('jquery'), '', true);
	wp_localize_script('sx-site-rating-js', 'sx_rating_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('sx_rating'),
		'text'     => array(
			'choose_rate'  => __('Please choose a rate', 'sx'),
			'submitting'   => __('Submitting...', 'sx'),
			'submit'       => __('Submit', 'sx'),
		),
	));
	wp_enqueue_script('sx-site-rating-js');
	wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'sx_rating_load_plugin_assets');

//load script for admin settings
function  sx_rating_load_admin_assets($hook) {
	wp_enqueue_style('sx-site-rating-css', plugin_dir_url(__FILE__) . 'assets/css/sx-site-rating.css', array(), '', 'screen');
	if ('sx-site-rating_page_sx-rating-settings' != $hook) {
		return;
	}
	wp_register_script('sx-site-rating-admin-js', plugin_dir_url(__FILE__) . 'assets/js/sx-site-rating-admin.js', array('jquery'), '', true);
	wp_localize_script('sx-site-rating-admin-js', 'sx_rating_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('sx_rating_admin'),
		'text'     => array(
			'question' => __('Are you sure you want to reset rating? You cannot undo this!', 'sx'),
		),
	));
	wp_enqueue_script('sx-site-rating-admin-js');
}
if (is_admin()) {
	add_action('admin_enqueue_scripts', 'sx_rating_load_admin_assets');
}
