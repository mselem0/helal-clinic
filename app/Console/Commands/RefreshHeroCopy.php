<?php

namespace App\Console\Commands;

use App\Models\PageSection;
use Illuminate\Console\Command;

class RefreshHeroCopy extends Command
{
    public const OLD_SHIPPED_DEFAULTS = [
        'field_1' => 'خبرة طبية.. واهتمام بكل التفاصيل',
        'field_7' => 'رعاية متخصصة لصحتكِ ورحلة الأمومة، مع استشاري أمراض النساء والتوليد والمناظير الجراحية والحقن المجهري.',
        'field_8' => 'اطلبي حجزكِ الآن',
        'field_9' => 'اكتشفي خدمات العيادة',
        'field_21' => 'افهمي قبل ما تقلقي',
    ];

    protected $signature = 'helal:refresh-hero-copy {--apply : Apply hero copy updates to the local home PageSection}';

    protected $description = 'Refresh default hero copy fields on the home PageSection when they still match old shipped defaults';

    public function handle(): int
    {
        $section = PageSection::where('section_key', 'home')->first();

        if (! $section) {
            $this->error('Home PageSection not found.');

            return self::FAILURE;
        }

        $body = json_decode($section->body ?? '', true);

        if (! is_array($body) || ! isset($body['fields']) || ! is_array($body['fields'])) {
            $this->error('Home PageSection body JSON does not contain a valid fields array.');

            return self::FAILURE;
        }

        $newDefaults = $this->loadNewHomeDefaults();

        if ($newDefaults === null) {
            $this->error('Unable to load target home defaults from database/content/helal.json.');

            return self::FAILURE;
        }

        $changes = [];

        foreach ($body['fields'] as &$field) {
            if (! is_array($field)) {
                continue;
            }

            $key = $field['key'] ?? null;

            if (! is_string($key) || ! array_key_exists($key, self::OLD_SHIPPED_DEFAULTS)) {
                continue;
            }

            $current = (string) ($field['value'] ?? '');
            $target = $newDefaults[$key];

            if (trim($current) === self::OLD_SHIPPED_DEFAULTS[$key] && $current !== $target) {
                $changes[] = [
                    'field' => $key,
                    'status' => $this->option('apply') ? 'updated' : 'would_update',
                    'from' => $current,
                    'to' => $target,
                ];
                $field['value'] = $target;
            } else {
                $status = trim($current) === trim($target) ? 'already_current' : 'preserved_custom';
                $this->line(sprintf('[%s] %s (%s)', $key, $status, $current));
            }
        }
        unset($field);

        foreach ($changes as $change) {
            $this->info(sprintf(
                '[%s] %s: "%s" -> "%s"',
                $change['field'],
                $change['status'],
                $change['from'],
                $change['to'],
            ));
        }

        $apply = (bool) $this->option('apply');

        if ($apply && $changes !== []) {
            $section->update([
                'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            ]);
        }

        $mode = $apply ? 'Applied' : 'Dry-run';
        $this->info(sprintf('%s: %d field(s) %s.', $mode, count($changes), $apply ? 'updated' : 'would be updated'));

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>|null
     */
    private function loadNewHomeDefaults(): ?array
    {
        $path = database_path('content/helal.json');

        if (! is_file($path)) {
            return null;
        }

        $sections = json_decode((string) file_get_contents($path), true);

        if (! is_array($sections)) {
            return null;
        }

        $home = collect($sections)->firstWhere('key', 'home');

        if (! is_array($home) || ! isset($home['fields']) || ! is_array($home['fields'])) {
            return null;
        }

        $fields = collect($home['fields'])
            ->filter(fn ($field) => is_array($field) && isset($field['key'], $field['value']))
            ->mapWithKeys(fn (array $field) => [(string) $field['key'] => (string) $field['value']])
            ->all();

        $resolved = [];

        foreach (array_keys(self::OLD_SHIPPED_DEFAULTS) as $key) {
            if (! array_key_exists($key, $fields)) {
                return null;
            }

            $resolved[$key] = $fields[$key];
        }

        return $resolved;
    }
}
