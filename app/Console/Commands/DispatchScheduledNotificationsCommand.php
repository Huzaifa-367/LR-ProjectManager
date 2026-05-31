<?php

namespace App\Console\Commands;

use App\Enums\ScheduledNotificationStatus;
use App\Models\ScheduledNotification;
use App\Support\NotificationDispatcher;
use Illuminate\Console\Command;
use Throwable;

class DispatchScheduledNotificationsCommand extends Command
{
    protected $signature = 'notifications:dispatch-scheduled';

    protected $description = 'Dispatch due scheduled notifications';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $due = ScheduledNotification::query()
            ->where('status', ScheduledNotificationStatus::Pending)
            ->where('trigger_at', '<=', now())
            ->orderBy('trigger_at')
            ->limit(100)
            ->get();

        foreach ($due as $scheduled) {
            $scheduled->update(['status' => ScheduledNotificationStatus::Processing]);

            try {
                $dispatcher->dispatchScheduled($scheduled);
                $scheduled->update(['status' => ScheduledNotificationStatus::Sent]);
            } catch (Throwable $exception) {
                $scheduled->update(['status' => ScheduledNotificationStatus::Failed]);
                report($exception);
            }
        }

        return self::SUCCESS;
    }
}
