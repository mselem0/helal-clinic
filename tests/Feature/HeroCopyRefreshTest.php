<?php

namespace Tests\Feature;

use App\Console\Commands\RefreshHeroCopy;
use App\Models\PageSection;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HeroCopyRefreshTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    public function test_refresh_hero_copy_dry_run_reports_changes_without_persisting_and_apply_updates_old_defaults_idempotently(): void
    {
        $home = PageSection::where('section_key', 'home')->firstOrFail();
        $body = json_decode($home->body, true, flags: JSON_THROW_ON_ERROR);

        $legacyValues = [
            'field_1' => ' خبرة طبية.. واهتمام بكل التفاصيل',
            'field_7' => 'رعاية متخصصة لصحتكِ ورحلة الأمومة، مع استشاري أمراض النساء والتوليد والمناظير الجراحية والحقن المجهري.',
            'field_8' => 'اطلبي حجزكِ الآن ',
            'field_9' => 'اكتشفي خدمات العيادة',
            'field_21' => 'افهمي قبل ما تقلقي ',
        ];

        foreach ($body['fields'] as &$field) {
            if (array_key_exists($field['key'], $legacyValues)) {
                $field['value'] = $legacyValues[$field['key']];
                $field['label'] = 'تسمية محفوظة '.$field['key'];
            }
        }
        unset($field);

        $legacyJson = json_encode($body, JSON_UNESCAPED_UNICODE);
        $home->update(['body' => $legacyJson]);

        $this->artisan('helal:refresh-hero-copy')
            ->expectsOutputToContain('Dry-run: 5 field(s) would be updated.')
            ->assertSuccessful();

        $this->assertSame($legacyJson, $home->fresh()->body);

        $this->artisan('helal:refresh-hero-copy', ['--apply' => true])
            ->expectsOutputToContain('Applied: 5 field(s) updated.')
            ->assertSuccessful();

        $updatedFields = collect(json_decode($home->fresh()->body, true, flags: JSON_THROW_ON_ERROR)['fields'])
            ->keyBy('key');

        $this->assertSame('رعاية متخصصة واهتمام بكل التفاصيل', $updatedFields['field_1']['value']);
        $this->assertSame('رعاية لصحتكِ في كل مرحلة، من متابعة الحمل والولادة إلى تأخر الإنجاب والمناظير الجراحية.', $updatedFields['field_7']['value']);
        $this->assertSame('احجزي ميعادك الآن', $updatedFields['field_8']['value']);
        $this->assertSame('تعرّفي على خدمات العيادة', $updatedFields['field_9']['value']);
        $this->assertSame('لحظات من متابعة الحمل مع د. محمد هلال.', $updatedFields['field_21']['value']);
        $this->assertSame('تسمية محفوظة field_1', $updatedFields['field_1']['label']);

        $snapshotAfterFirstApply = $home->fresh()->body;

        $this->artisan('helal:refresh-hero-copy', ['--apply' => true])
            ->expectsOutputToContain('Applied: 0 field(s) updated.')
            ->assertSuccessful();

        $this->assertSame($snapshotAfterFirstApply, $home->fresh()->body);
    }

    public function test_refresh_hero_copy_preserves_customized_values_metadata_other_fields_sections_and_draft_data(): void
    {
        $home = PageSection::where('section_key', 'home')->firstOrFail();
        $header = PageSection::where('section_key', 'header')->firstOrFail();
        $headerOriginalBody = $header->body;

        $body = json_decode($home->body, true, flags: JSON_THROW_ON_ERROR);
        $body['custom_meta'] = ['keep' => true];

        foreach ($body['fields'] as &$field) {
            if ($field['key'] === 'field_1') {
                $field['value'] = 'نص مخصص من العيادة لا يجب تغييره';
                $field['label'] = 'عنوان فرعي مخصص';
            }
            if ($field['key'] === 'field_8') {
                $field['value'] = RefreshHeroCopy::OLD_SHIPPED_DEFAULTS['field_8'];
                $field['label'] = 'تسمية زر الحجز';
            }
            if ($field['key'] === 'field_2') {
                $field['value'] = 'د. محمد هلال — تعديل خاص';
            }
        }
        unset($field);

        $draftPayload = [
            'body' => json_encode(['fields' => [['key' => 'field_1', 'value' => 'مسودة قيد المراجعة']]], JSON_UNESCAPED_UNICODE),
            'order' => 99,
        ];

        $publishedAt = now()->subDay()->startOfSecond();

        $home->update([
            'section_label' => 'واجهة مخصصة',
            'order' => 15,
            'is_active' => false,
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
            'draft_data' => $draftPayload,
            'published_at' => $publishedAt,
        ]);

        $this->artisan('helal:refresh-hero-copy', ['--apply' => true])
            ->expectsOutputToContain('Applied: 1 field(s) updated.')
            ->assertSuccessful();

        $freshHome = $home->fresh();
        $freshBody = json_decode($freshHome->body, true, flags: JSON_THROW_ON_ERROR);
        $fields = collect($freshBody['fields'])->keyBy('key');

        $this->assertSame('نص مخصص من العيادة لا يجب تغييره', $fields['field_1']['value']);
        $this->assertSame('عنوان فرعي مخصص', $fields['field_1']['label']);
        $this->assertSame('احجزي ميعادك الآن', $fields['field_8']['value']);
        $this->assertSame('تسمية زر الحجز', $fields['field_8']['label']);
        $this->assertSame('د. محمد هلال — تعديل خاص', $fields['field_2']['value']);
        $this->assertSame(['keep' => true], $freshBody['custom_meta']);
        $this->assertSame('واجهة مخصصة', $freshHome->section_label);
        $this->assertSame(15, $freshHome->order);
        $this->assertFalse($freshHome->is_active);
        $this->assertSame($draftPayload, $freshHome->draft_data);
        $this->assertTrue($publishedAt->equalTo($freshHome->published_at));
        $this->assertSame($headerOriginalBody, $header->fresh()->body);
    }

    public function test_home_hero_portrait_alt_and_video_trigger_titles_follow_field_20_with_featured_item_fallback(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('alt="د. محمد هلال"', false)
            ->assertSee('data-title="متابعة الحمل بالسونار"', false)
            ->assertSee('aria-label="شاهدي د. محمد هلال: متابعة الحمل بالسونار"', false)
            ->assertDontSee('الدكتور محمد هلال — فيديو عن سكر الحمل وطريقة الولادة', false);

        $home = PageSection::where('section_key', 'home')->firstOrFail();
        $body = json_decode($home->body, true, flags: JSON_THROW_ON_ERROR);

        foreach ($body['fields'] as &$field) {
            if ($field['key'] === 'field_14') {
                $field['value'] = '1122351437148430';
            }
            if ($field['key'] === 'field_20') {
                $field['value'] = '   ';
            }
        }
        unset($field);

        $home->update(['body' => json_encode($body, JSON_UNESCAPED_UNICODE)]);
        Video::where('reel', '1122351437148430')->update(['title' => 'عنوان الفيديو الاحتياطي']);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-title="عنوان الفيديو الاحتياطي"', false)
            ->assertSee('aria-label="شاهدي د. محمد هلال: عنوان الفيديو الاحتياطي"', false);
    }
}
