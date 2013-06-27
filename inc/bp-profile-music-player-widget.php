<?php

class slushman_bp_profile_music_player_widget extends WP_Widget {
	
/**
 *Register widget with WordPress
 */
 	function __construct() {
 	
 		$name 					= 'BP Profile Music Player';
 		$opts['description'] 	= __( 'Add a music player to your BuddyPress profile page.', 'slushman-bp-profile-music-player' );
 		
 		parent::__construct( false, $name, $opts );
 		
		// Future i10n support
		// load_plugin_textdomain( PLUGIN_LOCALE, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		
		// Form fields
		// required: name, underscored, type, & value. optional: desc, sels, size
		$this->fields[] = array( 'name' => 'Title', 'underscored' => 'title', 'type' => 'text', 'value' => 'Music Player' );
		$this->fields[] = array( 'name' => 'Width', 'underscored' => 'width', 'type' => 'text', 'value' => '200px' );
		$this->fields[] = array( 'name' => 'Empty Message', 'underscored' => 'emptymsg', 'type' => 'text', 'value' => 'This user has not activated their music player.' );

 	} // End of __construct()

/**
 * The output of the front-end of the widget
 *
 * @param   array 	$instance  Previously saved values from database.
 *
 * @uses    xprofile_get_field_data
 * @uses    oembed_transient
 * @uses    find_on_page
 */
	function widget_output( $instance ) {

		global $slushman_bp_profile_widgets, $slushkit;

		$accountURL 	= xprofile_get_field_data( 'Music Player URL' );
	 	$description 	= xprofile_get_field_data( 'Music Player Role' );
	 	$width 			= $instance['width'];

	 	if ( !empty( $accountURL ) ) {
		 	
		 	$host 		= parse_url( $accountURL, PHP_URL_HOST );
		 	$exp		= explode( '.', $host );
		 	$service 	= ( count( $exp ) >= 3 ? $exp[1] : $exp[0] );
		 			 	
	 	} // End of $accountURL empty check
	 	
	 	if ( empty( $accountURL ) || empty( $service ) ) {

			echo '<p>' . ( !empty( $instance['emptymsg'] ) ? $instance['emptymsg'] : '' ) . '</p>';
		
		} else {

			$oembed = $slushman_bp_profile_widgets->oembed_transient( $accountURL, $service, $width );

			if ( !$oembed && $service == 'bandcamp' ) {
	 	
		 		// Input example: http://thevibedials.bandcamp.com/album/the-vibe-dials
		 	
		 		$id_args['start'] 	= 'item_id=';
		 		$id_args['end'] 	= '&item_type=album';
		 		$id_args['url'] 	= $accountURL;
		 		$bandcamp			= $slushkit->find_on_page( $id_args ); ?>
			 	
			 	<iframe width="150" height="295" style="position: relative; display: block; width: 150px; height: 295px;" src="http://bandcamp.com/EmbeddedPlayer/v=2/album=<?php echo $bandcamp; ?>/size=tall/bgcol=FFFFFF/linkcol=4285BB/" allowtransparency="true" frameborder="0"></iframe><?php
		 	
		 	} elseif ( !$oembed && $service == 'tunecore' ) {
		 	
		 		// Input example: http://www.tunecore.com/music/thevibedials
		 	
		 		$id_args['start'] 	= '<embed src="http://widget.tunecore.com/swf/tc_run_h_v2.swf?widget_id=';
		 		$id_args['end'] 	= '" type="application/x-shockwave-flash"';
		 		$id_args['url'] 	= $accountURL;
		 		$tunecore			= $slushkit->find_on_page( $id_args ); ?>
			 	
			 	<object width="160" height="400" class="tunecore"><param name="movie" value="http://widget.tunecore.com/swf/tc_run_v_v2.swf?widget_id=<?php echo $tunecore; ?>"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://widget.tunecore.com/swf/tc_run_v_v2.swf?widget_id=<?php echo $tunecore; ?>" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="160" height="400"></embed></object><?php
		 	
		 	} elseif ( !$oembed && $service == 'reverbnation' ) {
		 	
		 		// Input example: http://www.reverbnation.com/thevibedials
		 	
		 		$id_args['start'] 	= 'become_fan/';
		 		$id_args['end'] 	= '?onbecomefan';
		 		$id_args['url'] 	= $accountURL;
		 		$reverbnation		= $slushkit->find_on_page( $id_args ); ?>

		 		<iframe class="widget_iframe" src="http://www.reverbnation.com/widget_code/html_widget/artist_<?php echo $reverbnation; ?>?widget_id=50&pwc[design]=default&pwc[background_color]=%23333333&pwc[included_songs]=1&pwc[photo]=0%2C1&pwc[size]=fit" width="100%" height="320px" frameborder="0" scrolling="no"></iframe><?php
		 	
		 	} elseif ( !$oembed && $service == 'noisetrade' ) {
		 	
		 		// Input example: http://noisetrade.com/thevibedials/
		 	
		 		$id_args['start'] 	= 'content="http://s3.amazonaws.com/static.noisetrade.com/w/';
		 		$id_args['end'] 	= '/cover1500x1500max.jpg"/>';
		 		$id_args['url'] 	= $accountURL;
		 		$noisetrade			= $slushkit->find_on_page( $id_args ); ?>
			 	
		 		<iframe src="http://noisetrade.com/service/sharewidget/?id=<?php echo $noisetrade; ?>" width="100%" height="400" scrolling="no" frameBorder="0"></iframe><?php
		 	
		 	/*} elseif ( !$oembed && $service == 'mixcloud' ) {

		 		// Input Example: http://www.mixcloud.com/MarvinHumes/marvins-jls-mixtape/ ?>

		 		<iframe width="<?php echo $width; ?>" height="480" src="//www.mixcloud.com/widget/iframe/?feed=<?php echo urlencode( $accountURL ); ?>&embed_uuid=d08fec02-304a-4666-bfc1-2f9486f4632c&stylecolor=&embed_type=widget_standard" frameborder="0"></iframe><?php*/

		 	} else {

				// Input Examples: 
				// 		http://soundcloud.com/christopher-joel/sets/fantasy-world-1/
				// 		http://www.mixcloud.com/MarvinHumes/marvins-jls-mixtape/
		
				echo $oembed;

			} // End of embed codes

		} // End of empty checks
	 	
	 	echo '<p>' . ( isset( $description ) && !empty( $description ) ? $description : '' ) . '</p>';

	} // End of widget_output()

/**
 * Back-end widget form.
 *
 * @see		WP_Widget::form()
 *
 * @uses	wp_parse_args
 * @uses	esc_attr
 * @uses	get_field_id
 * @uses	get_field_name
 * @uses	checked
 *
 * @param	array	$instance	Previously saved values from database.
 */ 	
 	function form( $instance ) {

		global $slushman_bp_profile_widgets;
 	
		foreach ( $this->fields as $field ) {

			$corv 				= ( $field['type'] == 'checkbox' ? 'check' : 'value' );
			$args[$corv]		= ( isset( $instance[$field['underscored']] ) ? $instance[$field['underscored']] : $field['value'] );
			$args['blank']		= ( $field['type'] == 'select' ? TRUE : '' );
			$args['class']		= $field['underscored'] . ( $field['type'] == 'text' ? ' widefat' : '' );
			$args['desc'] 		= ( !empty( $field['desc'] ) ? $field['desc'] : '' );
			$args['id'] 		= $this->get_field_id( $field['underscored'] );
			$args['label']		= $field['name'];
			$args['name'] 		= $this->get_field_name( $field['underscored'] );
			$args['selections']	= ( !empty( $field['sels'] ) ? $field['sels'] : array() );
			$args['type'] 		= ( empty( $field['type'] ) ? '' : $field['type'] );
			
			echo '<p>' . $slushman_bp_profile_widgets->create_settings( $args ) . '</p>';
			
		} // End of $fields foreach

 	} // End of form()

/**
 * Front-end display of widget.
 *
 * @see		WP_Widget::widget()
 *
 * @param	array	$args		Widget arguments.
 * @param 	array	$instance	Saved values from database.
 *
 * @uses	apply_filters
 * @uses	xprofile_get_field_data
 * @uses	find_on_page
 */	 	  
  	function widget( $args, $instance ) {

  		global $slushman_bp_profile_widgets, $slushkit;
	 	
	 	extract( $args );
	 	
	 	echo $before_widget;
	 	
	 	$title = ( empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', $instance['title'] ) );
	 	
	 	echo ( empty( $title ) ? '' : $before_title . $title . $after_title );
	 	
	 	do_action( 'bp_before_sidebar_me' );
	 	
	 	echo '<div id="sidebar-me">';
	 	
	 	$this->widget_output( $instance );
	 	
	 	do_action( 'bp_sidebar_me' );
	 	
	 	echo '</div>';
	 	
	 	do_action( 'bp_after_sidebar_me' );
	 	
	 	echo $after_widget;
	 	
 	} // End of widget()

/**
 * Sanitize widget form values as they are saved.
 *
 * @see		WP_Widget::update()
 *
 * @param	array	$new_instance	Values just sent to be saved.
 * @param	array	$old_instance	Previously saved values from database.
 *
 * @return 	array	$instance		Updated safe values to be saved.
 */	  	
 	function update( $new_instance, $old_instance ) {
	 	
	 	$instance = $old_instance;

	 	foreach ( $this->fields as $field ) {

	 		$name = $field['underscored'];
			
			switch ( $field['type'] ) {
 			
	 			case ( 'email' )		: $instance[$name] = sanitize_email( $new_instance[$name] ); break;
	 			case ( 'number' )		: $instance[$name] = intval( $new_instance[$name] ); break;
	 			case ( 'url' ) 			: $instance[$name] = esc_url( $new_instance[$name] ); break;
	 			case ( 'text' ) 		: $instance[$name] = sanitize_text_field( $new_instance[$name] ); break;
	 			case ( 'textarea' )		: $instance[$name] = esc_textarea( $new_instance[$name] ); break;
	 			case ( 'checkgroup' ) 	: $instance[$name] = strip_tags( $new_instance[$name] ); break;
	 			case ( 'radios' ) 		: $instance[$name] = strip_tags( $new_instance[$name] ); break;
	 			case ( 'select' )		: $instance[$name] = strip_tags( $new_instance[$name] ); break;
	 			case ( 'tel' ) 			: $instance[$name] = $slushkit->sanitize_phone( $new_instance[$name] ); break;
	 			case ( 'checkbox' ) 	: $instance[$name] = ( isset( $new_instance[$name] ) && $new_instance[$name] ? true : false ); break;
	 			
 			} // End of $inputtype switch

		} // End of $fields foreach
	 	
	 	return $instance;
	 	
 	} // End of update()
 		
} // End of slushman_bp_profile_music_player_widget class

?>