<?php
/**
 * Plugin Name:     Cronkite Ticker RSS
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          Jeremy Leggat
 * Text Domain:     Cronkite-Ticker-RSS
 * Domain Path:     /languages
 * Version:         0.6.0
 *
 * GitHub Plugin URI: https://github.com/cronkiteschool/Cronkite-Ticker-RSS-WP-Plugin
 * Primary Branch: main
 *
 * @package         Cronkite_Ticker_RSS
 */

// Your code starts here.
class CronkiteTicker
{
    public $pluginName = "csjticker";

	public function __construct() {
	    // Hook into the admin menu
	    add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );
	    add_action( 'admin_init', array( $this, 'setup_sections' ) );
	    add_action( 'admin_init', array( $this, 'setup_fields' ) );
	    add_action( 'init', array( $this, 'setup_rss' ) );
	}

    public function create_plugin_settings_page() {
	    // Add the menu item and page
	    $page_title = 'Ticker Feed Settings Page';
	    $menu_title = 'Ticker Feed Plugin';
	    $capability = 'manage_options';
	    $slug = 'csjticker_fields';
	    $callback = array( $this, 'plugin_settings_page_content' );

	    add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $slug, $callback, );
    }

	public function plugin_settings_page_content() { ?>
	    <div class="wrap">
		<h2>Cronkite Ticker Settings Page</h2>
		<form method="post" action="options.php">
		    <?php
			settings_fields( 'csjticker_fields' );
			do_settings_sections( 'csjticker_fields' );
			submit_button();
		    ?>
		</form>
	    </div> <?php
	}

	public function setup_sections() {
	    add_settings_section( 'config_section', 'Configuration', array( $this, 'section_callback' ), 'csjticker_fields' );
	}

	public function section_callback( $arguments ) {
	    switch( $arguments['id'] ){
		case 'config_section':
		    echo 'Set to read text from a Google Doc to RSS feed for the building\'s Ticker';
		    break;
		case 'test_section':
		    echo 'Test reading text from a Google Doc to RSS feed';
		    break;
	    }
	}

	public function setup_fields() {
	    $fields = array(
		array(
		    'uid' => 'feed_name',
		    'label' => 'Feed Name',
		    'section' => 'config_section',
		    'type' => 'text',
		    'options' => false,
		    'placeholder' => 'feedname',
		    'helper' => 'Keep this name simple as it is used to forms your this feed URL.',
		    'supplemental' => sprintf("The feed will be available at %s<em>%s</em>", site_url('/feed/'), get_option('feed_name')),
		    'default' => 'ticker'
		),
		array(
		    'uid' => 'gdoc_id',
		    'label' => 'Google Doc File ID',
		    'section' => 'config_section',
		    'type' => 'text',
		    'options' => false,
		    'placeholder' => 'documentId',
		    'helper' => 'This ID is the value between the "/d/" and the "/edit" in the URL of your document.',
		    'supplemental' => 'The document in your Google Drive has a URL like: https://docs.google.com/document/d/<em>documentId</em>/edit',
		    'default' => ''
		)
	    );
	    foreach( $fields as $field ){
		add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'csjticker_fields', $field['section'], $field );
		register_setting( 'csjticker_fields', $field['uid'] );
	    }
	}

	public function field_callback( $arguments ) {
	    $value = get_option( $arguments['uid'] ); // Get the current value, if there is one
	    if( ! $value ) { // If no value exists
		$value = $arguments['default']; // Set to our default
	    }

	    // Check which type of field we want
	    switch( $arguments['type'] ){
		case 'text': // If it is a text field
		    printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
		    break;
		case 'button': // If it is a button
		    printf( '<input name="%1$s" id="%1$s" type="%2$s" value="%3$s" />', $arguments['uid'], $arguments['type'], $value );
		    break;
	    }

	    // If there is help text
	    if( $helper = $arguments['helper'] ){
		printf( '<span class="helper"> %s</span>', $helper ); // Show it
	    }

	    // If there is supplemental text
	    if( $supplimental = $arguments['supplemental'] ){
		printf( '<p class="description">%s</p>', $supplimental ); // Show it
	    }
	}

	public function setup_rss() {
	    add_feed( get_option('feed_name'), array( $this, 'rss_callback' ) );
	}

	public function rss_callback() {
		$body     = $this->fetch_gdoc_text();
	    include_once( plugin_dir_path( __FILE__ ) . 'rss-cronkite-ticker.php' );
	}

	public function fetch_gdoc_text() {
		$url = sprintf("https://docs.google.com/document/d/%s/export?format=txt", get_option('gdoc_id'));
		$response = wp_safe_remote_get( $url );
		$body     = wp_remote_retrieve_body( $response );
		$body     = trim($body, "\xEF\xBB\xBF"); //remove hidden utf characters

		return sanitize_textarea_field( $body );
	}

}

// Create a new csjticker instance
new CronkiteTicker();
