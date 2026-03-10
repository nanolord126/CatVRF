<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Services\AI\Assistant\EcosystemVoiceAssistant;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Attributes\On;

/**
 * AI Voice/Chat Command Assistant for unified ecosystem management.
 */
class AIVoiceAssistantOverlay extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-microphone';
    protected static string $view = 'filament.tenant.pages.ai-voice-assistant-overlay';
    protected static ?string $title = 'Ecosystem Voice AI 2026';
    protected static ?string $navigationGroup = 'AI & Operations';

    public string $commandInput = '';
    public array $chatLog = [];
    public bool $isListening = false;

    public function mount(): void
    {
        $this->chatLog[] = [
            'role' => 'assistant',
            'content' => "Hello, I am the CatVRF 2026 Ecosystem Assistant. You can type or say commands like 'Go to AI Pricing' or 'Show taxi revenue'."
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('commandInput')
                ->label('Voice or Chat Command')
                ->placeholder('e.g., "Run simulation for taxi"...')
                ->autofocus()
                ->suffixAction(
                    \Filament\Forms\Components\Actions\Action::make('send')
                        ->icon('heroicon-o-paper-airplane')
                        ->action('executeCommand')
                )
                ->extraAttributes([
                    'onkeydown' => "if (event.key === 'Enter') { @this.executeCommand(); event.preventDefault(); }",
                ]),
        ];
    }

    /**
     * Executes the Natural Language command via the AI Assistant Service.
     */
    public function executeCommand(EcosystemVoiceAssistant $assistant): void
    {
        if (empty($this->commandInput)) return;

        // Add user message to log
        $this->chatLog[] = [
            'role' => 'user',
            'content' => $this->commandInput
        ];

        // Process via NLP service
        $result = $assistant->processCommand($this->commandInput);

        // Add assistant response to log
        $this->chatLog[] = [
            'role' => 'assistant',
            'content' => $result['message']
        ];

        // Redirect if action required
        if ($result['action'] === 'redirect') {
            $this->redirect($result['url']);
        }

        $this->commandInput = '';
    }

    /**
     * Simulation of Voice Activation (Speech-to-Text).
     * In a real 2026 app, this would use Web Speech API or Deepgram.
     */
    public function toggleListening(): void
    {
        $this->isListening = !$this->isListening;
        
        if (!$this->isListening) {
             // Mocked voice trigger for demo
             $this->commandInput = "Show revenue for taxi";
             $this->executeCommand(new EcosystemVoiceAssistant());
        }
    }
}
