<div class="space-y-3">
    @if($topProducts->isEmpty())
    <div class="text-center text-gray-500 py-8">
        Нет данных за выбранный период
    </div>
    @else
    @foreach($topProducts->take(5) as $index => $product)
    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-700 rounded-full font-bold text-sm">
                {{ $index + 1 }}
            </div>
            <div>
                <div class="font-medium text-gray-900">
                    {{ $product->name ?? 'Товар #' . $product->product_id }}
                </div>
                <div class="text-xs text-gray-500">
                    {{ number_format($product->total_qty, 0) }} шт.
                </div>
            </div>
        </div>
        <div class="text-right">
            <div class="font-semibold text-gray-900">
                {{ number_format($product->revenue, 0, ',', ' ') }} ₽
            </div>
        </div>
    </div>
    @endforeach
    @endif
</div>
