<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header( 'shop' ); ?>

	<?php
		/**
		 * WooCommerce before main content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 */
		do_action( 'woocommerce_before_main_content' );
	?>

		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>

			<?php
				// Retrieve the selected layout for the current product.
				$selected_layout = get_post_meta( get_the_ID(), '_custom_template_selection', true );

				// Get the product object and check if the product type is 'variable-subscription'.
				$product = wc_get_product( get_the_ID() );
			if ( 'custom_layout_1' === $selected_layout && $product && 'variable-subscription' === $product->get_type() ) {
				// Load custom layout template for variable subscription products.
				wc_get_template_part( 'content', 'single-product-custom-layout-1' );
			} else {
				echo 'Loading Default';
				// Load default layout.
				wc_get_template_part( 'content', 'single-product' );
			}
			?>

		<?php endwhile; // end of the loop. ?>

	<?php
		/**
		 * WooCommerce after main content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action( 'woocommerce_after_main_content' );
	?>

	<?php
		/**
		 * WooCommerce sidebar hook.
		 *
		 * @hooked woocommerce_get_sidebar - 10
		 */
		do_action( 'woocommerce_sidebar' );
	?>

<?php
get_footer( 'shop' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
