<?php

namespace App\Http\Controllers;

use App\Photo;
use Chumper\Zipper\Facades\Zipper;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class HomeController extends Controller
{
    //
    public function index(Request $request) {
        $files = Photo::query()->where('session', $request->session()->getId())->get(['url'])->pluck('url')->all();
        $files = array_map(function($file) {
            return ["src" => asset($file), "processed" => true, "processing" => false];
        }, $files);
        return view('welcome', compact('files'));
    }

    public function upload(Request $request) {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $destName = public_path('images/' . $file->getClientOriginalName());
            $img = Image::make($file->getRealPath())->resize(500, null, function($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->insert(base_path('maper.png'), 'bottom-right')->save($destName, 100);

            $this->_pushFile($request, 'images/' . $file->getClientOriginalName());

            return response()->json([
                "url" =>  asset('images/' . $file->getClientOriginalName()),
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
