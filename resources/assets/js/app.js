
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
String.prototype.hashCode = function () {
    var hash = 0, i, chr;
    if (this.length === 0) return hash;
    for (i = 0; i < this.length; i++) {
        chr = this.charCodeAt(i);
        hash = ((hash << 5) - hash) + chr;
        hash |= 0; // Convert to 32bit integer
    }
    return hash;
};

require('./bootstrap');

window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
    data: {
        processed: JSON.parse('{!! json_encode($files) !!}'),
        files: [],
        uploads: [],
        downloadable: false,
        watermark: '{!! $watermark !!}',
        watermarkPosition: 'bottom-right',
        config: {}
    },
    methods: {
        openUploader: function () {
            document.querySelector("#fileUploader").click();
        },
        preview: function () {
            this.readURL(document.querySelector("#fileUploader"));
        },
        readURL: function (input) {
            var _ = this;
            for (var i = 0; i < input.files.length; i++) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    var item = _.files.filter(function (item) {
                        return item.id == e.target.hashCode;
                    });
                    if (item.length > 0) {
                        item[0].src = e.target.result;
                    }
                };
                reader.readAsDataURL(input.files[i]);
                reader.hashCode = Math.random().toString().hashCode();
                _.files.push({
                    id: reader.hashCode,
                    src: null,
                    file: input.files[i],
                    processing: true,
                    processed: false
                })
            }
        },
        deleteAll: function () {
            var _ = this;
            axios.get('delete').then(function () {
                _.processed = [];
                _.files = [];
                _.downloadable = false;
            });
        },

        /* Water Mark */
        openWaterMarkUploader: function() {
            document.querySelector("#fileWaterMarkUploader").click();
        },
        previewWaterMark: function() {
            var _ = this;
            var input = document.querySelector("#fileWaterMarkUploader");
            if (input.files && input.files[0]) {
                var fileReader = new FileReader();
                fileReader.onload = function(e) {
                    _.watermark = e.target.result;
                    console.log(_.watermark);
                };
                fileReader.readAsDataURL(input.files[0]);

                /* Upload WaterMark */
                var fd = new FormData();
                fd.append('file', input.files[0]);
                axios.create({
                    baseURL: location.origin + "/",
                    timeout: 10000
                }).post('uploadWatermark', fd).then(function(response) {

                });
            }
        },

        /* Save Config */
        saveconfig: function() {
            var expiration_date = new Date();
            var cookie_string = '';
            expiration_date.setFullYear(expiration_date.getFullYear() + 1);
            cookie_string = "config=" + JSON.stringify(this.config) + "; path=/; expires=" + expiration_date.toUTCString();
            document.cookie = cookie_string;
        },
        getCookie: function(name) {
            var value = "; " + document.cookie;
            var parts = value.split("; " + name + "=");
            if (parts.length == 2) return parts.pop().split(";").shift();
        }
    },
    watch: {
        files: function (val) {
            var _ = this;
            for (var i = 0; i < val.length; i++) {
                if (val[i].processed == true) continue;
                var instance = axios.create({
                    baseURL: location.origin + "/",
                    timeout: 100000
                });
                var fd = new FormData();
                fd.append('file', val[i].file);
                fd.append('id', val[i].id);
                instance.post("upload", fd).then(function (response) {
                    var item = _.files.filter(function (item) {
                        return item.id == response.data.id;
                    });
                    if (item.length > 0) {
                        item[0].processing = false;
                        item[0].processed = true;
                        item[0].src = response.data.url;
                        _.downloadable = true;
                    }
                });
            }
            document.querySelector('#fileUploaderFormWrapper').reset();
        }
    },
    created: function() {
        /* Read Config From Cookie */
        var cookie = this.getCookie('config');
        if (cookie == undefined) {
            this.config = { size: {width: '', height: '', ratio: 'keep-ratio', quality: 100}, watermark: {position: 'bottom-right'} };
            this.saveconfig();
        } else {
            this.config = JSON.parse(cookie);
        }
    },
    mounted: function() {
        $('.menu .item').tab();
        $('.ui.dropdown').dropdown();
    }
});