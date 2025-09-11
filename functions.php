<?php
/**
 * BuddyBoss Child Theme Functions.
 *
 * @package BuddyBoss Child
 * The parent theme functions are located at /buddyboss-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */

/****************************** THEME SETUP ******************************/

/**
 * Sets up theme for translation
 *
 * @since BuddyBoss Child 1.0.0
 */
function buddyboss_theme_child_languages() {
	/**
	 * Makes child theme available for translation.
	 * Translations can be added into the /languages/ directory.
	 */

	// Translate text from the PARENT theme.
	load_theme_textdomain( 'buddyboss-theme', get_stylesheet_directory() . '/languages' );

	// Translate text from the CHILD theme only.
	// Change 'buddyboss-theme' instances in all child theme files to 'buddyboss-theme-child'.
	// load_theme_textdomain( 'buddyboss-theme-child', get_stylesheet_directory() . '/languages' );.
}
add_action( 'after_setup_theme', 'buddyboss_theme_child_languages' );

/**
 * Enqueues scripts and styles for child theme front-end.
 *
 * @since Boss Child Theme  1.0.0
 */
function buddyboss_theme_child_scripts_styles() {
	/**
	 * Scripts and Styles loaded by the parent theme can be unloaded if needed
	 * using wp_deregister_script or wp_deregister_style.
	 *
	 * See the WordPress Codex for more information about those functions:
	 * http://codex.wordpress.org/Function_Reference/wp_deregister_script
	 * http://codex.wordpress.org/Function_Reference/wp_deregister_style
	 */

	// Styles.
	wp_enqueue_style( 'buddyboss-child-css', get_stylesheet_directory_uri() . '/assets/css/custom.css', array(), filemtime( get_stylesheet_directory() . '/assets/css/custom.css' ) );

	// Register LearnDash Previous Essays stylesheet. Enqueued in the render function as needed.
	wp_register_style( 'bb-ld-previous-essays', get_stylesheet_directory_uri() . '/assets/css/ld-previous-essays.css', array(), filemtime( get_stylesheet_directory() . '/assets/css/ld-previous-essays.css' ) );

	// Javascript.
	wp_enqueue_script( 'buddyboss-child-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', array(), filemtime( get_stylesheet_directory() . '/assets/js/custom.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'buddyboss_theme_child_scripts_styles', 9999 );


/****************************** CUSTOM FUNCTIONS */


// Add custom fields to WooCommerce product editor.
add_action( 'woocommerce_product_options_advanced', 'add_custom_advanced_fields' );
add_action( 'woocommerce_product_after_variable_attributes', 'add_custom_discount_field', 1, 3 ); // Lower priority to move fields to top.
add_action( 'woocommerce_process_product_meta', 'save_custom_advanced_fields' );
add_action( 'woocommerce_save_product_variation', 'save_custom_text_field', 10, 2 );

// Display custom fields in the Advanced tab.
/**
 * Add custom fields to the product advanced tab.
 */
function add_custom_advanced_fields() {
	global $product_object;

	// Display template selection dropdown.
	if ( $product_object->get_type() === 'variable-subscription' ) {
		echo '<div class="options_group">';

		// Custom template selection.
		woocommerce_wp_select(
			array(
				'id'          => '_custom_template_selection',
				'label'       => __( 'Select Custom Template', 'woocommerce' ),
				'description' => __( 'Choose a template layout for this product.', 'woocommerce' ),
				'options'     => array(
					'default'         => __( 'Default Layout', 'woocommerce' ),
					'custom_layout_1' => __( 'Custom Layout 1', 'woocommerce' ),
				),
			)
		);

		// Custom color field.
		woocommerce_wp_text_input(
			array(
				'id'          => '_custom_product_color',
				'label'       => __( 'Product Color', 'woocommerce' ),
				'description' => __( 'Enter the color for this product.', 'woocommerce' ),
				'desc_tip'    => true,
				'value'       => get_post_meta( $product_object->get_id(), '_custom_product_color', true ),
			)
		);

		echo '</div>';
	}
}

// Save custom fields.
/**
 * Save custom fields for the product.
 *
 * @param int $post_id Product ID.
 */
function save_custom_advanced_fields( $post_id ) {
	// Check nonce for security.
	if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
		return;
	}

	// Save template selection.
	$selected_template = isset( $_POST['_custom_template_selection'] ) ? sanitize_text_field( wp_unslash( $_POST['_custom_template_selection'] ) ) : 'default';
	update_post_meta( $post_id, '_custom_template_selection', $selected_template );

	// Save color field.
	$product_color = isset( $_POST['_custom_product_color'] ) ? sanitize_text_field( wp_unslash( $_POST['_custom_product_color'] ) ) : '';
	update_post_meta( $post_id, '_custom_product_color', $product_color );
}

// Display the custom fields in each variation.
/**
 * Add custom fields to product variations.
 *
 * @param int     $loop Loop index.
 * @param array   $variation_data Variation data.
 * @param WP_Post $variation Variation post object.
 */
function add_custom_discount_field( $loop, $variation_data, $variation ) {
	// Custom Variation Title Field with tooltip.
	woocommerce_wp_text_input(
		array(
			'id'            => 'custom_variation_title_field_' . $variation->ID,
			'name'          => 'custom_variation_title_field[' . $variation->ID . ']',
			'label'         => __( 'Custom Variation Title', 'woocommerce' ),
			'value'         => get_post_meta( $variation->ID, '_custom_variation_title_field', true ),
			'wrapper_class' => 'form-field form-row', // Adds both classes to the <p> wrapper.
			'desc_tip'      => true, // Enables the tooltip icon.
			'description'   => __( 'Enter a custom title for this variation.', 'woocommerce' ), // Tooltip text.
		)
	);
	// Custom Discount Text Field with tooltip.
	woocommerce_wp_text_input(
		array(
			'id'            => 'custom_discount_text_field_' . $variation->ID,
			'name'          => 'custom_discount_text_field[' . $variation->ID . ']',
			'label'         => __( 'Discount Text', 'woocommerce' ),
			'value'         => get_post_meta( $variation->ID, '_custom_discount_text_field', true ),
			'wrapper_class' => 'form-field form-row', // Adds both classes to the <p> wrapper.
			'desc_tip'      => true, // Enables the tooltip icon.
			'description'   => __( 'Enter a discount description for this variation.', 'woocommerce' ), // Tooltip text.
		)
	);
	// Custom Monthly Pricing Field.
	woocommerce_wp_text_input(
		array(
			'id'                => 'custom_monthly_pricing_field_' . $variation->ID,
			'name'              => 'custom_monthly_pricing_field[' . $variation->ID . ']',
			'label'             => __( 'Monthly Pricing', 'woocommerce' ),
			'value'             => get_post_meta( $variation->ID, '_custom_monthly_pricing_field', true ),
			'wrapper_class'     => 'form-field form-row required-field', // Adds both classes to the <p> wrapper.
			'desc_tip'          => true, // Enables the tooltip icon.
			'description'       => __( 'Enter the monthly price for this variation. This field is required.', 'woocommerce' ), // Tooltip text.
			'custom_attributes' => array(
				'required' => 'required', // Make it required.
			),
		)
	);
}

// Save the custom fields for each variation.
/**
 * Save custom fields for product variations.
 *
 * @param int $variation_id Variation ID.
 * @param int $i Loop index (unused).
 */
function save_custom_text_field( $variation_id, $i ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- $i is required by hook.
	// Check nonce for security.
	if ( ! isset( $_POST['_wcsnonce_save_variations'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wcsnonce_save_variations'] ) ), 'wcs_subscription_variations' ) ) {
		return;
	}

	if ( isset( $_POST['custom_discount_text_field'][ $variation_id ] ) ) {
		update_post_meta( $variation_id, '_custom_discount_text_field', sanitize_text_field( wp_unslash( $_POST['custom_discount_text_field'][ $variation_id ] ) ) );
	}
	if ( isset( $_POST['custom_variation_title_field'][ $variation_id ] ) ) {
		update_post_meta( $variation_id, '_custom_variation_title_field', sanitize_text_field( wp_unslash( $_POST['custom_variation_title_field'][ $variation_id ] ) ) );
	}
	// Save the custom monthly pricing field.
	if ( isset( $_POST['custom_monthly_pricing_field'][ $variation_id ] ) ) {
		update_post_meta( $variation_id, '_custom_monthly_pricing_field', sanitize_text_field( wp_unslash( $_POST['custom_monthly_pricing_field'][ $variation_id ] ) ) );
	}
}

/**
 * Convert percentage to IELTS band score.
 *
 * @param float  $percentage The percentage score (0-100).
 * @param string $round_mode The rounding mode: 'nearest', 'up', or 'down'. Default 'nearest'.
 * @return float The IELTS band score (0-9).
 */
function bb_convert_percentage_to_ielts( $percentage, $round_mode = 'nearest' ) {
	$percentage = floatval( $percentage );
	$score      = ( $percentage / 100 ) * 9;
	switch ( $round_mode ) {
		case 'up':
			$rounded = ceil( $score * 2 ) / 2;
			break;
		case 'down':
			$rounded = floor( $score * 2 ) / 2;
			break;
		default:
			$rounded = round( $score * 2 ) / 2;
			break;
	}
	return min( 9, max( 0, $rounded ) );
}

/**
 * Render previous essay attempts before the quiz content loads.
 *
 * Hooked to 'learndash-quiz-actual-content-before'.
 * Shows user's prior essay submissions associated with this quiz.
 *
 * @param int $quiz_id   Quiz Post ID.
 * @param int $course_id Course Post ID.
 * @param int $user_id   User ID.
 */
function bb_render_previous_essays_for_quiz( $quiz_id, $course_id, $user_id ) {
	// Only proceed for logged-in users and valid quiz IDs.
	if ( empty( $user_id ) || empty( $quiz_id ) ) {
		return; // Nothing to do.
	}

	// Fetch all quiz attempts from user meta and filter for this quiz.
	$quiz_attempts = get_user_meta( $user_id, '_sfwd-quizzes', true );
	if ( empty( $quiz_attempts ) || ! is_array( $quiz_attempts ) ) {
		return; // No attempts found.
	}

	$attempts_for_quiz = array();
	foreach ( $quiz_attempts as $attempt ) {
		// Each attempt has keys like: quiz (post ID), time, graded (array of essays), etc.
		$attempt_quiz_post_id = isset( $attempt['quiz'] ) ? intval( $attempt['quiz'] ) : 0; // Quiz WP_Post ID.
		if ( intval( $quiz_id ) === $attempt_quiz_post_id && ! empty( $attempt['graded'] ) && is_array( $attempt['graded'] ) ) {
			$attempts_for_quiz[] = $attempt; // Keep attempts that contain essay grading data.
		}
	}

	if ( empty( $attempts_for_quiz ) ) {
		return; // Nothing to display.
	}

	// Get the quiz passing percentage from LearnDash settings (0-100%).
	$passing_percentage = null; // Default to unknown.
	if ( function_exists( 'learndash_get_setting' ) ) {
		$passing_percentage = learndash_get_setting( $quiz_id, 'passingpercentage' ); // Returns numeric percent or empty.
		if ( '' !== $passing_percentage ) {
			$passing_percentage = (int) $passing_percentage; // Normalize to int.
			$passing_percentage = max( 0, min( 100, $passing_percentage ) ); // Clamp to 0-100.
		} else {
			$passing_percentage = null; // Keep null if not set.
		}
	}

	// Enqueue the Previous Essays stylesheet now that we know we will render output.
	if ( ! wp_style_is( 'bb-ld-previous-essays', 'enqueued' ) ) {
		if ( ! wp_style_is( 'bb-ld-previous-essays', 'registered' ) ) {
			// Fallback in case registration did not occur earlier.
			wp_register_style( 'bb-ld-previous-essays', get_stylesheet_directory_uri() . '/assets/css/ld-previous-essays.css', array(), file_exists( get_stylesheet_directory() . '/assets/css/ld-previous-essays.css' ) ? filemtime( get_stylesheet_directory() . '/assets/css/ld-previous-essays.css' ) : '1.0.0' );
		}
		wp_enqueue_style( 'bb-ld-previous-essays' );
	}

	// Sort attempts by time descending so latest first.
	usort(
		$attempts_for_quiz,
		function ( $a, $b ) {
			$ta = isset( $a['time'] ) ? intval( $a['time'] ) : 0; // Attempt timestamp.
			$tb = isset( $b['time'] ) ? intval( $b['time'] ) : 0; // Attempt timestamp.
			return $tb <=> $ta; // Desc.
		}
	);

	// Begin output.
	?>
	<div class="ld-previous-essays ld-item-list ld-essay-cards" aria-label="<?php echo esc_attr__( 'Previous essay attempts', 'learndash' ); ?>">
		<div class="ld-section-heading">
			<h3 class="ld-heading"><?php echo esc_html__( 'Previous Essay Attempts', 'learndash' ); ?></h3>
		</div>

		<?php foreach ( $attempts_for_quiz as $attempt ) : ?>
			<?php
			$attempt_time  = isset( $attempt['time'] ) ? intval( $attempt['time'] ) : 0; // Attempt timestamp.
			$attempt_label = $attempt_time ? learndash_adjust_date_time_display( $attempt['time'] ) : esc_html__( 'Unknown time', 'learndash' ); // Human date.
			$essays        = isset( $attempt['graded'] ) && is_array( $attempt['graded'] ) ? $attempt['graded'] : array(); // Essay items.

			if ( empty( $essays ) ) {
				continue; // No essays for this attempt.
			}
			?>
			<div class="ld-essay-attempt">
				<div class="ld-item-list ld-essay-list">
					<div class="ld-item-list-items">

						<?php foreach ( $essays as $essay_item ) : ?>
							<?php
							$essay_post_id = isset( $essay_item['post_id'] ) ? intval( $essay_item['post_id'] ) : 0; // Essay post ID.
							if ( ! $essay_post_id ) {
								continue; // Skip invalid.
							}

							$essay = get_post( $essay_post_id ); // WP_Post for essay.
							if ( ! $essay || 'sfwd-essays' !== $essay->post_type ) {
								continue; // Ensure correct post type.
							}
							$essay_url = apply_filters( 'bb_essay_url', get_permalink( $essay_post_id ), $essay_post_id );

							$details = function_exists( 'learndash_get_essay_details' ) ? learndash_get_essay_details( $essay_post_id ) : false; // Essay details.
							?>
							<div class="ld-item-list-item ld-essay-card">
								<div class="ld-item-list-item-preview">
									<div>
										<a class="ld-item-list-item-preview-heading" href="<?php echo esc_url( $essay_url ); ?>" aria-label="<?php echo esc_attr__( 'Go to the essay page.', 'learndash' ); ?>">
											<?php echo wp_kses_post( get_the_title( $essay_post_id ) ); ?>
										</a>
										<div>
											<span class="ld-essay-attempt__label"><?php echo esc_html__( 'Attempt:', 'learndash' ) . ' ' . esc_html( $attempt_label ); ?></span>
										</div>
									</div>
									<div class="ld-item-list-item-details">
										<?php
										// Work out the display status using essay details and quiz pass/fail state.
										$raw_status     = ( $details && isset( $details['status'] ) ) ? $details['status'] : ''; // Essay raw status.
										$attempt_passed = isset( $attempt['pass'] ) ? (bool) $attempt['pass'] : null; // Quiz attempt pass/fail or null if unknown.
										$display_status = $raw_status; // Default to essay status.

										// If the attempt failed, override to not_pass regardless of essay status.
										if ( false === $attempt_passed ) {
											$display_status = 'not_pass'; // Custom status handled via filter.
										}

										if ( function_exists( 'learndash_status_bubble' ) && ! empty( $display_status ) ) {
											echo learndash_status_bubble( $display_status, 'essay', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output handled via LD helper.
										} else {
											// Fallback: Use post status or a generic label if unavailable.
											$status_text  = get_post_status_object( get_post_status( $essay_post_id ) ); // Fallback status.
											$status_label = $status_text ? $status_text->label : __( 'Unknown', 'learndash' ); // Fallback label.
											if ( 'not_pass' === $display_status ) {
												$status_label = esc_html__( 'Not Passed', 'learndash' ); // Provide readable label for custom status.
											} elseif ( 'not_graded' === $display_status ) {
												$status_label = esc_html__( 'Not Graded', 'learndash' ); // Align with LD labels.
											}
											?>
											<span class="ld-status ld-status--generic"><?php echo esc_html( $status_label ); ?></span>
											<?php
										}
										// If attempt failed and we know the passing threshold, display it for the user.
										if ( 'not_pass' === $display_status && null !== $passing_percentage && 0 < $passing_percentage ) :
											$passing_percentage_display = absint( $passing_percentage ); // Safe percent for display.
											$ielts_passing              = bb_convert_percentage_to_ielts( $passing_percentage, 'up' );
											$ielts_passing_formatted    = number_format( $ielts_passing, 1 );
											?>
												<span class="ld-essay__passing" title="<?php echo esc_attr__( 'Passing threshold for this quiz.', 'learndash' ); ?>">
													<?php
													/* translators: %1$s is the percentage required, %2$s is the IELTS band score. */
													printf( esc_html__( 'Requires: %1$s%% (%2$s)', 'learndash' ), esc_html( $passing_percentage_display ), esc_html( $ielts_passing_formatted ) );
													?>
												</span>
											<?php
										endif;
										?>
										<div>
											<?php if ( $details && isset( $details['points'] ) ) : ?>
												<?php
												$awarded = isset( $details['points']['awarded'] ) ? $details['points']['awarded'] : null; // Points awarded.
												$total   = isset( $details['points']['total'] ) ? $details['points']['total'] : null; // Points total.
												if ( null !== $awarded || null !== $total ) {
													$label       = ( isset( $details['status'] ) && 'graded' === $details['status'] ) ? esc_html__( 'Teacher Score:', 'learndash' ) : esc_html__( 'AI Score:', 'learndash' );
													$points_text = $label . ' ';
													if ( null !== $awarded && null !== $total && 0 < $total ) {
														$percentage   = ( $awarded / $total ) * 100;
														$ielts        = bb_convert_percentage_to_ielts( $percentage );
														$points_text .= esc_html( number_format( $percentage, 1 ) ) . '% (' . esc_html( number_format( $ielts, 1 ) ) . ')';
													} else {
														$points_text .= '—';
													}
													?>
													<span class="ld-essay__points" title="<?php echo esc_attr__( 'Percentage Score (IELTS Band)', 'learndash' ); ?>"><?php echo esc_html( $points_text ); ?></span>
													<?php
												}
												?>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div><!-- .ld-item-list-item -->
						<?php endforeach; ?>

					</div><!-- .ld-item-list-items -->
				</div><!-- .ld-item-list -->
			</div><!-- .ld-essay-attempt -->
		<?php endforeach; ?>

	</div><!-- .ld-previous-essays -->
	<?php
}
add_action( 'learndash-quiz-actual-content-before', 'bb_render_previous_essays_for_quiz', 10, 3 );

// Add support for custom LearnDash status bubble values like 'not_pass'.
add_filter( 'learndash_status_bubble', 'bb_ld_status_bubble_not_pass', 10, 2 );
/**
 * Extend LearnDash status bubble to support custom statuses such as 'not_pass'.
 *
 * @param string $bubble Existing bubble markup.
 * @param string $status Current status key.
 *
 * @return string Modified bubble markup supporting custom statuses.
 */
function bb_ld_status_bubble_not_pass( $bubble, $status ) {
	if ( 'not_pass' === $status ) {
		$bubble = '<div class="ld-status ld-secondary-background">' . esc_html_x( 'Not Passed', 'Quiz attempt status', 'learndash' ) . '</div>'; // Custom bubble for not passed state using warning background.
	}
	return $bubble;
}
