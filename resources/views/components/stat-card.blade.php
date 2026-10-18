@props(['label', 'value', 'icon', 'hint' => null])

<div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $label }}</p>
        <div class="w-9 h-9 bg-brand-50 rounded-lg flex items-center justify-center">
            <i class="fas {{ $icon }} text-brand-700 text-sm"></i>
        </div>
    </div>
    <p class="text-2xl font-bold text-slate-800">{{ $value }}</p>
    @if($hint)
        <p class="text-xs text-slate-500 mt-1.5">{{ $hint }}</p>
    @endif
</div>
