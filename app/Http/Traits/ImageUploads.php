<?php
    namespace App\Http\Traits;

    trait ImageUploads{

        public function imageUpload($request,$desired_path){
            if($_FILES['user_image']['name'] != ''){
                $file = $request->file('user_image');
                $fileName = rand(10000, 50000) . '_' . time() . '.' . $file->extension();
                $type = $file->getClientMimeType();
                $size = $file->getSize();
    
                $file->storeAs($desired_path, $fileName, 'public');
    
            }
            return $desired_path . $fileName;
        }
    }

?>