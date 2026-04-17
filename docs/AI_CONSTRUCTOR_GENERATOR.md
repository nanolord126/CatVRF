# AI Constructor Generator - Documentation

## Overview

The AI Constructor Generator is a production-ready code generation tool for CatVRF that automatically creates AI Constructor components for business verticals. It follows Clean Architecture principles and integrates with the existing ML infrastructure.

**Currently supports 64 verticals:** Advertising, Analytics, Art, Auto, Beauty, BooksAndLiterature, CRM, CarRental, CleaningServices, Collectibles, Communication, Confectionery, ConstructionAndRepair, Consulting, Content, Delivery, Education, Electronics, EventPlanning, FarmDirect, Fashion, Finances, Fitness, Flowers, Food, Freelance, Furniture, Gardening, Geo, GeoLogistics, GroceryAndDelivery, HobbyAndCraft, HomeServices, Hotels, HouseholdGoods, Insurance, Inventory, Legal, Logistics, Luxury, Marketplace, MeatShops, Medical, MusicAndInstruments, OfficeCatering, PartySupplies, Payment, PersonalDevelopment, Pet, Pharmacy, Photography, RealEstate, ShortTermRentals, Sports, SportsNutrition, Staff, Taxi, Tickets, ToysAndGames, Travel, VeganProducts, Veterinary, Wallet, WeddingPlanning.

## Architecture

### Components

1. **PromptBuilderInterface** - Interface for all prompt builders
2. **AbstractPromptBuilder** - Base class with common functionality
3. **Vertical-specific PromptBuilders** - Concrete implementations (e.g., TaxiRoutePromptBuilder)
4. **GenerateAIConstructorsCommand** - CLI command for generation
5. **DTOs** - Request/Response DTOs for AI services

### Benefits

- **Separation of Concerns**: Prompts are separated from service logic
- **Easy Updates**: Change prompts without regenerating services
- **Version Control**: Track prompt versions and changes
- **A/B Testing**: Test different prompt versions
- **Safety**: Dry-run mode to preview changes
- **Validation**: Automatic PHPStan and Pint validation

## Usage

### Generate AI Constructor for a Vertical

```bash
# Basic generation
php artisan ai:generate-constructors taxi

# Dry-run mode (preview changes without writing)
php artisan ai:generate-constructors taxi --dry-run

# Show diff between old and new files
php artisan ai:generate-constructors taxi --show-diff

# Force generation (skip validation errors)
php artisan ai:generate-constructors taxi --force

# Skip git backup
php artisan ai:generate-constructors taxi --skip-backup

# Skip validation
php artisan ai:generate-constructors taxi --skip-validation
```

### What Gets Generated

1. **PromptBuilder Class** - `app/Services/AI/Prompts/{Vertical}PromptBuilder.php`
   - System prompt generation
   - User prompt generation
   - Output schema definition
   - Version tracking

2. **Updated AI Constructor Service** - `app/Domains/{Vertical}/Services/AI/{Vertical}AIConstructorService.php`
   - Adds PromptBuilder dependency
   - Replaces hardcoded prompts with PromptBuilder calls
   - Adds structured output schema support

3. **DTOs** - `app/DTOs/AI/{Vertical}/`
   - `{Vertical}RequestDto.php`
   - `{Vertical}ResponseDto.php`

## PromptBuilder Interface

### Required Methods

```php
interface PromptBuilderInterface
{
    public function getSystemPrompt(array $context = []): string;
    public function getUserPrompt(array $context = []): string;
    public function getOutputSchema(): array;
    public function getVersion(): string;
    public function getMetadata(): array;
}
```

### Creating a Custom PromptBuilder

```php
<?php

declare(strict_types=1);

namespace App\Services\AI\Prompts;

final class MyVerticalPromptBuilder extends AbstractPromptBuilder
{
    protected string $version = '1.0.0';
    protected array $metadata = [
        'vertical' => 'my_vertical',
        'type' => 'ai_constructor',
        'description' => 'AI constructor for MyVertical',
        'language' => 'ru',
    ];

    public function getSystemPrompt(array $context = []): string
    {
        $prompt = <<<PROMPT
You are an expert in MyVertical.
Your task is to analyze data and provide recommendations.
PROMPT;

        return $this->sanitize($prompt);
    }

    public function getUserPrompt(array $context = []): string
    {
        $prompt = <<<PROMPT
Analyze the following data:
{{context_data}}

Provide structured recommendations.
PROMPT;

        return $this->interpolate($prompt, [
            'context_data' => json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'recommendations' => ['type' => 'array'],
                'confidence' => ['type' => 'number'],
            ],
            'required' => ['recommendations', 'confidence'],
        ];
    }
}
```

## Integration with Existing Services

### Before (Hardcoded Prompts)

```php
$response = $this->openai->chat([
    ['role' => 'system', 'content' => 'Hardcoded prompt here...'],
    ['role' => 'user', 'content' => $input],
], 0.3, 'text');
```

### After (With PromptBuilder)

```php
$systemPrompt = $this->promptBuilder->getSystemPrompt([
    'vertical' => 'taxi',
    'type' => 'route_optimization',
]);

$userPrompt = $this->promptBuilder->getUserPrompt($rideData);

$response = $this->openai->chat([
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userPrompt],
], 0.3, 'text', $this->promptBuilder->getOutputSchema());
```

## Safety Features

### Dry-Run Mode

Preview changes before applying them:

```bash
php artisan ai:generate-constructors taxi --dry-run --show-diff
```

This shows:
- Files that will be created/modified
- Diff between old and new content
- Summary of changes

### Git Backup

Automatic git backup before generation:

```bash
# Creates branch: ai-constructor-backup-2026-04-17-143022
php artisan ai:generate-constructors taxi
```

Skip backup if needed:

```bash
php artisan ai:generate-constructors taxi --skip-backup
```

### Validation Pipeline

Automatic validation after generation:

1. **PHPStan** - Static analysis
2. **Pint** - Code style checking

Skip validation if needed:

```bash
php artisan ai:generate-constructors taxi --skip-validation
```

Force generation despite validation errors:

```bash
php artisan ai:generate-constructors taxi --force
```

## Testing

### Running Tests

```bash
# Run PromptBuilder tests
php artisan test tests/Unit/Services/AI/Prompts/

# Run specific test
php artisan test tests/Unit/Services/AI/Prompts/PromptBuilderInterfaceTest.php
```

### Test Coverage

- Interface implementation
- Prompt generation
- Context interpolation
- Output schema validation
- Version tracking
- Metadata handling
- Sanitization

## Best Practices

1. **Version Control**: Always commit PromptBuilder changes with clear messages
2. **A/B Testing**: Use version field to test different prompts
3. **Monitoring**: Log prompt usage via built-in logging
4. **Sanitization**: Always sanitize prompts before sending to LLM
5. **Structured Output**: Use output schemas for consistent AI responses
6. **Context**: Pass relevant context to prompts for better results
7. **Testing**: Write tests for custom PromptBuilders

## Troubleshooting

### Common Issues

**Issue**: Git backup fails
```
Solution: Use --skip-backup flag or ensure git is properly configured
```

**Issue**: PHPStan validation fails
```
Solution: Fix type hints or use --force to skip validation
```

**Issue**: Pint validation fails
```
Solution: Run `vendor/bin/pint` to auto-fix or use --force to skip
```

**Issue**: PromptBuilder not found
```
Solution: Ensure the PromptBuilder class is in app/Services/AI/Prompts/
```

## Future Enhancements

- [ ] Integration with FeatureDriftDetector for prompt drift detection
- [ ] Chunked generation for large verticals (GenerateAIConstructorsJob)
- [ ] Prometheus metrics for generator performance
- [ ] Prompt versioning and rollback
- [ ] A/B testing framework for prompts
- [ ] Prompt encryption for sensitive verticals (Medical, Legal)

## Related Documentation

- [ML Model Retraining Infrastructure](../ML_MODEL_RETRAINING.md)
- [Feature Drift Detection](../FEATURE_DRIFT_DETECTION.md)
- [FraudML System](../FRAUDML_SYSTEM.md)

## Support

For issues or questions:
1. Check this documentation
2. Review test cases for examples
3. Check logs in `storage/logs/laravel.log`
4. Contact AI/ML team
2. Review test cases for examples
3. Check logs in `storage/logs/laravel.log`
4. Contact AI/ML team
- [ ] Chunked generation for large verticals (GenerateAIConstructorsJob)
- [ ] Prometheus metrics for generator performance
- [ ] Prompt versioning and rollback
- [ ] A/B testing framework for prompts
- [ ] Prompt encryption for sensitive verticals (Medical, Legal)

## Related Documentation

- [ML Model Retraining Infrastructure](../ML_MODEL_RETRAINING.md)
- [Feature Drift Detection](../FEATURE_DRIFT_DETECTION.md)
- [FraudML System](../FRAUDML_SYSTEM.md)

## Support

For issues or questions:
1. Check this documentation
2. Review test cases for examples
3. Check logs in `storage/logs/laravel.log`
4. Contact AI/ML team
