jQuery(document).ready(function ($) {
  "use strict";

  // Accordion toggle functionality
  $(document).on("click", ".cmc-accordion-header", function (e) {
    e.preventDefault();

    var $accordion = $(this).closest(".cmc-currency-accordion");

    // Toggle current accordion
    $accordion.toggleClass("open");

    // Optional: Close other accordions in the same wrapper (uncomment if you want only one open at a time)
    // $accordion.siblings('.cmc-currency-accordion').removeClass('open');
  });

  // Handle variations - need to reinitialize when variations are loaded/added
  $(document).on("woocommerce_variations_loaded", function () {
    // Accordions are already initialized via event delegation
  });

  // Handle when a new variation is added
  $("#variable_product_options").on(
    "woocommerce_variations_added",
    function () {
      // Accordions are already initialized via event delegation
    },
  );
});
