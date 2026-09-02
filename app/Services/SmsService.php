<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsService
{
    public function send(string $phone, string $message): void
    {
        $driver = (string) config('services.sms.driver', 'log');

        if ($driver === 'log') {
            Log::info('TechStore SMS', ['phone' => $phone, 'message' => $message]);
            return;
        }

        if ($driver !== 'twilio') {
            throw new RuntimeException('Cổng SMS chưa được hỗ trợ.');
        }

        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.from');

        if ($sid === '' || $token === '' || $from === '') {
            throw new RuntimeException('Chưa cấu hình Twilio SMS.');
        }

        Http::asForm()->withBasicAuth($sid, $token)->post(
            "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
            [
                'From' => $from,
                'To' => $phone,
                'Body' => $message,
            ],
        )->throw();
    }
}
