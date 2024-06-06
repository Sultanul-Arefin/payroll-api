<?php

namespace App\Http\Traits;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Storage;

trait Attachment
{
    public function uploadAttachment($request, $fileName=null, string $storagePath = '')
    {
        $finalFilePath = null;
        if($request->hasFile($fileName)){
            $file = $request->file($fileName);
            $fileName = rand(0, 999999999) . '_' . date('Ymdhis').'_' . rand(100, 999999999) . '.' . $file->getClientOriginalExtension();
            $file->storeAs($storagePath, $fileName, 'public');
            return "{$storagePath}/{$fileName}";
        }else{
            return false;
        }

        return ['fileName' => $finalFilePath, 'message' => 'somethings..'];
    }

    public function updateAttachment($file, $storagePath = '')
    {
        $result = $this->uploadAttachment($file, $storagePath);

        return $result['fileName'];
    }

    public function deleteAttachment($fileName)
    {
        $existFile = Storage::disk('public')->exists($fileName);
        if ($existFile) {
            return Storage::disk('public')->delete($fileName);
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
