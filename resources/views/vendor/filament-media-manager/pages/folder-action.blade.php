<div @click.stop="">
    <x-filament-actions::action
        :action="$action"
        dynamic-component="filament::icon-button"
        icon="heroicon-m-pencil-square"
        color="gray"
        size="sm"
        tooltip="تعديل المجلد"
        class="bg-white/80 dark:bg-gray-800/80 rounded-full shadow-sm"
    />
</div>
