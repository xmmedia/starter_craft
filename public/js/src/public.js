import { createApp } from 'vue';

// components
import Menu from './common/menu.vue';

import { initAjaxForms } from './common/ajax_form';

// CSS
import '../../css/public.css';

// icons
import '../../images/icons-public.svg';

const menuEl = document.getElementById('menu');
createApp(Menu, { ...menuEl.dataset }).mount(menuEl);

initAjaxForms();
