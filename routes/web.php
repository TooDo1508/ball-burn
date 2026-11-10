<?php

use App\Http\Controllers\Admin\Booking\AdminBookingController;
use App\Http\Controllers\Admin\Booking\AdminBookingManagementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\Contact\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\Court\CourtAdminController;
use App\Http\Controllers\Admin\Court\CourtClosureController;
use App\Http\Controllers\Admin\News\BlogCategoryController;
use App\Http\Controllers\Admin\News\NewsController;
use App\Http\Controllers\Booking\GuestBookingController;
use App\Http\Controllers\Blog\PublicBlogController;
use App\Http\Controllers\Contact\ContactMessageController;
use App\Http\Controllers\Contact\ContactPageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');

Route::get('/about', function () {
    $title = 'Về BALLBURN | PickleClub & Bistro';
    $description = 'Khám phá BALLBURN PickleClub & Bistro tại Hà Nội — điểm hẹn sân pickleball, ẩm thực và cộng đồng dành cho mọi người yêu vận động.';
    $image = url('/images/pickleball/court-rally-wide.jpg');

    return view('welcome', [
        'seo' => [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'canonical' => url('/about'),
            'structured_data' => [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'BALLBURN PickleClub & Bistro',
                'description' => $description,
                'url' => url('/about'),
                'image' => $image,
            ],
        ],
    ]);
})->name('about');
Route::get('/blog', [PublicBlogController::class, 'indexPage'])->name('blog.index');
Route::get('/blog/{slug}', [PublicBlogController::class, 'showPage'])->name('blog.show');
Route::get('/booking', [GuestBookingController::class, 'indexPage'])->name('booking.index');
Route::get('/booking/data', [GuestBookingController::class, 'availability'])->name('booking.availability');
Route::post('/booking/holds', [GuestBookingController::class, 'syncHolds'])->middleware('throttle:30,1')->name('booking.holds.sync');
Route::delete('/booking/holds', [GuestBookingController::class, 'releaseHolds'])->middleware('throttle:30,1')->name('booking.holds.release');
Route::post('/booking/payment-instructions', [GuestBookingController::class, 'paymentInstructions'])->middleware('throttle:10,1')->name('booking.payment-instructions');
Route::post('/booking/payment-proof', [GuestBookingController::class, 'submitPaymentProof'])->middleware('throttle:5,1')->name('booking.payment-proof');
Route::get('/booking/status/{token}/data', [GuestBookingController::class, 'status'])->whereAlphaNumeric('token')->name('booking.status');
Route::get('/booking/status/{token}', [GuestBookingController::class, 'statusPage'])->whereAlphaNumeric('token')->name('booking.status.page');
Route::get('/contact', [ContactPageController::class, 'indexPage'])->name('contact.index');
Route::post('/contact', [ContactMessageController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::post('bookings', [AdminBookingController::class, 'store'])->name('admin.bookings.store');
    Route::get('bookings', [AdminBookingManagementController::class, 'index'])->name('admin.bookings.index');
    Route::get('bookings/cancellations', [AdminBookingManagementController::class, 'cancellations'])->name('admin.bookings.cancellations');
    Route::get('bookings/notifications', [AdminBookingManagementController::class, 'notifications'])->name('admin.bookings.notifications');
    Route::put('bookings/notifications/{notificationId}/read', [AdminBookingManagementController::class, 'markNotificationRead'])->whereNumber('notificationId')->name('admin.bookings.notifications.read');
    Route::put('bookings/bulk/status', [AdminBookingManagementController::class, 'bulkUpdateStatus'])->name('admin.bookings.bulk-status');
    Route::delete('bookings/bulk', [AdminBookingManagementController::class, 'bulkDelete'])->name('admin.bookings.bulk-delete');
    Route::get('bookings/{bookingId}', [AdminBookingManagementController::class, 'show'])->whereNumber('bookingId')->name('admin.bookings.show');
    Route::put('bookings/{bookingId}/status', [AdminBookingManagementController::class, 'updateStatus'])->whereNumber('bookingId')->name('admin.bookings.status');
    Route::get('bookings/{bookingId}/payments/{paymentId}/proof', [AdminBookingManagementController::class, 'paymentProof'])
        ->whereNumber('bookingId')->whereNumber('paymentId')->name('admin.bookings.payment-proof');
    Route::get('courts', [CourtAdminController::class, 'index'])->name('admin.courts.index');
    Route::post('courts', [CourtAdminController::class, 'store'])->name('admin.courts.store');
    Route::put('courts/{courtId}', [CourtAdminController::class, 'update'])->whereNumber('courtId')->name('admin.courts.update');
    Route::post('court-closures', [CourtClosureController::class, 'store'])->name('admin.court-closures.store');
    Route::delete('court-closures/{closureId}', [CourtClosureController::class, 'destroy'])->whereNumber('closureId')->name('admin.court-closures.destroy');
    Route::get('contact-messages', [AdminContactMessageController::class, 'index'])->name('admin.contact-messages.index');
    Route::get('contact-messages/{messageId}', [AdminContactMessageController::class, 'show'])->whereNumber('messageId')->name('admin.contact-messages.show');
    Route::put('contact-messages/{messageId}', [AdminContactMessageController::class, 'update'])->whereNumber('messageId')->name('admin.contact-messages.update');
    Route::get('news', [NewsController::class, 'index'])->name('admin.news.index');
    Route::get('news/create', [NewsController::class, 'create'])->name('admin.news.create');
    Route::post('news', [NewsController::class, 'store'])->name('admin.news.store');
    Route::post('news/images', [NewsController::class, 'uploadImage'])->middleware('throttle:20,1')->name('admin.news.images');
    Route::delete('news/bulk', [NewsController::class, 'destroyMany'])->name('admin.news.bulk-destroy');
    Route::get('news/categories', [BlogCategoryController::class, 'index'])->name('admin.news.categories.index');
    Route::post('news/categories', [BlogCategoryController::class, 'store'])->name('admin.news.categories.store');
    Route::put('news/categories/{category}', [BlogCategoryController::class, 'update'])->name('admin.news.categories.update');
    Route::delete('news/categories/{category}', [BlogCategoryController::class, 'destroy'])->name('admin.news.categories.destroy');
    Route::get('news/{post}/edit', [NewsController::class, 'edit'])->name('admin.news.edit');
    Route::get('news/{post}/preview', [NewsController::class, 'preview'])->name('admin.news.preview');
    Route::put('news/{post}', [NewsController::class, 'update'])->name('admin.news.update');
    Route::delete('news/{post}', [NewsController::class, 'destroy'])->name('admin.news.destroy');
    require __DIR__.'/settings.php';
});
