<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RiderController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});









Route::middleware(['auth', 'setDB'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render(
            'Dashboard'
        );
    })->name('dashboard');
    Route::get('/user-dashboard', [UserController::class, 'display_info'])->name('user-dashboard');
    Route::get('/user-dashboard/search', [UserController::class, 'search'])->name('user.search');
    Route::post('/user-dashboard/placeorder', [RiderController::class, 'placeOrder'])->name('user.placeorder');




    Route::middleware(['rider'])->group(function () {
        Route::get('/Rider-dashboard', [RiderController::class, 'display_info'])->name('rider-dashboard');
            Route::post('/orders/accept-status', [RiderController::class, 'updateStatusTo2'])->name('rider.accept');
            Route::post('/orders/accept-status', [RiderController::class, 'updateStatusTo3'])->name('rider.deny');


        // Route::get('/librarian-dashboard/search', [LibrarianController::class, 'search'])->name('librarian.search');
        // Route::put('/librarian-dashboard/update/{id}', [LibrarianController::class, 'updateBook'])->name('librarian.update');
        // Route::put('/librarian-dashboard/add', [LibrarianController::class, 'store'])->name('librarian.add');
        // Route::post('/librarian-dashboard/destroy/{id}', [LibrarianController::class, 'destroy'])->name('librarian.destroy');
    });


    Route::middleware(['admin'])->group(function () {
        Route::get('/admin-dashboard', [AdminController::class, 'users'])->name('admin-dashboard');
        Route::put('/admin-users/{user}', [AdminController::class, 'updateUserRole'])->name('admin.updateUserRole');
        Route::delete('/admin-users/{user}', [AdminController::class, 'deleteUser'])->name('admin.deleteUser');
        Route::delete('/admin-stores/{storeId}', [AdminController::class, 'deleteStore'])->name('admin.deleteStore');
        Route::delete('/admin-menu/{menuItemId}', [AdminController::class, 'deleteMenuItem'])->name('admin.deleteMenu');

        Route::post('/admin-dashboard/add', [AdminController::class, 'addStore'])->name('admin.addStore');
        Route::post('/admin-dashboard/addmenu', [AdminController::class, 'addMenuItem'])->name('admin.addMenu');
        route::post('/admin-dashboard', [AdminController::class, 'refresh'])->name('admin.refresh');
    });
});

















Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
