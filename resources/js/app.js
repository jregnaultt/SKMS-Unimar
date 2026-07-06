import './bootstrap';

import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';

import documentUpload from './components/documentUpload';
import catalogSearch from './components/catalogSearch';
import advancedSelect from './components/advancedSelect';

window.Alpine = Alpine;
window.Chart = Chart;
Chart.register(...registerables);

Alpine.data('documentUpload', documentUpload);
Alpine.data('catalogSearch', catalogSearch);
Alpine.data('advancedSelect', advancedSelect);

Alpine.start();
