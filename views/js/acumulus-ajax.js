"use strict";
(function ($) {
    function addAcumulusAjaxHandling(elt) {
        const buttonSelector = "button, input[type=button], input[type=submit]";
        $(buttonSelector, ".acumulus-area").addClass("btn btn-primary"); // jQuery
        $(".acumulus-ajax", elt).click(function () { // jQuery
            // Area is the element that is going to be replaced and serves as the
            // parent in which we will search for form elements.
            const clickedElt = this;
            const area = $(clickedElt).parents(".acumulus-area").get(0); // jQuery
            $(buttonSelector, area).prop("disabled", true); // jQuery
            clickedElt.value = area.getAttribute('data-acumulus-wait');

            // The URL we are going to send to.
            const ajaxUrl = area.getAttribute('action');
            // The data we are going to send contains the following properties:
            // - ajax: 1 (PS)
            // - dataType: the expected format for the response (PS)
            // - clicked: the name of the element that was clicked, the name should
            //   make clear what action is requested on the server and, optionally, on
            //   what object.
            // - area: the id of the area from which this request originates, the
            //   "acumulus form part" (though not necessarily a form node). This may
            //   be used for further routing the request within our controller.
            // - {values}: values of all form elements in area: input, select and
            //   textarea, except buttons (inputs with type="button").
            const data = {
                ajax: 1,
                dataType: 'json',
                clicked: clickedElt.name,
                area: area.id,
            };
            // Area is a form node, so FormData will work.
            const formData = new FormData(area);
            for (let entry of formData.entries()) {
                data[entry[0]] = entry[1];
            }

            // Send the ajax request.
            $.post(ajaxUrl, data, function (response) { // jQuery
                area.insertAdjacentHTML('beforebegin', response.content);
                const newArea = area.previousElementSibling;
                area.parentNode.removeChild(area);
                addAcumulusAjaxHandling(newArea);
                addDetailsHandling(newArea);
            }, 'json');
        });
    }

    function addDetailsHandling(elt) {
        const panels = $(".panel-heading .details", elt).parent();
        panels.next().toggle(false); // jQuery
        panels.click(function () { // jQuery
            $(this).next().toggle(400); // jQuery
        });
    }

    $(document).ready(function () { // jQuery
        addAcumulusAjaxHandling(document);
        $(".acumulus-auto-click").click(); // jQuery
        addDetailsHandling(document);
    });
}(jQuery));
