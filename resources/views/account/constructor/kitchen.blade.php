@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-3xl font-black text-white italic tracking-tighter uppercase">
            {{ $template->name }} <span class="text-indigo-500 font-bold ml-2">PRO</span>
        </h1>
        <a href="{{ route('account.configurator.dashboard') }}" class="text-slate-400 hover:text-white transition-colors flex items-center gap-2 italic">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Назад к инструментам
        </a>
    </div>

    <!-- Тот самый ТОП-конструктор -->
    <x-configurators.kitchen-configurator :template="$template" />

</div>
@endsection

@push('scripts')
    @vite(['resources/js/configurators/kitchen.js'])
@endpush
