import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import './integrations-store';
import './swal';
import './toast';

Alpine.plugin(collapse);

window.Alpine = Alpine;

Alpine.start();
