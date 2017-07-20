<?php
function optionsframework_rolescheck () {
	if ( current_user_can( 'edit_theme_options' ) ) {
		add_action( 'admin_menu', 'optionsframework_add_page');
		add_action( 'admin_init', 'optionsframework_init' );
		add_action( 'wp_before_admin_bar_render', 'optionsframework_adminbar' );
	}
}
add_action( 'init', 'optionsframework_rolescheck' );

function optionsframework_load_sanitization() {
	require_once dirname( __FILE__ ) . '/options-sanitize.php';
}
add_action( 'init', 'optionsframework_load_sanitization' );

function optionsframework_init() {

	require_once dirname( __FILE__ ) . '/options-interface.php';
	require_once dirname( __FILE__ ) . '/options-media-uploader.php';
	
	if ( $optionsfile = locate_template( array('options.php') ) ) {
		require_once($optionsfile);
	}
	else if ( file_exists( dirname( __FILE__ ) . '/options.php' ) ) {
		require_once dirname( __FILE__ ) . '/options.php';
	}
	
	$optionsframework_settings = get_option('optionsframework' );
	
	optionsframework_option_name();
	
	if ( isset( $optionsframework_settings['id'] ) ) {
		$option_name = $optionsframework_settings['id'];
	}
	else {
		$option_name = 'options_framework_theme';
	}
	if ( ! get_option($option_name) ) {
		optionsframework_setdefaults();
	}
	
	register_setting( 'optionsframework', $option_name, 'optionsframework_validate' );

	add_filter( 'option_page_capability_optionsframework', 'optionsframework_page_capability' );
}

function optionsframework_page_capability( $capability ) {
	return 'edit_theme_options';
}

function optionsframework_setdefaults() {
	
	$optionsframework_settings = get_option( 'optionsframework' );
	$option_name = $optionsframework_settings['id'];

	if ( isset( $optionsframework_settings['knownoptions'] ) ) {
		$knownoptions =  $optionsframework_settings['knownoptions'];
		if ( !in_array( $option_name, $knownoptions ) ) {
			array_push( $knownoptions, $option_name );
			$optionsframework_settings['knownoptions'] = $knownoptions;
			update_option( 'optionsframework', $optionsframework_settings );
		}
	} else {
		$newoptionname = array( $option_name );
		$optionsframework_settings['knownoptions'] = $newoptionname;
		update_option( 'optionsframework', $optionsframework_settings );
	}
	
	$options = optionsframework_options();
	
	$values = of_get_default_values();
	
	if ( isset( $values ) ) {
		add_option( $option_name, $values ); // Add option with default settings
	}
}

/* Add a subpage called "Theme Options" to the appearance menu. */
if ( !function_exists( 'optionsframework_add_page' ) ) {

	function optionsframework_add_page() {
		$label = ( is_rtl() ) ? __("Options", ThemeID) . ' ' . ThemeName : ThemeName . ' ' . __("Options", ThemeID);
		$of_page = add_theme_page( $label, $label, 'edit_posts', 'options-framework','optionsframework_page' );

		// Load the required CSS and javscript
		add_action( 'admin_enqueue_scripts', 'optionsframework_load_scripts' );
		add_action( 'admin_print_styles-' . $of_page, 'optionsframework_load_styles' );
	}
	
}

/* Loads the CSS */

function optionsframework_load_styles() {
	wp_enqueue_style( 'optionsframework', get_template_directory_uri() . '/assets/css/admin.css' );
	if ( !wp_style_is( 'wp-color-picker','registered' ) ) {
		wp_register_style( 'wp-color-picker', ScratchOP.'css/color-picker.min.css' );
	}
	wp_enqueue_style( 'wp-color-picker' );
}

/* Loads the javascript */

function optionsframework_load_scripts( $hook ) {

	if ( 'appearance_page_options-framework' != $hook )
        return;

	// Enqueue colorpicker scripts for versions below 3.5 for compatibility
	if ( !wp_script_is( 'wp-color-picker', 'registered' ) ) {
		wp_register_script( 'iris', ScratchOP . 'js/iris.min.js', array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), false, 1 );
		wp_register_script( 'wp-color-picker', ScratchOP . 'js/color-picker.min.js', array( 'jquery', 'iris' ) );
		$colorpicker_l10n = array(
			'clear' => __( 'Clear',ThemeID ),
			'defaultString' => __( 'Default', ThemeID ),
			'pick' => __( 'Select Color', ThemeID )
		);
		wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );
	}
	
	// Enqueue custom option panel JS
	wp_enqueue_script( 'options-custom', ScratchOP . 'js/options-custom.js', array( 'jquery','wp-color-picker' ) );

	// Inline scripts from options-interface.php
	add_action( 'admin_head', 'of_admin_head' );
}

function of_admin_head() {
	// Hook to add custom scripts
	do_action( 'optionsframework_custom_scripts' );
}

/* 
 * Builds out the options panel.
 *
 * If we were using the Settings API as it was likely intended we would use
 * do_settings_sections here.  But as we don't want the settings wrapped in a table,
 * we'll call our own custom optionsframework_fields.  See options-interface.php
 * for specifics on how each individual field is generated.
 *
 * Nonces are provided using the settings_fields()
 *
 */

if ( !function_exists( 'optionsframework_page' ) ) :
function optionsframework_page() {
	settings_errors(); ?>

	<div id="optionsframework-wrap" class="wrap">
    <?php screen_icon( 'themes' ); ?>
    <nav class="nav-tab-wrapper">
        <?php echo optionsframework_tabs(); ?>
    </nav>

    <div id="optionsframework-metabox" class="metabox-holder">
	    <div id="optionsframework" class="postbox">
			<form action="options.php" method="post">
			<?php settings_fields( 'optionsframework' ); ?>
			<?php optionsframework_fields(); /* Settings */ ?>
			<div id="optionsframework-submit">
				<input type="submit" class="button-primary" name="update" value="<?php esc_attr_e( 'Save Options', ThemeID ); ?>" />
				<input type="submit" class="reset-button button-secondary" name="reset" value="<?php esc_attr_e( 'Restore Defaults', 'options_framework_theme' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to reset. Any theme settings will be lost!', ThemeID ) ); ?>' );" />
				<div class="clear"></div>
			</div>
			</form>
		</div> <!-- / #container -->
	</div>
	<?php do_action( 'optionsframework_after' ); ?>
	</div> <!-- / .wrap -->
	
<?php
}
endif;

/**
 * Validate Options.
 *
 * This runs after the submit/reset button has been clicked and
 * validates the inputs.
 *
 * @uses $_POST['reset'] to restore default options
 */
function optionsframework_validate( $input ) {

	/*
	 * Restore Defaults.
	 *
	 * In the event that the user clicked the "Restore Defaults"
	 * button, the options defined in the theme's options.php
	 * file will be added to the option for the active theme.
	 */

	if ( isset( $_POST['reset'] ) ) {
		add_settings_error( 'options-framework', 'restore_defaults', __( 'Default options restored.', ThemeID ), 'updated fade' );
		return of_get_default_values();
	}
	
	/*
	 * Update Settings
	 *
	 * This used to check for $_POST['update'], but has been updated
	 * to be compatible with the theme customizer introduced in WordPress 3.4
	 */
	 
	$clean = array();
	$options = optionsframework_options();
	foreach ( $options as $option ) {

		if ( ! isset( $option['id'] ) ) {
			continue;
		}

		if ( ! isset( $option['type'] ) ) {
			continue;
		}

		$id = preg_replace( '/[^a-zA-Z0-9._\-]/', '', strtolower( $option['id'] ) );

		// Set checkbox to false if it wasn't sent in the $_POST
		if ( 'checkbox' == $option['type'] && ! isset( $input[$id] ) ) {
			$input[$id] = false;
		}

		// Set each item in the multicheck to false if it wasn't sent in the $_POST
		if ( 'multicheck' == $option['type'] && ! isset( $input[$id] ) ) {
			foreach ( $option['options'] as $key => $value ) {
				$input[$id][$key] = false;
			}
		}

		// For a value to be submitted to database it must pass through a sanitization filter
		if ( has_filter( 'of_sanitize_' . $option['type'] ) ) {
			$clean[$id] = apply_filters( 'of_sanitize_' . $option['type'], $input[$id], $option );
		}
	}
	
	// Hook to run after validation
	do_action( 'optionsframework_after_validate', $clean );
	
	return $clean;
}

/**
 * Display message when options have been saved
 */
 
function optionsframework_save_options_notice() {
	add_settings_error( 'options-framework', 'save_options', __( 'Options saved.', ThemeID ), 'updated fade' );
}

add_action( 'optionsframework_after_validate', 'optionsframework_save_options_notice' );

/**
 * Format Configuration Array.
 *
 * Get an array of all default values as set in
 * options.php. The 'id','std' and 'type' keys need
 * to be defined in the configuration array. In the
 * event that these keys are not present the option
 * will not be included in this function's output.
 *
 * @return    array     Rey-keyed options configuration array.
 *
 * @access    private
 */
 
function of_get_default_values() {
	$output = array();
	$config = optionsframework_options();
	foreach ( (array) $config as $option ) {
		if ( ! isset( $option['id'] ) ) {
			continue;
		}
		if ( ! isset( $option['std'] ) ) {
			continue;
		}
		if ( ! isset( $option['type'] ) ) {
			continue;
		}
		if ( has_filter( 'of_sanitize_' . $option['type'] ) ) {
			$output[$option['id']] = apply_filters( 'of_sanitize_' . $option['type'], $option['std'], $option );
		}
	}
	return $output;
}

/**
 * Add Theme Options menu item to Admin Bar.
 */

function optionsframework_adminbar() {

	global $wp_admin_bar;
	
	$label = ( is_rtl() ) ? __("Options", ThemeID) . ' ' . ThemeName : ThemeName . ' ' . __("Options", ThemeID);
	
	$wp_admin_bar->add_menu( array(
			'parent' => 'appearance',
			'id' => 'of_theme_options',
			'title' => $label,
			'href' => admin_url( 'themes.php?page=options-framework' )
		));
}

if ( ! function_exists( 'of_get_option' ) ) {

	/**
	 * Get Option.
	 *
	 * Helper function to return the theme option value.
	 * If no value has been saved, it returns $default.
	 * Needed because options are saved as serialized strings.
	 */
	 
	function of_get_option( $name, $default = false ) {
		$config = get_option( 'optionsframework' );

		if ( ! isset( $config['id'] ) ) {
			return $default;
		}

		$options = get_option( $config['id'] );

		if ( isset( $options[$name] ) ) {
			return $options[$name];
		}

		return $default;
	}
}