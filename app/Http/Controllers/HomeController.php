<?php

namespace App\Http\Controllers;

use App\Photo;
use Chumper\Zipper\Facades\Zipper;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class HomeController extends Controller
{
    //
    public function index(Request $request) {
        $files = Photo::query()->where('session', $request->session()->getId())->get(['url'])->pluck('url')->all();
        $files = array_map(function($file) {
            return ["src" => asset($file), "processed" => true, "processing" => false];
        }, $files);

        $watermark = session()->get('watermark', null);
        return response()->view('welcome', compact('files', 'watermark'));
    }

    public function deleteWatermark(Request $request) {
        $watermark = session()->get('watermark', null);
        if ($watermark != null) {
            File::delete(public_path($watermark));
            session()->put('watermark', null);
        }
        return response()->json([]);
    }

    public function uploadWatermark(Request $request) {
        $file = $request->file('file');
        $fileName = 'watermark-' . $request->session()->getId() . "." . $file->getClientOriginalExtension();
        $file->move(public_path('images'), $fileName);
        session()->put('watermark', 'images/' . $fileName);
        return response()->json(session('watermark'));
    }

    public function upload(Request $request) {
        $config = json_decode(Cookie::get('config'));
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $resultFileName = 'images/' . $file->getClientOriginalName();

            # Xu ly trung file
            if (File::exists(public_path($resultFileName))) {
                $resultFileName = 'images/' . str_slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . "-"
                    . Str::random(5) . "." .$file->getClientOriginalExtension();
            }

            $destName = public_path($resultFileName);
            $img = Image::make($file->getRealPath());

            if ($config->size) {
                $img->resize($config->size->width, $config->size->height, function($constraint) use ($config) {
                    if ($config->size->ratio == 'keep-ratio') {
                        $constraint->aspectRatio();
                    }
                });
            }

            if ($config->watermark && session('watermark', null) != null) {
                $watermark = session('watermark', null);
                $img->insert(public_path($watermark), isset($config->watermark->position) ? $config->watermark->position : 'bottom-right');
            }

            if ($config->size) {
                $img->save($destName, $config->size->quality);
            } else {
                $img->save($destName, 100);
            }

            $this->_pushFile($request, $resultFileName);

            return response()->json([
                "url" =>  asset($resultFileName),
                "id" => $request->get("id")
            ]);
        }
    }

    public function download(Request $request) {
        $files = Photo::query()->where('session', $request->session()->getId())->get(['url'])->pluck('url')->all();
        $files = array_map(function($file) {
            return public_path($file);
        }, $files);
        $fileName = 'results/' . date("ddmmYHi") . ".zip";
        Zipper::make($fileName)->add($files)->close();
        return response()->download(public_path($fileName));
    }

    public function delete(Request $request) {
        $files = Photo::query()->where('session', $request->session()->getId())->get(['url'])->pluck('url')->all();
        $files = array_map(function($file) {
            return public_path($file);
        }, $files);
        foreach ($files as $file) {
            File::delete($file);
        }
        Photo::query()->where('session', $request->session()->getId())->delete();
        return response()->json([]);
    }

    private function _pushFile($request, $url) {
        Photo::create([
            'session' => $request->session()->getId(),
            'url' => $url
        ]);
    }
}
