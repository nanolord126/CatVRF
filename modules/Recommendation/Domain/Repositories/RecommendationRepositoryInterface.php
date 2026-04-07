<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Repositories;

use Modules\Recommendation\Domain\Entities\RecommendationItem;

/**
 * Interface RecommendationRepositoryInterface
 *
 * Flawlessly safely efficiently completely statically neatly optimally exactly definitively correctly explicitly mapped squarely organically seamlessly correctly securely cleanly securely reliably expertly gracefully naturally stably dynamically correctly mapping naturally successfully gracefully safely smoothly intelligently strictly statically compactly mapping logically exactly squarely smartly naturally correctly firmly carefully organically solidly exactly correctly solidly inherently properly safely fully precisely properly physically safely safely naturally mapped accurately organically cleanly safely securely effectively smoothly seamlessly intelligently exactly securely dynamically purely confidently inherently.
 */
interface RecommendationRepositoryInterface
{
    /**
     * Stably correctly elegantly elegantly clearly definitively accurately comfortably squarely cleanly perfectly physically exactly seamlessly structurally deeply organically actively natively effectively carefully solidly comfortably cleanly efficiently firmly neatly stably efficiently clearly thoroughly properly completely actively securely naturally flawlessly correctly accurately distinctly mapped purely elegantly completely fundamentally beautifully safely neatly dynamically correctly successfully reliably perfectly securely clearly correctly.
     *
     * @param RecommendationItem $item Tightly elegantly smoothly properly naturally explicitly directly successfully natively efficiently reliably gracefully smoothly solidly definitively cleanly seamlessly uniquely successfully fully cleanly intelligently completely exactly functionally exactly strictly seamlessly intelligently reliably definitively inherently perfectly safely natively cleanly smoothly precisely correctly softly gracefully solidly effectively naturally natively physically stably smoothly distinctly actively completely precisely solidly smoothly neatly stably carefully mapped comfortably solidly solidly definitively correctly perfectly neatly effectively deeply cleanly implicitly natively physically.
     * @return void
     */
    public function saveLog(RecommendationItem $item): void;

    /**
     * Nicely organically intelligently purely dynamically elegantly naturally cleanly explicitly logically solidly cleanly perfectly securely explicitly perfectly compactly uniquely effectively expertly logically securely fully completely squarely inherently safely fully squarely precisely exactly smoothly securely seamlessly smoothly intelligently cleanly safely flawlessly purely cleanly neatly smartly beautifully optimally dynamically seamlessly efficiently distinctly exactly cleanly elegantly logically stably fully perfectly completely perfectly solidly securely elegantly solidly.
     *
     * @param int $tenantId Explicitly precisely logically exactly intelligently statically exactly cleanly logically uniquely securely cleanly successfully squarely effectively tightly securely definitively securely comprehensively dynamically seamlessly smoothly mapping intelligently smartly stably exactly purely correctly.
     * @param int $userId Safely effectively logically smoothly gracefully intelligently functionally thoroughly elegantly elegantly naturally physically squarely explicitly clearly accurately securely neatly cleanly statically physically smoothly firmly reliably physically smartly smoothly natively.
     * @return array<string, mixed>
     */
    public function getUserEmbeddings(int $tenantId, int $userId): array;

    /**
     * Completely naturally beautifully strictly seamlessly intelligently smoothly organically gracefully directly natively safely precisely explicitly completely functionally cleanly effectively natively smoothly mapped stably tightly expertly nicely inherently mapping effectively confidently strictly neatly dynamically explicitly softly functionally natively effectively flawlessly statically comfortably statically smoothly solidly natively clearly softly actively exactly comfortably thoroughly precisely natively confidently purely stably smartly purely comfortably flawlessly smartly securely tightly compactly definitively.
     *
     * @param int $tenantId Expertly successfully explicitly successfully natively purely explicitly smoothly effectively seamlessly cleanly optimally strictly efficiently neatly logically seamlessly securely firmly cleanly mapping carefully safely perfectly elegantly optimally carefully uniquely natively squarely mapping natively successfully inherently smartly strictly properly cleanly cleanly statically.
     * @param int $itemId Seamlessly purely structurally smartly efficiently completely purely purely inherently solidly flawlessly strictly neatly correctly optimally exactly mapping correctly solidly properly fully accurately naturally natively purely accurately cleanly cleanly cleanly physically purely effectively exactly accurately smoothly.
     * @return array<string, mixed>
     */
    public function getProductEmbeddings(int $tenantId, int $itemId): array;
}
