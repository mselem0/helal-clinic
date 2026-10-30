<?php

namespace Tests\Feature;

use App\Models\WebsiteBookingRequest;
use App\Services\GoogleSheets\BookingSheetFields;
use App\Services\GoogleSheets\GoogleSheetsGateway;
use Google\Client;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class DropdownTestGateway extends GoogleSheetsGateway
{
    public function __construct(private HttpClient $http) {}

    public ?array $choices = null;

    protected function bookingChoices(): array
    {
        return $this->choices ?? [7 => array_values(WebsiteBookingRequest::STATUSES)];
    }

    protected function createClient(): Client
    {
        $client = new Client;
        $client->setHttpClient($this->http);
        $client->setAccessToken(['access_token' => 'local-test-token', 'created' => time(), 'expires_in' => 3600]);

        return $client;
    }
}
class GoogleSheetsDropdownTest extends TestCase
{
    private function rule(): array
    {
        return ['condition' => ['type' => 'ONE_OF_LIST', 'values' => array_map(fn ($v) => ['userEnteredValue' => $v], array_values(WebsiteBookingRequest::STATUSES))], 'strict' => true, 'showCustomUi' => true];
    }

    private function gateway(array $responses, array &$history): DropdownTestGateway
    {
        $stack = HandlerStack::create(new MockHandler(array_map(fn ($body) => new Response(200, ['Content-Type' => 'application/json'], json_encode($body)), $responses)));
        $stack->push(Middleware::history($history));

        return new DropdownTestGateway(new HttpClient(['handler' => $stack]));
    }

    public function test_new_tab_has_strict_arabic_status_dropdown_only_in_column_h(): void
    {
        $history = [];
        $gateway = $this->gateway([
            ['properties' => ['title' => 'Book'], 'sheets' => [['properties' => ['sheetId' => 1, 'title' => 'Unrelated']]]],
            ['replies' => [['addSheet' => ['properties' => ['sheetId' => 22, 'title' => 'Dedicated']]]]],
            ['replies' => [[]]], ['updatedRows' => 1],
        ], $history);
        $gateway->prepare('sheet-id', 'Dedicated', null);
        $payload = json_decode((string) $history[2]['request']->getBody(), true);
        $validation = $payload['requests'][0]['setDataValidation'];
        $this->assertEquals(['sheetId' => 22, 'startRowIndex' => 1, 'startColumnIndex' => 7, 'endColumnIndex' => 8], $validation['range']);
        $this->assertEquals($this->rule(), $validation['rule']);
        $this->assertStringContainsString('valueInputOption=RAW', (string) $history[3]['request']->getUri());
        $this->assertCount(4, $history);
    }

    public function test_service_branch_and_status_choices_use_separate_strict_columns(): void
    {
        $history = [];
        $gateway = $this->gateway([
            ['properties' => ['title' => 'Book'], 'sheets' => []],
            ['replies' => [['addSheet' => ['properties' => ['sheetId' => 22, 'title' => 'Dedicated']]]]],
            ['replies' => [[], [], []]], ['updatedRows' => 1],
        ], $history);
        $gateway->choices = [7 => array_values(WebsiteBookingRequest::STATUSES), 4 => ['خدمة أولى', 'خدمة ثانية'], 5 => ['الفرع الأول']];
        $gateway->prepare('sheet-id', 'Dedicated', null);
        $requests = array_values(array_filter(json_decode((string) $history[2]['request']->getBody(), true)['requests'], fn ($request) => isset($request['setDataValidation'])));
        $this->assertSame([7, 4, 5, 3], array_map(fn ($request) => $request['setDataValidation']['range']['startColumnIndex'], $requests));
        foreach ($requests as $request) {
            $this->assertTrue($request['setDataValidation']['rule']['strict']);
        }
    }

    public function test_existing_correct_dropdown_is_not_rewritten_each_sync(): void
    {
        $history = [];
        $cell = ['dataValidation' => $this->rule()];
        $row = ['values' => [['userEnteredFormat' => ['numberFormat' => ['type' => 'TEXT']]], ['dataValidation' => ['condition' => ['type' => 'DATE_IS_VALID'], 'strict' => true], 'userEnteredFormat' => ['numberFormat' => ['type' => 'DATE', 'pattern' => 'yyyy-mm-dd']]], [], [], [], $cell]];
        $data = ['startColumn' => 2, 'rowData' => [$row]];
        $sheet = ['data' => [$data]];
        $gateway = $this->gateway([
            ['properties' => ['title' => 'Book'], 'sheets' => [['properties' => ['sheetId' => 22, 'title' => 'Dedicated']]]],
            ['sheets' => [$sheet]],
        ], $history);
        $gateway->prepare('sheet-id', 'Dedicated', 22);
        $this->assertCount(2, $history);
        foreach ($history as $request) {
            $this->assertSame('GET', $request['request']->getMethod());
        }
    }

    public function test_existing_missing_dropdown_is_repaired(): void
    {
        $history = [];
        $gateway = $this->gateway([
            ['properties' => ['title' => 'Book'], 'sheets' => [['properties' => ['sheetId' => 22, 'title' => 'Dedicated']]]],
            ['sheets' => [['data' => []]]], ['replies' => [[]]],
        ], $history);
        $gateway->prepare('sheet-id', 'Dedicated', 22);
        $payload = json_decode((string) $history[2]['request']->getBody(), true);
        $this->assertEquals($this->rule(), $payload['requests'][0]['setDataValidation']['rule']);
        $this->assertCount(3, $history);
    }

    public function test_date_exports_as_serial_while_phone_and_formula_text_remain_raw_strings(): void
    {
        $history = [];
        $gateway = $this->gateway([[], [], [], []], $history);
        $values = ['42', '=1+1', '001234', '2024-02-29'];
        $gateway->write('sheet-id', 'Dedicated', 2, $values);
        $gateway->write('sheet-id', 'Dedicated', null, $values);
        $gateway->patch('sheet-id', 'Dedicated', 2, [2 => '001234', 3 => '2024-02-29', 8 => '=1+1']);
        $gateway->write('sheet-id', 'Dedicated', 1, ['id', 'name', 'phone', 'booking_date']);
        foreach ([0, 1] as $index) {
            $body = json_decode((string) $history[$index]['request']->getBody(), true);
            $this->assertSame(['42', '=1+1', '001234', 45351], $body['values'][0]);
            $this->assertSame('2024-02-29', (new BookingSheetFields)->canonical('booking_date', $body['values'][0][3]));
            $this->assertStringContainsString('valueInputOption=RAW', (string) $history[$index]['request']->getUri());
        }
        $patch = json_decode((string) $history[2]['request']->getBody(), true);
        $this->assertSame('RAW', $patch['valueInputOption']);
        $this->assertSame([[['001234']], [[45351]], [['=1+1']]], array_column($patch['data'], 'values'));
        $header = json_decode((string) $history[3]['request']->getBody(), true);
        $this->assertSame('booking_date', $header['values'][0][3]);
    }

    public function test_invalid_calendar_date_is_rejected_before_any_write(): void
    {
        $history = [];
        $gateway = $this->gateway([], $history);
        try {
            $gateway->patch('sheet-id', 'Dedicated', 2, [3 => '2025-02-29']);
            $this->fail('Invalid date accepted');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame([], $history);
        }
    }

    public function test_correct_dropdown_does_not_hide_missing_date_controls(): void
    {
        $history = [];
        $gateway = $this->gateway([
            ['properties' => ['title' => 'Book'], 'sheets' => [['properties' => ['sheetId' => 22, 'title' => 'Dedicated']]]],
            ['sheets' => [['data' => [['startColumn' => 7, 'rowData' => [['values' => [['dataValidation' => $this->rule()]]]]]]]]],
            [],
        ], $history);
        $gateway->prepare('sheet-id', 'Dedicated', 22);
        $requests = json_decode((string) $history[2]['request']->getBody(), true)['requests'];
        $this->assertCount(3, $requests);
        $this->assertSame('DATE_IS_VALID', $requests[0]['setDataValidation']['rule']['condition']['type']);
        $this->assertSame(1, $requests[0]['setDataValidation']['range']['startRowIndex']);
        $this->assertSame('DATE', $requests[2]['repeatCell']['cell']['userEnteredFormat']['numberFormat']['type']);
    }

    public function test_date_validation_and_format_are_repaired_independently(): void
    {
        foreach (['validation', 'format'] as $missing) {
            $history = [];
            $date = [];
            if ($missing !== 'validation') {
                $date['dataValidation'] = ['condition' => ['type' => 'DATE_IS_VALID'], 'strict' => true];
            }
            if ($missing !== 'format') {
                $date['userEnteredFormat'] = ['numberFormat' => ['type' => 'DATE', 'pattern' => 'yyyy-mm-dd']];
            }
            $gateway = $this->gateway([
                ['properties' => ['title' => 'Book'], 'sheets' => [['properties' => ['sheetId' => 22, 'title' => 'Dedicated']]]],
                ['sheets' => [['data' => [['startColumn' => 2, 'rowData' => [['values' => [
                    ['userEnteredFormat' => ['numberFormat' => ['type' => 'TEXT']]], $date, [], [], [], ['dataValidation' => $this->rule()],
                ]]]]]]]], [],
            ], $history);
            $gateway->prepare('sheet-id', 'Dedicated', 22);
            $requests = json_decode((string) $history[2]['request']->getBody(), true)['requests'];
            $this->assertCount(1, $requests);
            $this->assertArrayHasKey($missing === 'validation' ? 'setDataValidation' : 'repeatCell', $requests[0]);
        }
    }
}
