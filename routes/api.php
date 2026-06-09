<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{UserAuthController, AdminAuthController};
use App\Http\Controllers\{
    BookController,
    ReviewController,
    GenreController,
    WishlistController
};
use App\Http\Controllers\Admin\{
    AdminReviewController,
    AdminGenreController,
    AdminReportController,
    AdminBookController,
    AdminUserController
};
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::post('register', [UserAuthController::class, 'register']);
Route::post('login', [UserAuthController::class, 'login']);
Route::post('forgot-password', [UserAuthController::class, 'forgotPassword']);
Route::post('reset-password', [UserAuthController::class, 'resetPassword']);

// Public book routes
Route::get('books', [BookController::class, 'index']);
Route::get('books/featured', [BookController::class, 'featured']);
Route::get('books/{book}', [BookController::class, 'show']);
Route::get('genres', [GenreController::class, 'index']);
Route::get('reviews/latest', [ReviewController::class, 'latest']);

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [UserAuthController::class, 'logout']);

    // Reviews
    Route::post('books/{book}/reviews', [ReviewController::class, 'store']);
    Route::put('reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);
    Route::post('reviews/{review}/helpful', [ReviewController::class, 'markHelpful']);
    Route::post('reviews/{review}/responses', [ReviewController::class, 'addResponse']);
    Route::get('books/{book}/my-review', [ReviewController::class, 'myReview']);
    Route::get('books/{book}/reviews', [BookController::class, 'reviews']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);

    // User profile and reviews
    Route::get('/user/profile', [UserProfileController::class, 'show']);
    Route::put('/user/profile', [UserProfileController::class, 'update']);
    Route::get('/user/reviews', [UserProfileController::class, 'reviews']);
    Route::post('/user/profile-picture', [UserProfileController::class, 'uploadProfilePicture']);
});

// Admin routes
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('forgot-password', [AdminAuthController::class, 'forgotPassword']);

    Route::middleware('auth:sanctum', 'admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);

        // Books management
        Route::apiResource('books', AdminBookController::class);

        // Users management
        Route::apiResource('users', AdminUserController::class);
        Route::post('users/{user}/ban', [AdminUserController::class, 'ban']);
        Route::post('users/{user}/unban', [AdminUserController::class, 'unban']);

        // Reviews management
        Route::apiResource('reviews', AdminReviewController::class);
        Route::post('reviews/{review}/moderate', [AdminReviewController::class, 'moderate']);

        // Reports management
        Route::apiResource('reports', AdminReportController::class);
        Route::post('reports/{report}/moderate', [AdminReportController::class, 'moderate']);

        // Genres management
        Route::apiResource('genres', AdminGenreController::class);

        // Dashboard stats
        Route::get('dashboard-stats', [AdminUserController::class, 'dashboardStats']);
    });
});
