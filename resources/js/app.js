import './bootstrap';

import Alpine from 'alpinejs';

import documentUpload from './components/documentUpload';

window.Alpine = Alpine;
Alpine.data('documentUpload', documentUpload);

Alpine.start();
