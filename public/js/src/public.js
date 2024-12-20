import { createApp } from 'vue';
import Menu from './common/menu.vue';

// import * as filters from './common/filters';

// import formError from './common/form_error';
// import fieldErrors from './common/field_errors';
// import fieldError from './common/field_error';

// SASS/CSS
import '../../css/public.scss';
import '../../css/editor.scss';

// images
import '../../images/icons-public.svg';

// createApp(formError).mount('#form-error');
// createApp(fieldErrors).mount('#field-errors');
// createApp(fieldError).mount('#field-error');

createApp(Menu).mount('#menu');
