jQuery(document).ready(function ($) {
  "use strict";

  // Add any admin JavaScript functionality here

  // Example: Confirm before saving if no currencies selected
  $("form").on("submit", function (e) {
    var checkedCurrencies = $(
      'input[name="cmc_enabled_currencies[]"]:checked',
    ).length;

    if (checkedCurrencies === 0) {
      alert("Please select at least one currency.");
      e.preventDefault();
      return false;
    }
  });

  // Select/Deselect all functionality (if needed in future)
  if ($("#cmc-select-all").length) {
    $("#cmc-select-all").on("change", function () {
      $('input[name="cmc_enabled_currencies[]"]').prop(
        "checked",
        $(this).prop("checked"),
      );
    });
  }
});
