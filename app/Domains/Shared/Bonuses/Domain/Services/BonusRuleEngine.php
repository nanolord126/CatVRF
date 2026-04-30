<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Services;

use Modules\Bonuses\Application\DTOs\AwardBonusData;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class BonusRuleEngine
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}
    public function canAward(AwardBonusData $data): bool
    {
        $rules = $this->config->get('bonuses.rules');

        if (! isset($rules[$data->type])) {
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
            if ($referral->turnover < $this->config->get('bonuses.referral_turnover_threshold')) {
                return false;
            }
        }

        // Все проверки пройдены
        return true;
    }
}
