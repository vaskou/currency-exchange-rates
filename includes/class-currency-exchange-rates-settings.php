<?php

class Currency_Exchange_Rates_Settings {

	const PAGE_TITLE = 'Currency Exchange Rates Settings';
	const MENU_TITLE = self::PAGE_TITLE;
	const MENU_SLUG = 'currency_exchange_rates_settings';
	const OPTION_GROUP = self::MENU_SLUG;
	const SETTING_NAME = 'currency_exchange_rates_settings';
	const SETTING_SECTION_TITLE = self::PAGE_TITLE;
	const SETTING_SECTION_DESCRIPTION = '';
	const SETTING_PAGE = self::MENU_SLUG;
	const SETTING_FIELDS = array(
		'cer_oxr_api_url' => array(
			'title' => 'Open Exchange Rates API URL',
			'args'  => array(
				'type' => 'url'
			)
		),
		'cer_oxr_app_id'  => array(
			'title' => 'Open Exchange Rates App ID',
			'args'  => array(
				'type' => 'password'
			)
		),
		'cer_oxr_usage'   => array(
			'title' => 'Open Exchange Rates Usage',
			'args'  => array(
				'type' => 'oxr_usage'
			)
		),
	);

	private $_submenu_parent_slug = 'options-general.php';
	private static $instance = null;

	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'settings_page' ) );
		add_filter( 'whitelist_options', array( $this, 'whitelist_options' ) );
		add_action( 'admin_init', array( $this, 'add_setting_section' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_init', array( $this, 'register_setting_field' ) );
	}

	public function settings_page() {
//		add_menu_page(
		add_submenu_page(
			$this->_submenu_parent_slug,
			self::PAGE_TITLE,
			self::MENU_TITLE,
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'add_menu_page_callback' )
		);
	}

	public function add_menu_page_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}


		if ( isset( $_GET['settings-updated'] ) ) {
//			add_settings_error( self::MENU_SLUG . '_messages', self::MENU_SLUG . '_message', __( 'Settings Saved', 'currency-exchange-rates' ), 'success' );
			delete_transient( 'currency_exchange_rates_usage' );
		}

		settings_errors( self::MENU_SLUG . '_messages' );
		?>
        <form action="options.php" method="post">
			<?php
			settings_fields( self::OPTION_GROUP );
			do_settings_sections( self::MENU_SLUG );
			submit_button( 'Save Settings' );
			?>
        </form>
		<?php
	}

	public function whitelist_options( $whitelist_options ) {
		$options = array();

		foreach ( self::SETTING_FIELDS as $field_name => $field_args ) {
			array_push( $options, $field_name );
		}

		$whitelist_options[ self::MENU_SLUG ] = $options;

		return $whitelist_options;
	}

	public function add_setting_section() {

		add_settings_section(
			self::SETTING_NAME . '_section',
			__( self::SETTING_SECTION_TITLE, 'currency-exchange-rates' ),
			array( $this, 'settings_section_callback' ),
			self::SETTING_PAGE
		);
	}

	public function register_setting() {
		$settings = ! is_array( self::SETTING_FIELDS ) ? array( self::SETTING_FIELDS ) : self::SETTING_FIELDS;

		foreach ( $settings as $setting_name => $setting ) {
			register_setting( self::OPTION_GROUP, $setting_name );
		}
	}

	public function register_setting_field() {
		$setting_fields = ! is_array( self::SETTING_FIELDS ) ? array( self::SETTING_FIELDS ) : self::SETTING_FIELDS;

		foreach ( $setting_fields as $field_name => $field ) {
			$args = ! empty( $field['args'] ) ? $field['args'] : array();

			$args['field_name'] = $field_name;

			add_settings_field(
				$field_name,
				$field['title'],
				array( $this, 'settings_field_callback' ),
				self::SETTING_PAGE,
				self::SETTING_NAME . '_section',
				$args
			);
		}
	}

	public function settings_section_callback() {
		if ( ! empty( self::SETTING_SECTION_DESCRIPTION ) ):
			?>
            <p>
				<?php echo self::SETTING_SECTION_DESCRIPTION; ?>
            </p>
		<?php
		endif;
	}


	public function settings_field_callback( $args ) {

		$field_name = $args['field_name'];

		$setting = get_option( $field_name );

		$type = ! empty( $args['type'] ) ? $args['type'] : 'text';

		switch ( $type ) {

			case 'text':
			case 'password':
			case 'url':
				?>

                <input type="<?php echo $type; ?>" name="<?php echo $field_name; ?>" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>" style="width:100%;">
				<?php
				break;

			case 'pages':
				echo wp_dropdown_pages(
					array(
						'name'              => $field_name,
						'echo'              => 0,
						'show_option_none'  => __( '&mdash; Select &mdash;' ),
						'option_none_value' => '0',
						'selected'          => $setting,
					)
				);
				break;
			case 'checkbox':
				?>
                <input type="<?php echo $type; ?>" name="<?php echo $field_name; ?>" value="Y" <?php echo 'Y' == $setting ? 'checked' : ''; ?>>
				<?php
				break;
			case 'editor':
				wp_editor( $setting, $field_name, array(
					'textarea_rows' => 10,
				) );
				break;
			case 'oxr_usage':
				echo $this->usage_html();
				break;
		}
	}

	public static function get_setting( $setting_name ) {
		$setting = false;

		if ( array_key_exists( $setting_name, self::SETTING_FIELDS ) ) {
			$setting = get_option( $setting_name );

			if ( ! empty( self::SETTING_FIELDS[ $setting_name ]['args']['type'] ) && 'editor' == self::SETTING_FIELDS[ $setting_name ]['args']['type'] ) {
				global $wp_embed;
				$content = $wp_embed->autoembed( $setting );
				$content = $wp_embed->run_shortcode( $content );
				$content = wpautop( $content );
				$setting = do_shortcode( $content );
			}
		}

		return $setting;
	}

	public function usage_html() {

		$usage = Currency_Exchange_Rates::getInstance()->get_usage();
		if ( is_wp_error( $usage ) || empty( $usage ) ) {
			return '';
		}

		$plan_name           = ! empty( $usage['data']['plan']['name'] ) ? $usage['data']['plan']['name'] : '';
		$plan_requests       = ! empty( $usage['data']['usage']['requests'] ) ? $usage['data']['usage']['requests'] : '';
		$plan_requests_quota = ! empty( $usage['data']['usage']['requests_quota'] ) ? $usage['data']['usage']['requests_quota'] : '';

		ob_start();
		?>

        <div>
            <span><b><?php _e( 'Plan:', 'currency-exchange-rates' ); ?></b></span> <span><?php echo $plan_name; ?></span>
        </div>
        <div>
            <span><b><?php _e( 'Usage:', 'currency-exchange-rates' ); ?></b></span> <span><?php echo $plan_requests; ?>/<?php echo $plan_requests_quota; ?></span>
        </div>

		<?php
		return ob_get_clean();
	}

}