jQuery(document).ready(function ($) {
  "use strict";

  // Currency switcher functionality
  $(".cmc-currency-button").on("click", function (e) {
    e.preventDefault();

    var url = $(this).attr("href");

    // Add loading state
    $(this).addClass("loading");

    // Navigate to new URL (page will reload with new currency)
    window.location.href = url;
  });

  // Optional: AJAX currency switching (commented out by default)
  /*
    $('.cmc-currency-select').on('change', function() {
        var currency = $(this).val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cmc_switch_currency',
                currency: currency,
                nonce: cmc_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    */
});
