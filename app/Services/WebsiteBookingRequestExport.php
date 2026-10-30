<?php

namespace App\Services;

use App\Filament\Resources\WebsiteBookingRequestResource;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WebsiteBookingRequestExport
{
    public function spreadsheet(Builder $query): Spreadsheet
    {
        abort_unless(WebsiteBookingRequestResource::canViewAny(), 403);
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setRightToLeft(true);
        $row = 1;
        $write = function (array $values) use ($sheet, &$row): void {
            foreach ($values as $index => $value) {
                // Explicit strings preserve phone zeroes and prevent spreadsheet formula execution.
                $sheet->setCellValueExplicit([$index + 1, $row], (string) $value, DataType::TYPE_STRING);
            }
            $row++;
        };
        $write(['الرقم', 'الاسم', 'الهاتف', 'تاريخ الزيارة', 'الخدمة', 'الفرع', 'الحالة', 'ملاحظات الإدارة', 'المصدر', 'وقت الطلب', 'نوع الزيارة', 'فترة الزيارة المعروضة', 'وقت تأكيد قراءة الموعد', 'الساعة المختارة (القاهرة)']);
        foreach ((clone $query)->cursor() as $record) {
            $write([$record->id, $record->patient_name, $record->patient_phone, $record->booking_date->toDateString(), $record->service_name, $record->branch_name, $record::STATUSES[$record->status] ?? $record->status, $record->admin_notes, $record->source_host, $record->created_at->timezone('Africa/Cairo')->format('Y-m-d H:i:s'), $record::VISIT_TYPES[$record->visit_type] ?? '', $record->visit_window, $record->visit_window_acknowledged_at?->timezone('Africa/Cairo')->format('Y-m-d H:i:s'), $record->booking_time]);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:N'.($row - 1));
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setWidth(24);
        }

        return $book;
    }

    public function download(Builder $query)
    {
        $book = $this->spreadsheet($query);

        return response()->streamDownload(function () use ($book): void {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'website-booking-requests-'.now()->format('Y-m-d').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
    }
}
