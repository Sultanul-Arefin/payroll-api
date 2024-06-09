<?php

namespace App\Http\Traits;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Storage;

trait Attachment
{
    public function uploadAttachment($request, $fileName=null,  $storagePath = '')
    {
        if($request->hasFile($fileName)){
            $file = $request->file($fileName);
            $uniqueFileName = rand(0, 999999999) . '_' . date('Ymdhis').'_' . rand(100, 999999999) . '.' . $file->getClientOriginalExtension();
            $file->storeAs($storagePath, $uniqueFileName, 'public');
            return "{$storagePath}/{$uniqueFileName}";
        }else{
            return false;
        }
    }

    public function updateAttachment($file, $storagePath = '')
    {
            $uniqueFileName = rand(0, 999999999) . '_' . date('Ymdhis').'_' . rand(100, 999999999) . '.' . $file->getClientOriginalExtension();
            $file->storeAs($storagePath, $uniqueFileName, 'public');
            return "{$storagePath}/{$uniqueFileName}";
    }

    public function deleteAttachment($fileName)
    {
       $takeOnlyImageName = explode('/', $fileName, 5);
        $existFile = Storage::disk('public')->exists($takeOnlyImageName[4]);
        if ($existFile) {
            return Storage::disk('public')->delete($takeOnlyImageName[4]);
        }
    }

    public function uploadMultipleAttachment($files, string $storagePath = '')
    {
        $finalFilePath = [];
        if ($files) {
            foreach ($files as $file) {
                $fileName = rand(0, 999999999).'_'.date('Ymdhis').'_'.rand(100, 999999999).'.'.$file->getClientOriginalExtension();
                $file->storeAs($storagePath, $fileName, 'public');
                $name = "{$storagePath}/{$fileName}";
                array_push($finalFilePath, $name);
            }
        }

        return ['fileName' => $finalFilePath, 'message' => 'somethings..'];
    }

    public function deleteMultipleAttachment($fileNames)
    {
        foreach ($fileNames as $fileName) {
            $existFile = Storage::disk('public')->exists($fileName);
            if (! empty($existFile)) {
                Storage::disk('public')->delete($fileName);
            } else {
                throw new CustomException('Something Wrong!');
            }
        }

    }
}
