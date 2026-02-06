(function( window, wp ) {
    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getPaymentMethodData } = window.wc.wcSettings;
    const { decodeEntities } = window.wp.htmlEntities; 
    const { createElement } = window.wp.element;

    const settings = getPaymentMethodData( 'abacatepay', {} );

    const defaultTitle = 'Abacate Pay - PIX';
    const defaultDesc = 'Pague via PIX instantâneo.';
    const iconUrl = settings.icon_url || '';
    const labelText = decodeEntities( settings.title || defaultTitle );
    
    const iconElement = createElement('img', {
        src: iconUrl, alt: 'PIX',
        style: { width: '24px', height: '24px', marginRight: '10px', verticalAlign: 'middle', objectFit: 'contain' }
    });

    const titleElement = createElement('span', {
        style: { fontWeight: 'bold', fontSize: '1em', verticalAlign: 'middle', color: 'inherit' }
    }, labelText);

    const labelContainer = createElement('div', {
        style: { display: 'flex', alignItems: 'center', width: '100%' }
    }, iconElement, titleElement);

    const Content = () => {
        return createElement(
            'div',
            { className: 'wc-block-components-payment-method-details' },
            decodeEntities( settings.description || defaultDesc )
        );
    };

    registerPaymentMethod( {
        name: "abacatepay",
        label: labelContainer,
        content: createElement( Content, null ),
        edit: createElement( Content, null ),
        canMakePayment: () => true,
        ariaLabel: labelText,
        supports: { features: settings.supports || ['products'] },
    } );

})( window, window.wp );
