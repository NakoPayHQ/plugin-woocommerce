(function () {
    var registry = window.wc && window.wc.wcBlocksRegistry;
    if (!registry || !registry.registerPaymentMethod) return;
    var settings = (window.wc.wcSettings && window.wc.wcSettings.getSetting('nakopay_data', {})) || {};
    var el = window.wp && window.wp.element;
    if (!el) return;
    var label = settings.title || 'NakoPay (Crypto)';
    var content = el.createElement('div', null, settings.description || '');
    registry.registerPaymentMethod({
        name: 'nakopay',
        label: label,
        ariaLabel: label,
        content: content,
        edit: content,
        canMakePayment: function () { return true; },
        supports: { features: settings.supports || ['products'] }
    });
})();
