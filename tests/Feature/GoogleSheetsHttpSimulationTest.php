<?php

namespace Tests\Feature;

use App\Models\WebsiteBookingRequest;
use App\Services\GoogleSheets\BookingSheetFields;
use App\Services\GoogleSheets\GoogleSheetsGateway;
use Google\Client;
use Google\Service\Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\TransferException;
use Tests\TestCase;

class GoogleSheetsHttpSimulationTest extends TestCase
{
    protected function gateway(): GoogleSheetsGateway
    {
        return new class extends GoogleSheetsGateway
        {
            protected function createClient(): Client
            {
                $client = new Client;
                $client->setAccessToken(['access_token' => 'local-simulation-only', 'created' => time(), 'expires_in' => 3600]);
                $client->setHttpClient(new HttpClient(['timeout' => 3, 'connect_timeout' => 1]));
                $client->setConfig('retry', ['retries' => 0]);

                return $client;
            }
        };
    }

    public function test_actual_google_sdk_roundtrip_and_raw_strings(): void
    {
        $root = getenv('GOOGLE_SHEETS_SIMULATION_URL');
        if (! $root) {
            $this->markTestSkipped('Opt in with local GOOGLE_SHEETS_SIMULATION_URL');
        }
        $this->assertSame('127.0.0.1', parse_url($root, PHP_URL_HOST));
        config(['google_sheets.api_root' => rtrim($root, '/').'/']);
        (new HttpClient)->delete(rtrim($root, '/').'/reset');
        $api = $this->gateway();
        $tab = $api->prepare('test-book', 'Bookings', null);
        $api->write('test-book', $tab['worksheet_title'], null, ['=HYPERLINK("fake")', '01012345678']);
        $rows = $api->rows('test-book', $tab['worksheet_title']);
        $validation = json_decode((string) (new HttpClient)->get(rtrim($root, '/').'/validation')->getBody(), true);
        $formatting = json_decode((string) (new HttpClient)->get(rtrim($root, '/').'/formatting')->getBody(), true);
        $this->assertSame(2, $formatting[0]['range']['startColumnIndex']);
        $this->assertSame('TEXT', $formatting[0]['cell']['userEnteredFormat']['numberFormat']['type']);
        $this->assertSame('userEnteredFormat.numberFormat', $formatting[0]['fields']);
        $this->assertSame(1, $validation['range']['startRowIndex']);
        $this->assertSame(7, $validation['range']['startColumnIndex']);
        $this->assertTrue($validation['rule']['strict']);
        $this->assertTrue($validation['rule']['showCustomUi']);
        $this->assertSame(array_values(WebsiteBookingRequest::STATUSES), array_column($validation['rule']['condition']['values'], 'userEnteredValue'));

        $this->assertSame('=HYPERLINK("fake")', $rows[1][0]);
        $this->assertSame('01012345678', $rows[1][1]);
        $api->write('test-book', $tab['worksheet_title'], null, ['2', 'Original', '01000000000', '2026-11-01', '', '', '', 'new', '', '', '', '', '', '=SUM(A1:A2)']);
        $api->patch('test-book', $tab['worksheet_title'], 3, [1 => 'Updated', 3 => '2026-11-02', 7 => 'contacted']);
        $rows = $api->rows('test-book', $tab['worksheet_title']);
        $this->assertSame('Updated', $rows[2][1]);
        $this->assertSame('contacted', $rows[2][7]);
        $this->assertSame('01000000000', $rows[2][2]);
        $this->assertIsNumeric($rows[2][3]);
        $this->assertSame('2026-11-02', (new BookingSheetFields)->canonical('booking_date', $rows[2][3]));
        $extra = json_decode((string) (new HttpClient)->get(rtrim($root, '/').'/v4/spreadsheets/test-book/values/'.rawurlencode("'".$tab['worksheet_title']."'!A:N"))->getBody(), true);
        $this->assertSame('=SUM(A1:A2)', $extra['values'][2][13]);
        $this->assertSame(7, $api->prepare('test-book', $tab['worksheet_title'], 7)['worksheet_id']);
    }

    public function test_dependency_failure_is_bounded_and_propagates(): void
    {
        $root = getenv('GOOGLE_SHEETS_FAILURE_URL');
        if (! $root) {
            $this->markTestSkipped('Opt in with local GOOGLE_SHEETS_FAILURE_URL');
        }
        $this->assertSame('127.0.0.1', parse_url($root, PHP_URL_HOST));
        config(['google_sheets.api_root' => rtrim($root, '/').'/']);
        $started = microtime(true);
        try {
            $this->gateway()->rows('test-book', 'Bookings');
            $this->fail('Dependency failure must propagate');
        } catch (Exception|TransferException $error) {
            $this->assertLessThan(5, microtime(true) - $started);
            $this->assertNotEmpty($error->getMessage());
            if ($expected = getenv('GOOGLE_SHEETS_EXPECTED_ERROR')) {
                $this->assertStringContainsString($expected,$error->getMessage());
            }
        }
    }
}
