<?php
    namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;

    trait ImageUploads{

        public function imageUpload($request,$desired_path){
            $file = $request->file('user_image');

            if($file){
                $image_name = rand(10000, 50000) . '_' . time().'.'.$file->extension();  
                $file->storeAs($desired_path, $image_name, 'public');
            }
            return $desired_path . $image_name;
        }

        public function isImageExist($fileName){
            return Storage::disk('public')->exists($fileName);
        }

        public function deleteImage($fileName){
            Storage::disk('public')->delete($fileName);
        }
    }

?>