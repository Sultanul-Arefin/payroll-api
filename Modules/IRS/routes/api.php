<?php

use Illuminate\Support\Facades\Route;
use Modules\IRS\Http\Controllers\Form940Controller;
use Modules\IRS\Http\Controllers\IrsController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/irs/form941/generate', [IrsController::class, 'generateForm941XML']);
    //Route::get('/irs/validate/xml', [IrsController::class, 'validateXml']);
    Route::post('/irs/form940/generate', [Form940Controller::class, 'generateForm940XML']);
    Route::post('/irs/form941/create', [IrsController::class, 'submitForm941JsonToTaxBandits']);
    Route::put('/irs/form941/update', [IRSController::class, 'updateForm941JsonToTaxBandits']);
    Route::post('/irs/form941/validateForm', [IrsController::class, 'validateForm941JsonToTaxBandits']);
    Route::get('/irs/form941/validate', [IrsController::class, 'validateSubmittedForm941']);
    Route::post('/irs/transmit/form941', [IRSController::class, 'transmitForm941']);
    Route::post('/irs/taxbandits/upload-8453emp', [IRSController::class, 'uploadForm8453EMP']);
    Route::get('/irs/form941/list', [IrsController::class, 'listForm941']);
    Route::get('/irs/form941/get', [IrsController::class, 'getForm941']);
    Route::delete('/irs/form941/delete', [IrsController::class, 'deleteForm941']);
    Route::get('/irs/form941/status', [IrsController::class, 'getForm941Status']);
    Route::get('/irs/mainData', [IrsController::class, 'getMainData']);

    Route::post('form941/draft/store', [IrsController::class, 'validateForm941']);



    //For IRS Form940
    Route::post('/irs/form940/create', [Form940Controller::class, 'submitForm940JsonToTaxBandits']);
    Route::put('/irs/form940/update', [Form940Controller::class, 'updateForm940']);
    Route::post('/irs/form940/validateForm', [Form940Controller::class, 'validateForm940JsonToTaxBandits']);
    Route::get('/irs/form940/status', [Form940Controller::class, 'getForm940Status']);
    Route::delete('/irs/form940/delete', [Form940Controller::class, 'deleteForm940']);
    Route::get('/irs/form940/list', [Form940Controller::class, 'listForm940']);
    Route::get('/irs/form940/get', [Form940Controller::class, 'getForm940']);
    
  
    

    Route::post('/irs/generate-pdf-from-xml', [IrsController::class, 'generatePdfFromXml'])->name('irs.generate.pdf.from.xml');
    Route::get('/irs/download/{file}', function ($file) {
        $file = basename($file);
        $path = storage_path("app/irs/{$file}");

        if (file_exists($path)) {
            return response()->download($path);
        }

        abort(404);
    })->name('irs.download.file');


});
   
 Route::prefix('api/v1')->group(function () {
    Route::get('/irs/form941/request-pdf', [IrsController::class, 'getForm941PDF']);
    Route::post('/taxbandits/webhook/pdf', [IrsController::class, 'handlePdfWebhook']);
    Route::get('/irs/taxbandits/form8453emp/download', [IRSController::class, 'downloadForm8453EMP']);
    Route::get('/irs/form941/DownloadForm8879EMP', [IrsController::class, 'downloadForm8879EMP']);
    Route::get('/irs/form941/download', [IrsController::class, 'downloadForm941Pdf']);
    Route::get('/irs/form940/request-pdf', [Form940Controller::class, 'getForm940Pdf']);


});



