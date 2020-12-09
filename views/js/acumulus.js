"use strict";
(function($) {
  function addDetailsHandling() {
    const panels = $(".panel-heading .details").parent();
    panels.next().toggle(false); // jQuery
    panels.click(function() { // jQuery
      $(this).next().toggle(400); // jQuery
    });
  }

  $(document).ready(function() { // jQuery
    addDetailsHandling();
  });
}(jQuery));
