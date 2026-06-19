import './bootstrap';

import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';

import documentUpload from './components/documentUpload';

window.Alpine = Alpine;
window.Chart = Chart;
Chart.register(...registerables);

Alpine.data('documentUpload', documentUpload);

Alpine.start();
