<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\CurrentUserController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\ClientConfigController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Proposals\ProposalAttachmentController;
use App\Http\Controllers\Proposals\ProposalController;
use App\Http\Controllers\Proposals\ProposalReviewController;
use App\Http\Controllers\Proposals\ProposalStatusController;
use App\Http\Controllers\TagController;
use App\Models\Proposal;
use Illuminate\Support\Facades\Route;

Route::get('config', ClientConfigController::class)->name('config');

Route::middleware('guest')->group(function (): void {
    Route::post('register', RegisterController::class)->middleware('throttle:register')->name('register');
    Route::post('login', [SessionController::class, 'store'])->middleware('throttle:login')->name('login');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', [SessionController::class, 'destroy'])->name('logout');
    Route::get('me', CurrentUserController::class)->name('me');

    Route::get('proposals', [ProposalController::class, 'index'])
        ->can('viewAny', Proposal::class)
        ->name('proposals.index');
    Route::post('proposals', [ProposalController::class, 'store'])
        ->can('create', Proposal::class)
        ->name('proposals.store');
    Route::get('proposals/{proposal}', [ProposalController::class, 'show'])
        ->can('view', 'proposal')
        ->name('proposals.show');
    Route::get('proposals/{proposal}/attachment', ProposalAttachmentController::class)
        ->can('downloadAttachment', 'proposal')
        ->name('proposals.attachment');
    Route::patch('proposals/{proposal}/status', ProposalStatusController::class)
        ->can('changeStatus', 'proposal')
        ->name('proposals.status');
    Route::put('proposals/{proposal}/review', ProposalReviewController::class)
        ->can('review', 'proposal')
        ->name('proposals.review');

    Route::get('tags', TagController::class)->name('tags.index');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});
