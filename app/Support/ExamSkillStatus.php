<?php

namespace App\Support;

class ExamSkillStatus
{
    public static function withDeviceIp(array $status): array
    {
        if (! empty($status['device_ip'])) {
            return $status;
        }

        try {
            $ip = request()->ip();
            if (is_string($ip) && $ip !== '') {
                $status['device_ip'] = $ip;
            }
        } catch (\Throwable) {
            // Never block exam flow
        }

        return $status;
    }
}
