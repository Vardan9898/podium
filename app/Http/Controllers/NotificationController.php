<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Notifications\ListNotifications;
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
    public function index(#[CurrentUser] User $user, ListNotifications $listNotifications): AnonymousResourceCollection
    {
        return NotificationResource::collection($listNotifications->handle($user))
            ->additional(['unread_count' => $user->unreadNotificationCount()]);
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
        $markRead->handle($user, $request->toData());

        return response()->noContent();
    }
}
