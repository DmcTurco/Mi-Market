@props(['active', 'on' => 'Activo', 'off' => 'Inactivo'])

<span @class([
    'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium',
    'bg-emerald-50 text-emerald-700' => $active,
    'bg-slate-100 text-slate-500' => !$active,
])>
    <span @class(['w-1.5 h-1.5 rounded-full', 'bg-emerald-500' => $active, 'bg-slate-400' => !$active])></span>
    {{ $active ? $on : $off }}
</span>
