<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\CommonActionController;
use App\Http\Controllers\API\LoginController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function (){


    Route::post('updatePatient', [\App\Http\Controllers\API\SmsController::class,'updatePatient']);
    Route::post('sendSms', [\App\Http\Controllers\API\SmsController::class,'sendSms']);
    Route::get('getAllHospitals', [\App\Http\Controllers\API\HospitalApiController::class,'getAllHospitals']);
    Route::get('getAllPoliceStation', [\App\Http\Controllers\API\HospitalApiController::class,'getAllPoliceStation']);
    Route::get('getAllPollingStations', [\App\Http\Controllers\API\HospitalApiController::class,'getAllPollingStations']);


    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::get('hospital/{id?}', [\App\Http\Controllers\API\HospitalApiController::class,'getHospital']);
        Route::post('add-hospital', [\App\Http\Controllers\API\HospitalApiController::class,'addHospital']);
        Route::post('update-hospital', [\App\Http\Controllers\API\HospitalApiController::class,'updateHospital']);


        Route::post('add-police-station', [\App\Http\Controllers\API\PoliceStationApiController::class,'addPoliceStation']);
        Route::post('update-police-station', [\App\Http\Controllers\API\PoliceStationApiController::class,'updatePoliceStation']);
        Route::get('police-station/{id?}', [\App\Http\Controllers\API\PoliceStationApiController::class,'getPoliceStation']);

        Route::post('add-polling-station', [\App\Http\Controllers\API\PollingStationApiController::class,'addPollingStation']);
        Route::post('update-polling-station', [\App\Http\Controllers\API\PollingStationApiController::class,'updatePollingStation']);
        Route::get('get-polling-station/{id?}', [\App\Http\Controllers\API\PollingStationApiController::class,'getPollingStation']);

        Route::post('add-police-post', [\App\Http\Controllers\API\PolicePostApiController::class,'addPolicePost']);
        Route::post('update-police-post', [\App\Http\Controllers\API\PolicePostApiController::class,'updatePolicePost']);
        Route::get('get-police-post/{id?}', [\App\Http\Controllers\API\PolicePostApiController::class,'getPolicePost']);

        Route::post('add-police-line', [\App\Http\Controllers\API\PoliceLineApiController::class,'addPoliceLine']);
        Route::post('update-police-line', [\App\Http\Controllers\API\PoliceLineApiController::class,'updatePoliceLine']);
        Route::get('get-police-line/{id?}', [\App\Http\Controllers\API\PoliceLineApiController::class,'getPoliceLine']);


        Route::post('update-police-mobile', [\App\Http\Controllers\API\PoliceMobileApiController::class,'updatePoliceMobile']);
        Route::get('get-police-mobile/{id?}', [\App\Http\Controllers\API\PoliceMobileApiController::class,'getPoliceMobile']);


        Route::post('create-meeting', [\App\Http\Controllers\API\MeetingApiController::class,'createMeeting']);
        Route::get('get-zoom-meetings', [\App\Http\Controllers\API\MeetingApiController::class,'getZoomMeetings']);
        Route::post('save-mobile-user-token', [CommonActionController::class,'saveMobileUserToken']);
        Route::post('find-nearest', [CommonActionController::class,'findNearest']);
        Route::get('contact-info', [\App\Http\Controllers\ContactInformationController::class,'contactInfo']);
        Route::post('send-mobile-notification', [CommonActionController::class,'sendMobileNotification']);
        Route::get('getNotifications', [CommonActionController::class,'getNotifications']);
        Route::get('getEmergencyAlerts', [CommonActionController::class,'getEmergencyAlerts']);
        Route::post('emergency-alert', [CommonActionController::class,'emergencyAlert']);
        Route::post('update-vehicle-location', [\App\Http\Controllers\API\PoliceMobileHistory::class,'saveLocation']);
        Route::get('get-user-meeting', [\App\Http\Controllers\API\MeetingApiController::class,'getUserMeeting']);

        Route::post('findNearestMobile', [CommonActionController::class,'findNearestMobile']);
    });//---- end of auth sanctum


    Route::get('sensitivity/{id?}', [\App\Http\Controllers\CommonApiController::class,'sensitivity']);
    Route::get('facilityType/{id?}', [\App\Http\Controllers\CommonApiController::class,'facilityType']);
    Route::get('policeStations/{id?}', [\App\Http\Controllers\CommonApiController::class,'policeStations']);
    Route::get('districts/{id?}', [\App\Http\Controllers\CommonApiController::class,'districts']);
    Route::get('getVehicleType/{id?}', [\App\Http\Controllers\CommonApiController::class,'getVehicleType']);

    Route::post('circles/{id?}', [\App\Http\Controllers\CommonApiController::class,'circles']);
    Route::get('rank', [\App\Http\Controllers\CommonApiController::class,'getRank']);
    Route::get('tehsils/{district_id?}/{tehsil_id?}', [\App\Http\Controllers\CommonApiController::class,'tehsils']);
    Route::post('user-login', [LoginController::class,'eGovUserlogin']);



});



