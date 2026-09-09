import Alpine from 'alpinejs';

import customerForm from './components/customerForm';
import deleteOrder from './components/deleteOrder';
import orderForm from './components/orderForm';
import restockProduct from './components/restockProduct';
import toasts from './stores/toasts';

Alpine.store('toasts', toasts);
Alpine.data('orderForm', orderForm);
Alpine.data('deleteOrder', deleteOrder);
Alpine.data('customerForm', customerForm);
Alpine.data('restockProduct', restockProduct);

window.Alpine = Alpine;
Alpine.start();
