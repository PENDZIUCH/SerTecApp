<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-base font-semibold text-gray-950 dark:text-white">¿Nuevo trabajo?</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Creá una orden de trabajo y asignala a un técnico.</p>
            </div>
            <x-filament::button tag="a" :href="$this->getCreateUrl()" icon="heroicon-o-plus" color="primary">
                Nueva Orden
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
