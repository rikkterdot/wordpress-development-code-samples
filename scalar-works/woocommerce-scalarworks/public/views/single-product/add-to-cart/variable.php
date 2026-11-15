<?php
/**
 * Variable Product Template.
 * 
 * @package WooCommerce/Scalarworks
 * @version 10.0.0
 */
defined( 'ABSPATH' ) || exit;

// Global.
global $product;

// Variables.
$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

// Before add to cart form.
do_action( 'woocommerce_before_add_to_cart_form' ); 

// Form. ?>
<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>"><?php 

	// Before variations form.
	do_action( 'woocommerce_before_variations_form' );
	
	// Check for available variations.
	if ( empty( $available_variations ) && false !== $available_variations ) { 
		
		// Out of stock. ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p><?php 
	
	} else { 
		
		// Variations table. ?>
		<table class="variations" cellspacing="0">
			<tbody><?php 

				// Set count.
				$count = 0;

				// Check for defaults.
				$defaults = ( ! empty( $product->get_default_attributes() ) ) ? true : false;

				// Get taxonomies.
				$taxonomies = WooCommerceScalarworks\Public\attributes::get_attribute_type( $product );

				// Loop through attributes.
				foreach( $attributes as $attribute_name => $options ) {

					// Set disabled.
					$disabled = ( $count === 0 || $defaults == true ) ? '' : ' scalar-disabled';
					$count++;

					// Get attribute.
					$attribute = wc_get_attribute( wc_attribute_taxonomy_id_by_name( $attribute_name ) );

					// Get description.
					$description = ( ! empty( get_term_meta( $attribute->id, 'attribute_description', true ) ) ) ? ' &mdash; ' . get_term_meta( $attribute->id, 'attribute_description', true ) : '';

					// Set attribute label.
					$attribute_label = ( ! empty( $attribute ) ) ? $attribute->name : str_replace( 'pa_', '', $attribute_name );

					// Get attribute values for this attribute name.
					$options = wc_get_product_terms( $product->get_id(), $attribute_name, array( 'fields' => 'all' ) );
					
					// Set type.
					if( isset( $taxonomies[$attribute_name] ) && ! $taxonomies[$attribute_name] ) {

						// Load default.
						include WOOSCALAR_PATH . 'public/views/single-product/add-to-cart/variable-select.php';

					} else {

						// Check if type exists.
						if( ! file_exists( WOOSCALAR_PATH . 'public/views/single-product/add-to-cart/variable-' . $attribute->type . '.php' ) ) {

							// Load default.
							include WOOSCALAR_PATH . 'public/views/single-product/add-to-cart/variable-select.php';

						} else {

							// Load type.
							include WOOSCALAR_PATH . 'public/views/single-product/add-to-cart/variable-' . $attribute->type . '.php';

						}

					}
				
					// Reset link.
					echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : '';
				
				} 
				
				// Check for attached products.
				if( get_field( 'enable_attached_product', get_the_ID() ) && ! empty( get_field( 'scalarworks_attached_products', get_the_ID() ) ) ) {

					// Include.
					include WOOSCALAR_PATH . 'public/views/single-product/add-to-cart/variable-attached.php';

				} ?>

			</tbody>
		</table>

		<div class="single_variation_wrap"><?php

			/**
			 * Hook: woocommerce_before_single_variation.
			 */
			do_action( 'woocommerce_before_single_variation' );

			/**
			 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
			 *
			 * @since 2.4.0
			 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
			 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
			 */
			do_action( 'woocommerce_single_variation' );

			/**
			 * Hook: woocommerce_after_single_variation.
			 */
			do_action( 'woocommerce_after_single_variation' ); ?>

		</div>

	<?php }
	
	// After variations form.
	do_action( 'woocommerce_after_variations_form' ); ?>

	<script>
		const scalarworksCombinations = <?php echo wp_json_encode(
			array_filter( array_map( function( $variation ) {
				return array_filter( $variation['attributes'], fn( $v ) => $v !== '' );
			}, $available_variations ) )
		); ?>;
	</script>


</form>

<script>
	jQuery(document).ready(function ($) {
		if (typeof scalarworksCombinations === 'undefined') return;

		const attrNames = [...new Set($('.scalarworks-single').map(function () {
			return $(this).data('id');
		}).get())];

		function getCurrentSelection() {
			const selection = {};
			attrNames.forEach(name => {
				const $active = $(`.scalarworks-single[data-id="${name}"].active`);
				if ($active.length) {
					selection[`attribute_${name}`] = $active.data('value');
				}
			});
			return selection;
		}

		function getValidOptionsForNext(index) {
			const currentSelection = getCurrentSelection();
			const currentAttr = attrNames[index];
			const nextAttr = attrNames[index + 1];

			if (!nextAttr) return {};

			const filteredCombinations = scalarworksCombinations.filter(comb => {
				return Object.entries(currentSelection).every(([k, v]) => comb[k] === v);
			});

			const valid = {};

			filteredCombinations.forEach(comb => {
				const val = comb[`attribute_${nextAttr}`];
				if (val) {
					if (!valid[nextAttr]) valid[nextAttr] = new Set();
					valid[nextAttr].add(val);
				}
			});

			return valid;
		}

		function updateNextTiles(index) {
			const nextAttr = attrNames[index + 1];
			if (!nextAttr) return;

			const validMap = getValidOptionsForNext(index);
			const key = nextAttr;

			$(`.scalarworks-single[data-id="${key}"]`).each(function () {
				const $tile = $(this);
				const val = $tile.data('value');

				if (validMap[key] && validMap[key].has(val)) {
					$tile.show();
				} else {
					$tile.hide().removeClass('active');
				}
			});
		}

		function resetFollowingAttributes(fromIndex) {
			for (let i = fromIndex + 1; i < attrNames.length; i++) {
				const attr = attrNames[i];
				$(`.scalarworks-single[data-id="${attr}"]`)
					.removeClass('active')
					.show(); // show all options again
			}
		}

		$('.scalarworks-single').on('click', function () {
			const $this = $(this);
			const id = $this.data('id');
			const index = attrNames.indexOf(id);
			if (index === -1) return;

			// Set active
			$(`.scalarworks-single[data-id="${id}"]`).removeClass('active');
			$this.addClass('active');

			// Reset all following attributes
			resetFollowingAttributes(index);

			// Filter only next attribute
			updateNextTiles(index);
		});

	// Initial pass: show all, no filters
	});
</script>
<?php

// After add to cart form.
do_action( 'woocommerce_after_add_to_cart_form' );
