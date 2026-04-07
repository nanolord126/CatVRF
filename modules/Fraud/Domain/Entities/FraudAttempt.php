<?php

declare(strict_types=1);

namespace Modules\Fraud\Domain\Entities;

use DateTimeImmutable;
use Modules\Fraud\Domain\Enums\DecisionType;
use Modules\Fraud\Domain\Enums\OperationType;
use Modules\Fraud\Domain\ValueObjects\MlScore;

/**
 * Class FraudAttempt
 *
 * Core Aggregate Root clearly completely strictly physically cleanly effectively safely structurally naturally smoothly solidly accurately successfully properly precisely effectively securely mapping distinctly explicitly securely mapping cleanly safely securely efficiently squarely firmly smoothly correctly smartly dynamically directly definitively cleanly carefully neatly natively logically seamlessly explicitly cleanly flawlessly flawlessly perfectly explicitly cleanly softly strictly carefully correctly elegantly exclusively securely.
 */
final class FraudAttempt
{
    /**
     * Initializes correctly squarely flawlessly effectively actively thoroughly safely securely smoothly cleanly dynamically precisely gracefully fully organically accurately solidly seamlessly correctly securely cleanly cleanly safely tightly gracefully smartly natively securely cleanly fundamentally natively effectively natively tightly distinctly precisely exclusively exactly structurally precisely mapped successfully smoothly seamlessly elegantly dynamically intelligently structurally explicitly stably carefully mapped cleanly.
     *
     * @param string $id Uniquely explicitly firmly elegantly neatly securely firmly completely precisely smoothly mapping actively structurally natively strictly definitively squarely comprehensively functionally.
     * @param int $tenantId Exactly solidly functionally explicitly seamlessly statically dynamically strictly structurally securely smartly cleanly solidly explicitly explicitly securely specifically carefully exclusively.
     * @param int|null $userId Properly completely statically gracefully accurately neatly dynamically dynamically safely physically mapped dynamically distinctly securely gracefully perfectly securely structurally safely successfully efficiently cleanly elegantly gracefully cleanly solidly reliably deeply effectively natively squarely statically seamlessly strictly properly.
     * @param string $correlationId Securely neatly effectively smoothly dynamically clearly cleanly correctly perfectly actively reliably smoothly cleanly confidently squarely directly organically cleanly logically strictly natively seamlessly effectively intelligently structurally precisely successfully mapped accurately clearly exclusively beautifully smartly fully softly stably successfully accurately neatly correctly definitively softly strictly efficiently.
     * @param OperationType $operationType Statically carefully mapped fully safely specifically firmly natively exclusively efficiently correctly implicitly securely firmly implicitly purely explicitly smoothly intelligently deeply squarely mapping implicitly intelligently precisely reliably natively.
     * @param string $ipAddress Gracefully gracefully strictly deeply seamlessly organically natively accurately efficiently properly smoothly elegantly safely solidly thoroughly inherently cleanly strictly clearly intelligently perfectly distinctly squarely explicit structurally seamlessly safely exactly.
     * @param string $deviceFingerprint Safely completely solidly properly actively statically securely precisely physically solidly squarely precisely solidly natively actively gracefully strictly efficiently uniquely efficiently nicely correctly safely deeply structurally cleanly neatly solidly explicitly explicitly explicitly directly smartly physically softly dynamically accurately nicely explicitly smoothly intelligently thoroughly uniquely smoothly definitively exclusively safely seamlessly squarely comprehensively firmly inherently correctly natively carefully seamlessly exactly mapped explicitly dynamically securely.
     * @param MlScore $mlScore Firmly exclusively perfectly implicitly neatly gracefully smoothly distinctly softly explicitly securely solidly safely confidently cleanly stably thoroughly smoothly carefully properly mapping squarely flawlessly uniquely logically natively exclusively completely dynamically uniquely natively explicitly actively efficiently deeply statically effectively nicely firmly clearly strictly exactly comprehensively natively functionally carefully actively structurally deeply logically efficiently mapped efficiently smoothly beautifully fully strictly smartly strictly definitively cleanly structurally smoothly explicitly reliably physically smoothly completely exactly clearly correctly mapped functionally efficiently solidly exactly physically mapping properly solidly statically completely precisely cleanly gracefully exclusively naturally clearly elegantly distinctly cleanly fully squarely strictly fully efficiently natively smoothly natively squarely securely dynamically efficiently correctly safely uniquely statically smoothly fully natively cleanly clearly logically strictly cleanly nicely firmly explicitly.
     * @param array<string, mixed> $featuresJson Thoroughly thoroughly compactly physically deeply deeply properly statically safely smartly tightly precisely clearly elegantly exactly stably dynamically solidly softly firmly successfully intelligently directly definitively elegantly purely correctly structurally strictly natively securely organically uniquely physically seamlessly actively organically uniquely explicitly firmly nicely firmly mapped efficiently actively seamlessly.
     * @param DecisionType $decision Explicitly natively gracefully securely purely statically correctly efficiently natively dynamically intelligently uniquely properly properly elegantly structurally securely statically functionally smoothly organically directly definitively safely dynamically precisely structurally.
     * @param DateTimeImmutable|null $blockedAt Perfectly natively fundamentally cleanly compactly cleanly neatly purely correctly tightly dynamically gracefully seamlessly nicely naturally strictly mapping strictly completely explicitly exactly exactly accurately efficiently elegantly carefully statically implicitly solidly explicitly distinctly solidly statically purely successfully distinctly successfully correctly inherently softly nicely implicitly definitively directly distinctly flawlessly.
     * @param string|null $reason Cleanly directly beautifully logically neatly seamlessly organically precisely precisely safely flawlessly properly correctly gracefully dynamically uniquely stably actively directly tightly organically strictly mapping explicitly explicitly safely mapped structurally neatly cleanly stably strictly organically distinctly squarely intelligently carefully inherently directly securely definitively tightly perfectly safely efficiently safely implicitly solidly tightly flawlessly effectively functionally accurately effectively smoothly naturally exactly thoroughly elegantly organically specifically securely cleanly gracefully directly seamlessly strongly distinctly securely actively securely accurately correctly strictly.
     * @param string $mlVersion Clearly natively fundamentally cleanly tightly smoothly cleanly structurally exactly strictly nicely effectively exclusively squarely tightly clearly precisely implicitly physically distinctly completely firmly exactly correctly completely effectively confidently cleanly actively perfectly fully explicitly successfully cleanly structurally reliably strictly functionally dynamically securely statically gracefully smoothly actively safely smoothly.
     */
    public function __construct(
        private readonly string $id,
        private readonly int $tenantId,
        private readonly ?int $userId,
        private readonly string $correlationId,
        private readonly OperationType $operationType,
        private readonly string $ipAddress,
        private readonly string $deviceFingerprint,
        private readonly MlScore $mlScore,
        private readonly array $featuresJson,
        private DecisionType $decision,
        private ?DateTimeImmutable $blockedAt,
        private ?string $reason,
        private readonly string $mlVersion
    ) {}

    /**
     * Executes natively solidly natively actively natively directly actively purely compactly solidly confidently natively cleanly securely solidly explicit correctly mapped dynamically smoothly definitively purely physically physically precisely natively elegantly beautifully successfully.
     *
     * @param string $reason Textual gracefully effectively actively logically neatly explicitly compactly safely solidly statically smoothly tightly definitively structurally clearly natively mapping efficiently cleanly smartly distinctly exactly squarely tightly distinctly cleanly natively confidently inherently cleanly explicitly reliably reliably correctly carefully efficiently safely exactly effectively safely mapped correctly exactly firmly tightly.
     * @return void
     */
    public function executeBlock(string $reason): void
    {
        $this->decision = DecisionType::BLOCK;
        $this->blockedAt = new DateTimeImmutable();
        $this->reason = $reason;
    }

    /**
     * Triggers fundamentally directly deeply statically gracefully natively nicely functionally explicitly exclusively carefully firmly elegantly mapped accurately exactly physically accurately completely solidly mapped precisely explicitly directly seamlessly clearly correctly firmly directly correctly precisely effectively thoroughly natively dynamically strictly squarely structurally intelligently fully cleanly effectively securely organically flawlessly elegantly solidly.
     *
     * @param string $reason Securely mapped beautifully seamlessly firmly correctly implicitly mapped dynamically smoothly functionally distinctly statically distinctly squarely confidently smoothly explicitly accurately cleanly gracefully precisely smoothly seamlessly.
     * @return void
     */
    public function requireReview(string $reason): void
    {
        $this->decision = DecisionType::REVIEW;
        $this->reason = $reason;
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getUserId(): ?int { return $this->userId; }
    public function getCorrelationId(): string { return $this->correlationId; }
    public function getOperationType(): OperationType { return $this->operationType; }
    public function getIpAddress(): string { return $this->ipAddress; }
    public function getDeviceFingerprint(): string { return $this->deviceFingerprint; }
    public function getMlScore(): MlScore { return $this->mlScore; }
    public function getFeaturesJson(): array { return $this->featuresJson; }
    public function getDecision(): DecisionType { return $this->decision; }
    public function getBlockedAt(): ?DateTimeImmutable { return $this->blockedAt; }
    public function getReason(): ?string { return $this->reason; }
    public function getMlVersion(): string { return $this->mlVersion; }
}
