import Alpine from 'alpinejs';

import orderForm from './components/orderForm';
import restockProduct from './components/restockProduct';
import voidOrder from './components/voidOrder';
import toasts from './stores/toasts';

Alpine.store('toasts', toasts);
Alpine.data('orderForm', orderForm);
Alpine.data('voidOrder', voidOrder);
Alpine.data('restockProduct', restockProduct);

window.Alpine = Alpine;
Alpine.start();
