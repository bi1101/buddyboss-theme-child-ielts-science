<?php
defined('ABSPATH') || exit;

global $product;

// Only print the layout if the product type is variable-subscription.
if ($product->get_type() !== 'variable-subscription') {
    echo 'Layout Only Works For Variable Subscription';
    return;
}

// Exit if not on a product page.
if (post_password_required()) {
    echo get_the_password_form();
    return;
}

$available_variations = $product->get_available_variations();
$product_attributes = $product->get_attributes(); // All Attributes
$default_attributes = $product->get_default_attributes(); // Retrieve default variation attributes
// Get the product color from the custom field
$product_color = get_post_meta($product->get_id(), '_custom_product_color', true);
// Set fallback color if no custom color is set
$product_color = $product_color ?: '#FFBE00';
$product_name = $product->get_name();
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>

    <div class="cl1-container">
        <div class="cl1-product-data-container">
            <div class="cl1-content-container">
                <div class="cl-1-content">
                    <h3 style="color: <?php echo $product_color; ?>;"><?php echo esc_html($product_name); ?></h3>
                    <!-- All Variation Descriptions Here  -->
                    <div class="variation-descriptions-wrap">
                        <?php if ($available_variations) {
                            foreach ($available_variations as $variation) {
                                $variation_id = $variation['variation_id'];
                                $variation_description = do_shortcode($variation['variation_description']); // Render Shortcodes 
                                // $variation_description = apply_filters( 'the_content', $variation_description ); // Render description with HTML
                                $is_default = true;

                                if (!$variation_description) {
                                    $variation_description = __('No Description Provided', 'buddyboss-theme-child');
                                }
                                // Check if this variation matches the default attributes
                                foreach ($variation['attributes'] as $attr_name => $attr_value) {
                                    $clean_attr_name = str_replace('attribute_', '', $attr_name);
                                    if (isset($default_attributes[$clean_attr_name]) && $default_attributes[$clean_attr_name] !== $attr_value) {
                                        $is_default = false;
                                        break;
                                    }
                                }
                                // Assign "active" class if this is the default variation
                                $active_class = $is_default ? 'active' : '';
                                ?>
                                <!-- Variation Description -->
                                <div class="variation-description <?php echo esc_attr($active_class); ?>"
                                    id="variation-description-<?php echo esc_attr($variation_id); ?>">
                                    <?php echo $variation_description; // Rendered with HTML and shortcodes ?>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="cl1-order-container">
                <div class="cl-1-content">
                    <div class="cl1-add-to-cart-wrap">
                        <div class="product-and-variation-name">
                            <h3><?php echo $product_name; ?> - <span id="order-details-duration-label"></span></h3>
                        </div>
                        <div class="cl1-product-totals">
                            <div class="subtotal-wrap">
                                <div class="variation-subtotal-label"><?php echo __('Tổng phụ', 'buddyboss-theme-child'); ?></div>
                                <div class="variation-subtotal-value">
                                    <?php
                                    $default_price = '';
                                    if ($available_variations) {
                                        foreach ($available_variations as $variation) {
                                            $variation_id = $variation['variation_id'];
                                            $is_default = true;

                                            foreach ($variation['attributes'] as $attr_name => $attr_value) {
                                                $clean_attr_name = str_replace('attribute_', '', $attr_name);
                                                if (isset($default_attributes[$clean_attr_name]) && $default_attributes[$clean_attr_name] !== $attr_value) {
                                                    $is_default = false;
                                                    break;
                                                }
                                            }

                                            if ($is_default) {
                                                $default_price = wc_price($variation['display_price']);
                                                break;
                                            }
                                        }
                                    }
                                    echo $default_price ? $default_price : wc_price($product->get_price());
                                    ?>
                                </div>
                            </div>
                            <div class="recurring-total-wrap">
                                <div class="variation-recurring-total-label"><?php echo __('Tổng', 'buddyboss-theme-child'); ?></div>
                                <div class="variation-recurring-total-value">
                                    <?php
                                    if ($available_variations) {
                                        foreach ($available_variations as $variation) {
                                            $variation_id = $variation['variation_id'];
                                            $is_default = true;

                                            foreach ($variation['attributes'] as $attr_name => $attr_value) {
                                                $clean_attr_name = str_replace('attribute_', '', $attr_name);
                                                if (isset($default_attributes[$clean_attr_name]) && $default_attributes[$clean_attr_name] !== $attr_value) {
                                                    $is_default = false;
                                                    break;
                                                }
                                            }

                                            if ($is_default) {
                                                // Define $subscription_price before using it.
                                                $subscription_price = isset($variation['display_regular_price']) ? wc_price($variation['display_regular_price']) : wc_price(0);
                                                $subscription_period = get_post_meta($variation_id, '_subscription_period', true) ?: 'month';

                                                $subscription_interval = get_post_meta($variation_id, '_subscription_period_interval', true) ?: 1;

                                                $subscription_interval_label = $subscription_interval > 1 ? $subscription_interval . ' ' : '';

                                                echo sprintf(

                                                    /* translators: 1: subscription price, 2: subscription interval label, 3: subscription period */

                                                    __('%1$s / %2$s%3$s', 'buddyboss-theme-child'),

                                                    $subscription_price,

                                                    $subscription_interval_label,

                                                    ucfirst($subscription_period)

                                                );
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <form class="cart" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>">
                            <input type="hidden" name="variation_id" id="selected-variation-id" value="">
                            <button type="submit" style ="width: 100%" class="single_add_to_cart_button button alt"><?php echo __('Đăng kí', 'buddyboss-theme-child'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="cli-choices-container">
                <?php
                foreach ($product_attributes as $attribute_key => $attribute) {
                    if ($attribute->get_variation()) {
                        $attribute_name = $attribute->get_name();
                        $attribute_label = wc_attribute_label($attribute_name);
                        ?>
                        <div class="cl1-product-choice-container <?php echo esc_attr($attribute_key); ?>">
                            <div class="cl-1-header">
                                <h2><?php echo esc_html($attribute_label); ?></h2>
                            </div>
                            <div class="cl-1-content">
                                <div class="cl1-product-choices">
                                    <?php
                                    foreach ($available_variations as $variation) {
                                        $variation_id = $variation['variation_id'];
                                        $variation_attributes = $variation['attributes'];
                                        $variation_value = $variation_attributes['attribute_' . $attribute_key] ?? '';
                                        $variation_term = get_term_by('slug', $variation_value, $attribute_name);
                                        $variation_title = $variation_term ? $variation_term->name : 'Variation ' . $variation_id;

                                        $subscription_price = wc_price($variation['display_regular_price']);
                                        $subscription_period = get_post_meta($variation_id, '_subscription_period', true) ?: 'month';
                                        $subscription_interval = get_post_meta($variation_id, '_subscription_period_interval', true) ?: 1;

                                        $subscription_interval_label = $subscription_interval > 1 ? $subscription_interval . ' ' : '';
                                        $discount_text = get_post_meta($variation_id, '_custom_discount_text_field', true);
                                        $monthly_pricing = get_post_meta($variation_id, '_custom_monthly_pricing_field', true);

                                        // Determine if this variation is the default
                                        $is_default = true;
                                        foreach ($default_attributes as $default_attr_name => $default_attr_value) {
                                            if (
                                                isset($variation_attributes['attribute_' . $default_attr_name]) &&
                                                $variation_attributes['attribute_' . $default_attr_name] !== $default_attr_value
                                            ) {
                                                $is_default = false;
                                                break;
                                            }
                                        }
                                        ?>
                                        <label class="variation-item" for="variation_<?php echo $variation_id; ?>">
                                            <input type="radio" name="variation_<?php echo get_the_ID(); ?>"
                                                id="variation_<?php echo $variation_id; ?>"
                                                data-varition-id="<?php echo $variation_id; ?>"
                                                data-duration-label="<?php echo esc_html($variation_title); ?>"
                                                data-price="<?php echo esc_attr($variation['display_regular_price']); ?>"
                                                data-price-html="<?php echo esc_attr(sprintf('%s / %s%s', wc_price($variation['display_regular_price']), $subscription_interval_label, ucfirst($subscription_period))); ?>"
                                                onchange="updateVariationUIData()" <?php echo $is_default ? 'checked' : ''; ?>>
                                            <div class="variation-checkbox-wrap">
                                                <span
                                                    class="variation-value"><?php echo esc_html($variation_title); ?></span><br>
                                                <span class="per-month-price"><?php echo $monthly_pricing; ?></span><br>
                                                <!-- Price difference -->
                                                <span class="price-difference" data-variation-id="<?php echo $variation_id; ?>" data-price="<?php echo $variation['display_regular_price']; ?>"></span>
                                                <?php if (!empty($discount_text)): ?>
                                                    <span class="discount-text"><?php echo esc_html($discount_text); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

</div>
<style>
    /* Making Product Page Full Width, Hiding Side Sidebar  */
    .single-product div#secondary {
        display: none;
    }

    .single-product div#primary {
        max-width: 100% !important;
    }
	
	Nav.woocommerce-breadcrumb	{
	  Display: none!important
	}

    /* Woocommerce Subscritpions Custom Layout 1 Styling */
    .cl1-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .cl1-order-container {
        grid-column-start: 4;
        grid-column-end: 6;
        grid-row-start: 1;
        grid-row-end: 2;
        display: flex;
        flex-direction: column;
    }

    .cl1-container .cl1-product-data-container {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
        gap: 16px;
    }

    .cl1-content-container {
        grid-column-start: 1;
        grid-column-end: 4;
        grid-row-start: 1;
        grid-row-end: 2;
    }

    .cli-choices-container {
        grid-column-start: 1;
        grid-column-end: 4;
    }

    .cl1-content-container {
        display: flex;
        flex-direction: column;
    }

    .cl1-content-container .cl-1-content {
        height: 100%;
    }

    .cl-1-header>* {
        font-size: 18px;
        font-weight: 600;
    }

    .cl-1-content:not(.cl1-product-choice-container .cl-1-content) {
        border: 1px solid #D6D9DD;
        padding: 2em;
        border-radius: 8px;
    }

    .cl1-product-choices .variation-item {
        position: relative;
        flex: 1;
    }

    .cl1-product-choices {
        display: flex;
        gap: 32px;
    }

    .cl1-product-choices>* {
        border: 1px solid #D6D9DD;
        padding: 16px;
        padding-left: 2.3em;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s ease;
    }

    .cl1-product-choices>*:hover {
        background-color: #fafafa;
    }

    .cl1-product-choices>* input {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
    }

    .cl1-product-choices>* .variation-checkbox-wrap:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 18px;
        height: 18px;
        background: white;
        border: 1px solid #D6D9DD;
        border-radius: 50%;
        top: 20px;
        left: 16px;
    }

    .cl1-product-choices>* input:checked+.variation-checkbox-wrap:before {
        border: 5px solid #FF2E2E;
    }

    .cl1-product-choices>* .discount-text {
        position: absolute;
        top: 0;
        right: 0;
        background: linear-gradient(90deg, #FFDF00 0%, #DDAC17 80%, #FFDF00 100%);
        font-weight: bold;
        font-family: Roboto;
        font-size: 13px;
        font-weight: 700;
        line-height: 27px;
        text-align: center;
        padding: 2px 9px 2px 9px;
        border-radius: 15px;
        transform: translate(50%, -30%)
    }
    
    /* Price difference styling */
    .price-difference {
        display: block;
        font-size: 14px;
        margin-top: 4px;
    }

    .cl1-content-container .variation-descriptions-wrap .variation-description:not(.active) {
        display: none;
    }

    .product-and-variation-name h3 {
        font-size: 18px;
    }

    .cl1-product-totals .subtotal-wrap {
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #D6D9DD;
        padding-bottom: 8px;
    }

    .cl1-product-totals .subtotal-wrap .variation-subtotal-value {
        color: #1E2132;
        font-weight: 600;
    }

    .cl1-product-totals .recurring-total-wrap {
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;

    }

    .cl1-product-totals .recurring-total-wrap .variation-recurring-total-value {
        font-size: 20px;
        font-weight: 700;
    }

    .cl1-product-totals {
        margin-bottom: 32px;
    }

    .cl1-order-container form.cart {
        margin-bottom: 0 !important;
    }

    .cl1-order-container form.cart .single_add_to_cart_button {
        width: 100%;
    }

    @media only screen and (max-width:1024px) {
        .cl1-product-data-container {
            display: flex !important;
            flex-direction: column;
        }

        .cli-choices-container {
            order: 2 !important;
        }

        .cl1-order-container {
            order: 3;
        }

        .cli-choices-container .cl-1-content .cl1-product-choices {
            flex-direction: column;

        }

        @media only screen and (max-width: 768px) {
            .cl-1-header h2 {
                font-size: 18px;
                margin-bottom: 10px;
            }

            .cl1-product-choices>* {
                font-size: 14px;
            }

            .variation-item {
                padding-left: 45px;
            }

            .product-and-variation-name h3 {
                font-size: 16px;
            }

            .cl1-product-totals .subtotal-wrap {
                font-size: 12px;
            }

            .cl1-product-totals .recurring-total-wrap,
            .variation-recurring-total-value {
                font-size: 16px !important;
            }
        }
    }
</style>
<script>
    // Run On Page Load
    updateVariationUIData();
    function updateVariationUIData() {
        // Get the currently selected radio input
        const selectedVariation = document.querySelector('input[type="radio"][name^="variation_"]:checked');

        if (!selectedVariation) {
            console.error("No variation selected.");
            return;
        }

        // Extract values from the selected input's data attributes
        const variationId = selectedVariation.getAttribute('data-varition-id');
        const durationLabel = selectedVariation.getAttribute('data-duration-label');
        const priceHtml = selectedVariation.getAttribute('data-price-html');
        const selectedPrice = parseFloat(selectedVariation.getAttribute('data-price'));

        // Update the order details duration label
        const durationLabelElement = document.querySelector('#order-details-duration-label');
        if (durationLabelElement) {
            durationLabelElement.textContent = durationLabel || '';
        }

        // Update the hidden input for variation ID
        const selectedVariationIdInput = document.querySelector('#selected-variation-id');
        if (selectedVariationIdInput) {
            selectedVariationIdInput.value = variationId;
        }

        // Update the subtotal value
        const variationSubtotalElement = document.querySelector('.variation-subtotal-value');
        if (variationSubtotalElement) {
            variationSubtotalElement.innerHTML = priceHtml || ''; // Use innerHTML to render the price with HTML
        }

        // Update the recurring total value
        const recurringTotalElement = document.querySelector('.recurring-total-wrap .variation-recurring-total-value');
        if (recurringTotalElement) {
            recurringTotalElement.innerHTML = priceHtml || ''; // Assuming recurring total uses the same priceHtml
        }

        // Handle "variation-description" active class
        const allDescriptions = document.querySelectorAll('.variation-description');
        allDescriptions.forEach(description => description.classList.remove('active')); // Remove active class from all

        const activeDescription = document.querySelector(`#variation-description-${variationId}`);
        if (activeDescription) {
            activeDescription.classList.add('active'); // Add active class to the current description
        }
        
        // Calculate and update price differences
        updatePriceDifferences(selectedPrice);
    }
    
    // Function to calculate and display price differences.
    function updatePriceDifferences(selectedPrice) {
        // Get all price difference elements.
        const priceDifferenceElements = document.querySelectorAll('.price-difference');
        
        priceDifferenceElements.forEach(element => {
            const variationPrice = parseFloat(element.getAttribute('data-price'));
            const difference = variationPrice - selectedPrice;
            
            // Skip the currently selected variation or when price is lower.
            if (difference <= 0) {
                element.textContent = '';
                return;
            }
            
            // Only show additional cost for more expensive variations.
            if (difference > 0) {
                // Format the price using WooCommerce's format price function.
                const formattedPrice = woocommerce_format_price(difference);
                element.textContent = '+ ' + formattedPrice;
            }
        });
    }
    
    // Function to format price using WooCommerce's currency settings
    function woocommerce_format_price(amount) {
        // Get the format from a sample formatted price from WooCommerce
        const samplePrice = document.querySelector('.woocommerce-Price-amount');
        if (samplePrice) {
            const currencySymbol = samplePrice.querySelector('.woocommerce-Price-currencySymbol')?.textContent || 'đ';
            const sampleHTML = samplePrice.innerHTML;
            
            // Determine if currency symbol is before or after the amount
            if (sampleHTML.indexOf(currencySymbol) === 0) {
                return currencySymbol + amount.toLocaleString();
            } else {
                return amount.toLocaleString() + currencySymbol;
            }
        }
        
        // Fallback if no sample price is found
        return amount.toLocaleString();
    }

</script>
<?php
do_action('woocommerce_after_single_product');
?>