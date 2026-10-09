<?php
/**
 * Settings under WooCommerce, Products, quantity calculator.
 *
 * Without this page every shop would have to set the waste allowance and the
 * selection of product groups in code. An agency can do that, a shop
 * owner cannot.
 *
 * @package FlooringCalculator
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Settings page.
 */
final class CAQC_Settings {

	const SECTION = 'caqc';

	const OPTION_WASTE        = 'caqc_waste_percent';
	const OPTION_WASTE_LENGTH = 'caqc_waste_percent_length';
	const OPTION_TYPES        = 'caqc_enabled_types';
	const OPTION_SHOW_TOTAL   = 'caqc_show_total';
	const OPTION_STOCK        = 'caqc_stock_in_size';
	const OPTION_LENGTH_HINT  = 'caqc_length_hint';

	/**
	 * Hooks the page into the WooCommerce settings.
	 */
	public static function init(): void {
		add_filter( 'woocommerce_get_sections_products', array( __CLASS__, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( __CLASS__, 'settings' ), 10, 2 );

		// A custom section gets its own save routine. Without it the page
		// shows the fields, but the input ends up nowhere.
		add_action( 'woocommerce_update_options_products_' . self::SECTION, array( __CLASS__, 'save' ) );

		add_filter( 'plugin_action_links_' . CAQC_PLUGIN_BASENAME, array( __CLASS__, 'action_link' ) );
	}

	/**
	 * Registers the section.
	 *
	 * @param array $sections Existing sections.
	 * @return array
	 */
	public static function add_section( $sections ) {
		$sections[ self::SECTION ] = __( 'Flooring calculator', 'flooring-calculator' );

		return $sections;
	}

	/**
	 * Link in the plugin list.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public static function action_link( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=products&section=' . self::SECTION );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'flooring-calculator' ) . '</a>'
		);

		return $links;
	}

	/**
	 * The fields of the section.
	 *
	 * @param array  $settings Existing fields.
	 * @param string $section  Current section.
	 * @return array
	 */
	public static function settings( $settings, $section ) {
		if ( self::SECTION !== $section ) {
			return $settings;
		}

		$fields = array(
			array(
				'title' => __( 'Flooring calculator', 'flooring-calculator' ),
				'type'  => 'title',
				'desc'  => self::intro(),
				'id'    => 'caqc_options',
			),
			array(
				'title'             => __( 'Waste for area products (%)', 'flooring-calculator' ),
				'desc'              => __( 'Default the customer can add with a checkbox. Products can set their own value.', 'flooring-calculator' ),
				'id'                => self::OPTION_WASTE,
				'type'              => 'number',
				'default'           => '5',
				'custom_attributes' => array(
					'min'  => '0',
					'max'  => '100',
					'step' => '0.5',
				),
				'css'               => 'width: 80px;',
				'desc_tip'          => __( 'Applies to packs, rolls and panels. 0 hides the checkbox.', 'flooring-calculator' ),
			),
			array(
				'title'             => __( 'Waste for skirting and length products (%)', 'flooring-calculator' ),
				'desc'              => __( 'Default for skirting boards and goods sold by the meter. Products can set their own value.', 'flooring-calculator' ),
				'id'                => self::OPTION_WASTE_LENGTH,
				'type'              => 'number',
				'default'           => '0',
				'custom_attributes' => array(
					'min'  => '0',
					'max'  => '100',
					'step' => '0.5',
				),
				'css'               => 'width: 80px;',
				'desc_tip'          => __( 'Applies to meters / pieces and meters. 0 hides the checkbox.', 'flooring-calculator' ),
			),
			array(
				'title'    => __( 'Show total price', 'flooring-calculator' ),
				'desc'     => __( 'Show a running total below the calculator', 'flooring-calculator' ),
				'id'       => self::OPTION_SHOW_TOTAL,
				'type'     => 'checkbox',
				'default'  => 'yes',
				'desc_tip' => __( 'Turn off if your theme already shows a running total.', 'flooring-calculator' ),
			),
			array(
				'title'    => __( 'Convert stock display', 'flooring-calculator' ),
				'desc'     => __( 'Show stock in the product\'s measure instead of units', 'flooring-calculator' ),
				'id'       => self::OPTION_STOCK,
				'type'     => 'checkbox',
				'default'  => 'yes',
				'desc_tip' => __( '"534 in stock" becomes "534.06 m² in stock". Only affects area based quantity types.', 'flooring-calculator' ),
			),
			array(
				'title'       => __( 'Note for goods sold by the meter', 'flooring-calculator' ),
				'desc'        => __( 'Fixed text below the quantity field for the type Meters. Leave empty to show the product\'s piece length instead.', 'flooring-calculator' ),
				'id'          => self::OPTION_LENGTH_HINT,
				'type'        => 'text',
				'default'     => '',
				'placeholder' => __( 'Sold by the meter. Length per piece approx. 2.40 to 2.70 meters.', 'flooring-calculator' ),
				'css'         => 'min-width: 340px;',
			),
			array(
				'title'   => __( 'Available quantity types', 'flooring-calculator' ),
				'desc'    => __( 'Only the selected types appear in the product dropdown. Values already set are kept.', 'flooring-calculator' ),
				'id'      => self::OPTION_TYPES,
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'options' => CAQC_Types::options( true ),
				'default' => array_keys( CAQC_Types::all() ),
				'css'     => 'min-width: 340px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'caqc_options',
			),
		);

		return $fields;
	}

	/**
	 * Short introduction above the fields, including a note about a possible
	 * data migration.
	 */
	private static function intro(): string {
		$text = '<p>' . esc_html__(
			'The calculator appears on a product page once a quantity type is selected and a size per unit is entered under Product data, General.',
			'flooring-calculator'
		) . '</p>';

		$pending = CAQC_Migrate::pending();

		if ( ! empty( $pending ) ) {
			$text .= '<p><strong>' . esc_html__( 'Data from a previous solution found.', 'flooring-calculator' ) . '</strong> ';
			$text .= esc_html(
				sprintf(
					/* translators: %d: number of meta keys. */
					_n(
						'%d field still uses an old name. The plugin takes it over on the next activation.',
						'%d fields still use old names. The plugin takes them over on the next activation.',
						count( $pending ),
						'flooring-calculator'
					),
					count( $pending )
				)
			) . '</p>';
		}

		return $text;
	}

	/**
	 * Saves the fields of the plugin's own section.
	 */
	public static function save(): void {
		if ( ! class_exists( 'WC_Admin_Settings' ) ) {
			return;
		}

		WC_Admin_Settings::save_fields( self::settings( array(), self::SECTION ) );

		CAQC_Types::flush();
	}

	/**
	 * Unfiltered default waste allowance of a group in percent.
	 *
	 * Values outside 0 to 100 fall back to the option default, so a typo in
	 * the settings cannot double the order.
	 *
	 * @param string $group CAQC_Types::WASTE_AREA or CAQC_Types::WASTE_LENGTH.
	 */
	public static function waste_default( string $group ): float {
		if ( CAQC_Types::WASTE_LENGTH === $group ) {
			$percent = (float) get_option( self::OPTION_WASTE_LENGTH, 0 );

			return ( $percent < 0 || $percent > 100 ) ? 0.0 : $percent;
		}

		$percent = (float) get_option( self::OPTION_WASTE, 5 );

		return ( $percent < 0 || $percent > 100 ) ? 5.0 : $percent;
	}

	/**
	 * Default waste allowance for area products, filtered.
	 *
	 * Kept for code that knew only the single shop-wide value. Product
	 * specific values come from CAQC_Product::waste_percent().
	 */
	public static function waste_percent(): float {
		/**
		 * Allows changing the waste allowance per shop or product.
		 *
		 * @param float           $percent Percentage.
		 * @param WC_Product|null $product Product, null for the shop default.
		 */
		return (float) apply_filters( 'caqc_waste_percent', self::waste_default( CAQC_Types::WASTE_AREA ), null );
	}

	/**
	 * Fixed notice text for goods sold by the meter, empty if the product length
	 * should be shown.
	 */
	public static function length_hint(): string {
		return trim( (string) get_option( self::OPTION_LENGTH_HINT, '' ) );
	}

	/**
	 * Should the total price be shown?
	 */
	public static function show_total(): bool {
		return 'no' !== get_option( self::OPTION_SHOW_TOTAL, 'yes' );
	}

	/**
	 * Should the stock be converted?
	 */
	public static function stock_in_size(): bool {
		return 'no' !== get_option( self::OPTION_STOCK, 'yes' );
	}

	/**
	 * Which quantity types are available?
	 *
	 * @return array<int, string> Empty array means: all.
	 */
	public static function enabled_types(): array {
		$values = get_option( self::OPTION_TYPES, array() );

		return is_array( $values ) ? array_filter( array_map( 'strval', $values ) ) : array();
	}
}
