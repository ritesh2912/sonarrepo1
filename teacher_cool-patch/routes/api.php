<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminForgetPasswordController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\NewsLetterController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\AssignmentPaymentController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\JobInternshipController;
use App\Http\Controllers\Admin\QuestionAnswerController;
use App\Http\Controllers\Admin\ZoomController as AdminZoomController;

//User Controllers
use App\Http\Controllers\User\DashboardContentController;
use App\Http\Controllers\User\LoginController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\ForgetPasswordController;
use App\Http\Controllers\User\UserContentController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\BillingInfoController;
use App\Http\Controllers\User\SocialLoginController;
use App\Http\Controllers\User\UserNotificationController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\UserAssignmentController;
use App\Http\Controllers\User\TeacherAssignmentController;
use App\Http\Controllers\User\ZoomController;
use App\Http\Controllers\User\MessageController;
use App\Http\Controllers\Seller\SellerController;
// date_default_timezone_set("UTC");
use App\Http\Controllers\User\FreeAssistenceController;
use App\Http\Controllers\Copyright;


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

Route::get('login', [LoginController::class, 'login']);



/*
---------------------------------------------------------
    USER ROUTES
----------------------------------------------------------
*/


Route::prefix('v1')->group(function () {    
    
    Route::post('login', [LoginController::class, 'login']);
    Route::post('register', [LoginController::class, 'register']);
    Route::post('social-login', [SocialLoginController::class, 'socialLogin']);
    Route::post('fb-check', [SocialLoginController::class, 'fbCheck']);
    
    // Route::get('linkedin', [SocialLoginController::class, 'redirectToLinkedin']);
    // Route::get('linkedin-callback', [SocialLoginController::class, 'handleLinkedinCallback']);
    
    Route::group(['middleware' => ['web']], function () {
        Route::get('auth/linkedin', [SocialLoginController::class, 'redirectToLinkedin'])->name('login.linkedin');
        Route::get('auth/linkedin/callback', [SocialLoginController::class, 'handleLinkedinCallback']);
    });
    Route::get('test/whatsapp', [LoginController::class, 'sendMessage']);
    


    Route::post('forget-password', [ForgetPasswordController::class, 'forgetPassword']);
    Route::post('reset-password', [ForgetPasswordController::class, 'resetPassword']);
    Route::post('verify-reset-token', [ForgetPasswordController::class, 'verifyResetPassToken']);
    
    Route::post('verify-email', [LoginController::class, 'verifyEmail']);
    Route::get('register-info', [LoginController::class, 'registerInfo']);
    //Free assistence url
    Route::post('free-assistance', [FreeAssistenceController::class, 'index']);
    // Order Callback
    // Route::get('order-callback', [OrderController::class, 'changeOrderStatus']);

    // content
    Route::get('dashboard-content', [DashboardContentController::class, 'index']);

    Route::get('/search', [UserController::class, 'search']);
    Route::middleware('auth:api')->get('/view-answer', [UserController::class, 'viewAnswer']);
    Route::get('/search-single', [UserController::class, 'searchSingleContent']);
    Route::get('/content/list', [UserController::class, 'allContent']);

    Route::get('/plans', [PaymentController::class, 'index']);
    Route::post('/webhook', [PaymentController::class, 'webhookCallback']);
    Route::post('/payment-callback', [PaymentController::class, 'paymentCallback']);

    Route::get('/careers', [UserController::class, 'careers']);

    Route::get('article', [UserController::class, 'article']);

    Route::get('article/{id}', [UserController::class, 'articleSingle']);

    Route::get('assignment-data', [UserAssignmentController::class, 'assignmentData']);


    //USer Sell Content Routes
    Route::post('seller/register', [SellerController::class,'register'])->name('seller.register');
    Route::post('seller/login', [SellerController::class,'login'])->name('seller.login');
    Route::get('seller/verify/email', [SellerController::class,'verifyEmail'])->name('verify.email');
    Route::prefix('seller')->middleware(['auth:api'])->group(function (){
        Route::get('profile', [SellerController::class, 'index']);
        Route::post('update/profile', [SellerController::class, 'update']);
        Route::get('billing-info', [BillingInfoController::class, 'index']);
        Route::post('billing/details', [BillingInfoController::class,'addSellerBillingInfo'])->name('seller.billing.details');
        Route::post('sell-content', [UserContentController::class, 'uploadContent']);
        Route::get('content/list', [SellerController::class, 'contentList']);
        Route::delete('content/{id}',[UserContentController::class,'destroy']);
        Route::put('update/{id}', [UserContentController::class, 'update']);
    });

    //PROTDECTED ROUTES FOR BOTH TEACHER AND STUDENT
    Route::middleware(['auth:api', 'scope:user,teacher'])->group(function (){
        Route::get('/stats-info', [UserController::class, 'statsInfo']);

        Route::get('/profile', [UserController::class, 'index']);
        Route::post('edit-profile', [UserController::class, 'editProfile']);
        Route::get('/reffral', [UserController::class, 'genrateReaffral']);
        Route::post('/change-password', [UserController::class, 'changePassword']);

        // Notifications
        Route::get('/notification', [UserNotificationController::class, 'index']);
        // Route::post('/notification/{id}', [UserNotificationController::class, 'notification']);

        Route::post('/download-file', [UserController::class, 'downloadFile']);

        Route::post('/buy-content', [UserController::class, 'buyContent']);

        Route::post('/sos', [UserController::class, 'sosEmail']);

        Route::post('/apply-career', [UserController::class, 'applyCareer']);

        Route::get('/zoom-request', [ZoomController::class, 'index']);
        Route::get('/zoom-request/{id}', [ZoomController::class, 'zoomRequestDetail']);
        Route::post('/zoom-request', [ZoomController::class, 'zoomRequest']);

        Route::get('/all-chat', [MessageController::class, 'index']);
        Route::post('/all-message', [MessageController::class, 'allMessage']);
        Route::get('/find-user', [MessageController::class, 'findUser']);
        Route::post('/first-message', [MessageController::class, 'firstMessage']);
        // Route::get('/message', [MessageController::class, 'message']);

        Route::get('/wallet-transection', [PaymentController::class, 'walletTransection']);

        Route::get('/logged-in-plans', [PaymentController::class, 'loggedInPlans']);

        // Assignments
        Route::get('/check-subscription', [UserAssignmentController::class, 'checkSubscription']);
    });
    // Route for anyone...
    Route::get('/careers/{id}', [UserController::class, 'careersDetail']);

    //PROTDECTED ROUTES FOR TEACHER ONLY
    Route::middleware(['auth:api', 'scopes:teacher'])->group(function (){
        // Content
        Route::get('billing-info', [BillingInfoController::class, 'index']);
        Route::post('billing-info', [BillingInfoController::class, 'addBillingInfo']);

        // Assignments
        Route::get('/assignment-request', [TeacherAssignmentController::class, 'assignmentRequest']);
        Route::post('/accept-assignment', [TeacherAssignmentController::class, 'acceptAssignment']);
        Route::post('/answer-assignment', [TeacherAssignmentController::class, 'answerAssignment']);
        Route::get('/my-answer-assignment', [TeacherAssignmentController::class, 'myAnswerAssignment']);
        Route::get('/my-answer-assignment/{id}', [TeacherAssignmentController::class, 'myAssignmentSingle']);
        Route::get('/resubmit-requests', [TeacherAssignmentController::class, 'resubmitRequests']);
        Route::post('/answer-resubmit', [TeacherAssignmentController::class, 'answerResubmit']);
        Route::post('/bid-assignment', [TeacherAssignmentController::class, 'bidAssignment']);

        
    });

    //PROTDECTED ROUTES FOR STUDENT ONLY
    Route::middleware(['auth:api', 'scopes:user'])->group(function (){
        // Content
        Route::get('content', [UserContentController::class, 'index']);
        

        // Order
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'singleOrder']);
        // Route::post('place-order', [OrderController::class, 'placeOrder']);

        // Payments
        Route::post('/checkout', [PaymentController::class, 'checkout']);
        Route::post('/proceed-pay', [PaymentController::class, 'proceedPay']);
        
        Route::get('/assignment', [UserAssignmentController::class, 'index']);
        Route::post('/assignment', [UserAssignmentController::class, 'assignment']);
        Route::get('/user-assignment/{id}', [UserAssignmentController::class, 'singleAssignment']);
        Route::post('/resubmit-assignment', [UserAssignmentController::class, 'resubmitAssignment']);

        // Order
        Route::get('rewards', [UserController::class, 'rewards']);

    });

    //Seller Payment   
    Route::get('pay-after-15-days', [PaymentController::class, 'payAfter15Days']);

    //Duplicate content list
    Route::get('duplicate/content/{id}', [ContentController::class, 'duplicateContent']);

    //Copyright take down form route
    Route::post('copyright/takedown', [Copyright::class, 'takeDownEmail']);
});
/*
---------------------------------------------------------
    ADMIN ROUTES
----------------------------------------------------------
*/
Route::prefix('admin')->group(function (){
    Route::post('login', [AdminLoginController::class, 'login']);
    Route::post('forget-password', [AdminForgetPasswordController::class, 'forgetPassword']);
    Route::post('reset-password', [AdminForgetPasswordController::class, 'resetPassword']);

    //Protected route Both for Super Admin and Sub-admin
    Route::middleware(['auth:admin-api','scope:admin,sub-admin'])->group(function () {

        Route::get('', [AdminController::class, 'index']);

        // Profile Routes
        Route::get('profile', [AdminController::class, 'profile']);
        Route::post('edit-profile', [AdminController::class, 'editProfile']);
        Route::post('change-password', [AdminController::class, 'changePassword']);

        // Assignment Orders
        Route::get('assignment', [AssignmentController::class, 'index']);
        Route::get('assignment/{id}', [AssignmentController::class, 'assignmentDetail']);
        // Route::get('assignment/{id}', [AssignmentController::class, 'orderDetail']);
        Route::post('assignment-status', [AssignmentController::class, 'updateStatus']);
        Route::get('assignment-bid', [AssignmentController::class, 'assignmentBid']);
        Route::get('assignment-bid/{id}', [AssignmentController::class, 'singleAssignmentBid']);
        Route::post('assign-teacher', [AssignmentController::class, 'assignTeacher']);
    });

    //Protected route Only for Super Admin
    Route::middleware(['auth:admin-api','scopes:admin'])->group(function () {
        //Users 
       
        Route::get('users', [AdminController::class, 'getUsers']);
        Route::get('users/{id}', [AdminController::class, 'userDetails']);
        // Route::delete('users', [AdminController::class, 'deleteUsers']);
        Route::post('user-status', [AdminController::class, 'usersStatus']);

        // Sub Admins
        Route::post('sub-admin', [AdminController::class, 'addSubAdmin']);
        Route::post('edit-sub-admin', [AdminController::class, 'editSubAdmin']);
        Route::get('sub-admin', [AdminController::class, 'getSubAdmin']);
        Route::delete('sub-admin', [AdminController::class, 'deleteSubAdmin']);

        // Subscription
        Route::get('subscription', [SubscriptionController::class, 'index']);
        // Route::post('add-subscription', [SubscriptionController::class, 'addSubscription']);
        Route::post('edit-subscription', [SubscriptionController::class, 'editSubscription']);
        Route::get('subscription/{id}', [SubscriptionController::class, 'subscriptionDetail']);
        // Teacher
        Route::get('teacher-request', [TeacherController::class, 'index']);
        Route::post('teacher-request-status', [TeacherController::class, 'changeStatus']);

        // Content
        Route::get('content', [ContentController::class, 'index']);
        Route::get('content/{id}', [ContentController::class, 'getContent']);
        Route::post('content', [ContentController::class, 'uploade'])->withoutMiddleware('throttle');
        Route::post('content-request', [ContentController::class, 'contentPublishStatus']);
        Route::post('content-export', [ContentController::class, 'bulkExport'])->withoutMiddleware('throttle');   
        Route::post('content-approve', [ContentController::class, 'contentApprove']);   
                

        // NewsLetter
        Route::get('news-letter', [NewsLetterController::class, 'index']);
        Route::get('news-letter/{id}', [NewsLetterController::class, 'singleNewsLetter']);
        // Route::get('news-letter-history', [NewsLetterController::class, 'newsletterHistory']);
        Route::post('news-letter', [NewsLetterController::class, 'sendNewsletterNotification']);
        Route::post('news-letter/{id}', [NewsLetterController::class, 'updateNewsLetter']);
        Route::delete('news-letter/{id}',[NewsLetterController::class,'destroy']);

        // Orders
        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/{id}', [AdminOrderController::class, 'orderDetail']);

        

        //Subject or Categories Management
        Route::get('subject',[SubjectController::class,'index']);
        Route::post('add-subject',[SubjectController::class,'addSubject']);
        Route::get('subject/{id}',[SubjectController::class,'getSubject']);
        Route::post('subject/{id}',[SubjectController::class,'editSubject']);
        Route::delete('subject/{id}',[SubjectController::class,'destroy']);

        //Manage Payment or Teacher Cool Weighage
        Route::get('admin-payment',[SystemSettingController::class,'index']);
        Route::post('add-admin-payment',[SystemSettingController::class,'addPayment']);
        Route::post('admin-payment',[SystemSettingController::class,'editPayment']);
        
        //Order Payment Management
        Route::get('order-payment',[AssignmentPaymentController::class,'paymentList']);
        Route::get('single-order-payment',[AssignmentPaymentController::class,'singlePaymentTeacher']);
        Route::post('block-order-payment',[AssignmentPaymentController::class,'blockTeacherPayment']);

        Route::get('notification', [NotificationController::class, 'index']);
        Route::post('notification', [NotificationController::class, 'addNotification']);

        Route::get('notification', [NotificationController::class, 'index']);
        Route::post('notification', [NotificationController::class, 'addNotification']);

        Route::get('job-internship', [JobInternshipController::class, 'index']);
        Route::get('job-internship/{id}', [JobInternshipController::class, 'singleRecord']);
        Route::post('job-internship', [JobInternshipController::class, 'addRecord']);
        Route::post('job-internship/{id}', [JobInternshipController::class, 'editRecord']);
        Route::post('job-internship-status', [JobInternshipController::class, 'jobInternshipStatus']);

        Route::get('/q&a', [QuestionAnswerController::class, 'index']);
        Route::post('/q&a', [QuestionAnswerController::class, 'upload']);
        Route::post('/add-q&a', [QuestionAnswerController::class, 'addQueAns']);
        Route::get('/q&a/{id}', [QuestionAnswerController::class, 'getSingleQueAns']);
        Route::post('/edit-q&a', [QuestionAnswerController::class, 'editQueAns']);

        //Zoom Request Routes
        Route::get('all/zoom-request', [AdminZoomController::class, 'index']);
        Route::put('zoom-request/change-status/{id}', [AdminZoomController::class, 'update']);

        // Currency Exchange
        Route::get('currency-exchange', [SystemSettingController::class, 'currencyExchange']);
        Route::post('currency-exchange', [SystemSettingController::class, 'updateCurrencyExchange']);
    });
});
