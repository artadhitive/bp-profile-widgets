<?php

/*
Plugin Name: BP Profile Widgets
Plugin URI: http://slushman.com/plugins/bp-profile-widgets
Description: BP Profile Widgets allows BuddyPress users to embed a music player, a video player, a photo gallery, and/or a custom text widget on the sidebar of the user's profile page using custom profile fields from their profile form. This plugin requires that BuddyPress be installed and the theme have at least one sidebar.
Version: 0.4.1
Author: Slushman
Author URI: http://slushman.com
License: GPLv2

**************************************************************************

  Copyright (C) 2013 Slushman

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General License for more details.

  You should have received a copy of the GNU General License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************

ToDo:

* Figure out a way to get multiple instances of each widget.
* 	Create the BP profile fields when a widget is dragged onto a sidebar.
* 	Name them using the get_field_name and get_field_id functions
*/

$widgets = array( 'music-player', 'video-player', 'photo-gallery', 'text', 'rss' );

foreach ( $widgets as $widget ) {

	require_once( plugin_dir_path( __FILE__ ) . '/inc/bp-profile-' . $widget . '-widget.php' );

} // End of $files foreach loop

$tools = array( 'slushman_toolkit', 'make_fields' );

foreach ( $tools as $tool ) {

	require_once( plugin_dir_path( __FILE__ ) . '/toolkit/' . $tool . '.php' );

} // End of $files foreach loop

if ( !class_exists( 'Slushman_BP_Profile_Widgets' ) ) { //Start Class

	class Slushman_BP_Profile_Widgets {
	
		public static $instance;

		private $make_fields;
		private $slushkit;
		private $options;

/**
 * Constructor
 */	
		function __construct() {
		
			self::$instance = $this;

			// Include the config file
            require_once( plugin_dir_path( __FILE__ ) . 'inc/config.php' );

			$sets_args = array( 'constants' => $this->constants, 'sections' => $this->sections, 'fields' => $this->fields );

			$this->make_fields 	= new Slushman_Toolkit_Make_Fields;
			$this->slushkit 	= new Slushman_Toolkit;
			
			// Runs when plugin is activated
			register_activation_hook( __FILE__, array( $this, 'install' ) );

			// Register and define the settings
			add_action( 'admin_init', array( $this, 'settings_reg' ) );

			// Add menu
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			
			//	Add "Settings" link to plugin page
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) , array( $this, 'settings_link' ) );
			
			// Create the Buddypress profile field group
			add_action( 'admin_init', array( $this, 'create_profile_group' ) );
			
			// Create the Buddypress profile fields
			add_action( 'admin_init', array( $this, 'create_profile_fields' ) );
			
			// Register the widget if its selected
            add_action( 'widgets_init', array( $this, 'widgets_init' ) );

            // Add oEmbed support for uStream & new Viddler
            add_action( 'plugins_loaded', array( $this, 'enable_oembed_ustream' ), 7 );

            // Add oEmbed support for Mixcloud
            add_action( 'plugins_loaded', array( $this, 'enable_oembed_mixcloud' ), 7 );

			$this->constants 	= $this->constants + array( 'file' => __FILE__ );
			$this->options 		= (array) get_option( $this->constants['name'] );

		} // End of __construct()

/**
 * Creates the plugin settings
 *
 * Creates an array containing each setting and sets the default values to blank.
 * Then saves the array in the plugin option.
 *
 * @since	0.1
 * 
 * @uses	settings_init
 */	
		function install() {

			$this->settings_init();

		} // End of install()



/* ==========================================================================
   Plugin Settings
   ========================================================================== */		

/**
 * Creates the plugin settings
 *
 * Creates an array containing each setting and sets the default values to blank.
 * Then saves the array in the plugin option.
 *
 * @since	0.1
 * 
 * @uses	update_option
 */		
		function settings_init() {

			foreach ( $this->fields as $field ) {

				$settings[$field['underscored']] = $field['value'];

			} // End of $fields foreach loop
		
			update_option( $this->constants['name'], $settings );
			
		} // End of settings_init()

/**
 * Registers the plugin option, settings, and sections
 *
 * Instead of writing the registration for each field, I used a foreach loop to write them all.
 * add_settings_field has an argument that can pass data to the callback, which I used to send the specifics
 * of each setting field to the callback that actually creates the setting field. 
 *
 * @since	0.1
 * 
 * @uses	register_setting
 * @uses	add_settings_section
 * @uses	add_settings_field
 */	
		function settings_reg() {

			$options = (array) get_option( $this->constants['name'] );

			register_setting( 
				$this->constants['name'], 
				$this->constants['name'],
				array( $this, 'validate_options' )
			);

			foreach ( $this->sections as $section ) {

				if ( isset( $section['desc'] ) && !empty( $section['desc'] ) ) {
	        
	                $section['desc'] 	= '<div class="inside">' . $section['desc'] . '</div>';
	                $callback 			= create_function( '', 'echo "' . str_replace( '"', '\"', $section['desc'] ) . '";' );
	        
	            } else {
	        
	                $callback = '__return_false';
	        
	            } // End of description check

				add_settings_section( 
					$this->constants['prefix'] . $section['underscored'], 
					$section['name'] . ' Settings', 
					$callback, 
					$this->constants['name']
				);

			} // End of $sections foreach loop
			
			foreach ( $this->fields as $field ) {

				$defaults 	= array( 'desc' => '', 'id' => '', 'type' => 'text', 'sels' => array(), 'size' => '' );
				$field 		= wp_parse_args( $field, $defaults );

				$corv 				= ( $field['type'] == 'checkbox' ? 'check' : 'value' );
				$dorl				= ( $field['type'] == 'checkbox' ? 'label' : 'desc' );
				$args[$corv] 		= $options[$field['underscored']];
				$args[$dorl] 		= $field['desc'];
				$args['blank']		= ( $field['type'] == 'select' ? TRUE : '' );
				$args['class']		= $this->constants['prefix'] . $field['underscored'];
				$args['id'] 		= $field['underscored'];
				$args['name'] 		= $this->constants['name'] . '[' . $field['underscored'] . ']';
				$args['selections']	= $field['sels'];
				$args['size']		= $field['size'];
				$args['type'] 		= $field['type'];
				
				add_settings_field(
					$this->constants['prefix'] . $field['underscored'] . '_field', 
					$field['name'], 
					array( $this, 'create_settings' ), 
					$this->constants['name'],
					$this->constants['prefix'] . $field['section'],
					$args
				);
				
			} // End of $fields foreach

		} // End of settings_reg()

/**
 * Creates the settings fields
 *
 * Accepts the $params from settings_reg() and creates a setting field
 *
 * @since	0.1
 *
 * @params	$params		The data specific to this setting field, comes from settings_reg()
 * 
 * @uses	checkbox
 */	
 		function create_settings( $params ) {

 			$defaults 	= array( 'blank' => '', 'check' => '', 'class' => '', 'desc' => '', 'id' => '', 'label' => '', 'name' => '', 'selections' => '', 'size' => '', 'type' => 'text', 'value' => '' );
 			$args 		= wp_parse_args( $params, $defaults );
 					
 			switch ( $args['type'] ) {
	 			
	 			case ( 'email' )		:
	 			case ( 'number' )		:
	 			case ( 'tel' ) 			: 
	 			case ( 'url' ) 			: 
	 			case ( 'text' ) 		: echo $this->make_fields->make_text( $args ); break;
	 			case ( 'checkbox' ) 	: echo $this->make_fields->make_checkbox( $args ); break;
	 			case ( 'textarea' )		: echo $this->make_fields->make_textarea( $args ); break;
	 			case ( 'checkboxes' ) 	: echo $this->make_fields->make_checkboxes( $args ); break;
	 			case ( 'radios' ) 		: echo $this->make_fields->make_radios( $args ); break;
	 			case ( 'select' )		: echo $this->make_fields->make_select( $args ); break;
	 			case ( 'file' )			: echo $this->make_fields->make_file( $args ); break;
	 			case ( 'password' )		: echo $this->make_fields->make_password( $args ); break;
	 			
 			} // End of $inputtype switch
			
		} // End of create_settings_fn()

/**
 * Validates the plugin settings before they are saved
 *
 * Loops through each plugin setting and sanitizes the data before returning it.
 *
 * @since	0.1
 *
 * @uses    sanitize_email
 * @uses    esc_url
 * @uses    sanitize_text_field
 * @uses    esc_textarea
 * @uses    sanitize_phone
 */				
		function validate_options( $input ) {

			foreach ( $this->fields as $field ) {

				$name = $field['underscored'];
			
				switch ( $field['type'] ) {
	 			
		 			case ( 'email' )		: $valid[$name] = sanitize_email( $input[$name] ); break;
		 			case ( 'number' )		: $valid[$name] = intval( $input[$name] ); break;
		 			case ( 'url' ) 			: $valid[$name] = esc_url( $input[$name] ); break;
		 			case ( 'text' ) 		: $valid[$name] = sanitize_text_field( $input[$name] ); break;
		 			case ( 'textarea' )		: $valid[$name] = esc_textarea( $input[$name] ); break;
		 			case ( 'checkgroup' ) 	: 
		 			case ( 'radios' ) 		: 
		 			case ( 'select' )		: $valid[$name] = strip_tags( $input[$name] ); break;
		 			case ( 'tel' ) 			: $valid[$name] = $this->slushkit->sanitize_phone( $input[$name] ); break;
		 			case ( 'checkbox' ) 	: $valid[$name] = ( isset( $input[$name] ) ? 1 : 0 ); break;
		 			
	 			} // End of $inputtype switch
			
			} // End of $checks foreach

			return $valid;
		
		} // End of validate_options()

/**
 * Creates the settings page
 *
 * @since	0.1
 *
 * @uses	get_plugin_data
 * @uses	plugins_url
 * @uses	settings_fields
 * @uses	do_settings_sections
 * @uses	submit_button
 */					
		function get_page() {

			$plugin = get_plugin_data( $this->constants['file'] ); ?>
			<div class="wrap">
			<div class="icon32" style="background-image:url(<?php echo plugins_url( 'images/logo.png', $this->constants['file'] ); ?>); background-repeat:no-repeat;"></div>
			<h2><?php echo $plugin['Name']; ?></h2><?php
			//settings_errors();
			?><form method="post" action="options.php"><?php
			
				settings_fields( $this->constants['name'] );
				do_settings_sections( $this->constants['name'] );
				echo '<br />'; 
				submit_button(); ?>
				
			</form>
			</div><?php

		} // End of get_page()

/**
 * Adds a link to the plugin settings page to the plugin's listing on the plugin page
 *
 * @since	0.1
 * 
 * @uses	admin_url
 */			
		function settings_link( $links ) {
		
			$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=' . $this->constants['name'] ), __( 'Settings' ) );
			
			array_unshift( $links, $settings_link );
			
			return $links;
			
		} // End of settings_link()

/**
 * Adds the plugin settings page to the appropriate admin menu
 *
 * @since	0.1
 * 
 * @uses	add_options_page
 */				
		function add_menu() {

			if ( $this->constants['menu'] == 'options' ) {

				add_options_page( 
					__( $this->constants['plug'] . ' Settings' ), 
					__( $this->constants['plug'] ), 
					'manage_options', 
					$this->constants['name'], 
					array( $this, 'get_page' ) 
				);

			} elseif ( $this->constants['menu'] == 'submenu' ) {

				add_submenu_page(
					'edit.php?post_type=' . $this->constants['cpt'],
					__( $this->constants['plug'] . ' Settings' ),
					__( 'Settings' ),
					'edit_posts',
					$this->constants['slug'] . '-settings',
					array( $this, 'get_page' )
				);

			} // End of menu check
		
		} // End of add_menu()
   

		
/* ==========================================================================
   Plugin Functions
   ========================================================================== */	
		
/**
 * Create the XProfile field group for the widgets
 *
 * Check options for if any widgets are selected. If at least is selected, see if the field group 
 * already exists. If not, create it.
 *
 * @since	0.1
 * 
 * @uses	get_xprofile_group_id_from_name
 * @uses	xprofile_insert_field_group
 */		
		function create_profile_group() {
			
			$sel = 0;
			
			foreach ( $this->fields as $field ) {
			
				if ( $this->options[$field['underscored']] == 1 ) { 
					
						$sel++;
					
				} // End of option check
				
			} // End of $fields foreach

			$groupcheck = $this->slushkit->xprofile_get_group_id_from_name( 'Profile Widgets' );

			if ( $sel > 0 && !$groupcheck ) {
				
				$group_args['name'] = 'Profile Widgets';
				
				xprofile_insert_field_group( $group_args );
				
			} // End of $sel check
			
		} // End of 
		
/**
 * Create the custom XProfile fields for each widget
 *
 * Checks for the existance of the Profile Widgets field group. Checks each option field to see which are 
 * selected and if they already exists. If its selected, but doesn't exist, it gets created.
 * 
 * If a widget is not selected, it gets deleted.
 *
 * @since	0.1
 * 
 * @uses	get_xprofile_group_id_from_name
 * @uses	xprofile_insert_field
 * @uses	xprofile_delete_field
 */
		function create_profile_fields() {
		
			$group_id 	= $this->xprofile_get_group_id_from_name( 'Profile Widgets' );
			$i 			= 0;

			if ( $group_id == 0 ) { return; }
							
			$fields[$i]['widget'] 	= 'Music Player';
			$fields[$i]['name'] 	= 'Music Player URL';
			$fields[$i]['type'] 	= 'textbox';
			$fields[$i]['desc'] 	= 'Please enter the URL for your album / set / profile from any of the following services: Bandcamp, SoundCloud, Reverbnation, Tunecore, Mixcloud, or Noisetrade.';
			$i++;

			$fields[$i]['widget'] 	= 'Music Player';
			$fields[$i]['name'] 	= 'Music Player Role';
			$fields[$i]['type'] 	= 'textbox';
			$fields[$i]['desc'] 	= 'Please explain your role in the music (artist, writer, player, producer, etc).';
			$i++;
			
			$fields[$i]['widget'] 	= 'Video Player';
			$fields[$i]['name'] 	= 'Video Player URL';
			$fields[$i]['type'] 	= 'textbox';
			$fields[$i]['desc'] 	= 'Please enter the URL to the YouTube, Vimeo, Veoh, DailyMotion, Blip.tv, uStream, or Facebook video you want to display on your profile.';
			$i++;
			
			$fields[$i]['widget'] 	= 'Video Player';
			$fields[$i]['name'] 	= 'Video Player Role';
			$fields[$i]['type'] 	= 'textbox';
			$fields[$i]['desc'] 	= 'Please explain your role in the video (actor, writer, crew, producer, etc).';
			$i++;
			
			$fields[$i]['widget'] 	= 'Photo Gallery';
			$fields[$i]['name'] 	= 'Photo Gallery URL';
			$fields[$i]['type'] 	= 'textbox';
			$fields[$i]['desc'] 	= 'Please enter the URL for the set / gallery / album / profile from any of the following services: Flickr, Picasa, Photobucket, Fotki, dotPhoto, or Imgur.';
			$i++;

			$fields[$i]['widget'] 	= 'Photo Gallery';
			$fields[$i]['name'] 	= 'Photo Gallery Role';
			$fields[$i]['type'] 	= 'textbox';
			$fields[$i]['desc'] 	= 'Please explain your role in the gallery (model, photographer, editor, etc).';
			$i++;
			
			$fields[$i]['widget'] 	= 'Text Box';
			$fields[$i]['name'] 	= 'Custom Text Box';
			$fields[$i]['type'] 	= 'textarea';
			$fields[$i]['desc'] 	= 'Please enter the text you want to appear on your profile. HTML is allowed.';
			$i++;

			$fields[$i]['widget'] 	= 'RSS';
			$fields[$i]['name'] 	= 'RSS Feed URL';
			$fields[$i]['type'] 	= 'textbox';
			$fields[$i]['desc'] 	= 'Please enter the URL for the RSS or Atom feed you want to appear on your profile.';
			$i++;
			
			foreach ( $fields as $key => $field ) {
				
				$under 	= str_replace( ' ', '_', strtolower( $field['widget'] ) );
				$check 	= $this->options['BP_profile_' . $under . '_widget'];

				if ( $check == 1 ) {
				
					$field_args['field_group_id'] 		= $group_id;
					$field_args['name'] 				= $field['name'];
					$field_args['can_delete'] 			= false;
					$field_args['field_order'] 			= $key + 1;
					$field_args['is_required'] 			= false;
					$field_args['type'] 				= $field['type'];
					$field_args['description']			= $field['desc'];
					$fields_args['default-visibility'] 	= 'adminsonly';
					
					$exists = xprofile_get_field_id_from_name( $field['name'] );
						
					if ( empty( $exists ) ) {
					
						$field_id = xprofile_insert_field( $field_args );

					} // End of $textfield empty check
				
					if ( !empty( $field_id ) ) {
						
						bp_xprofile_update_field_meta( $field_id, 'default_visibility', 'adminsonly' );
						bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', 'disabled' );

					} // End of $field_id check
				
				} elseif ( $check == 0 ) {
					
					$id = xprofile_get_field_id_from_name( $field['name'] );
					
					if ( !empty( $id ) ) {
				
						xprofile_delete_field( $id );
						
					} // End of $id1 empty check
					
				} // End of $selcheck check
				
			} // End of $fields foreach

		} // End of create_profile_fields()

/**
 * Adds uStream to the list of oEmbed providers
 * 
 * @since	0.1
 *
 * @uses wp_oembed_add_provider
 */
		function enable_oembed_ustream() {

			wp_oembed_add_provider( '#http://(www\.)?ustream.tv/*#i', 'http://www.ustream.tv/oembed', true );

		} // End of enable_oembed_ustream()

/**
 * Adds MixCloud to the list of oEmbed providers
 * 
 * @since	0.1
 *
 * @uses wp_oembed_add_provider
 */
		function enable_oembed_mixcloud() {

			wp_oembed_add_provider( '#http://(www\.)?mixcloud.com/*#i', 'http://www.mixcloud.com/oembed', true );

		} // End of enable_oembed_ustream()		

/**
 * Returns the oEmbed code from either a saved transient or from the service's site
 * 
 * @since	0.1
 *
 * @param 	string 			$url 		The URL of the item being requested
 * @param 	string 			$width 		The width of the media player being requested
 * @param 	string 			$height 	The height of the media player being requested
 *
 * @uses 	get_transient
 * @uses  	wp_oembed_get
 * @uses   	set_transient
 * 
 * @return 	bool | mixed 	$oembed		Returns FALSE or the oEmbed code
 */
		function oembed_transient( $url, $service = '', $width = '', $height = '' ) {

			//require_once( ABSPATH . WPINC . '/class-oembed.php' );

			if ( empty( $url ) ) { return FALSE; }

			$key 	= md5( $url );
			$oembed = get_transient( 'bppw_' . $key );

			if ( !$oembed && $url ) {

				if ( $service == 'viddler' ) {

				    $oem    = _wp_oembed_get_object();
				    $json   = 'http://www.viddler.com/oembed/?format=json&amp;url=' . urlencode( $url );
				    $return = $oem->fetch( $json, $url, array( 'width' => $width, 'height' => $height ) );
				    $oembed = $return->html;

				} else {

				    $oembed = wp_oembed_get( $url, array( 'width' => $width, 'height' => $height ) );

				}

				if ( !$oembed ) { return FALSE; }

				set_transient( 'bppw_' . $key, $oembed, HOUR_IN_SECONDS );

			}

			return $oembed;

		} // End of oembed_transient()

/**
 * Initiates widgets based on the plugin options
 *
 * Checks each option field to see which widgets are selected. If selected, it gets registered.
 * If not, it gets unregistered.
 *
 * @since	0.1
 * 
 * @uses	register_widget
 * @uses	unregister_widget
 */		
		function widgets_init() {

			foreach ( $this->fields as $field ) {
				
				if ( $this->options[$field['underscored']] == 1 ) { 
				
					register_widget( 'slushman_' . $field['underscored'] );
			
				} elseif ( $this->options[$field['underscored']] == 0 ) {
					
					unregister_widget( 'slushman_' . $field['underscored'] );
					
				} // End of options check
				
			} // End of $fields foreach
			
        } // End of widgets_init()



/* ==========================================================================
   Slushman Toolkit Functions
   ========================================================================== */

/**
 * Get the BuddyPress XProfile field group ID by the group's name
 *
 * Check the WP and BP databases. 
 * 
 * This function will return 0 under these conditions:
 *  If profile->table_name_fields is empty
 *  If the $field_name param is not set
 *  If the database search returns nothing
 * 
 * Otherwise, the BuddyPress xProfile tables are searched for the name of the 
 * field group name entered as the param.
 *
 * @uses	get_var
 *
 * @param	string	$group_name The name of the field group to find
 *
 * @return	int		The field group's ID - 0 if the group doesn't exist
 */	
		function xprofile_get_group_id_from_name( $group_name ) {
			
			global $wpdb, $bp;

			if ( empty( $bp->profile->table_name_fields ) || !isset( $group_name ) ) {
			
				return 0;
				
			} // End of table and field name checks

			$return = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_groups} WHERE name = %s", $group_name ) );
			
			return ( empty( $return ) ? 0 : $return );
		
		} // End of get_xprofile_group_id_from_name() 
				
	} // End of Slushman_BP_Profile_Widgets class
	
} // End of class check

$slushman_bp_profile_widgets = new Slushman_BP_Profile_Widgets();

?>