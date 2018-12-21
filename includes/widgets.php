<?php
/**
 * Our custom widget functions.
 *
 * @package DripPress
 */

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Formatting as Formatting;

/**
 * Start our engines.
 */
add_action( 'widgets_init', 'dppress_load_custom_widgets' );

/**
 * Load our custom widgets.
 *
 * @return void
 */
function dppress_load_custom_widgets() {
	register_widget( 'DPPress_Tag_Widget' );
	register_widget( 'DPPress_Cat_Widget' );
}

/**
 * Widget for displaying the ordered drip content.
 */
class DPPress_Tag_Widget extends WP_Widget {

	/**
	 * The widget construct.
	 */
	public function __construct() {

		// Set my widget ops.
		$widget_ops = array(
			'classname'     => 'dppress-tag-widget',
			'description'   => __( 'Display a list of your dripped content via tags.', 'drip-press' ),
		);

		// Set my parent construct.
		parent::__construct( 'dppress-tag-widget', __( 'DripPress List - Tags', 'drippress' ), $widget_ops );
	}

	/**
	 * The display portion of the widget.
	 *
	 * @param  array $args      The variables set up in the sidebar area definition.
	 * @param  array $instance  The saved args in the widget settings.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {

		// Bail if no term was set.
		if ( empty( $instance['term'] ) ) {
			return;
		}

		// Fetch the count of items to display.
		$list_count = ! empty( $instance['count'] ) ? $instance['count'] : 5;

		// Fetch my list items.
		$list_items = Helpers\get_drip_list( $instance['term'], 'post_tag', absint( $list_count ) );

		// Bail if nothing for the list exists.
		if ( empty( $list_items ) ) {
			return;
		}

		// Check for a title, then wrap the filter around it.
		$title  = empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', $instance['title'] );

		// Output the opening widget markup.
		echo $args['before_widget'];

		// Output the title (if we have one).
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// Now output the list itself.
		Formatting\display_drip_html_list( $list_items );

		// Output the closing widget markup.
		echo $args['after_widget'];
	}

	/**
	 * Validate and store the values being passed in the widget settings.
	 *
	 * @param  array $new_instance  The new settings being passed.
	 * @param  array $old_instance  The existing settings.
	 *
	 * @return array instance       The data being stored.
	 */
	public function update( $new_instance, $old_instance ) {

		// Set our instance variable as the existing data.
		$instance = $old_instance;

		// Set our items to be sanitized.
		$instance['title']  = sanitize_text_field( $new_instance['title'] );
		$instance['term']   = sanitize_text_field( $new_instance['term'] );
		$instance['count']  = absint( $new_instance['count'] );

		// Return the instance.
		return $instance;
	}

	/**
	 * The widget settings form.
	 *
	 * @param  array $instance  The stored settings instance.
	 *
	 * @return void
	 */
	public function form( $instance ) {

		// Set the default values (if any).
		$instance   = wp_parse_args( (array) $instance, array(
			'count' => 5,
		) );
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title:' ); ?></label>-
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
			</p>

			<p>
				<?php Formatting\get_admin_term_dropdown( 'post_tag', $this->get_field_name( 'term' ), $this->get_field_id( 'term' ), $instance['term'] ); ?>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Post Count:' ); ?></label>
				<input class="small-text" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo absint( $instance['count'] ); ?>" />
			</p>

		<?php
	}

} // End the extended widget class.

/**
 * Custom widget for displaying the ordered drip content.
 */
class DPPress_Cat_Widget extends WP_Widget {

	/**
	 * The widget construct.
	 */
	public function __construct() {

		// Set my widget ops.
		$widget_ops = array(
			'classname'     => 'dppress-cat-widget',
			'description'   => __( 'Display a list of your dripped content via categories.', 'drip-press' ),
		);

		// Set my parent construct.
		parent::__construct( 'dppress-cat-widget', __( 'DripPress List - Category', 'drippress' ), $widget_ops );
	}

	/**
	 * The display portion of the widget.
	 *
	 * @param  array $args      The variables set up in the sidebar area definition.
	 * @param  array $instance  The saved args in the widget settings.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {

		// Bail if no term was set.
		if ( empty( $instance['term'] ) ) {
			return;
		}

		// Fetch the count of items to display.
		$list_count = ! empty( $instance['count'] ) ? $instance['count'] : 5;

		// Fetch my list items.
		$list_items = Helpers\get_drip_list( $instance['term'], 'category', absint( $list_count ) );

		// Bail if nothing for the list exists.
		if ( empty( $list_items ) ) {
			return;
		}

		// Check for a title, then wrap the filter around it.
		$title  = empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', $instance['title'] );

		// Output the opening widget markup.
		echo $args['before_widget'];

		// Output the title (if we have one).
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// Now output the list itself.
		Formatting\display_drip_html_list( $list_items );

		// Output the closing widget markup.
		echo $args['after_widget'];
	}

	/**
	 * Validate and store the values being passed in the widget settings.
	 *
	 * @param  array $new_instance  The new settings being passed.
	 * @param  array $old_instance  The existing settings.
	 *
	 * @return array instance       The data being stored.
	 */
	public function update( $new_instance, $old_instance ) {

		// Set our instance variable as the existing data.
		$instance = $old_instance;

		// Set our items to be sanitized.
		$instance['title']  = sanitize_text_field( $new_instance['title'] );
		$instance['term']   = sanitize_text_field( $new_instance['term'] );
		$instance['count']  = absint( $new_instance['count'] );

		// Return the instance.
		return $instance;
	}

	/**
	 * The widget settings form.
	 *
	 * @param  array $instance  The stored settings instance.
	 *
	 * @return void
	 */
	public function form( $instance ) {

		// Set the default values (if any).
		$instance   = wp_parse_args( (array) $instance, array(
			'count' => 5,
		) );

		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title:' ); ?></label>-
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
			</p>

			<p>
				<?php Formatting\get_admin_term_dropdown( 'category', $this->get_field_name( 'term' ), $this->get_field_id( 'term' ), $instance['term'] ); ?>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Post Count:' ); ?></label>
				<input class="small-text" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo absint( $instance['count'] ); ?>" />
			</p>

		<?php
	}

} // End the extended widget class.
