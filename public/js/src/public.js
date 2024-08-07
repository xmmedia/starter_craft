import Vue from 'vue';
// import Vuelidate from 'vuelidate';
// import PortalVue from 'portal-vue';

// import * as filters from './common/filters';

// import formError from './common/form_error';
// import fieldErrors from './common/field_errors';
// import fieldError from './common/field_error';

// SASS/CSS
import '../../css/public.scss';
import '../../css/editor.scss';

// images
import '@/../../images/icons-public.svg';

// disable the warning about dev/prod
Vue.config.productionTip = false;

// Vue.use(Vuelidate);
// Vue.use(PortalVue);

// Vue.component('form-error', formError);
// Vue.component('field-errors', fieldErrors);
// Vue.component('field-error', fieldError);

window.App = new Vue({
    el: '#app',

    data () {
        return {
            showMobileMenu: false,
        };
    },

    mounted () {
        this.$nextTick(() => {
            window.addEventListener('resize', () => { this.showMobileMenu = false });
        });
    },

    methods: {
        toggleMobileMenu () {
            this.showMobileMenu = !this.showMobileMenu;
        },
    },
});
