@extends('company.layouts.base')

@section('title', 'Categorías')

@section('content')
{{--
    Crear y editar en un mismo modal (Alpine). Los campos ocultos _modal y _id viajan con el formulario:
    si la validación falla, el servidor devuelve old('_modal') / old('_id') y el modal se reabre
    en el mismo modo, con lo que se había escrito y apuntando a la misma categoría.
--}}
<div x-data="{
        modal: @js(old('_modal')),
        showErrors: @js(old('_modal') !== null),
        form: {
            id: @js(old('_id')),
            name: @js(old('name', '')),
            active: @js((bool) old('active', true)),
        },
        storeUrl: @js(route('company.categories.store')),
        updateUrl: @js(route('company.categories.update', '__ID__')),

        get action() {
            return this.modal === 'edit' ? this.updateUrl.replace('__ID__', this.form.id) : this.storeUrl;
        },
        openCreate() {
            this.form = { id: null, name: '', active: true };
            this.showErrors = false;
            this.modal = 'create';
            this.$nextTick(() => this.$refs.name.focus());
        },
        openEdit(category) {
            this.form = { ...category };
            this.showErrors = false;
            this.modal = 'edit';
            this.$nextTick(() => this.$refs.name.focus());
        },
     }">

    <x-page-header title="Categorías" subtitle="Agrupa los productos de tu tienda">
        <button type="button" @click="openCreate()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nueva categoría
        </button>
    </x-page-header>

    <x-search-form :action="route('company.categories.index')" placeholder="Nombre de la categoría..." />

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($categories->isEmpty())
            <div class="flex flex-col items-center justify-center py-16">
                <div class="w-14 h-14 bg-slate-100 rounded-xl flex items-center justify-center mb-3">
                    <i class="fas fa-tags text-slate-300 text-xl"></i>
                </div>
                <p class="text-sm font-medium text-slate-500">Sin categorías</p>
                <p class="text-xs text-slate-400 mt-1">{{ request()->filled('buscar') ? 'Nada coincide con la búsqueda' : 'Crea la primera para empezar tu catálogo' }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            <th class="text-left px-5 py-3">Nombre</th>
                            <th class="text-center px-4 py-3">Productos</th>
                            <th class="text-center px-4 py-3">Estado</th>
                            <th class="text-center px-5 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($categories as $category)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-3 font-semibold text-slate-800">{{ $category->name }}</td>
                                <td class="px-4 py-3 text-center text-slate-700">{{ $category->products_count }}</td>
                                <td class="px-4 py-3 text-center"><x-status-badge :active="$category->active" on="Activa" off="Inactiva" /></td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" title="Editar"
                                                @click="openEdit(@js(['id' => $category->id, 'name' => $category->name, 'active' => $category->active]))"
                                                class="inline-flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                        <form method="POST" action="{{ route('company.categories.destroy', $category) }}"
                                              @submit.prevent="$store.confirm.ask($el, @js("Se eliminará la categoría «{$category->name}»."), '¿Eliminar categoría?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Eliminar"
                                                    class="inline-flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                                <i class="fas fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($categories->hasPages())
                <div class="px-5 py-3 border-t border-slate-100">{{ $categories->links() }}</div>
            @endif
        @endif
    </div>

    {{-- Modal crear / editar --}}
    <div x-show="modal" x-cloak x-transition.opacity @keydown.escape.window="modal = null"
         class="fixed inset-0 bg-black/50 z-40 flex items-center justify-center p-4">
        <form method="POST" :action="action" @click.outside="modal = null" x-data="{ sending: false }" @submit="sending = true"
              class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            @csrf
            <template x-if="modal === 'edit'">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <input type="hidden" name="_modal" :value="modal">
            <input type="hidden" name="_id" :value="form.id">

            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800" x-text="modal === 'edit' ? 'Editar categoría' : 'Nueva categoría'"></h3>
                <button type="button" @click="modal = null" class="text-slate-400 hover:text-slate-600" title="Cerrar">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" x-ref="name" x-model="form.name" maxlength="100"
                           class="w-full px-3 py-2 text-sm border rounded-lg bg-white transition focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                           :class="showErrors && @js($errors->has('name')) ? 'border-red-400' : 'border-slate-300'">
                    @error('name')
                        <p x-show="showErrors" class="text-xs text-red-600 mt-1 flex items-center gap-1">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <input type="hidden" name="active" value="0">
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" name="active" value="1" x-model="form.active" class="w-4 h-4 accent-brand-600">
                    <span class="text-sm text-slate-700">Activa (se puede usar en productos nuevos)</span>
                </label>
            </div>

            <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <button type="button" @click="modal = null"
                        class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
                <button type="submit" :disabled="sending"
                        class="px-5 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-lg flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin" x-show="sending" x-cloak></i>
                    <span x-text="modal === 'edit' ? 'Guardar cambios' : 'Registrar'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
