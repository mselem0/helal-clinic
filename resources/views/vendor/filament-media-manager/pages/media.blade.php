@php
    $isMediaPage = $this instanceof \TomatoPHP\FilamentMediaManager\Resources\MediaResource\Pages\ListMedia;
    $folderId = $isMediaPage ? $this->folder_id : (request()->has('folder_id') ? request()->get('folder_id') : null);
    $currentFolder = $folderId ? \TomatoPHP\FilamentMediaManager\Models\Folder::find($folderId) : null;
    
    if(filament('filament-media-manager')->allowSubFolders){
        $folders = \TomatoPHP\FilamentMediaManager\Models\Folder::query()
            ->where('parent_id', $folderId)
            ->get();
    }
    else {
        $folders = [];
    }

    $panelId = filament()->getCurrentPanel()->getId();
    $foldersRoute = "filament.{$panelId}.resources.folders.index";
    $mediaRoute = "filament.{$panelId}.resources.media.index";
    
    $hasParent = !empty($currentFolder?->parent_id);
    $backUrl = $hasParent 
        ? route($mediaRoute, ['folder_id' => $currentFolder->parent_id]) 
        : route($foldersRoute);
@endphp

<style>
    /* Hide the standard table controls that clutter the Media Library */
    .fi-ta-header, .fi-ta-header-toolbar {
        display: none !important;
    }
    .folder-card-wrapper:hover {
        transform: translateY(-2px);
        transition: all 0.2s ease-in-out;
    }
</style>

@if($folderId)
    <div class="flex items-center gap-3 p-4 border-b dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 rounded-t-xl mb-2">
        @if($isMediaPage && $hasParent)
            <x-filament::button 
                wire:click="$set('folder_id', {{ $currentFolder->parent_id }})"
                color="gray" 
                icon="heroicon-o-arrow-left"
                size="sm"
                variant="outline"
            >
                عودة للخلف
            </x-filament::button>
        @else
            <x-filament::button 
                tag="a"
                href="{{ $backUrl }}"
                color="gray" 
                icon="heroicon-o-arrow-left"
                size="sm"
                variant="outline"
            >
                عودة للخلف
            </x-filament::button>
        @endif
        
        <div class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-200">
            <x-icon name="heroicon-o-folder-open" class="w-5 h-5 text-primary-500" />
            <span>{{ $currentFolder?->name }}</span>
        </div>
    </div>
@endif

@if(isset($records) || count($folders) > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4">
        @if(isset($records))
            @foreach($records as $item)
                @if($item instanceof \TomatoPHP\FilamentMediaManager\Models\Folder)
                    <div class="relative group">
                        @include('vendor.filament-media-manager.pages.folder-card-ui', ['item' => $item])
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            {{ ($this->folderAction($item))(['record' => $item]) }}
                        </div>
                    </div>
                @else
                    <x-filament::modal width="3xl" slide-over>
                        <x-slot name="trigger" class="w-full h-full">
                            <div class="flex flex-col justify-start gap-4 border dark:border-gray-700 rounded-lg shadow-sm p-2 w-full h-full">
                                <div class="flex flex-col items-center justify-center p-4 h-full">
                                    @if(str($item->mime_type)->contains('image'))
                                        <img src="{{ $item->getUrl() }}" />
                                    @elseif(str($item->mime_type)->contains('video'))
                                        <video src="{{ $item->getUrl() }}"></video>
                                    @elseif(str($item->mime_type)->contains('audio'))
                                        <x-icon name="heroicon-o-musical-note" class="w-32 h-32" />
                                    @else
                                        @php
                                            $hasPreview = false;
                                            $loadTypes = \TomatoPHP\FilamentMediaManager\Facade\FilamentMediaManager::getTypes();
                                            $type = null;
                                            foreach ($loadTypes as $getType) {
                                                if(str($item->file_name)->contains($getType->exstantion)){
                                                    $hasPreview = $getType->preview;
                                                    $type = $getType;
                                                }
                                            }
                                        @endphp
                                        @if($hasPreview && $type)
                                            <x-icon :name="$type->icon" class="w-32 h-32" />
                                        @else
                                            <x-icon name="heroicon-o-document" class="w-32 h-32" />
                                        @endif
                                    @endif
                                </div>
                                <div>
                                    <div class="flex flex-col justify-between border-t dark:border-gray-700 p-4">
                                        <div>
                                            <h1 class="font-bold break-words">{{ $item->hasCustomProperty('title') ? (!empty($item->getCustomProperty('title')) ? $item->getCustomProperty('title') : $item->name) : $item->name }}</h1>
                                        </div>

                                        @if($item->hasCustomProperty('description') && !empty($item->getCustomProperty('description')))
                                            <div>
                                                <div>
                                                    <h1 class="font-bold">Description</h1>
                                                </div>
                                                <div class="flex justify-start">
                                                    <p class="text-sm">
                                                        {{ $item->getCustomProperty('description') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="flex justify-start">
                                            <p class="text-gray-600 dark:text-gray-300 text-sm truncate ...">
                                                {{ $item->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-slot>

                        <x-slot name="heading">
                            {{ $item->uuid }}
                        </x-slot>

                        <x-slot name="description">
                            {{ $item->file_name }}
                        </x-slot>

                        <div>
                            <div class="flex flex-col justify-start w-full h-full">
                                @if(str($item->mime_type)->contains('image'))
                                    <a href="{{ $item->getUrl() }}" target="_blank" class="flex flex-col items-center justify-center p-4 h-full border dark:border-gray-700 rounded-lg">
                                        <img src="{{ $item->getUrl() }}" />
                                    </a>
                                @elseif(str($item->mime_type)->contains('video'))
                                    <a href="{{ $item->getUrl() }}" target="_blank" class="flex flex-col items-center justify-center p-4 h-full border dark:border-gray-700 rounded-lg">
                                        <video class="w-full h-full" controls>
                                            <source src="{{ $item->getUrl() }}" type="{{ $item->mime_type }}">
                                        </video>
                                    </a>
                                @elseif(str($item->mime_type)->contains('audio'))
                                    <a href="{{ $item->getUrl() }}" target="_blank" class="flex flex-col items-center justify-center p-4 h-full border dark:border-gray-700 rounded-lg">
                                        <video class="w-full h-full" controls>
                                            <source src="{{ $item->getUrl() }}" type="{{ $item->mime_type }}">
                                        </video>
                                    </a>
                                @else
                                    @php
                                        $hasPreview = false;
                                        $loadTypes = \TomatoPHP\FilamentMediaManager\Facade\FilamentMediaManager::getTypes();
                                        foreach ($loadTypes as $type) {
                                            if(str($item->file_name)->contains($type->exstantion)){
                                                $hasPreview = $type->preview;
                                            }
                                        }
                                    @endphp
                                    @if($hasPreview)
                                        @include($hasPreview, ['media' => $item])
                                    @else
                                        <a href="{{ $item->getUrl() }}" target="_blank" class="flex flex-col items-center justify-center p-4 h-full border dark:border-gray-700 rounded-lg">
                                            @if($type)
                                                <x-icon :name="$type->icon" class="w-32 h-32" />
                                            @else
                                                <x-icon name="heroicon-o-document" class="w-32 h-32" />
                                            @endif
                                        </a>
                                    @endif
                                @endif

                                <div class="flex flex-col gap-4 my-4">
                                    @if($item->model)
                                        <div>
                                            <div>
                                                <h1 class="font-bold">{{ trans('filament-media-manager::messages.media.meta.model') }}</h1>
                                            </div>
                                            <div class="flex justify-start">
                                                <p class="text-sm">
                                                    {{str($item->model_type)->afterLast('\\')->title()}}[ID:{{ $item->model?->id }}]
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                    <div>
                                        <div>
                                            <h1 class="font-bold">{{ trans('filament-media-manager::messages.media.meta.file-name') }}</h1>
                                        </div>
                                        <div class="flex justify-start">
                                            <p class="text-sm">
                                                {{ $item->file_name }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div>
                                            <h1 class="font-bold">{{ trans('filament-media-manager::messages.media.meta.type') }}</h1>
                                        </div>
                                        <div class="flex justify-start">
                                            <p class="text-sm">
                                                {{ $item->mime_type }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div>
                                            <h1 class="font-bold">{{ trans('filament-media-manager::messages.media.meta.size') }}</h1>
                                        </div>
                                        <div class="flex justify-start">
                                            <p class="text-sm">
                                                {{ $item->humanReadableSize }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div>
                                            <h1 class="font-bold">{{ trans('filament-media-manager::messages.media.meta.disk') }}</h1>
                                        </div>
                                        <div class="flex justify-start">
                                            <p class="text-sm">
                                                {{ $item->disk  }}
                                            </p>
                                        </div>
                                    </div>
                                    @if($item->custom_properties)
                                        @foreach($item->custom_properties as $key=>$value)
                                            @if($value)
                                                <div>
                                                    <div>
                                                        <h1 class="font-bold">{{str($key)->title()}}</h1>
                                                    </div>
                                                    <div class="flex justify-start">
                                                        <p class="text-sm">
                                                            {{ $value }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if(filament('filament-media-manager')->allowUserAccess && (!empty($currentFolder->user_id)))
                            @if($currentFolder->user_id === auth()->user()->id && $currentFolder->user_type === get_class(auth()->user()))
                                <x-slot name="footer">
                                    {{ ($this->deleteMedia)(['record' => $item]) }}
                                </x-slot>
                            @endif
                        @else
                            <x-slot name="footer">
                                {{ ($this->deleteMedia)(['record' => $item]) }}
                            </x-slot>
                        @endif
                    </x-filament::modal>
                @endif
            @endforeach
        @endif

        @if(filament('filament-media-manager')->allowSubFolders)
            @foreach($folders as $folder)
                <div class="relative group">
                    @include('vendor.filament-media-manager.pages.folder-card-ui', ['item' => $folder])
                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        {{ ($this->folderAction($folder))(['record' => $folder]) }}
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@else
    <div class="fi-ta-empty-state px-6 py-12">
        <div class="fi-ta-empty-state-content mx-auto grid max-w-lg justify-items-center text-center">
            <div class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 p-3 dark:bg-gray-500/20">
                <x-filament::icon icon="heroicon-o-x-mark" class="fi-ta-empty-state-icon h-6 w-6 text-gray-500 dark:text-gray-400" />
            </div>
            <x-filament-tables::empty-state.heading>
                {{ trans('filament-media-manager::messages.empty.title') }}
            </x-filament-tables::empty-state.heading>
        </div>
    </div>
@endif
