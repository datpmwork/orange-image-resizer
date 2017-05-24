<!doctype html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('semantic/semantic.min.css') }}">
    <!-- Styles -->
    <style>
        h1 {
            text-align: center;
        }
        #app {
            width: 80%;
            margin: auto;
        }
        .image-wrapper {
            display: inline-block;
            position: relative;
        }
        .image.loading {
            width: 100px !important;
            height: auto;
        }
        .image-wrapper.processing:after {
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            content: 'Đang xử lý';
            color: white;
            top: 0; left: 0;
            right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.2);
        }
        span.count {
            background-color: orange;
            padding: 5px;
            display: inline-block;
            margin-left: 15px;
        }
    </style>
</head>
<body>

<div class="container" id="app">

    <h1>Upload Images</h1>
    <input type="file" id="fileUploader" v-on:change="preview()" multiple hidden>
    <button class="ui basic button" v-on:click="openUploader()">
        <i class="icon upload"></i> Thêm ảnh
    </button>
    <a href="/download" class="ui basic button" v-on:click="downloadAll()" v-if="processed.length > 0 || downloadable">
        <i class="icon download"></i> Tải về
    </a>
    <button class="ui basic button negative" v-on:click="deleteAll()">
        <i class="icon download"></i> Xóa tất cả
    </button>
    <br>

    <div class="ui top attached tabular menu">
        <a class="active item" data-tab="first">Hiện tại</a>
        <a class="item" data-tab="second">Lịch sử <span class="count">@{{ processed.length }}</span></a>
    </div>
    <div class="ui bottom attached active tab segment" data-tab="first">
        <div class="ui visible message" v-if="files.length == 0">
            <p>Click thêm ảnh để bắt đầu</p>
        </div>
        <div v-for="image in files" class="image-wrapper" v-bind:class="{ processing : image.processing }">
            <img :src="image.src" alt="" class="ui medium bordered image" v-if="image.src != null">
            <img src="loading.gif" alt="" class="ui medium bordered image loading" v-if="image.src == null">
        </div>
    </div>
    <div class="ui bottom attached tab segment" data-tab="second">
        <div v-for="image in processed" class="image-wrapper" v-bind:class="{ processing : image.processing }">
            <img :src="image.src" alt="" class="ui medium bordered image" v-if="image.src != null">
            <img src="loading.gif" alt="" class="ui medium bordered image loading" v-if="image.src == null">
        </div>
    </div>
</div>

</body>

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="{{ asset('semantic/semantic.min.js') }}"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script src="https://unpkg.com/vue"></script>
<script>
    $(document).ready(function() {
        $('.menu .item').tab();
    });
    String.prototype.hashCode = function() {
        var hash = 0, i, chr;
        if (this.length === 0) return hash;
        for (i = 0; i < this.length; i++) {
            chr   = this.charCodeAt(i);
            hash  = ((hash << 5) - hash) + chr;
            hash |= 0; // Convert to 32bit integer
        }
        return hash;
    };

    var app = new Vue({
        el: '#app',
        data: {
            processed: JSON.parse('{!! json_encode($files) !!}'),
            files: [],
            uploads: [],
            downloadable : false
        },
        methods: {
            openUploader: function() {
                document.querySelector("#fileUploader").click();
            },
            preview: function() {
                this.readURL(document.querySelector("#fileUploader"));
            },
            readURL: function(input) {
                var _ = this;
                for (var i = 0; i < input.files.length; i++) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        var item = _.files.filter(function(item){
                            return item.id == e.target.hashCode;
                        });
                        if (item.length > 0) {
                            item[0].src = e.target.result;
                        }
                    };
                    reader.readAsDataURL(input.files[i]);
                    reader.hashCode = input.files[i].name.hashCode();
                    _.files.push({
                        id: reader.hashCode,
                        src: null,
                        file: input.files[i],
                        processing: true,
                        processed: false
                    })
                }
            },
            deleteAll: function() {
                var _ = this;
                axios.get('delete').then(function() {
                    _.processed = [];
                    _.downloadable = false;
                });
            }
        },
        watch: {
            files: function(val) {
                var _ = this;
                for (var i = 0; i < val.length; i++) {
                    if (val[i].processed == true) continue;
                    var instance = axios.create({
                        baseURL: 'http://maper-image.ivivi.vn/',
                        timeout: 100000,
                    });
                    var fd = new FormData();
                    fd.append('file', val[i].file);
                    fd.append('id', val[i].id);
                    instance.post("upload", fd).then(function(response) {
                        var item = _.files.filter(function(item){
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
            }
        }
    })
</script>
</html>
