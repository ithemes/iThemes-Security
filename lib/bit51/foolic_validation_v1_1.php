<?php
/**
 * FooLicensing License Key Validation
 *
 * @author    Brad Vincent
 * @version   1.4
 */

if (!class_exists('foolic_validation_v1_1')) {

	class foolic_validation_v1_1 {

		protected $plugin_validation_url;
		protected $plugin_slug;

		function foolic_validation_v1_1($plugin_validation_url, $plugin_slug) {
			$this->plugin_validation_url = $plugin_validation_url;
			$this->plugin_slug = $plugin_slug;

			if (is_admin()) {
				//output the needed css and js
				add_action('admin_enqueue_scripts', array(&$this, 'include_css') );
				add_action('admin_footer', array(&$this, 'include_js') );

				//wire up the ajax callbacks
				add_action('wp_ajax_foolic_validate_license-'.$this->plugin_slug, array($this, 'ajax_validate_license'));
				add_action('wp_ajax_foolic_license_set_validity-'.$this->plugin_slug, array($this, 'ajax_license_set_validity'));
				add_action('wp_ajax_foolic_license_store_error-'.$this->plugin_slug, array($this, 'ajax_license_store_error'));
				add_action('wp_ajax_foolic_license_clear_key-'.$this->plugin_slug, array($this, 'ajax_license_clear_key'));

				//output the validation HTML
				add_filter('foolic_get_validation_data-'.$this->plugin_slug, array($this, 'get_validation_data'));
			}
		}

		function get_validation_data() {
			$default_text = __( 'Enter your license key here', $this->plugin_slug );
			if ( get_option( $this->plugin_slug . '_licensekey' ) === false || get_option( $this->plugin_slug . '_licensekey' ) === $default_text ) {
				$license = $default_text;
				$onClick = ' onblur="if(this.value == \'\') { this.value=\'' . $default_text . '\'}" onfocus="if (this.value == \'' . $default_text . '\') {this.value=\'\'}"';
			} else {
				$license = get_option($this->plugin_slug . '_licensekey');
				$onClick = '';
			}
			$valid = !empty($license) ? get_option($this->plugin_slug . '_valid') : false;
			$expires = get_option($this->plugin_slug . '_valid_expires');
			if ($expires !== false && $expires !== 'never') {
				if (strtotime($expires) < strtotime(date("Y-m-d"))) {
					$valid = 'expired'; //it has expired!
				}
			}
			$input_id = $this->plugin_slug . '_licensekey';
			$input_type = apply_filters('foolic_validation_input_type-'.$this->plugin_slug, 'password');
			$input_size = apply_filters('foolic_validation_input_size-'.$this->plugin_slug, '40');
			$input = '<input size="'. $input_size . '" class="foolic-input foolic-input-' . $this->plugin_slug . '' . ($valid !== false ? ($valid=='valid' ? ' foolic-valid' : ' foolic-invalid') : '') . '" type="' . $input_type . '" id="' . $input_id . '" name="' . $this->plugin_slug . '[license]" value="' . $license . '"' . $onClick . ' />';
			$button = '<input class="foolic-check foolic-check-' . $this->plugin_slug . '" type="button" name="foolic-check-' . $this->plugin_slug . '" value="' . __('Validate', $this->plugin_slug) . '" />';
			$nonce = '<span style="display:none" class="foolic-nonce-' . $this->plugin_slug . '">' . wp_create_nonce($this->plugin_slug . '_foolic-ajax-nonce') . '</span>';
			if ($valid == 'expired') {
				$message = '<div class="foolic-error foolic-message-' . $this->plugin_slug . '">' . __('The license key has expired!', $this->plugin_slug) . '</div>';
			} else {
				$message = '<div style="display:none" class="foolic-message foolic-message-' . $this->plugin_slug . '"></div>';
			}
			return array(
				'slug' => $this->plugin_slug,
				'license' => $license,
				'valid' => $valid,
				'expires' => $expires,
				'input' => $input,
				'button' => $button,
				'nonce' => $nonce,
				'message' => $message,
				'html' => '<div class="foolic-validation-' . $this->plugin_slug . '">' . $input . $button . $nonce . $message . '</div>'
			);
		}

		function include_css($hook_suffix) {
			$screen = get_current_screen();
			$include = apply_filters('foolic_validation_include_css-'.$this->plugin_slug, $screen);

			//if the filter was not overridden then add the css and js on the plugin settings page
			if ($include === $screen) $include = ($hook_suffix === $this->plugin_slug || $hook_suffix === 'settings_page_' . $this->plugin_slug);
			if (!$include) return;

?>
<style type="text/css">
	.foolic-check {
		cursor: pointer;
	}

	.foolic-loading {
		background-image: url(data:image/gif;base64,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);
		background-repeat: no-repeat;
		background-position: right center;
	}

	input.foolic-input.foolic-valid {
		background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAANkE3LLaAgAAAjpJREFUeJy90k1IkwEcx/Hvs2evMt1KJ741Wy0zexMy0US6WHSKgii8pAUZKUEFubSgkjKRQgT1kL0hZNChMpJCi/RQGQll0sBE09wWw5c2c5rbs+fptIgutQ59758//8MP/nNCcsWSm5ajS+8C6qh1con5So+3W3ni6lTiS81XAe1f45QDsXV3JloVT2BC8c57lGZng6LZJVz8+Ub8fpVD0Mri1DVqf8dpZYYLZ6pOOjJi1jDqHyIoS7xwdyMbla1qANNO7fHDx0rrZPV3WufbpOl26iM4/YjuXEXlwdNWvZ3xuY9IssKDT23c6+0l3McjUVfEoe2Vm5vyEwuJ1yVgyRO3jflHfIFBXtvK1dUljt016ZpM/MFJZiUfTyfbed7/Ct9t6hmiRkzeR2Moddo6G5xBJYZJjEkiMUcoIvtrzo7iLeUpOhu+oJcpycPA3DPefXiP6zoN0gAOQBYRyLRslAqmtS7coSF8iguNQVFZs0yrtYIGb2iE0eBb3OFBvMMzOBuk2oV+qgAZQFz8zMvwPGkrc3XZQlyIb4KfsNqPUYhFL6pRqWQMOjULEwJ9l3yXZ/uojmAAEQgFhukKLsq2rLyE9XqTiiTtMuwxWaQb7Cw3ZjDjCtBx1tk41SNX/oojBwBCfiddQUlalVtgX5tqsmHVrWCdKZfxL2M0nXrY4nksnQDCf9pL3IZy/f1m917ljXxD6fCeV+zF2ugWB5gLHcbOFtceZVOZ4RagjwZHSrLkUwHE/guOqh90ld9+870vDgAAAABJRU5ErkJggg==);
		background-repeat: no-repeat;
		background-position: right center;
		padding-right: 3px;
	}

	input.foolic-input.foolic-invalid {
		background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAANkE3LLaAgAAAwFJREFUeJx1k11IWwcUx3+598abu9wlJC46ilGn8SM4pqUPSqstzTLfisyGUtaB0MHasD0UGUy6D7a3bWyMDgZjA1/aUqRTRi3VLoxp+1AZLRam1qVaZklqayQ0vcluPu7HHlw7YfP/eA6/P+d/Dge2VJXo6xu7FomMAzI7KAwta0eP3vwoGBzeXn/+52h0wk4kbDuRsK8eOPAToPwf/Gc8fteem7Ptc+fsz0KhEUAQXoaeqM/3OpoG2Sz98fjAdG/v2HaTMLRMDQ1daejvD7G2Bk4nMa/3Q+AFUYA239LSsZYnT5CbmqBYJNTZ2bYvk3nlbDo93gr1V48cmW6IRkNoGmgaD86c4Yf5+dJ1+FbMgy7Ca5WVldqWQgE5GIRikeb29raDm5t7T3R1vd20f387mgb5PKnRUS4sLHAFfkzBBQeAEzoGYewQdAx0d6NGImDb4HBsZbBtMAweXLrEeDLJRZi4Dm8Bj0UACzKL8Ksbeq10+sXGQgG5thaKRSiVoFhkfXKSydVVJmBiBo4DOQBh26LvnIVDKUlaFlT1X7hchkoFORBgXpKmf4FjT2EAcfupDldV7fu4p+cNbyjkxrK2Rv8nguL10lEuW7/lcrMpy3r4H4OTLtfQaHX1eVWSPOTzADxKJilnsyi2Dek0PkGojqlqbEHXbycrldVnBiOKcurrmprvBK9XxOUCv59HmQy/37rF5sYGnro6lF27QBBwyfJzMbc7ltb1e7dLpQVxWJbf+SIQ+AaPBzweaGhgs1Dg7o0bfGWa398xzXut6+sdrlAIJRgEw0AUBOeAqg4uadqiFDDNiO104lBVaGwkm8uxMjPDp5XK5wk4DShlXdfev3z5OIOD+Jub4f59rELBUVMuHxRfsqxXO3V9T1U4zF+GwR9TU+Z7pvnBDHwC2EBlEaZThqHsXl7eq9TXY4oia7OzXCyVplAh/CXM57q77Tm3W98NJ3Z4RkcfjNz0eIyNri77NFwDgk+brcN+/1hYEN7cAX6mPZIUf9fnOw/UAfwNR/k0R/06qqwAAAAASUVORK5CYII=);
		background-repeat: no-repeat;
		background-position: right center;
		padding-right: 3px;
	}

	.foolic-message {
		display: table-cell;
		background-color: lightYellow;
		border: solid 1px #E6DB55;
		padding: 3px 10px;
	}

	.foolic-error {
		display: table-cell;
		background-color: #ffebe8;
		border: solid 1px #c00;
		padding: 3px 10px;
	}
</style>
<?php	}

		function include_js() {
			$screen = get_current_screen();
			$include = apply_filters('foolic_validation_include_js-'.$this->plugin_slug, $screen);

			//if the filter was not overridden then add the js on the plugin settings page
			if ($include === $screen) $include = (array_key_exists('page', $_GET) && $_GET['page'] == $this->plugin_slug);
			if (!$include) return;

			$namespace = 'foolic_' . str_replace('-', '_', $this->plugin_slug);
?>
<script type="text/javascript">
(function( <?php echo $namespace; ?>, $, undefined ) {
	<?php echo $namespace; ?>.init = function() {
		$('.foolic-validation-<?php echo $this->plugin_slug; ?> input.foolic-check').click(function (e) {
			e.preventDefault();
			var $this = $(this);
			var $input = $this.prev('.foolic-input-<?php echo $this->plugin_slug; ?>');
			if ($input.val().length == 0) {
				alert('<?php echo __('Please enter a license key', $this->plugin_slug); ?>');
			} else {
				<?php echo $namespace; ?>.send_request($input, 'foolic_validate_license');
			}
		});

		$('.foolic-clear-<?php echo $this->plugin_slug; ?>').click(function (e) {
			e.preventDefault();
			var nonce = $('.foolic-nonce-<?php echo $this->plugin_slug; ?>').text();
			var data = { action: 'foolic_license_clear_key-<?php echo $this->plugin_slug; ?>', nonce: nonce };
			$.ajax({ url: ajaxurl, cache: false, type: 'POST', data: data,
				success: function (data) {
					$(document).trigger('foolic-cleared-<?php echo $this->plugin_slug; ?>');
				},
				error: function(a,b,c) {
					alert(a);
				}
			});
		});
	};

	<?php echo $namespace; ?>.send_request = function($input, action) {
		var $message = $input.siblings('.foolic-message-<?php echo $this->plugin_slug; ?>');
		var nonce = $input.siblings('.foolic-nonce-<?php echo $this->plugin_slug; ?>').text();

		$input.removeClass('foolic-valid foolic-invalid').addClass('foolic-loading');
		$message.hide().removeClass('foolic-message foolic-error');

		var data = { action: action + '-<?php echo $this->plugin_slug; ?>', license: $input.val(), nonce: nonce, input: $input.attr('name') };

		$.ajax({
			url: ajaxurl,
			cache: false,
			type: 'POST',
			data: data,
			dataType: "json",
			success: function (data) {
				$input.removeClass('foolic-loading');
				var message = '';
				if (data.license_message) {
					message = data.license_message;
				}
				//message += '<strong style="color:' + data.response.color + '">' + data.response.message + '</strong>';
				if (data.validation_message)
					message += '<div>' + data.validation_message + '</div>';
				$message.html(message).show();
				$input.addClass(data.response.valid ? 'foolic-valid' : 'foolic-invalid');
				<?php echo $namespace; ?>.set_validity(data.response.valid, data.expires, nonce);
				if (data.response.valid) {
					$(document).trigger('foolic-validated-<?php echo $this->plugin_slug; ?>');
				}
			},
			error: function (a, b, c) {
				$message.html('Something went wrong when trying to validate your license. The error was : ' + a.responseText).show();
				$input.removeClass('foolic-loading');
				<?php echo $namespace; ?>.store_validation_error(a.responseText, nonce);
			}
		});
	}

	<?php echo $namespace; ?>.store_validation_error = function(response, nonce) {
		if (response) {
			var data = { action: 'foolic_license_store_error-<?php echo $this->plugin_slug; ?>', response: response, nonce: nonce };
			$.ajax({ url: ajaxurl, cache: false, type: 'POST', data: data });
		}
	}

	<?php echo $namespace; ?>.set_validity = function(valid, expires, nonce) {
		var data = { action: 'foolic_license_set_validity-<?php echo $this->plugin_slug; ?>', valid: valid ? 'valid' : 'invalid', expires : expires, nonce: nonce };
		$.ajax({ url: ajaxurl, cache: false, type: 'POST', data: data });
	}
}( window.<?php echo $namespace; ?> = window.<?php echo $namespace; ?> || {}, jQuery ));

jQuery(function($) {
	<?php echo $namespace; ?>.init();
});
</script>
<?php	}

		function clear_all_options() {
			delete_option($this->plugin_slug . '_licensekey');
			delete_option($this->plugin_slug . '_valid');
			delete_option($this->plugin_slug . '_valid_expires');
			delete_option($this->plugin_slug . '_lasterror');
		}

		function valid_nonce() {
			return wp_verify_nonce($_REQUEST['nonce'], $this->plugin_slug . '_foolic-ajax-nonce');
		}

		function output_valid_response() {
			echo '1';
			die;
		}

		function ajax_license_set_validity() {
			if ($this->valid_nonce()) {
				$valid   = $_REQUEST['valid'];
				$expires = $_REQUEST['expires'];
				update_option($this->plugin_slug . '_valid', $valid);
				if (!empty($expires)) {
					update_option($this->plugin_slug . '_valid_expires', $expires);
				}
				$this->output_valid_response();
			}
		}

		function ajax_license_clear_key() {
			if ($this->valid_nonce()) {
				$this->clear_all_options();
				$this->output_valid_response();
			}
		}

		function ajax_license_store_error() {
			if ($this->valid_nonce()) {
				$response = $_REQUEST['response'];
				update_option($this->plugin_slug . '_lasterror', $response);
				$this->output_valid_response();
			}
		}

		function ajax_validate_license() {
			try {
				if ($this->valid_nonce()) {

					$this->clear_all_options();

					$license = $_REQUEST['license'];

					update_option($this->plugin_slug . '_licensekey', $license);

					$response_raw = wp_remote_post($this->plugin_validation_url, $this->prepare_validate_request($license));

					if (is_wp_error($response_raw)) {
						$error = $response_raw->get_error_message();
						$this->output_json_error(__('An error occurred while trying to validate your license key', $this->plugin_slug),
							$error);
						die;
					} else if (wp_remote_retrieve_response_code($response_raw) != 200) {
						$this->output_json_error(__('An error occurred while trying to validate your license key', $this->plugin_slug),
							sprintf(__('The response code of [%s] was not expected', $this->plugin_slug), wp_remote_retrieve_response_code($response_raw)));
					} else {

						$response = $response_raw['body'];

						header('Content-type: application/json');

						echo $response;
						die;
					}

				} else {
					$this->output_json_error(__('The validation request was invalid', $this->plugin_slug),
						__('The validation NONCE could not be validated!', $this->plugin_slug));
				}
			}
			catch (Exception $e) {
				$this->output_json_error(__('An unexpected error occurred', $this->plugin_slug),
					$e->getMessage());
			}
		}

		function output_json_error($error, $message) {
			$details = array(
				'response'           => array(
					'valid'   => false,
					'message' => $error,
					'color'   => '#ff0000',
					'error'   => true
				),
				'validation_message' => $message
			);

			header('Content-type: application/json');
			echo json_encode($details);
			die;
		}

		function prepare_validate_request($license, $action = 'validate') {
			global $wp_version;

			return array(
				'body'       => array(
					'action'  => $action,
					'license' => $license,
					'site'    => home_url()
				),
				'timeout' => 45,
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url()
			);
		}

	}
}