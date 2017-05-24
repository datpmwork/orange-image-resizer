<!doctype html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="https://orange.ivivi.vn/favico.png"/>
    <title>Orange - Image Processor</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('semantic/semantic.min.css') }}">
    <!-- Styles -->
    <meta name="og:title" content="Orange - Simple Image Editor">
    <meta name="og:description" content="Tool xử lý hình đơn giản - Chèn Watermark - Giảm chất lượng ảnh">
    <meta name="og:image" content="https://orange.ivivi.vn/sample.jpg">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<div class="container ui segment" id="app">

    <div class="ui grid equal width">
        <div class="column">
            <div class="ui segment green">
                <h4 class="title">Chèn WaterMark</h4>
                <input type="file" id="fileWaterMarkUploader" v-on:change="previewWaterMark()" multiple hidden accept="image/*">
                <button class="ui basic button" v-on:click="openWaterMarkUploader()">
                    <i class="icon upload"></i> Chèn Watermark
                </button>
                <select class="ui dropdown" v-model="config.watermark.position" v-on:change="saveconfig()">
                    <option value="">Vị trí Watermark</option>
                    <option value="bottom-left">Phía dưới - Bên trái</option>
                    <option value="bottom-right">Phía dưới - Bên phải</option>
                    <option value="top-left">Phía trên - Bên trái</option>
                    <option value="top-right">Phía trên - Bên phải</option>
                </select>
                <button class="ui button negative basic" v-on:click="deleteWatermark()" :disabled="watermark == null"><i class="icon trash"></i> Xóa</button>
            </div>
            <div class="ui segment orange">
                <h4 class="title">Xử lý ảnh đơn giản</h4>
                <div class="ui form">
                    <div class="fields">
                        <div class="field">
                            <label>Chiều rộng</label>
                            <input type="text" placeholder="Chiều rộng" v-model="config.size.width" v-on:change="saveconfig()">
                        </div>
                        <div class="field">
                            <label>Chiều cao</label>
                            <input type="text" placeholder="Chiều cao" v-model="config.size.height" v-on:change="saveconfig()">
                        </div>
                        <div class="field">
                            <label>Tỷ lệ</label>
                            <select name="" id="" class="ui dropdown" v-model="config.size.ratio" v-on:change="saveconfig()">
                                <option value="">Chọn kiểu cắt hình</option>
                                <option value="keep-ratio">Giữ đúng tỷ lệ</option>
                                <option value="custom-ratio">Cắt đúng kích thước</option>
                            </select>
                        </div>
                    </div>
                    <div class="fields">
                        <div class="field">
                            <label for="">Chất lượng ảnh</label>
                            <input type="text" placeholder="Chất lượng ảnh (1 - 100%)" v-model="config.size.quality" v-on:change="saveconfig()">
                        </div>
                    </div>
                </div>
            </div>
            <div class="ui segment green">
                <h4>Quản lý hình ảnh</h4>
                <form id="fileUploaderFormWrapper">
                    <input type="file" id="fileUploader" v-on:change="preview()" multiple hidden accept="image/*">
                </form>
                <button class="ui basic button" v-on:click="openUploader()">
                    <i class="icon upload"></i> Thêm ảnh
                </button>
                <a href="/download" class="ui basic button" v-on:click="downloadAll()" v-if="processed.length > 0 || downloadable">
                    <i class="icon download"></i> Tải về
                </a>
                <button class="ui basic button negative" v-on:click="deleteAll()">
                    <i class="icon download"></i> Xóa tất cả
                </button>
            </div>
        </div>

        <div class="column">
            <div class="preview-setting" style="background-image: url('sample.jpg')">
                <img :src="watermark" alt="" v-if="watermark != null" v-bind:class="[config.watermark.position]">
            </div>
        </div>
    </div>

    <div class="ui segment red">
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

</div>

</body>

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="{{ asset('semantic/semantic.min.js') }}"></script>
{{--<script src="https://unpkg.com/axios/dist/axios.min.js"></script>--}}
<script>
    window.processedFiles = JSON.parse('{!! json_encode($files) !!}');
    window.watermark = {!! $watermark == null ? 'null' : "'" . $watermark . "'"!!};
</script>
<script src="{{ asset('js/app.js') }}"></script>
</html>
