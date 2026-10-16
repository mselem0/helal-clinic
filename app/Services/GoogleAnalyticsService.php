<?php

// app/Services/GoogleAnalyticsService.php

namespace App\Services;

use App\Helpers\SeoHelper;
use Carbon\Carbon;
use Google\Client;
use Google\Service\AnalyticsData;
use Google\Service\AnalyticsData\DateRange;
use Google\Service\AnalyticsData\Dimension;
use Google\Service\AnalyticsData\DimensionOrderBy;
use Google\Service\AnalyticsData\Metric;
use Google\Service\AnalyticsData\OrderBy;
use Google\Service\AnalyticsData\RunRealtimeReportRequest;
use Google\Service\AnalyticsData\RunReportRequest;

class GoogleAnalyticsService
{
    protected $client;

    protected $analytics;

    public function __construct()
    {
        if (! config('clinic-integrations.google')) { return; }
        $this->client = new Client;
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));

        $tokenJson = SeoHelper::getSetting('google_access_token');
        if ($tokenJson) {
            $token = json_decode($tokenJson, true);
            $this->client->setAccessToken($token);

            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = SeoHelper::getSetting('google_refresh_token');
                if ($refreshToken) {
                    try {
                        $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                        SeoHelper::setSetting('google_access_token', json_encode($newToken), 'verification');
                    } catch (\Exception $e) {
                        \Log::error('Google token refresh failed: '.$e->getMessage());
                    }
                }
            }

            $this->analytics = new AnalyticsData($this->client);
        }
    }

    public function isConnected(): bool
    {
        if (! config('clinic-integrations.google')) {
            return false;
        }

        return ! empty($this->analytics);
    }

    public function getRealtimeActiveUsers(): int
    {
        if (! $this->isConnected()) {
            return 0;
        }

        try {
            $propertyId = SeoHelper::getSetting('google_property_id');
            if (! $propertyId) {
                return 0;
            }

            $request = new RunRealtimeReportRequest;
            $request->setMetrics([new Metric(['name' => 'activeUsers'])]);

            $response = $this->analytics->properties->runRealtimeReport("properties/$propertyId", $request);

            $rows = $response->getRows() ?? [];

            return $rows[0]?->getMetricValues()[0]?->getValue() ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getDailySessionsLast7Days(): array
    {
        if (! $this->isConnected()) {
            return ['labels' => [], 'data' => []];
        }

        try {
            $propertyId = SeoHelper::getSetting('google_property_id');
            if (! $propertyId) {
                return ['labels' => [], 'data' => []];
            }

            $request = new RunReportRequest;
            $request->setDateRanges([new DateRange(['start_date' => '7daysAgo', 'end_date' => 'today'])]);
            $request->setMetrics([new Metric(['name' => 'sessions'])]);
            $request->setDimensions([new Dimension(['name' => 'date'])]);
            $request->setOrderBys([
                new OrderBy([
                    'dimension' => new DimensionOrderBy(['dimension_name' => 'date']),
                    'desc' => false,
                ]),
            ]);

            $response = $this->analytics->properties->runReport("properties/$propertyId", $request);

            $labels = [];
            $data = [];

            foreach ($response->getRows() as $row) {
                $dateStr = $row->getDimensionValues()[0]->getValue();
                $labels[] = Carbon::createFromFormat('Ymd', $dateStr)->translatedFormat('d M');
                $data[] = (int) $row->getMetricValues()[0]->getValue();
            }

            return [
                'labels' => $labels,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            \Log::error('GA Daily Sessions fetch failed: '.$e->getMessage());

            return ['labels' => [], 'data' => []];
        }
    }

    public function getTrafficOverview(): array
    {
        if (! $this->isConnected()) {
            return ['sessions' => 0, 'sessions_yesterday' => 0];
        }

        try {
            $propertyId = SeoHelper::getSetting('google_property_id');
            if (! $propertyId) {
                return ['sessions' => 0, 'sessions_yesterday' => 0];
            }

            $request = new RunReportRequest;
            $request->setDateRanges([
                new DateRange(['start_date' => 'today', 'end_date' => 'today']),
                new DateRange(['start_date' => 'yesterday', 'end_date' => 'yesterday']),
            ]);
            $request->setMetrics([new Metric(['name' => 'sessions'])]);

            $response = $this->analytics->properties->runReport("properties/$propertyId", $request);

            // Simplified logic to extract data
            $rows = $response->getRows() ?? [];
            $today = $rows[0]?->getMetricValues()[0]?->getValue() ?? 0;
            $yesterday = $rows[1]?->getMetricValues()[0]?->getValue() ?? 0;

            return [
                'sessions' => $today,
                'sessions_yesterday' => $yesterday,
            ];
        } catch (\Exception $e) {
            return ['sessions' => 0, 'sessions_yesterday' => 0];
        }
    }
}
