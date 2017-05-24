<?php

namespace App\Http\Controllers;

use Chumper\Zipper\Facades\Zipper;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class HomeController extends Controller
{
    //
    public function index() {
        $files = session()->get('files', []);
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
            })->insert(base_path('maper.png'), 'bottom-right')->save($destName);

            $this->_pushFile($request, 'images/' . $file->getClientOriginalName());

            return response()->json([
                "url" =>  asset('images/' . $file->getClientOriginalName()),
                "id" => $request->get("id")
            ]);
        }
    }

    public function download() {
        $files = session()->get('files', []);
        $files = array_map(function($file) {
            return public_path($file);
        }, $files);
        $fileName = 'results/' . date("ddmmYHi") . ".zip";
        Zipper::make($fileName)->add($files)->close();
        return response()->download(public_path($fileName));
    }

    public function delete() {
        $files = session()->get('files', []);
        $files = array_map(function($file) {
            return public_path($file);
        }, $files);
        foreach ($files as $file) {
            File::delete($file);
        }
        session()->push('files', []);
        return response()->json([]);
    }

    private function _pushFile($request, $url) {
        if (session()->get('files', [])) session()->put('files', []);
        $request->session()->push('files', $url);
    }
}
