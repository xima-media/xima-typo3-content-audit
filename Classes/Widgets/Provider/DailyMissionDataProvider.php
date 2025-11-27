<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class DailyMissionDataProvider implements ListDataProviderInterface
{
    /**
    * Returns daily motivational message key based on day of week
    */
    public function getItems(): array
    {
        $dayOfWeek = (int)date('N'); // 1=Monday, 7=Sunday

        $messageKeys = [
            1 => 'widgets.daily_mission.message_monday',
            2 => 'widgets.daily_mission.message_tuesday',
            3 => 'widgets.daily_mission.message_wednesday',
            4 => 'widgets.daily_mission.message_thursday',
            5 => 'widgets.daily_mission.message_friday',
            6 => 'widgets.daily_mission.message_saturday',
            7 => 'widgets.daily_mission.message_sunday',
        ];

        return [
            'messageKey' => $messageKeys[$dayOfWeek],
            'dayOfWeek' => $dayOfWeek,
        ];
    }
}
