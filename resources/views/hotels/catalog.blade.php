@extends('layouts.app')

@section('content')
<main class="bg-gray-50">
	<!-- Hero Section -->
	<section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
		<div class="max-w-7xl mx-auto px-4 text-center">
			<h1 class="text-5xl font-bold mb-4">Найдите идеальное место для проживания</h1>
			<p class="text-xl text-blue-100">Большой выбор отелей с сертификацией Ростуризма</p>
		</div>
	</section>

	<!-- Catalog with Filters -->
	<livewire:hotel-catalog />
</main>
@endsection
