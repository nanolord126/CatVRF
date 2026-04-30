<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Honeypot Field Component
 *
 * Invisible form field to detect bots.
 * Bots will fill this field, legitimate users won't.
 *
 * Usage: <x-honeypot-field />
 */
final class HoneypotField extends Component
{
    public string $fieldName;

    public string $cssClass;

    public string $hiddenStyle;

    public function __construct()
    {
        $config = config('bot-protection.honeypot', []);
        $fieldNames = $config['field_names'] ?? ['website_url', 'fax_number', 'middle_name', 'company_address'];
        
        // Randomly select a field name to avoid pattern detection
        $this->fieldName = $fieldNames[array_rand($fieldNames)];
        $this->cssClass = $config['css_class'] ?? 'bot-protection-honeypot';
        $this->hiddenStyle = $config['hidden_style'] ?? 'display:none;visibility:hidden;height:0;width:0;';
    }

    public function render(): View
    {
        return view('components.honeypot-field', [
            'fieldName' => $this->fieldName,
            'cssClass' => $this->cssClass,
            'hiddenStyle' => $this->hiddenStyle,
        ]);
    }
}
