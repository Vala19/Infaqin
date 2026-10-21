<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

Route::get('/language/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
Route::get('/campaigns/{campaign}/comments', [CommentController::class, 'index'])->name('campaigns.comments');

Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/faq', 'faq')->name('faq');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show');

Route::post('/payments/midtrans/notification', MidtransNotificationController::class)
    ->middleware('throttle:120,1')->name('payments.midtrans.notification');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/home', HomeController::class)->name('home');

    Route::middleware('verified')->group(function () {
        Route::get('/campaigns/{campaign}/donate', [DonationController::class, 'create'])->name('donations.create');
        Route::post('/campaigns/{campaign}/donate', [DonationController::class, 'store'])->middleware('throttle:20,1')->name('donations.store');
        Route::get('/donations/{donation}', [DonationController::class, 'show'])->name('donations.show');
        Route::get('/donations/{donation}/status', [DonationController::class, 'status'])->middleware('throttle:60,1')->name('donations.status');
    });
    Route::post('/donations/{donation}/cancel', [DonationController::class, 'cancel'])->middleware('throttle:10,1')->name('donations.cancel');

    Route::post('/campaigns/{campaign}/comments', [CommentController::class, 'store'])->middleware('throttle:10,1')->name('comments.store');
    Route::post('/comments/{comment}/amen', [CommentController::class, 'amen'])->middleware('throttle:60,1')->name('comments.amen');

    Route::get('/history', HistoryController::class)->name('history');

    // Saving is allowed before email verification; only donating requires it.
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::get('/campaigns/{campaign}/wishlist', [WishlistController::class, 'campaign'])->name('wishlist.campaign');
    Route::post('/campaigns/{campaign}/wishlist', [WishlistController::class, 'update'])->middleware('throttle:30,1')->name('wishlist.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:3,1')->name('verification.send');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::resource('news', Admin\NewsController::class)->except('show')->parameters(['news' => 'news']);
    Route::resource('campaigns', Admin\CampaignController::class)->except('show');
    Route::post('/campaigns/{campaign}/withdrawals', [Admin\WithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::delete('/withdrawals/{withdrawal}', [Admin\WithdrawalController::class, 'destroy'])->name('withdrawals.destroy');
    Route::get('/donations', Admin\DonationController::class)->name('donations.index');
});
