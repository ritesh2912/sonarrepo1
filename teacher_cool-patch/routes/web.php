<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\TeacherAssignmentController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\Admin\AssignmentPaymentController;
use App\Http\Controllers\Admin\ContentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// cron to pay to teachers
Route::get('pay-teacher', [AssignmentPaymentController::class, 'payToTeacher']);
Route::get('content/payment', [ContentController::class, 'payUserForContent'])->name('content.user.payment');

// Route::get('/assign-teacher', [TeacherAssignmentController::class, 'assignTeacher']);
// Route::get('/payment-callback', [PaymentController::class, 'paymentCallback']);

// Route::get('/test', function () {
//     $data['subject'] = "eng";
//     $data['email'] = "test@email.com";
//     $data['category'] = "IT";
//     $data['word_count'] = "12";
//     $data['question']= "test";
//     $data['contact']= "test";
//     $welcomedata=[
//         'to'=> "test@email.com",
//         'receiver_name'=> "test",
//         'data'=> $data,
//         'body' => "Hope, You will have wonderful experience here.Please verify your email from the link below:",
//         'subject' => "Regarding Welcome"
//     ];
//     return view('emails.getqoute', $welcomedata);
// });

// Route::get('verify-email/{code}', [LoginController::class, 'verifyEmail']);
