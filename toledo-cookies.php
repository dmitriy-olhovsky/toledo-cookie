<?php
/*
Plugin Name:    Toledo cookies
Description:    Cookie notice for Beetroot
Version:        2017-07-26
Author:         Dmitriy Olkhovskiy
Text Domain:    toledo-cookie
Domain Path:    /lang
*/

// Exit if accessed directly
if (!defined('ABSPATH'))
	exit;

/**
 * Class Toledo_Cookie
 */
class Toledo_Cookie {

	// Options (with default values)
	private $options = array (
		'tc_message'       =>  'Our Website uses cookies to improve your experience. Please visit our Privacy policy page for more information about cookies and how we use them.',
		'tc_icon'          =>  '',
		'tc_link'          =>  '#',
		'tc_banner_color'  =>  '#555',
		'tc_button_color'  =>  '#ccc',
		'tc_text_color'  =>  '#fff'
	);

	/**
	 * Toledo_Cookie constructor.
	 */
	public function __construct() {

		// Register hooks
		register_activation_hook(__FILE__, array($this, 'tc_activation'));
		register_deactivation_hook(__FILE__, array($this, 'tc_deactivation'));

		// Get data from DB
		$this->options = get_option('toledo_cookie_options', $this->options);

		// Add actions
		add_action('admin_init', array($this, 'tc_register_settings'));
		add_action('admin_menu', array($this, 'tc_menu_settings'));
		add_action('plugins_loaded', array($this, 'tc_load_textdomain'));
		add_action('after_setup_theme', array($this, 'tc_prepare_wpml'));
		add_action('admin_enqueue_scripts', array($this, 'tc_admin_enqueue_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'tc_user_enqueue_scripts'));
		add_action('wp_footer', array($this, 'tc_cookie_frontend'));

		// Add settings menu item on plugins page
		$plugin = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$plugin", array($this, 'tc_add_settings_link'));
	}

	/**
	 * Add setting menu item
	 */
	function tc_menu_settings() {
		add_options_page(
			__('Toledo cookie', 'toledo-cookie'),
			__('Toledo cookie', 'toledo-cookie'),
			'manage_options',
			'toledo-cookie',
			array($this, 'tc_options_page')
		);
	}

	/**
	 * Plugin options page
	 */
	function  tc_options_page() {
		echo '<div class="wrap"><h1>'.__('Toledo Cookie options', 'toledo-cookie').'</h1>';
		echo '<form method="post" action="options.php">';

		settings_fields('toledo_cookie_options');
		do_settings_sections('toledo_cookie_options');

		submit_button();
	}

	/**
	 * Load textdomain
	 */
	function tc_load_textdomain() {
		load_plugin_textdomain('toledo-cookie', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

	/**
	 * Prepare string for WPML & Polylang support
	 */
	function tc_prepare_wpml() {
	    // Depending on plugins version
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
			global $wpdb;

			$strings_array = array(
				'Message'	=> $this->options['tc_message'],
				'Link'	    => $this->options['tc_link']
			);

			// Get current registered strings
			$results = $wpdb->get_col($wpdb->prepare( "SELECT name FROM " . $wpdb->prefix . "icl_strings WHERE context = %s", 'Toledo cookie'));

			// Check results
			foreach( $strings_array as $string => $value ) {
				if (!in_array( $string, $results, true)) {
					do_action('wpml_register_single_string', 'Toledo cookie', $string, $value);
				}
			}

		} elseif (function_exists( 'icl_register_string')) {
			icl_register_string('Toledo cookie', 'Message', $this->options['tc_message']);
			icl_register_string('Toledo cookie', 'Link', $this->options['tc_link']);
		}
    }

	/**
	 * Register plugin settings
	 */
	function tc_register_settings() {
		register_setting('toledo_cookie_options', 'toledo_cookie_options', array($this, 'tc_sanitize_settings'));

		add_settings_section('toledo_cookie_options_section', __( 'Common settings', 'toledo-cookie' ), array( $this, 'tc_section_configuration' ), 'toledo_cookie_options');

		add_settings_field('tc_message', __( 'Message', 'toledo-cookie' ), array( $this, 'tc_message_field' ), 'toledo_cookie_options', 'toledo_cookie_options_section');
		add_settings_field('tc_icon', __( 'Icon', 'toledo-cookie' ), array( $this, 'tc_icon_field' ), 'toledo_cookie_options', 'toledo_cookie_options_section');
		add_settings_field('tc_link', __( 'Additional info link', 'toledo-cookie' ), array( $this, 'tc_link_field' ), 'toledo_cookie_options', 'toledo_cookie_options_section');

		add_settings_section('toledo_cookie_colors_section', __( 'Colors', 'toledo-cookie' ), array( $this, 'tc_section_color' ), 'toledo_cookie_options');

		add_settings_field('tc_banner_color', __( 'Banner background color', 'toledo-cookie' ), array( $this, 'tc_banner_color_field' ), 'toledo_cookie_options', 'toledo_cookie_colors_section');
		add_settings_field('tc_button_color', __( 'Accept button color', 'toledo-cookie' ), array( $this, 'tc_button_color_field' ), 'toledo_cookie_options', 'toledo_cookie_colors_section');
		add_settings_field('tc_text_color', __( 'Text color', 'toledo-cookie' ), array( $this, 'tc_text_color_field' ), 'toledo_cookie_options', 'toledo_cookie_colors_section');
	}

	/**
	 * Fields functions
	 */
	function tc_message_field() {
		$settings = array(
			'textarea_name' => 'toledo_cookie_options[tc_message]',
			'textarea_rows' => 5
		);
		wp_editor( $this->options['tc_message'], 'toledo_cookie_options', $settings);
	}

	function tc_icon_field() {
		wp_enqueue_media();

		$icon_url = ($current_icon = $this->options['tc_icon']) !== '' ? wp_get_attachment_image_src($current_icon) : '';
	?>
		<div class='image-preview-wrapper'>
			<img id='tc-image-preview' src='<?= $icon_url[0] ?>' width='100' height='100' style='max-height: 100px; width: 100px;'>
		</div>

		<input id="tc_upload_logo_button" type="button" class="button"
		       value="<?php _e( 'Upload icon', 'toledo-cookie' ); ?>">
		<input type='hidden' name='toledo_cookie_options[tc_icon]' id='tc_upload_logo' value='<?= $current_icon ?>'>

	<?php
	}

	function tc_link_field() {
		echo "<input id='tc_link' name='toledo_cookie_options[tc_link]' size='40' type='text' value='{$this->options['tc_link']}'>
              <p class='description'>" . __( 'Do not show if empty.', 'toledo-cookie' ) . "</p>";
	}

	function tc_banner_color_field() {
		echo "<input type='text' name='toledo_cookie_options[tc_banner_color]' value='{$this->options['tc_banner_color']}' class='tc-color-picker'>";

	}

	function tc_button_color_field() {
		echo "<input type='text' name='toledo_cookie_options[tc_button_color]' value='{$this->options['tc_button_color']}' class='tc-color-picker'>";
	}

	function tc_text_color_field() {
		echo "<input type='text' name='toledo_cookie_options[tc_text_color]' value='{$this->options['tc_text_color']}' class='tc-color-picker'>";
	}

	/**
	 * Check and sanitize settings
	 */
	function tc_sanitize_settings($value) {

	    // Add target=_blank to all links
		$document_temp = new DOMDocument();
		$document_temp->loadHTML($value['tc_message'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$links = $document_temp->getElementsByTagName('a');

		foreach ($links as $item) {
			if (!$item->hasAttribute('target'))
				$item->setAttribute('target','_blank');
		}

		$value['tc_message'] = $document_temp->saveHTML();

		return $value;
	}

	/**
	 * Admin page JS & CSS
	 */
	function tc_admin_enqueue_scripts() {
		wp_register_script( 'toledo-cookie-upload', plugin_dir_url( __FILE__ ) .'/admin/js/icon-upload.js', array('jquery', 'wp-color-picker') );

		// For color-picker
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script('jquery');
		wp_enqueue_script('toledo-cookie-upload');
	}

	/**
	 * User page JS & CSS
	 */
	function tc_user_enqueue_scripts() {
		wp_enqueue_script( 'toledo-cookie-client', plugin_dir_url( __FILE__ ) .'/user/js/tc-user-js.js', array( 'jquery' ));
		wp_enqueue_style( 'toledo-cookie-client', plugin_dir_url( __FILE__ ) .'/user/css/tc-user-style.css');
	}

	/**
	 * Check if cookie accepted
	 */
	private function tc_is_cookie_accepted() {
		return isset($_COOKIE['tc_cookie_accept']);
	}

	/**
	 * Show cookie message to user
	 */
	function tc_cookie_frontend() {
		// May be cookie already accepted
		if (!$this->tc_is_cookie_accepted()) {

		    // Add multi language support
			// Depending on plugins version
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
				$this->options['tc_message'] = apply_filters( 'wpml_translate_single_string', $this->options['tc_message'], 'Toledo Cookie', 'Message' );
			} elseif ( function_exists( 'icl_t' ) ) {
				$this->options['tc_message'] = icl_t( 'Toledo Cookie', 'Message', $this->options['tc_message']);
			}

			$icon_url = ($current_icon = $this->options['tc_icon']) !== '' ? wp_get_attachment_image_src($current_icon) : '';

			// Message output
            $banner_bg = ($this->options['tc_banner_color'] !== '') ? " style='background-color: {$this->options['tc_banner_color']};'" : '';
            $button_bg = ($this->options['tc_button_color'] !== '') ? " style='background-color: {$this->options['tc_button_color']};'" : '';
            $text_color = ($this->options['tc_text_color'] !== '') ? " style='color: {$this->options['tc_text_color']};'" : '';

			?>

			<div class="tc-cookie tc-cookie--bottom" id="toledo-cookie-banner" role="banner"<?= $banner_bg ?>>

				<?php if($icon_url): ?>
					<div class="tc-cookie__logo">
						<img src="<?= $icon_url[0] ?>" alt="Cookies icon">
					</div>
			    <?php endif; ?>

				<div class="tc-cookie__message"<?= $text_color ?>>
					<?= $this->options['tc_message'] ?>

                    <?php
                        if ($this->options['tc_link'] != ''){
                            echo __('You can read', 'toledo-cookie') . ' <a href="#" target="_blank">' . __('additional info.', 'toledo-cookie') . '</a>';
                        }
                    ?>

					<a href="#" class="tc-cookie__accept toledo-cookie-accept"<?= $button_bg ?>><?= __('Accept', 'toledo-cookie') ?></a>
				</div>

                <a href="#" class="tc-cookie__close toledo-cookie-accept">
                    <svg viewBox="0 0 512 512"
                        <g>
                            <g>
                                <path d="M403.1,108.9c-81.2-81.2-212.9-81.2-294.2,0s-81.2,212.9,0,294.2c81.2,81.2,212.9,81.2,294.2,0 S484.3,190.1,403.1,108.9z M390.8,390.8c-74.3,74.3-195.3,74.3-269.6,0c-74.3-74.3-74.3-195.3,0-269.6s195.3-74.3,269.6,0 C465.2,195.5,465.2,316.5,390.8,390.8z"/>
                            </g>
                            <polygon points="340.2,160 255.8,244.2 171.8,160.4 160,172.2 244,256 160,339.8 171.8,351.6 255.8,267.8 340.2,352  352,340.2 267.6,256 352,171.8 	"/>
                        </g>
                    </svg>
                </a>
			</div>

		<?php
        // TODO Here possible apply filters for output if user want to modify it.
		}
	}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	function tc_add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=toledo-cookie">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Plugin Activation
	 *
	 * Use options for storing plugin data
	 */
	public function tc_activation() {
		add_option( 'toledo_cookie_options', $this->options, '', 'no' );
	}

	/**
	 * Plugin Deactivation
	 */
	public function tc_deactivation() {
		// Now do nothing
		// Options will deleted during uninstall
	}
}

// Set plugin instance
new Toledo_Cookie();