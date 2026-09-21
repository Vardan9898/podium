<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Notifications\MarkNotificationsRead;
use App\Http\Requests\Notifications\MarkNotificationsReadRequest;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class NotificationController extends Controller
{
    /**
     * List your notifications, newest first.
     */
    public function index(#[CurrentUser] User $user): AnonymousResourceCollection
    {
        return NotificationResource::collection($user->notifications()->paginate(20))
            ->additional(['unread_count' => $user->unreadNotifications()->count()]);
    }

    /**
     * Mark notifications as read.
     *
     * Omit `ids` to mark all of them.
     */
    public function markAsRead(
        MarkNotificationsReadRequest $request,
        #[CurrentUser] User $user,
        MarkNotificationsRead $markRead,
    ): Response {
        $markRead->handle($user, $request->ids());

        return response()->noContent();
    }
}
