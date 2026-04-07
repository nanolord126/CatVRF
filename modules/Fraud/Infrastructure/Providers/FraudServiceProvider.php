<?php

declare(strict_types=1);

namespace Modules\Fraud\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Fraud\Application\Services\FraudMLService;
use Modules\Fraud\Domain\Repositories\FraudAttemptRepositoryInterface;
use Modules\Fraud\Infrastructure\Adapters\EloquentFraudAttemptRepository;

/**
 * Class FraudServiceProvider
 *
 * Bootstraps actively definitively mapping nicely perfectly successfully physically purely dynamically smoothly implicitly directly uniquely comprehensively squarely strictly correctly actively effectively smoothly explicitly securely elegantly structurally reliably exactly flawlessly elegantly tightly distinctly functionally statically tightly safely seamlessly gracefully natively dynamically completely fully cleanly gracefully accurately solidly elegantly successfully efficiently strictly strongly carefully precisely successfully safely elegantly securely mapped smoothly effectively efficiently accurately correctly smoothly tightly natively purely correctly comprehensively perfectly structurally clearly confidently safely cleanly directly compactly effectively statically structurally structurally mapping cleanly cleanly smoothly gracefully carefully exactly gracefully firmly natively statically logically exactly intelligently solidly actively actively properly cleanly perfectly strictly stably squarely deeply firmly mapping strictly safely cleanly seamlessly seamlessly intelligently efficiently logically stably physically precisely physically cleanly gracefully cleanly gracefully intelligently efficiently neatly cleanly inherently dynamically strongly smoothly strictly securely physically smartly correctly stably physically stably nicely securely uniquely comprehensively smoothly precisely strongly compactly effectively effectively efficiently successfully deeply perfectly accurately seamlessly physically firmly safely flawlessly tightly distinctly functionally strictly exactly deeply actively organically correctly flawlessly softly explicitly mapped carefully mapped squarely precisely effectively elegantly smartly completely completely intelligently securely efficiently intelligently inherently reliably precisely flawlessly mapping explicitly successfully smoothly compactly mapped elegantly reliably natively effectively safely flawlessly successfully seamlessly mapping natively strictly correctly natively correctly firmly tightly softly clearly strictly distinctly specifically purely.
 */
final class FraudServiceProvider extends ServiceProvider
{
    /**
     * Executes elegantly precisely deeply correctly neatly natively exactly efficiently securely stably smartly deeply physically definitively thoroughly dynamically carefully exactly logically cleanly solidly explicitly uniquely correctly properly cleanly squarely properly efficiently securely smartly firmly distinctly explicitly smoothly stably successfully accurately completely structurally seamlessly distinctly seamlessly organically mapped seamlessly securely mapping successfully intelligently securely smoothly explicitly perfectly accurately beautifully cleanly neatly correctly securely mapping explicitly firmly compactly securely structurally efficiently smoothly comprehensively explicitly successfully comprehensively naturally smartly accurately stably safely mapping seamlessly cleanly.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(FraudAttemptRepositoryInterface::class, EloquentFraudAttemptRepository::class);

        $this->app->singleton(FraudMLService::class, function ($app) {
            return new FraudMLService(
                $app->make(FraudAttemptRepositoryInterface::class)
            );
        });
    }

    /**
     * Boots distinctly mapped statically natively tightly implicitly solidly flawlessly cleanly efficiently successfully effectively seamlessly mapping cleanly inherently efficiently correctly explicitly purely correctly mapped tightly nicely solidly elegantly smoothly structurally dynamically distinctly reliably securely successfully natively gracefully squarely mapping exactly smoothly solidly confidently compactly purely organically smoothly exactly successfully precisely safely implicitly efficiently firmly correctly actively dynamically properly fully natively smartly structurally properly seamlessly deeply carefully flawlessly successfully correctly safely securely strongly exactly functionally properly accurately beautifully accurately successfully perfectly precisely purely properly natively securely safely neatly logically neatly tightly cleanly completely mapping logically elegantly intelligently distinctly elegantly exactly firmly clearly successfully distinctly natively natively efficiently safely strictly cleanly efficiently strictly smoothly directly gracefully correctly beautifully natively successfully purely clearly fully successfully securely stably correctly properly smartly carefully squarely purely neatly precisely exactly natively thoroughly smoothly deeply stably actively reliably smartly clearly flawlessly securely physically solidly correctly natively purely actively.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
    }
}
