<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Services;

use Modules\Bonuses\Application\DTOs\AwardBonusData;
use Illuminate\Support\Facades\Config;

final class BonusRuleEngine
{
    public function canAward(AwardBonusData $data): bool
    {
        $rules = Config::get('bonuses.rules');

        if (!isset($rules[$data->type])) {
            return false; // Правило для такого типа бонуса не найдено
        }

        $rule = $rules[$data->type];

        // Пример простого правила: проверяем, что сумма бонуса не превышает лимит
        if (isset($rule['max_amount']) && $data->amount > $rule['max_amount']) {
            return false;
        }

        // Пример правила для реферальной программы
        if ($data->type === 'referral') {
            // Здесь должна быть логика проверки, что реферал выполнил условия
            // Например, проверка оборота реферала
            // $referral = Referral::find($data->sourceId);
            // if ($referral->turnover < config('bonuses.referral_turnover_threshold')) {
            //     return false;
            // }
        }

        // Все проверки пройдены
        return true;
    }
}
