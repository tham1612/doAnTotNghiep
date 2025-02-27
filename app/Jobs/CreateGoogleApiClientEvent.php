<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateGoogleApiClientEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventData;
    protected $userOrTaskId;
    protected $attendees;
    protected $accessToken;

    /**
     * Create a new job instance.
     */
    public function __construct($eventData, $attendees, $accessToken, $userOrTaskId)
    {
        $this->eventData = $eventData;
        $this->attendees = $attendees;
        $this->accessToken = $accessToken;
        $this->userOrTaskId = $userOrTaskId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $client = $this->getClient();
        //        $accessToken = User::query()->where('id', auth()->id())->value('remember_token'); // lay ra token trong db
        $accessToken = $this->accessToken;
        if ($accessToken) {

            $client->setAccessToken($accessToken);
            // Làm mới token nếu hết hạn
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                // Cập nhật token mới vào database
                User::query()
                    ->where('id', $this->userOrTaskId['user_id'])
                    ->update([
                        'access_token' => $client->getAccessToken()
                    ]);
            }

            $service = new \Google_Service_Calendar($client);

            // Tạo sự kiện
            $event = new \Google_Service_Calendar_Event($this->eventData);

            // Thêm người tham gia (attendees)
            if (!empty($this->attendees)) {
                $event->setAttendees($this->attendees);
            }

            // Khởi tạo đối tượng Google_Service_Calendar_EventReminders
            $reminders = new \Google_Service_Calendar_EventReminders();
            $reminders->setUseDefault($this->eventData['reminders']['useDefault']);

            // Tạo danh sách các reminders
            $reminderOverrides = $this->eventData['reminders']['overrides'];

            // Gán reminders cho sự kiện
            $reminders->setOverrides($reminderOverrides);
            $event->setReminders($reminders);

            $calendarId = 'primary'; // Hoặc sử dụng calendarId khác nếu cần
            $eventId = $service->events->insert($calendarId, $event);
            Task::query()
                ->where('id', $this->userOrTaskId['task_id'])
                ->update([
                    'id_google_calendar' => $eventId->getId()
                ]);
        }
    }

    public function getClient()
    {
        $client = new \Google_Client();
        $client->setAuthConfig(storage_path('app/google-calendar/credentials.json'));
        $client->addScope(\Google_Service_Calendar::CALENDAR);
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->setAccessType('offline'); // Lấy refresh token để dùng lâu dài
        $client->setPrompt('consent');

        return $client;
    }
}
