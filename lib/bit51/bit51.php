<?php

/*
 * Bit51 standard library functions for all Bit51.com plugins
 * 
 * Thanks to Yoast (http://www.yoast.com), W3 Total Cache and Ozh Richard (http://planetozh.com) for a lot of the inspiration and some code snipets used in the rewrite of this plugin. Many of the ideas for this class as well as some of the functions of it's functions and the associated CSS are borrowed from the work of these great developers (I don't think anything is verbatim but some is close as I didn't feel it necessary to reinvent the wheel, in particular with regards to admin page layout).
 */

if ( ! class_exists( 'Bit51' ) ) {

	abstract class Bit51 {
	
		var $feed = 'http://bit51.com/feed'; //current address of Bit51.com feed
	
		/**
		 * Register admin javascripts (only for plugin admin page)
		 *
		 **/
		function config_page_scripts() {
		
			//make sure we're on the appropriate page
			if ( isset( $_GET['page'] ) && strpos( $_GET['page'], $this->hook ) !== false ) {
			
				wp_enqueue_script( 'postbox' );
				wp_enqueue_script( 'dashboard' );
				wp_enqueue_script( 'thickbox' );
				wp_enqueue_script( 'media-upload' );
				
			}
			
		}
		
		/**
		 * Register admin css styles (only for plugin admin page)
		 *
		 **/
		function config_page_styles() {
		
			//make sure we're on the appropriate page
			if ( isset( $_GET['page'] ) && strpos( $_GET['page'], $this->hook ) !== false ) {
			
				wp_enqueue_style( 'dashboard' );
				wp_enqueue_style( 'thickbox' );
				wp_enqueue_style( 'global' );
				wp_enqueue_style( 'wp-admin' );
				wp_enqueue_style( 'bit51-css', plugin_dir_url( $this->pluginbase, __FILE__ ). 'lib/bit51/bit51.css' );
				
			}
			
		}
		
		/**
		 * Register all settings groups
		 *
		 * Registers all settings groups defined in main plugin file
		 *
		 **/
		function register_settings() {
			
			foreach ( $this->settings as $group => $settings ) { //look at each main group
			
				foreach ( $settings as $setting => $option ) { //look at each option set
				
					if ( isset( $option['callback'] ) ) { //if callback is defined register with callback
					
						register_setting( $group, $setting, array( $this, $option['callback'] ) );
						
					} else { //register without callback
					
						register_setting( $group, $setting );
						
					}
					
			    }
			    
			}
			
		}
		
		/**
		 * Add action link to plugin page
		 * 
		 * Adds plugin settings link to plugin page in WordPress admin area.
		 *
		 * @param object $links Array of WordPress links
		 * @param string $file String name of current file
		 * @return object Array of WordPress links
		 *
		 **/
		function add_action_link( $links, $file ) {
			static $this_plugin;
			
			if ( empty( $this_plugin ) ) {
				$this_plugin = $this->pluginbase;
			}
			
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="' . $this->plugin_options_url() . '">' . __( 'Settings', $this->hook ) . '</a>';
				array_unshift( $links, $settings_link );
			}
			
			return $links;
		}
		
		/**
		 * Return URL of options page
		 *
		 * @return object WordPress admin URL
		 *
		 **/
		function plugin_options_url() {
		
			return admin_url('options-general.php?page=' . $this->hook);
			
		}
		
		/**
		 * Setup and call admin messages
		 *
		 * Sets up messages and registers actions for WordPress admin messages
		 *
		 * @param object $errors WordPress error object or string of message to display
		 *
		 **/
		function showmessages( $errors ) {
			
			global $savemessages; //use global to transfer to add_action callback
			
			$savemessages = ''; //initialize so we can get multiple error messages (if needed)
			
			if ( function_exists( 'apc_store' ) ) { 
				apc_clear_cache(); //Let's clear APC (if it exists) when big stuff is saved.
			}
			
			if ( is_wp_error( $errors ) ) { //see if object is even an error
			
				$errors = $errors->get_error_messages(); //get all errors if it is
				
				foreach ( $errors as $error => $string ) {
					$savemessages .= '<div id="message" class="error"><p>' . $string . '</p></div>';
				}
							
			} else { //no errors so display settings saved message
			
				$savemessages .= '<div id="message" class="updated"><p><strong>' . $errors . '</strong></p></div>';
				
			}
			
			//register appropriate message actions
			add_action('admin_notices', array(&$this, 'dispmessage'));
			add_action('network_admin_notices', array(&$this, 'dispmessage'));
			
		}
		
		/**
		 * Set all default settings
		 *
		 * Takes default settings defined in main plugin file and saves them as a WordPress option
		 *
		 **/
		function default_settings() {
		
			foreach ( $this->settings as $settings ) {
			
				foreach ( $settings as $setting => $defaults ) {
				
					$options = get_option( $setting ); //Get the option if it already exists
					
					//set missing options
					foreach ( $defaults as $option => $value ) {
					
						if ( $option != 'callback' && !isset( $options[$option] ) ) {
							$options[$option] = $value;
						}
						
					}
					
					//remove obsolete options
					foreach ( $options as $option => $value ) {
					
						if ( ! isset( $defaults[$option] ) && $option != 'version' ) {
							unset( $options[$option] ); 
						}
						
					}
					
					update_option( $setting, $options ); //save new options
				}
				
			}
			
			return $options;
			
		}
		
		/**
		 * Echos admin messages
		 * 
		 * Takes care of echoing admin message when appropriate action is called
		 *
		 **/
		function dispmessage() {
		
			global $savemessages;
			
			echo $savemessages;
			
			unset($savemessages); //delete any saved messages
			
		}
		
		/**
		 * Setup postbox
		 *
		 * Echos postbox for settings screen
		 *
		 * @param string $id css ID for postbox
		 * @param string $title title to display to user
		 * @param string $content postbox content
		 **/
		function postbox( $id, $title, $content ) {
			?>
			<div id="<?php echo $id; ?>" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
				<h3 class="hndle"><span><?php echo $title; ?></span></h3>
				<div class="inside">
					<?php 
						//execute content if it's a function or just echo it
						if ( ! strstr( $content, ' ' ) && method_exists( $this, $content ) ) {
						
							$this->$content();
							
						} else {
						
							echo $content; 
							
						}
					?>
				</div>
			</div>
			<?php
		}

		function admin_tabs( $tabs, $current = NULL, $page = true ) {
			if ( $current == NULL ) {
				$current = $this->hook;
			}
			$tabs = $tabs;
			echo '<div id="icon-themes" class="icon32"><br></div>';
			echo '<h2 class="nav-tab-wrapper">';
			foreach( $tabs as $location => $tabname ){
				if ( is_array( $tabname ) ) {
					$class = ( $location == $current ) ? ' nav-tab-active' : '';
					echo '<a class="nav-tab' . $class. '" href="?page=' . $tabname[1] . '&tab='. $location . '">' . $tabname[0] . '</a>';
				} else {
					$class = ( $location == $current ) ? ' nav-tab-active' : '';
					echo '<a class="nav-tab' . $class. '" href="?page=' . $location . '">' . $tabname . '</a>';
				}
			}
			echo '</h2>';
		}
		
		/**
		 * Setup main admin page box
		 *
		 * Sets up main admin page layout and loads default sidebar boxes
		 *
		 * @param string $title Title of page to display to user
		 * @param object $boxes array of primary content boxes in postbox form
		 * @param string $icon[optional] icon file to display
		 * @param object $tabs[optional] array of tabs to display
		 * @param boolean $page[optional] true if stand-alone page, false otherwise
		 *
		 **/
		function admin_page( $title, $boxes, $icon = '', $tabs = NULL, $page = true ) {

			if ( ( $page != true && !isset( $_GET['tab'] ) ) || ( $page == true && isset( $_GET['tab'] ) ) ) {
				return;
			}

			?>
				<div class="wrap">
					<?php if ( $icon == '' ) { ?>
						<a href="http://bit51.com/"><div id="bit51-icon" style="background: url(<?php echo plugin_dir_url( $this->pluginbase, __FILE__ ); ?>lib/bit51/images/bit51.png) no-repeat;" class="icon32"><br /></div></a>
					<?php } else { ?>
						<a href="http://bit51.com/"><div id="bit51-icon" style="background: url(<?php echo $icon; ?>) no-repeat;" class="icon32"><br /></div></a>
					<?php } ?>
					<h2><?php _e( $title, $this->hook ) ?></h2>
					<?php 
						if ( $tabs != NULL ) {
							if ( isset ( $_GET['tab'] ) ) {
								$this->admin_tabs( $tabs, filter_var( $_GET['tab'], FILTER_SANITIZE_STRING ), false ); 
							} elseif( isset( $_GET['page'] ) ) {
								$this->admin_tabs( $tabs, filter_var( $_GET['page'], FILTER_SANITIZE_STRING ) ); 
							} else { 
								$this->admin_tabs( $tabs ); 
							}
						}
					?>
					<div class="postbox-container" style="width:65%;">
						<div class="metabox-holder">	
							<div class="meta-box-sortables">
								<?php 
									foreach ( $boxes as $content ) { 
										$this->postbox( 'adminform', $content[0], $content[1] );
									} 
								?>
							</div>
						</div>
					</div>
					<div class="postbox-container side" style="width:20%;">
						<div class="metabox-holder">	
							<div class="meta-box-sortables">
								<?php
									$this->donate();
									$this->support();
									$this->news(); 
									$this->social();
								?>
							</div>
						</div>
					</div>
				</div>
			<?php
		}
		
		/**
		 * Display tech support information
		 *
		 * Displays standard tech support box in admin sidebar
		 *
		 **/
		function support() {
		
			$content = __('If you need help getting this plugin or have found a bug please visit the <a href="' . $this->supportpage . '" target="_blank">support forums</a>.', $this->hook);
			
			$this->postbox( 'bit51support', __( 'Need Help?', $this->hook ), $content ); //execute as postbox
			
		}
		
		/**
		 * Display Bit51's latest posts
		 *
		 * Displays latest posts from Bit51 in admin page sidebar
		 *
		 **/
		function news() {
		
			include_once( ABSPATH . WPINC . '/feed.php' ); //load WordPress feed info
			
			$feed = fetch_feed( $this->feed ); //get the feed

			if ( ! isset( $feed->errors ) ) {

				$feeditems = $feed->get_items( 0, $feed->get_item_quantity( 5 ) ); //narrow feed to last 5 items
			
				$content = '<ul>'; //start list
			
				if ( ! $feeditems ) {
			
			    	$content .= '<li class="bit51">' . __( 'No news items, feed might be broken...', $this->hook ) . '</li>';
			    
				} else {
			
					foreach ( $feeditems as $item ) {
						
						$url = preg_replace( '/#.*/', '', esc_url( $item->get_permalink(), $protocolls = null, 'display' ) );
						
						$content .= '<li class="bit51"><a class="rsswidget" href="' . $url . '" target="_blank">'. esc_html( $item->get_title() ) .'</a></li>';
						
					}
					
				}	
									
				$content .= '</ul>'; //end list

			} else {
				$content = __( 'It appears as if the feed is currently down. Please try again later', $this->hook );
			}
			
			$this->postbox( 'bit51posts' , __( 'The Latest from Bit51', $this->hook ), $content ); //set up postbox
			
		}
		
		/**
		 * Display donate box
		 *
		 * Displays bit51 donate box in sidebar of admin pages
		 *
		 **/
		function donate() {
		
			$content = __( 'Have you found this plugin useful? Please help support it\'s continued development with a donation of $20, $50, or even $100.', $this->hook );
			
			$content .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="' . $this->paypalcode . '"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>';
			
			$content .= '<p>' . __( 'Short on funds?', $this->hook ) . '</p>';
			
			$content .= '<ul>';
			
			$content .= '<li><a href="' . $this->wppage . '" target="_blank">' . __( 'Rate', $this->hook ) . ' ' . $this->pluginname . __( ' 5★\'s on WordPress.org', $this->hook ) . '</a></li>';
			
			$content .= '<li>' . __( 'Talk about it on your site and link back to the ', $this->hook ) . '<a href="' . $this->homepage . '" target="_blank">' . __( 'plugin page.', $this->hook ) . '</a></li>';
			
			$content .= '<li><a href="http://twitter.com/home?status=' . urlencode( 'I use ' . $this->pluginname . ' for WordPress by @bit51 and you should too - ' . $this->homepage ) . '" target="_blank">' . __( 'Tweet about it. ', $this->hook ) . '</a></li>';
			
			$content .= '</ul>';
			
			$this->postbox( 'donate', __( 'Support This Plugin' , $this->hook ), $content ); //setup the postbox
			
		}
		
		/**
		 * Display social links
		 *
		 * Displays Bit51's social links on admin sidebar
		 *
		 **/
		function social() {
		
			$content = '<ul>';
			
			$content .= '<li class="facebook"><a href="https://www.facebook.com/bit51" target="_blank">' . __( 'Like Bit51 on Facebook', $this->hook ) . '</a></li>';
			
			$content .= '<li class="twitter"><a href="http://twitter.com/Bit51" target="_blank">' . __( 'Follow Bit51 on Twitter', $this->hook ) . '</a></li>';
			
			$content .= '<li class="google"><a href="https://plus.google.com/104513012839087985497" target="_blank">' . __( 'Circle Bit51 on Google+', $this->hook ) . '</a></li>';
			
			$content .= '<li class="subscribe"><a href="http://bit51.com/subscribe" target="_blank">' . __( 'Subscribe with RSS or Email', $this->hook ) . '</a></li>';
			
			$content .= '</ul>';
			
			$this->postbox( 'bit51social', __( 'Bit51 on the Web', $this->hook ), $content ); //setup the postbox
			
		}
		
		/**
		 * Display (and hide) donation reminder
		 *
		 * Adds reminder to donate or otherwise support on dashboard
		 *
		 **/
		function ask() {
		
			global $blog_id; //get the current blog id
			
			if ( is_multisite() && ( $blog_id != 1 || ! current_user_can( 'manage_network_options' ) ) ) { //only display to network admin if in multisite
				return;
			}
			
			$options = get_option( $this->plugindata );
			
			//this is called at a strange point in WP so we need to bring in some data
			global $plugname;
			global $plughook;
			global $plugopts;
			$plugname = $this->pluginname;
			$plughook = $this->hook;
			$plugopts = $this->plugin_options_url();
			
			//display the notifcation if they haven't turned it off and they've been using the plugin at least 30 days
			if ( ! isset( $options['no-nag'] ) && $options['activatestamp'] < ( current_time( 'timestamp' ) - 2952000 ) ) {
			
				if ( ! function_exists( 'bit51_plugin_donate_notice' ) ) {
			
					function bit51_plugin_donate_notice(){
				
						global $plugname;
						global $plughook;
						global $plugopts;
					
					    echo '<div class="updated">
				       <p>' . __( 'It looks like you\'ve been enjoying', $plughook ) . ' ' . $plugname . ' ' . __( 'for at least 30 days. Would you consider a small donation to help support continued development of the plugin?', $plughook ) . '</p> <p><input type="button" class="button " value="' . __( 'Support This Plugin', $plughook ) . '" onclick="document.location.href=\'?bit51_lets_donate=yes&_wpnonce=' .  wp_create_nonce('bit51-nag') . '\';">  <input type="button" class="button " value="' . __('Rate it 5★\'s', $plughook) . '" onclick="document.location.href=\'?bit51_lets_rate=yes&_wpnonce=' .  wp_create_nonce( 'bit51-nag' ) . '\';">  <input type="button" class="button " value="' . __( 'Tell Your Followers', $plughook ) . '" onclick="document.location.href=\'?bit51_lets_tweet=yes&_wpnonce=' .  wp_create_nonce( 'bit51-nag' ) . '\';">  <input type="button" class="button " value="' . __( 'Don\'t Bug Me Again', $plughook ) . '" onclick="document.location.href=\'?bit51_donate_nag=off&_wpnonce=' .  wp_create_nonce( 'bit51-nag' ) . '\';"></p>
					    </div>';
				    
					}
				
				}
				
				add_action( 'admin_notices', 'bit51_plugin_donate_notice' ); //register notification
				
			}
			
			//if they've clicked a button hide the notice
			if ( ( isset( $_GET['bit51_donate_nag'] ) || isset( $_GET['bit51_lets_rate'] ) || isset( $_GET['bit51_lets_tweet'] ) || isset( $_GET['bit51_lets_donate'] ) ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'bit51-nag' ) ) {
			
				$options = get_option( $this->plugindata );
				$options['no-nag'] = 1;
				update_option( $this->plugindata,$options );
				remove_action( 'admin_notices', 'bit51_plugin_donate_notice' );
				
				//take the user to paypal if they've clicked donate
				if ( isset( $_GET['bit51_lets_donate'] ) ) {
					wp_redirect( 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=' . $this->paypalcode, '302' );
				}
				
				//Go to the WordPress page to let them rate it.
				if ( isset( $_GET['bit51_lets_rate'] ) ) {
					wp_redirect( $this->wppage, '302' );
				}
				
				//Compose a Tweet
				if ( isset( $_GET['bit51_lets_tweet'] ) ) {
					wp_redirect( 'http://twitter.com/home?status=' . urlencode( 'I use ' . $this->pluginname . ' for WordPress by @bit51 and you should too - ' . $this->homepage ) , '302' );
				}
				
			}
			
		}
		
	}
	
}