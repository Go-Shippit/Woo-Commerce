jQuery(document)
    .ready(
        function ($) {
            registerOnchangeEventForMarginAmount('.shippit-margin', $);
        }
    );

function registerOnchangeEventForMarginAmount(className, $)
{
    // Register the event handler
    // Handle change event for select2 versions > 4.0.0 as well as older versions
    $(className)
        .on(
            'select2:select, change',
            function() {
                changeMarginAmountVisibility(className, $);
            }
        );

    // Update the current display
    changeMarginAmountVisibility(className, $);
}

function changeMarginAmountVisibility(className, $)
{
   var value = $(className).val();

    if (value == 'no') {
        $(className + '-amount').val('');
        $(className + '-amount').closest('tr').hide();
    }
    else {
        $(className + '-amount').closest('tr').show();
    }
}
