<?php namespace BEA\ACF_Options_For_Polylang;

class Main {
	/**
	 * Use the trait
	 */
	use Singleton;

	protected function init() {
		// Set the setting's lang
		add_filter( 'acf/validate_post_id', [ $this, 'set_options_id_lang' ], 10, 2 );

		// Set Polylang current lang
		add_filter( 'acf/settings/current_language', [ $this, 'set_current_site_lang' ] );

		// Get default Polylang's option page value
		add_filter( 'acf/load_value', [ $this, 'get_default_value' ], 10, 3 );

		add_action( 'init', [ $this, 'init_translations' ] );
	}

	/**
	 * Get the current Polylang's locale or the wp's one
	 *
	 * @author Maxime CULEA
	 *
	 * @return bool|string
	 */
	public function set_current_site_lang() {
		return pll_current_language( 'locale' );
	}

	/**
	 * Load default value (all languages) in front if none found for an acf option
	 *
	 * @param $value
	 * @param $post_id
	 * @param $field
	 *
	 * @author Maxime CULEA
	 *
	 * @return mixed|string|void
	 */
	public function get_default_value( $value, $post_id, $field ) {
		if ( is_admin() || ! self::is_option_page( $post_id ) ) {
			return $value;
		}

		/**
		 * Activate or deactivate the default value (all languages)
		 *
		 * @since 1.0.2
		 */
		if ( ! apply_filters( 'bea.aofp.get_default', true ) ) {
			return $value;
		}

		/**
		 * According to his type, check the value to be not an empty string.
		 * While false or 0 could be returned, so "empty" method could not be here useful.
		 *
		 * @see   https://github.com/atomicorange : Thx to atomicorange for the issue
		 *
		 * @since 1.0.1
		 */
		if ( ! is_null( $value ) ) {
			if ( is_array( $value ) ) {
				// Get from array all the not empty strings
				$is_empty = array_filter( $value, function ( $value_c ) {
					return "" !== $value_c;
				} );

				if ( ! empty( $is_empty ) ) {
					// Not an array of empty values
					return $value;
				}
			} else {
				if ( "" !== $value ) {
					// Not an empty string
					return $value;
				}
			}
		}

		/**
		 * Delete filters for loading "default" Polylang saved value
		 * and for avoiding infinite looping on current filter
		 */
		remove_filter( 'acf/settings/current_language', [ $this, 'set_current_site_lang' ] );
		remove_filter( 'acf/load_value', [ $this, 'get_default_value' ] );

		$post_id = Helpers::original_option_id( $post_id );

		// Get the "All language" value
		$value = acf_get_metadata( $all_language_post_id, $field['name'] );

		/**
		 * Re-add deleted filters
		 */
		add_filter( 'acf/settings/current_language', [ $this, 'set_current_site_lang' ] );
		add_filter( 'acf/load_value', [ $this, 'get_default_value' ], 10, 3 );

		return $value;
	}

	/**
	 * Get all registered options pages as array [ post_id => page title ]
	 *
	 * @since  1.0.2
	 * @author Maxime CULEA
	 *
	 * @return array
	 */
	function get_option_page_ids() {
		return wp_list_pluck( acf_options_page()->get_pages(), 'post_id' );
	}

	/**
	 * Check if the given post id is from an options page or not
	 *
	 * @param string $post_id
	 *
	 * @since  1.0.2
	 * @author Maxime CULEA
	 *
	 * @return bool
	 */
	function is_option_page( $post_id ) {
		$post_id = Helpers::original_option_id( $post_id );
		if ( false !== strpos( $post_id, 'options' ) ) {
			return true;
		}

		$options_pages = $this->get_option_page_ids();
		if ( ! empty( $options_pages ) && in_array( $post_id, $options_pages ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Manage to change the post_id with the current lang to save option against
	 *
	 * @param string $future_post_id
	 * @param string $original_post_id
	 *
	 * @since  1.0.2
	 * @author Maxime CULEA
	 *
	 * @return string
	 */
	function set_options_id_lang( $future_post_id, $original_post_id ) {
		// Only on custom post id option page
		$options_pages = $this->get_option_page_ids();
		if ( empty( $options_pages ) || ! in_array( $original_post_id, $options_pages ) ) {
			return $future_post_id;
		}

		$dl = acf_get_setting( 'default_language' );
		$cl = acf_get_setting( 'current_language' );
		if ( $cl && $cl !== $dl ) {
			$future_post_id .= '_' . $cl;
		}

		return $future_post_id;
	}

	/**
	 * Load the plugin translation
	 */
	public function init_translations() {
		// Load translations
		load_plugin_textdomain( 'bea-acf-options-for-polylang', false, BEA_ACF_OPTIONS_FOR_POLYLANG_PLUGIN_DIRNAME . '/languages' );
	}
}
