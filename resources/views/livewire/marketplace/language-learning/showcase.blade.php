<div class="p-6 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        {{-- Header + Filter --}}
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">
                Language <span class="text-primary-600">Learning</span> Hub 2026
            </h1>

            <div class="flex gap-2 w-full md:w-auto">
                <input wire:model.live="search" type="text" placeholder="Search courses..."
                       class="w-full md:w-64 rounded-xl border-slate-200 bg-white/50 backdrop-blur-md shadow-sm focus:ring-primary-500">

                <button wire:click="toggleAiPanel"
                        class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-indigo-200 transition-all font-semibold flex items-center gap-2">
                    <x-heroicon-o-cpu-chip class="w-5 h-5"/>
                    AI Constructor
                </button>
            </div>
        </div>

        {{-- AI Panel (Slide-down) --}}
        @if($showAiPanel)
            <div class="mb-10 p-8 rounded-3xl bg-white/40 backdrop-blur-xl border border-white/60 shadow-2xl relative overflow-hidden transition-all animate-in slide-in-from-top duration-500"
                 style="background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%), radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);">
                <div class="grid md:grid-cols-4 gap-6 items-center">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Language</label>
                        <select wire:model="aiLanguage" class="w-full rounded-lg border-slate-200 bg-white">
                            <option value="English">English</option>
                            <option value="German">German</option>
                            <option value="Mandarin">Mandarin</option>
                            <option value="Italian">Italian</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Current Level</label>
                        <select wire:model="aiLevel" class="w-full rounded-lg border-slate-200 bg-white">
                            @foreach(['A0','A1','A2','B1','B2','C1'] as $lvl) <option value="{{$lvl}}">{{$lvl}}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Hours / Week</label>
                        <input type="number" wire:model="aiWeeklyHours" min="1" max="40" class="w-full rounded-lg border-slate-200 bg-white">
                    </div>
                    <div class="pt-6">
                        <button wire:click="generateAiPath"
                                class="w-full py-3 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-bold shadow-xl">
                            Construct Learning Plan
                        </button>
                    </div>
                </div>

                @if($aiResult)
                    <div class="mt-8 grid md:grid-cols-2 gap-8 animate-in fade-in zoom-in duration-500">
                        <div class="bg-white/60 p-6 rounded-2xl border border-white/80">
                            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                                <x-heroicon-o-academic-cap class="w-6 h-6 text-indigo-600"/> Personalized Curriculum
                            </h3>
                            <div class="space-y-4">
                                @foreach($aiResult['steps'] as $step)
                                    <div class="flex gap-4 items-start">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0">
                                            {{ $loop->iteration }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800">{{ $step['title'] }}</p>
                                            <p class="text-sm text-slate-500">{{ $step['focus'] }} ({{$step['duration']}} weeks)</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="bg-indigo-900/5 p-6 rounded-2xl border border-indigo-200/50">
                            <h3 class="text-xl font-bold mb-4 text-indigo-900">Recommended Courses</h3>
                            <div class="space-y-3">
                                @foreach($aiResult['recommended_courses'] as $rec)
                                    <div class="p-3 bg-white rounded-xl shadow-sm border border-indigo-50 flex justify-between items-center">
                                        <div>
                                            <p class="font-extrabold text-sm">{{ $rec['title'] }}</p>
                                            <p class="text-xs text-slate-400">by {{ $rec['teacher'] }}</p>
                                        </div>
                                        <div class="text-indigo-600 font-bold">₽{{ number_format($rec['price'] / 100, 0, '.', ' ') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Courses Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach($courses as $course)
                <div class="group relative bg-white rounded-[2rem] overflow-hidden border border-slate-100 shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
                    <div class="h-40 bg-gradient-to-br from-indigo-500 to-purple-500 relative">
                        <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition"></div>
                        <div class="absolute top-4 left-4">
                            <span class="px-3 py-1 bg-white/90 backdrop-blur text-indigo-600 text-xs font-bold rounded-full uppercase tracking-widest">
                                {{ $course->level_from }} Level
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold text-slate-900 mb-2 leading-tight">{{ $course->title }}</h3>
                        <p class="text-slate-500 text-sm mb-4 line-clamp-2">{{ strip_tags($course->description) ?: 'Master ' . $course->language . ' with our immersive program.' }}</p>

                        <div class="flex items-center gap-3 mb-6">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($course->teacher->full_name) }}&background=6366f1&color=fff" class="w-8 h-8 rounded-full border border-slate-200">
                            <span class="text-sm font-bold text-slate-700">{{ $course->teacher->full_name }}</span>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-slate-50">
                            <div>
                                <span class="text-2xl font-black text-slate-900">₽{{ number_format($course->price_total / 100, 0, '.', ' ') }}</span>
                            </div>
                            <button class="px-5 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-indigo-600 transition-colors shadow-lg">
                                Enroll Now
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-12 text-center text-slate-400 text-sm font-medium">
            &copy; 2026 CatVRF Language Learning Platform. All educational tracks are AI-constructed.
        </div>
    </div>
</div>
