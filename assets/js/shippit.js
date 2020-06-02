jQuery(document).ready(function () {
    // Ensure the attributes are displayed based on the currently active options
    registerOnchangeEvent('wc_settings_shippit_tariff_code');
    registerOnchangeEvent('wc_settings_shippit_dangerous_goods_code');
    registerOnchangeEvent('wc_settings_shippit_dangerous_goods_text');
    registerOnchangeEvent('wc_settings_shippit_origin_country_code');
});

function registerOnchangeEvent(attributeId)
{
    // Register the event handler
    jQuery('[name="' + attributeId + '_attribute"]').on('select2:select', function() {
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
