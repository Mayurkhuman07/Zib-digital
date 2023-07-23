jQuery(document).ready(function ($) {
    $('#filter-button').on('click', function () {
        var selectedColors = getSelectedTerms('product_color');
        var selectedSizes = getSelectedTerms('product_size');

        $.ajax({
            type: 'POST',
            url: customProductFilterAjax.ajaxurl,
            data: {
                action: 'product_filter',
                colors: selectedColors,
                sizes: selectedSizes,
            },
            success: function (response) {
                // Replace the filtered products with the new product listing.
                $('.filtered-products').html(response);
            },
        });
    });

    function getSelectedTerms(taxonomy) {
        var selectedTerms = [];
        $('input.filter-checkbox[data-taxonomy="' + taxonomy + '"]:checked').each(function () {
            selectedTerms.push($(this).data('term'));
        });
        return selectedTerms;
    }
});
