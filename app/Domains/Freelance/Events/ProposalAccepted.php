declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Freelance\Events;

use App\Domains\Freelance\Models\FreelanceProposal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * ProposalAccepted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ProposalAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FreelanceProposal $proposal,
        public readonly string $correlationId,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
}
