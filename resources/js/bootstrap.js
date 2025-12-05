window._ = require('lodash');

window.axios = require('axios').default;

if (window.axios) {
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
} else {
    console.error('Axios NO se cargó correctamente en bootstrap.js');
}


