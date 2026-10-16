<div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4">
    @foreach($records as $item)
        <div class="relative group">
            @include('vendor.filament-media-manager.pages.folder-card-ui', ['item' => $item])
            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                {{ ($this->folderAction($item))(['record' => $item]) }}
            </div>
        </div>
    @endforeach
</div>
