"use strict";
(function($) {
  function addAcumulusAjaxHandling() {
    $("#acumulus-invoice input[type=button]").addClass("btn btn-primary");
    $(".acumulus-ajax").click(function() { // jQuery
      // Area is the element that is going to be replaced and serves as the
      // parent in which we will search for form elements.
      const clickedElt = this;
      const area = $(clickedElt).parents(".acumulus-area").get(0); // jQuery
      //noinspection JSUnresolvedVariable
      clickedElt.value = area.getAttribute('data-acumulus-wait');
      clickedElt.disabled = true;


      // The URL we are going to send to.
      const ajaxUrl = area.getAttribute('action');
      // The data we are going to send consists of:
      // - ajax: 1 (PS)
      // - Controller
      // - clicked: the name of the element that was clicked, the name should
      //   make clear what action is requested on the server and, optionally, on
      //   what object.
      // - area: the id of the area from which this request originates, the
      //   "acumulus form part" (though not necessarily a form node). This may
      //   be used for further routing the request within the controller.
      // - {values}: values of all form elements in area: input, select and
      //   textarea, except buttons (inputs with type="button").
      //noinspection JSUnresolvedVariable
      const data = {
        ajax: 1,
        dataType: 'json',
        clicked: clickedElt.name,
        area: area.id,
      };
      // Area is a form node, so FormData will work.
      const formData = new FormData(area);
      for(let entry of formData.entries()) {
        data[entry[0]] = entry[1];
      }

      // Send the ajax request.
      $.post(ajaxUrl, data, function(response) { // jQuery
        area.insertAdjacentHTML('beforebegin', response.content);
        area.parentNode.removeChild(area);
        addAcumulusAjaxHandling();
      });
    });
  }

  $(document).ready(function() { // jQuery
    addAcumulusAjaxHandling();
  });
}(jQuery));