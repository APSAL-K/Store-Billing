import Alpine from 'alpinejs';

import orderForm from './components/orderForm';
import toasts from './stores/toasts';

Alpine.store('toasts', toasts);
Alpine.data('orderForm', orderForm);

window.Alpine = Alpine;
Alpine.start();
