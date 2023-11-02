jQuery(document).ready(function () {
    // Ensure the attributes are displayed based on the currently active options
    registerOnchangeEvent('wc_settings_shippit_tariff_code');
    registerOnchangeEvent('wc_settings_shippit_dangerous_goods_code');
    registerOnchangeEvent('wc_settings_shippit_dangerous_goods_text');
    registerOnchangeEvent('wc_settings_shippit_origin_country_code');
    registerOnchangeEventForSelectInputsWithBooleanValues('.shippit-margin', '-amount');
    registerOnchangeEventForSelectInputsWithBooleanValues('.woocommerce-mamis-shippit-filter-attribute', '-code');
    registerOnchangeEventForSelectInputsWithBooleanValues('.woocommerce-mamis-shippit-filter-attribute', '-value');
});

function registerOnchangeEvent(attributeId)
{
    // Register the event handler
    // Handle change event for select2 versions > 4.0.0 as well as older versions
    jQuery('[name="' + attributeId + '_attribute"]').on('select2:select, change', function() {
        visibilityCustomAttribute(attributeId);
    });

    // Update the current display
    visibilityCustomAttribute(attributeId);
}

function visibilityCustomAttribute(attributeId)
{
    var value = jQuery('[name="' + attributeId + '_attribute"]').val();

    if (value == '_custom') {
        jQuery('input[name="' + attributeId + '_custom_attribute"]').closest('tr').show();
    }
    else {
        jQuery('input[name="' + attributeId + '_custom_attribute"]').closest('tr').hide();
    }
}

function registerOnchangeEventForSelectInputsWithBooleanValues(className, suffix)
{
    // Register the event handler
    // Handle change event for select2 versions > 4.0.0 as well as older versions
    jQuery(className)
        .on(
            'select2:select, change',
            function() {
                changeSelectInputVisibility(className, suffix);
            }
        );

    // Update the current display
    changeSelectInputVisibility(className, suffix);
}

function changeSelectInputVisibility(className, suffix)
{
   var value = jQuery(className).val();

    if (value == 'no') {
        jQuery(className + suffix).closest('tr').hide();
    }
    else {
        jQuery(className + suffix).closest('tr').show();
    }
}
