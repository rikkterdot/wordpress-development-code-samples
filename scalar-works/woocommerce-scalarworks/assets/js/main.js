/* main.js */
jQuery(document).ready(function($) {

    // Store original select options
    const originalSelectOptions = {};
    $('.scalarworks-value select').each(function () {
        const id = $(this).attr('id');
        if (id) originalSelectOptions[id] = $(this).html(); // Save full select HTML
    });


    // Check select fields to see if already selected.
    $('.scalarworks-single').each(function() {
        // Get ID.
        var fieldId = $(this).data('id');
        var fieldValue = $(this).data('value');
        // Check if selected.
        if ($('select#' + fieldId).val() == fieldValue) {
            // Add active class.
            $(this).addClass('active');
        }
    });

    // On custom field selection.
    $('.scalarworks-single').on('click', function() {
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
        
        // Update fields.
        updateFields($(this));
    });

    

    // On selection.
    $('td.scalarworks-value select').on('change', function() {
        // Update fields.
        updateFields($(this));
    });

    // On optic selection.
    $('#attached_optic .scalarworks-package-single').on('click', function() {
        // Get value.
        var productId = $(this).data('value');
        // Get stock status. 
        var status = $(this).data('stock');
        // Remove.
        $('#attached_optic .scalarworks-package-single').removeClass('active');
        // Add active class.
        $(this).addClass('active');
        // Get variation ID.
        var variationId = $('form.variations_form.cart input.variation_id').val();
        // Update data.
        updateData(productId);
        // Update price.
        updatePrice();
        // Update images.
        updateImages();
        // Update stock.
        updateStock(status, productId, variationId);
    });

    // On variation change.
    $('form.variations_form.cart input.variation_id').on('change', function() {
        // Get variation ID.
        var variationId = $(this).val();
        // Update price.
        updatePrice();
        // Update images.
        updateImages();
        // Check for attached optics.
        if($('#attached_optic').length > 0 && $('#attached_optic .scalarworks-package-single.active').length > 0) {
            // Get product ID.
            var productId = $('#attached_optic .scalarworks-package-single.active').data('value');
            // Update stock.
            var status = $('#attached_optic .scalarworks-package-single.active').data('stock');
            updateStock(status, productId, variationId);
            // Show or Hide Optics.
            showHideOptics(variationId);
        }
    });

    // On FAQ open.
    $('.scalarworks-faq').on('click', function() {
        // Is this section Disabled?
        if( $(this).closest("tr").hasClass("scalar-disabled") ) {
            return;
        }
        // Get ID.
        var faqId = $(this).data('id');
        // Add active class.
        $('#faq-modal-' + faqId).addClass('active');
        // Set HTML overflow.
        $('html').css('overflow', 'hidden');
    });

    // On FAQ close.
    $('.scalarworks-faq-modal__close span').on('click', function() {
        // Get ID.
        var faqId = $(this).data('id');
        // Remove active class.
        $('#faq-modal-' + faqId).removeClass('active');
        // Set HTML overflow.
        $('html').css('overflow', 'auto');
    });

    // Close FAQ on escape key.
    $(document).on('keydown', function(e) {
        // Check if FAQ is open.
        if ($('.scalarworks-faq-modal.active').length > 0) {
            // Check if escape key is pressed.
            if (e.key === 'Escape') {
                // Remove active class.
                $('.scalarworks-faq-modal.active').removeClass('active');
                // Set HTML overflow.
                $('html').css('overflow', 'auto');
            }
        }
    });

    // On thumbnail.
    $(document).on('click', '.woocommerce-scalarworks-gallery-thumb', function() {
        // Check if active already.
        if($(this).hasClass('active')) {
            return;
        }
        // Remove active class.
        $('.woocommerce-scalarworks-gallery-thumb.active').removeClass('active');
        // Get image.
        var image = $(this).find('img').attr('src');
        // Set main image.
        $('.woocommerce-scalarworks-gallery-main img').attr('src', image);
        $('.woocommerce-scalarworks-gallery-lightbox img').attr('src', image);
        // Add active class.
        $(this).addClass('active');
    });

    // On lightbox open.
    $('#woocommerce-scalarworks-lightbox-open').on('click', function() {
        $('.woocommerce-scalarworks-gallery-lightbox').addClass('active');
    });

    // On lightbox close.
    $('#woocommerce-scalarworks-lightbox-close').on('click', function() {
        $('.woocommerce-scalarworks-gallery-lightbox').removeClass('active');
    });

    // On gallery hover.
    var $container = $('.woocommerce-scalarworks-gallery-main');
    var $image = $container.find('img');
    $container.on('mousemove', function(e) {
        var offset = $container.offset();
        var relX = e.pageX - offset.left;
        var relY = e.pageY - offset.top;

        var percentX = (relX / $container.width()) * 100;
        var percentY = (relY / $container.height()) * 100;

        $image.css({
        'transform-origin': percentX + '% ' + percentY + '%',
        'transform': 'scale(2.5)' // adjust zoom level here
        });
    });
    $container.on('mouseleave', function() {
        $image.css({
        'transform': 'scale(1)',
        'transform-origin': 'center'
        });
    });

    // Update fields.
    function updateFields(item) {
        // Get ID.
        var fieldId = $(item).data('id');
        var fieldValue = $(item).data('value');
        var fieldName = $(item).data('name');
        // Clear all other active classes from this group.
        $('#scalarworks-custom_' + fieldId + ' .scalarworks-single').removeClass('active');
        // Add active class to this.
        $(item).addClass('active');
        // Check for label.
        // if($('#scalarworks-custom_' + fieldId + '-label').length > 0) {
        //     // Update label.
        //     $('#scalarworks-custom_' + fieldId + '-label').text(fieldName);
        // }
        // Update field value.
        $('select#' + fieldId).val(fieldValue).trigger('change');
        // Get next table row.
        var nextRow = $(item).closest('tr').next('tr');
        // Remove scalar-disabled class from next row.
        if(nextRow.hasClass('scalar-disabled')) {
            nextRow.removeClass('scalar-disabled');
        }
    }

    // Update data.
    function updateData(productId) {
        // Check if product ID is standalone.
        if(productId == 'standalone') {
            // Clear.
            $('#attached_optic_data').val('');
            // Remove SKU.
            $('span.sku_wrapper').find('.sku-added').remove();
        } else {
            // Remove SKU.
            $('span.sku_wrapper').find('.sku-added').remove();
            // Get product data.
            var productData = $('#attached_optic').data('optics');
            // Get data using product ID.
            var product = productData[productId];
            // Add JSON data to hidden field.
            $('#attached_optic_data').val(JSON.stringify(product));
            // Check if product has SKU.
            if(product.sku) {
                // Update SKU.
                $('span.sku_wrapper').append('<span class="sku-added" data-o_content="' + product.sku + '"> + ' + product.sku + '</span>');
            }
        }
    }

    // Update price.
    function updatePrice() {
        // Check for price.
        if($('.scalarworks-product-price').length == 0) {
            return;
        }
        // Variation ID.
        var variationId = $('form.variations_form.cart input.variation_id').val();
        if(variationId == '') {
            return;
        }
        // Get variation from form.variations_form.cart.
        var variation = $('form.variations_form.cart').data('product_variations').find(function(item) {
            return item.variation_id == variationId;
        });
        // Get variation price.
        var price = variation.display_price;
        // Check standalone.
        if($('div#attached_optic .scalarworks-package-single.active').data('value') == 'standalone' || $('div#attached_optic .scalarworks-package-single.active').data('value') == '') {
            // Reveal price.
            $('p.price').show();
            // Hide Scalarworks price.
            $('.scalarworks-product-price').hide();
            return;
        } else {
            // Hide price.
            $('p.price').hide();
            // Show Scalarworks price.
            $('.scalarworks-product-price').show();
        }
        // Get data.
        var productData = JSON.parse($('#attached_optic_data').val());
        // Update price.
        $('.scalarworks-product-price span.woocommerce-Price-amount.amount bdi').text(woocommerce_scalarworks.currency + (parseFloat(price) + parseFloat(productData.price)));
    }

    // Update images.
    function updateImages() {
        // Variation ID.
        var variationId = $('form.variations_form.cart input.variation_id').val();
        if(variationId == '') {
            return;
        }
        // Check if optic is selected.
        if($('#attached_optic_data').val() == '' || $('#attached_optic_data').val() == undefined) {
            // Show.
            $('.woocommerce-scalarworks-image-wrapper').removeClass('hidden');
            // Hide.
            $('.woocommerce-scalarworks-gallery').hide();
            $(window).trigger('resize');
            return;
        } else {
            // Get data.
            var opticData = JSON.parse($('#attached_optic_data').val());
            // Get images.
            var images = opticData.images[variationId];
            // Check if images exist.
            if(images == undefined || images == '') {
                // Show.
                $('.woocommerce-scalarworks-image-wrapper').removeClass('hidden');
                // Hide.
                $('.woocommerce-scalarworks-gallery').hide();
                $(window).trigger('resize');
                return;
            }
            // Clear.
            $('.woocommerce-scalarworks-gallery-thumbnails').empty();
            // Loop through imagesHtml.
            $.each(images, function(index, image) {
                // Check if first image.
                if(index == 0) {
                    // Set main image.
                    $('.woocommerce-scalarworks-gallery-main img').attr('src', image);
                    $('.woocommerce-scalarworks-gallery-lightbox img').attr('src', image);
                    // Append image.
                    $('.woocommerce-scalarworks-gallery-thumbnails').append('<div class="woocommerce-scalarworks-gallery-thumb active"><img src="' + image + '" alt="" /></div>');
                } else {
                    // Append image.
                    $('.woocommerce-scalarworks-gallery-thumbnails').append('<div class="woocommerce-scalarworks-gallery-thumb"><img src="' + image + '" alt="" /></div>');
                }
            });
            // Hide.
            $('.woocommerce-scalarworks-image-wrapper').addClass('hidden');
            // Show.
            $('.woocommerce-scalarworks-gallery').show();
        }

    }

    // Update stock.
    function updateStock(status, productId, variationId) {
        // Check for status.
        if(status == undefined || status == '') {
            return;
        }
        // Add to cart.
        var $addToCart = $('.single_add_to_cart_button');
        // Availability.
        var $availability = $('.woocommerce-variation-availability');
        // Get stock status for variation ID.
        var stockStatus = $('form.variations_form.cart').data('product_variations').find(function(item) {
            return item.variation_id == variationId;
        });
        // Get is_in_stock from object.
        if(stockStatus != undefined && stockStatus.is_in_stock == false) {
            return;
        } else {
            // Add to cart.
            $('.single_add_to_cart_button').prop('disabled', false);
            // Availability.
            $availability.html(woocommerce_scalarworks.instock);
        }
        console.log('Status: ' + status);
        // Check stock.
        if(status == 'outofstock') {
            // Inject WooCommerce-style out of stock message.
            $availability.html(woocommerce_scalarworks.outofstock);
            // Update.
            $('input#backinstock_button').data('product_id', productId);
            // Trigger WooCommerce Back in Stock Notifier to show the form
            $('body').trigger('check_variation_for_backinstockbox');
            // Disable Add to Cart button
            $addToCart.prop('disabled', true);
        } else {
            // Add to cart.
            $('.single_add_to_cart_button').prop('disabled', false);
            // Availability.
            $availability.html(woocommerce_scalarworks.instock);
        }
    }

     // Show/Hide optics.
     function showHideOptics(variationId) {
        $('#attached_optic .scalarworks-package-single').each(function() {

            // Exit if standalone.
            if($(this).data('value') == 'standalone') {
                return;
            }

            // Get IDs.
            var $el       = $(this);
            var parentIds = $el.data('vparent');

            // Exit if empty.
            if (!parentIds) {
                return;
            }
            
            // Setup parentIds.
            parentIds = parentIds.toString().split(',').map(id => id.trim());

            // Check if variationId is in parentIds.
            if (parentIds.includes(variationId.toString())) {
                $el.show();
            } else {
                $el.hide();
            }
        });
        
        // Choose standalone on change
        $('#attached_optic .scalarworks-package-single[data-value="standalone"]').click();

    }

    // Get all unique attribute names from the tiles
    const attrNames = [...new Set($('.scalarworks-single').map(function () {
        return $(this).data('id');
    }).get())];

    // Get current selections
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

    // Get all Valid options
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

    // Update next tiles based on current selection
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

    // Reset following attributes
    function resetFollowingAttributes(fromIndex) {
        for (let i = fromIndex + 1; i < attrNames.length; i++) {
            const attr = attrNames[i];
    
            // Reset custom tiles
            $(`.scalarworks-single[data-id="${attr}"]`).removeClass('active').show();
    
            // Reset <select> HTML and re-init Select2
            const $select = $('.scalarworks-value select#' + attr);
            if ($select.length && originalSelectOptions[attr]) {
                $select.html(originalSelectOptions[attr]); // restore options
    
                // If WooCommerce initialized Select2, destroy and re-init it
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');         // destroy current
                    $select.select2();                  // re-initialize
                }
    
                $select.trigger('change'); // fire change event to sync UI
            }
        }
    }

});