<?php

namespace Modules\Company\Http\Traits;

use Illuminate\Support\Facades\Storage;

trait CompanyTrait{

    public function upload_logo($request)
    {
        if($_FILES['company_logo']['name'] != ''){
            $file = $request->file('company_logo');
            $fileName = rand(10000, 50000) . '_' . time() . '.' . $file->extension();
            $type = $file->getClientMimeType();
            $size = $file->getSize();

            $file->storeAs('uploads/company/photo/', $fileName, 'public');

            $data['client_name'] = $file->getClientOriginalName();
            $data['file_ext'] = $file->getClientOriginalExtension();
            $data['file_name'] = $fileName;
            $data['file_path'] = Storage::disk('public')->url("uploads/company/photo/{$fileName}");
            $data['file_size'] = $file->getSize();
            $data['file_type'] = $file->getClientMimeType();
            $data['full_path'] = asset(
                'storage/uploads/company/photo/' . $fileName
            );
            $data['full_path'] = Storage::disk('public')->url("uploads/company/photo/{$fileName}");
            $data['orig_name'] = $file->getClientOriginalName();

            $data['raw_name'] = $fileName;
        }
        return 'uploads/company/photo/' . $fileName;
        // return $data['full_path'];
    }

    public function isLogoExist($fileName){
        return Storage::disk('public')->exists($fileName);
    }

    public function deleteLogo($fileName){
        Storage::disk('public')->delete($fileName);
    }
}
