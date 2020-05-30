<?php
if (!defined('ABSPATH')) {
	return;
}

include_once  plugin_dir_path(__FILE__) .'incl/rgb_hsl_converter.inc.php'; //for color transformation and hex validation

class Sx_Site_Rating_Widget extends WP_Widget {
	private $__ratings; // the ratings option from db
	private $__settings; // the settings from db
	private $__total_rate; //sum of star ratings
	private $__total_number; //number of votes
	private $__mo; //the average rating
	private $__user_rate; //loaded rating from the cookie
	private $__defaults; //default values for each instance

	public function __construct() {
		$this->__defaults = array(
			'title'           => '',
			'star_color'      => '#009688',
			'highlight_color' => '#34C8BA',
			'inactive_color'  => '#cccccc',
			'show_stars'      => true,
			'show_votes'      => true,
			'show_mo'         => true,
			'show_voting'     => true,
			'centered'        => true,
		);

		$widget_options = array(
			'classname'   => 'Sx_Site_Rating_Widget',
			'description' => 'Display the average rating and an option to rate the site',
		);

		$this->__calculate();

		parent::__construct('Sx_Site_Rating_Widget', 'Sx Site Rating', $widget_options);

		//ajax calls when submiting a rating through the widget
		add_action('wp_ajax_sx_submit_rating', array($this, 'sx_submit_rating'));
		add_action('wp_ajax_nopriv_sx_submit_rating', array($this, 'sx_submit_rating'));
		// prints a script for the colorpicker to work in widget settings
		add_action('admin_footer-widgets.php', array($this, 'print_scripts'), 9999);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget($args, $instance) {
		//$ratings = get_option('sx_ratings', array());
		$title = apply_filters('widget_title', $instance['title']);
		echo $args['before_widget'];
		$id = $this->id;
		?>

		<div id="<?php echo $id . '-scoped'; ?>">
			<!-- sx-widget-style -->
			<style scoped>
		    <?php echo '#' . $id . '-scoped'; ?>{
					--sx-rating-star-color:<?php echo ($instance['star_color'] == $this->__defaults['star_color'] ? 'inherit' : $instance['star_color']); ?>;
					--sx-rating-highlight-color:<?php echo ($instance['highlight_color'] == $this->__defaults['highlight_color'] ? 'inherit' : $instance['highlight_color']); ?>;
					--sx-rating-inactive-color:<?php echo ($instance['inactive_color'] == $this->__defaults['inactive_color'] ? 'inherit' : $instance['inactive_color']); ?>;
					--sx-rating-centered : <?php echo ($instance['centered'] == $this->__defaults['centered'] ? 'inherit' : '0'); ?>;
				}
		  	</style>

		<?php echo $args['before_title'] . $title . $args['after_title']; ?>

			<div class='sx-rating-widget-wrapper'>

		<?php if ($instance['show_mo']) {
			$t = ($instance['show_stars']) ? '' : '<span>/5</span>';
			?>
    			<div class="sx-mo"> <?php echo number_format($this->__mo, 1, '.', '') . $t; ?> </div>
		<?php } ?>

		<?php if ($instance['show_stars']) { ?>
  				<div class="sx-star-wrapper">
      	<?php

			foreach ($this->__ratings as $rate => $number) {
				if ($rate + 1 <= $this->__mo) {
					echo '<span class="sx-star"><i class="dashicons dashicons-star-filled active"></i></span>' . PHP_EOL;
				} elseif ($rate + 1 - $this->__mo > 0 && $rate + 1 - $this->__mo < 1) {
					$p = round(($this->__mo - $rate), 2) * 100;
					echo '<span class="sx-star"><i class="dashicons dashicons-star-filled active" style="width:' . $p . '%;"></i><i class="dashicons dashicons-star-filled inactive"></i></span>' . PHP_EOL;
				} else {
					echo '<span class="sx-star"><i class="dashicons dashicons-star-filled inactive"></i></span>' . PHP_EOL;
				}
			}

			?>
  				</div>
		<?php } ?>


		<?php if ($instance['show_votes'] ) { ?>
			<p class="sx-rating-total"><?php echo __('based on', 'sx') . ' <strong>' . $this->__total_number . '</strong> ' . __('votes', 'sx'); ?></p>
		<?php } ?>

		<?php if ($instance['show_voting'] && $this->__settings['voting']) { ?>
		  	<div class="sx-rate-div">
			    <span class="sx-rate-title"> <?php echo ((int)$this->__user_rate != "" ? "" : __('Rate Us!', 'sx')); ?></span>
			    <form class="sx-rate-form <?php echo ((int)$this->__user_rate != "" ? "rated" : ""); ?>" >
				    <div class="sx-star-wrapper">
			<?php

			for ($i = 5; $i > 0; $i--) {
				echo "<input type='radio' id='star$i-$id' name='rating' value='$i'" . ((int)$this->__user_rate == $i ? "checked" : "") . " /><label class = 'full' for='star$i-$id' title='$i stars'></label>" . PHP_EOL;
			}

			?>
				    </div>
			    <button type="button" name="button" class="sx-submit-rating"><?php _e('Submit', 'sx'); ?></button>
			  	</form>
				<div class="sx-errors" ></div>
			</div>
		<?php } ?>

			</div> <!-- end sx-rating-widget-wrapper -->

		</div> <!-- end scoped div -->
		<?php

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form($instance) {
		$instance = wp_parse_args((array)$instance, $this->__defaults); ?>
	    <p>
	        <label for="<?php echo $this->get_field_name('title'); ?>"><?php _e('Title:', 'sx'); ?></label>
	        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" placeholder="<?php echo __('Widget title', 'sx'); ?>" />
		</p>
		<p>
    		<label for="<?php echo $this->get_field_id('star_color'); ?>" > <span> <?php _e('Rated Star Color:', 'sx'); ?></span> </label><br />
			<input id="<?php echo $this->get_field_id('star_color'); ?>" name="<?php echo $this->get_field_name('star_color'); ?>" type="text" class="sx-rate-color-picker" value="<?php echo esc_attr($instance['star_color']); ?>" data-default-color="<?php echo esc_attr($this->__defaults['star_color']); ?>" size="6" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('inactive_color'); ?>" > <span> <?php _e('Unrated Star Color:', 'sx'); ?></span> </label><br />
			<input id="<?php echo $this->get_field_id('inactive_color'); ?>" name="<?php echo $this->get_field_name('inactive_color'); ?>" type="text" class="sx-rate-color-picker" value="<?php echo esc_attr($instance['inactive_color']); ?>" data-default-color="<?php echo esc_attr($this->__defaults['inactive_color']); ?>" size="6" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('show_mo'); ?>" >  <?php _e('Show Result (Number):', 'sx'); ?> </label>
			<input id="<?php echo $this->get_field_id('show_mo'); ?>" name="<?php echo $this->get_field_name('show_mo'); ?>" type="checkbox" class="widefat" value="<?php echo esc_attr('yes'); ?>"  <?php echo ($instance['show_mo'] ? 'checked' : ''); ?>/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('show_stars'); ?>" >  <?php _e('Show Stars:', 'sx'); ?> </label>
			<input id="<?php echo $this->get_field_id('show_stars'); ?>" name="<?php echo $this->get_field_name('show_stars'); ?>" type="checkbox" class="widefat" value="<?php echo esc_attr('yes'); ?>"  <?php echo ($instance['show_stars'] ? 'checked' : ''); ?>/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('show_votes'); ?>" >  <?php _e('Show Total Votes:', 'sx'); ?> </label>
			<input id="<?php echo $this->get_field_id('show_votes'); ?>" name="<?php echo $this->get_field_name('show_votes'); ?>" type="checkbox" class="widefat" value="<?php echo esc_attr('yes'); ?>"  <?php echo ($instance['show_votes'] ? 'checked' : ''); ?>/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('show_voting'); ?>" >  <?php _e('Allow Voting:', 'sx'); ?> </label>
			<input id="<?php echo $this->get_field_id('show_voting'); ?>" name="<?php echo $this->get_field_name('show_voting'); ?>" type="checkbox" class="widefat" value="<?php echo esc_attr('yes'); ?>"  <?php echo ($instance['show_voting'] ? 'checked' : ''); ?>/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('centered'); ?>" >  <?php _e('Center widget:', 'sx'); ?> </label>
			<input id="<?php echo $this->get_field_id('centered'); ?>" name="<?php echo $this->get_field_name('centered'); ?>" type="checkbox" class="widefat" value="<?php echo esc_attr('yes'); ?>"  <?php echo ($instance['centered'] ? 'checked' : ''); ?>/>
		</p>
	    <?php

	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update($new_instance, $old_instance) {
		$instance          = array();
		$instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';

		if (!empty($new_instance['star_color'])) { //todo: needs better code here
			if (function_exists('hex2hsl')) {
				$hsl = hex2hsl($new_instance['star_color']);

				if ($hsl[2] > 0.55) {
					$hsl[2] = $hsl[2] - 0.2;
				} else {
					$hsl[2] = $hsl[2] + 0.2;
				}
				$instance['highlight_color'] = hsl2hex($hsl);
				$instance['star_color']      = '#' . validate_hex($new_instance['star_color']);
				$instance['inactive_color']  = (!empty($new_instance['inactive_color'])) ? '#' . validate_hex($new_instance['inactive_color']) : 'inherit';
			} else {
				$instance['highlight_color'] = $new_instance['star_color'];
				$instance['star_color']      = $new_instance['star_color'];
				$instance['inactive_color']  = (!empty($new_instance['inactive_color'])) ? $new_instance['inactive_color'] : 'inherit';
			}

		} else {
			$instance['star_color']      = 'inherit';
			$instance['highlight_color'] = 'inherit';
			$instance['inactive_color']  = (!empty($new_instance['inactive_color'])) ? $new_instance['inactive_color'] : 'inherit';
		}

		$instance['show_mo']     = (!empty($new_instance['show_mo']) && $new_instance['show_mo'] == 'yes') ? true : false;
		$instance['show_stars']  = (!empty($new_instance['show_stars']) && $new_instance['show_stars'] == 'yes') ? true : false;
		$instance['show_votes']  = (!empty($new_instance['show_votes']) && $new_instance['show_votes'] == 'yes') ? true : false;
		$instance['show_voting'] = (!empty($new_instance['show_voting']) && $new_instance['show_voting'] == 'yes') ? true : false;
		$instance['centered']    = (!empty($new_instance['centered']) && $new_instance['centered'] == 'yes') ? true : false;
		return $instance;
	}

	//ajax call
	public function sx_submit_rating() {
		check_ajax_referer('sx_rating', '_wpnonce', true);

		if (!$this->__settings['voting']) {
			return;
		}
		//$ratings      = get_option('sx_ratings', false);
		$result       = array('success' => 1, 'message' => '');
		$ratingCookie = isset($_COOKIE['sx_rating']) ? unserialize(base64_decode($_COOKIE['sx_rating'])) : "";
		$star         = isset($_POST['rating']) ? $_POST['rating'] : 0;
		$expire       = ($this->__settings['expire'] != 0) ? time() + $this->__settings['expire'] * 365 * DAY_IN_SECONDS : "";

		if (!$this->__ratings) {
			$result['success'] = 0;
			$result['message'] = __('Something went wrong. Try again later', 'sx');
		} elseif ($ratingCookie == $star) {
			$result['success'] = 0;
			$result['message'] = __('Please choose a different rating!', 'sx');
		} else {

			if ($ratingCookie !== "") {
				if ($this->__ratings[$ratingCookie - 1] != 0) { // if its 0 there is propably a reset
					$this->__ratings[$ratingCookie - 1] = $this->__ratings[$ratingCookie - 1] - 1; //delete one if it is re-rated
				}
			}

			$this->__ratings[$star - 1] = $this->__ratings[$star - 1] + 1;

			$r = update_option('sx_ratings', $this->__ratings);
			if ($r) {
				$result['message'] = __('Thank You for Your Rating!', 'sx');
				$ratingCookie      = $star;
				setcookie('sx_rating', base64_encode(serialize($ratingCookie)), $expire, COOKIEPATH, COOKIE_DOMAIN);
				$_COOKIE['sx_rating'] = base64_encode(serialize($ratingCookie));
				$this->__calculate();
				$result['mo']           = number_format($this->__mo, 1, '.', '');
				$result['total_number'] = $this->__total_number;
			} else {
				$result['success'] = 0;
				$result['message'] = __('Something went wrong. Try again laterzzz', 'sx');
			}
		}

		echo json_encode($result);
		wp_die();
	}

	private function __calculate() {
		$this->__ratings  = get_option('sx_ratings', array());
		$this->__settings = get_option('sx_rating_settings', array());
		$total_rate       = 0;
		$total_number     = 0;
		$max_number       = 0;

		foreach ($this->__ratings as $rate => $number) {
			$star         = $rate + 1;
			$total_rate   = $total_rate + ($star * $number);
			$total_number = $total_number + $number;
			if ($number > $max_number) {
				$max_number = $number;
			}
		}
		if ($total_number == 0) {
			$this->__mo = 0;
		} else {
			$this->__mo = $total_rate / $total_number;
		}
		$this->__total_number = $total_number;
		$this->__total_rate   = $total_rate;

		$this->__user_rate = isset($_COOKIE['sx_rating']) ? unserialize(base64_decode($_COOKIE['sx_rating'])) : "";
	}

	/**
	 * Print scripts.
	 *
	 * Reference https://core.trac.wordpress.org/attachment/ticket/25809/color-picker-widget.php
	 *
	 */
	public function print_scripts() {
		?>
			<script>

				( function( $ ){
					function initColorPicker( widget ) {
						widget.find( '.sx-rate-color-picker' ).wpColorPicker( {

							change: function ( event ) {
								var $picker = $( this );
								_.throttle(setTimeout(function () {
									$picker.trigger( 'change' );
								}, 5), 250);
							}

						});
					}

					function onFormUpdate( event, widget ) {
						initColorPicker( widget );
					}

					$( document ).on( 'widget-added widget-updated', onFormUpdate );

					$( document ).ready( function() {
						$( '#widgets-right .widget:has(.sx-rate-color-picker)' ).each( function () {
							initColorPicker( $( this ) );
						} );
					} );
				}( jQuery ) );
			</script>

		<?php

	}

}

function register_sx_widget() {
	register_widget('Sx_Site_Rating_Widget');
}
add_action('widgets_init', 'register_sx_widget');

function load_color_picker($hook) {
	if ('widgets.php' != $hook) {
		return;
	}

	wp_enqueue_style('wp-color-picker');
	wp_enqueue_script('wp-color-picker');
	wp_enqueue_script('underscore');
}
add_action('admin_enqueue_scripts', 'load_color_picker');
