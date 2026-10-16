<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TomatoPHP\FilamentMediaManager\Models\Folder;

class MediaManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create top-level folders for major models
        $models = Media::query()->distinct()->pluck('model_type');

        foreach ($models as $modelType) {
            $modelName = Str::of($modelType)->afterLast('\\')->title()->toString();

            // Create a parent folder for the model type
            $parentFolder = Folder::firstOrCreate([
                'model_type' => $modelType,
                'model_id' => null,
                'collection' => null,
            ], [
                'name' => $modelName,
                'description' => "All media for {$modelName}",
                'icon' => 'heroicon-o-folder',
            ]);

            // 2. Create subfolders for each collection within that model
            $collections = Media::where('model_type', $modelType)->distinct()->pluck('collection_name');
            foreach ($collections as $collection) {
                Folder::firstOrCreate([
                    'model_type' => $modelType,
                    'model_id' => null,
                    'collection' => $collection,
                ], [
                    'parent_id' => $parentFolder->id,
                    'name' => Str::of($collection)->title()->toString(),
                    'description' => "Media collection: {$collection}",
                    'icon' => 'heroicon-o-photo',
                ]);
            }

            // 3. Create instance-specific folders if needed (optional, but requested "folder structures and everything")
            $instances = Media::where('model_type', $modelType)->distinct()->pluck('model_id');
            foreach ($instances as $modelId) {
                if (! $modelId) {
                    continue;
                }

                Folder::firstOrCreate([
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                    'collection' => null,
                ], [
                    'parent_id' => $parentFolder->id,
                    'name' => "{$modelName} [#{$modelId}]",
                    'description' => "Media for specific instance #{$modelId}",
                ]);
            }
        }

        // 4. Create a Global Uploads folder for general use
        Folder::firstOrCreate([
            'name' => 'Global Uploads',
        ], [
            'description' => 'General media assets not tied to specific records',
            'icon' => 'heroicon-o-globe-alt',
            'color' => '#00B4D8',
        ]);
    }
}
