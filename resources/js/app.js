import Alpine from 'alpinejs';

import customerForm from './components/customerForm';
import deleteOrder from './components/deleteOrder';
import orderForm from './components/orderForm';
import productForm from './components/productForm';
import restockProduct from './components/restockProduct';
import toasts from './stores/toasts';

Alpine.store('toasts', toasts);
Alpine.data('orderForm', orderForm);
Alpine.data('deleteOrder', deleteOrder);
Alpine.data('customerForm', customerForm);
Alpine.data('restockProduct', restockProduct);
Alpine.data('productForm', productForm);

window.Alpine = Alpine;
Alpine.start();
