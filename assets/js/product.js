jQuery(document).ready(function ($) {
  "use strict";

  // Accordion toggle functionality
  $(document).on("click", ".cmc-accordion-header", function (e) {
    e.preventDefault();

    var $accordion = $(this).closest(".cmc-currency-accordion");

    // Toggle current accordion
    $accordion.toggleClass("open");
  });

  // Validate sale price vs regular price
  function validateCmcPrices($accordion) {
    var $content = $accordion.find(".cmc-accordion-content");
    var $regularInput = $content.find('input[data-cmc-field="regular"]');
    var $saleInput = $content.find('input[data-cmc-field="sale"]');
    var $error = $content.find(".cmc-price-error");

    var regularVal = $regularInput.val().trim().replace(",", ".");
    var saleVal = $saleInput.val().trim().replace(",", ".");

    if (regularVal !== "" && saleVal !== "") {
      var regularPrice = parseFloat(regularVal);
      var salePrice = parseFloat(saleVal);

      if (!isNaN(regularPrice) && !isNaN(salePrice) && salePrice >= regularPrice) {
        $error.show();
        $saleInput.addClass("cmc-input-error");
        return;
      }
    }

    $error.hide();
    $saleInput.removeClass("cmc-input-error");
  }

  // Trigger validation on input for both fields
  $(document).on(
    "input",
    '.cmc-accordion-content input[data-cmc-field="regular"], .cmc-accordion-content input[data-cmc-field="sale"]',
    function () {
      var $accordion = $(this).closest(".cmc-currency-accordion");
      validateCmcPrices($accordion);
    }
  );

  // Handle variations - need to reinitialize when variations are loaded/added
  $(document).on("woocommerce_variations_loaded", function () {
    // Accordions are already initialized via event delegation
  });

  // Handle when a new variation is added
  $("#variable_product_options").on(
    "woocommerce_variations_added",
    function () {
      // Accordions are already initialized via event delegation
    }
  );
});
