@props([
    'fieldName' => 'website_url',
    'cssClass' => 'bot-protection-honeypot',
    'hiddenStyle' => 'display:none;visibility:hidden;height:0;width:0;',
])

<input
    type="text"
    name="{{ $fieldName }}"
    id="{{ $fieldName }}"
    class="{{ $cssClass }}"
    style="{{ $hiddenStyle }}"
    tabindex="-1"
    autocomplete="off"
    aria-hidden="true"
>
