<div class="p-4 bg-white/10 backdrop-blur-3xl rounded-3xl border border-white/20 shadow-2xl">
    <label class="text-xs font-black uppercase tracking-widest text-white/50 mb-2 block">Switch Branch</label>
    <select name="branch" class="w-full bg-black/60 border-none rounded-2xl text-white outline-none ring-1 ring-white/10"
            @change="window.location.href = 'https://' + $event.target.value + '.catvrf.ru'">
        @foreach($branches as $branch)
            <option value="{{ $branch->id }}" {{ $branch->id === tenant()->id ? 'selected' : '' }}>{{ $branch->name }}</option>
        @endforeach
    </select>
</div>