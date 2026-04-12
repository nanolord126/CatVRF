<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\Providers;

use App\Domains\RealEstate\Application\B2B\UseCases\ConfirmViewingUseCase;
use App\Domains\RealEstate\Application\B2B\UseCases\CreateContractUseCase;
use App\Domains\RealEstate\Application\B2B\UseCases\CreatePropertyUseCase;
use App\Domains\RealEstate\Application\B2B\UseCases\PublishPropertyUseCase;
use App\Domains\RealEstate\Application\B2B\UseCases\SignContractUseCase;
use App\Domains\RealEstate\Application\B2C\UseCases\GetPropertyDetailsUseCase;
use App\Domains\RealEstate\Application\B2C\UseCases\RequestViewingUseCase;
use App\Domains\RealEstate\Application\B2C\UseCases\SearchPropertiesUseCase;
use App\Domains\RealEstate\Domain\Repository\AgentRepositoryInterface;
use App\Domains\RealEstate\Domain\Repository\ContractRepositoryInterface;
use App\Domains\RealEstate\Domain\Repository\PropertyRepositoryInterface;
use App\Domains\RealEstate\Domain\Repository\ViewingRepositoryInterface;
use App\Domains\RealEstate\Domain\Services\MortgageCalculatorServiceInterface;
use App\Domains\RealEstate\Domain\Services\PropertySearchServiceInterface;
use App\Domains\RealEstate\Infrastructure\Eloquent\Repositories\EloquentAgentRepository;
use App\Domains\RealEstate\Infrastructure\Eloquent\Repositories\EloquentContractRepository;
use App\Domains\RealEstate\Infrastructure\Eloquent\Repositories\EloquentPropertyRepository;
use App\Domains\RealEstate\Infrastructure\Eloquent\Repositories\EloquentViewingRepository;
use App\Domains\RealEstate\Infrastructure\Services\DatabasePropertySearchService;
use App\Domains\RealEstate\Infrastructure\Services\FakeMortgageCalculatorService;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

final class RealEstateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Repository bindings ────────────────────────────────────────────────
        $this->app->bind(
            PropertyRepositoryInterface::class,
            EloquentPropertyRepository::class,
        );

        $this->app->bind(
            ViewingRepositoryInterface::class,
            EloquentViewingRepository::class,
        );

        $this->app->bind(
            AgentRepositoryInterface::class,
            EloquentAgentRepository::class,
        );

        $this->app->bind(
            ContractRepositoryInterface::class,
            EloquentContractRepository::class,
        );

        $this->app->bind(
            MortgageCalculatorServiceInterface::class,
            FakeMortgageCalculatorService::class,
        );

        $this->app->bind(
            PropertySearchServiceInterface::class,
            DatabasePropertySearchService::class,
        );

        // ── LoggerInterface → audit channel для всех UseCases ─────────────────
        $useCases = [
            CreatePropertyUseCase::class,
            PublishPropertyUseCase::class,
            ConfirmViewingUseCase::class,
            CreateContractUseCase::class,
            SignContractUseCase::class,
            SearchPropertiesUseCase::class,
            GetPropertyDetailsUseCase::class,
            RequestViewingUseCase::class,
        ];

        foreach ($useCases as $useCase) {
            $this->app->when($useCase)
                ->needs(LoggerInterface::class)
                ->give(static fn ($app) => $app->make('log')->channel('audit'));
        }

        // ── LoggerInterface → audit channel для Repository (инфраструктура) ───
        $repositories = [
            EloquentPropertyRepository::class,
            EloquentViewingRepository::class,
            EloquentContractRepository::class,
            EloquentAgentRepository::class,
        ];

        foreach ($repositories as $repo) {
            $this->app->when($repo)
                ->needs(LoggerInterface::class)
                ->give(static fn ($app) => $app->make('log')->channel('audit'));
        }
    }

    public function boot(): void
    {
        // no heavy boot actions
    }
}
