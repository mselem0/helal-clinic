<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>روشتة طبية - {{ $prescription->patient->name }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family={{ $template ? $template->font_family : "Almarai" }}:wght@400;700&display=swap');
        
        :root {
            --primary-color: {{ $template ? $template->primary_color : "#002855" }};
            --secondary-color: {{ $template ? $template->secondary_color : "#00B4D8" }};
            --font-family: '{{ $template ? $template->font_family : "Almarai" }}', sans-serif;
        }

        body {
            font-family: var(--font-family);
            margin: 0;
            padding: 40px;
            color: #333;
            background-color: #f1f5f9;
        }

        .prescription-page {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            padding: 30px;
            position: relative;
            min-height: 297mm; /* Standard A4 ratio for preview, adjusts dynamically */
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        /* Preview banner */
        .preview-banner {
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preview-banner button {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: white;
            padding: 5px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-family: var(--font-family);
            font-size: 12px;
            font-weight: bold;
            transition: all 0.2s;
        }

        .preview-banner button:hover {
            background-color: white;
            color: #4f46e5;
        }

        /* Margin adjustments for printer */
        @page {
            size: A5 portrait;
            margin-top: {{ $template ? $template->margin_top : 0 }}mm;
            margin-bottom: {{ $template ? $template->margin_bottom : 0 }}mm;
            margin-left: {{ $template ? $template->margin_left : 0 }}mm;
            margin-right: {{ $template ? $template->margin_right : 0 }}mm;
        }

        /* Dynamic Margins for Screen View */
        .margin-wrapper {
            margin-top: {{ ($template && $template->margin_top > 0) ? $template->margin_top . 'mm' : '0' }};
            margin-bottom: {{ ($template && $template->margin_bottom > 0) ? $template->margin_bottom . 'mm' : '0' }};
            margin-left: {{ ($template && $template->margin_left > 0) ? $template->margin_left . 'mm' : '0' }};
            margin-right: {{ ($template && $template->margin_right > 0) ? $template->margin_right . 'mm' : '0' }};
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-grow: 1;
        }

        /* Letterhead placeholder in Preview Mode */
        .letterhead-placeholder {
            border: 2px dashed #94a3b8;
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .letterhead-placeholder-bottom {
            border: 2px dashed #94a3b8;
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            margin-top: 25px;
        }

        /* Header design matching brand */
        .clinic-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px double var(--primary-color);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .clinic-logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .clinic-logo-img {
            max-height: 80px;
            max-width: 150px;
            object-fit: contain;
        }

        .clinic-logo-text {
            color: var(--primary-color);
        }

        .clinic-logo-text h1 {
            font-size: 22px;
            margin: 0;
            font-weight: 700;
            line-height: 1.4;
        }

        .clinic-logo-text p {
            font-size: 13px;
            margin: 5px 0 0 0;
            color: var(--secondary-color);
            font-weight: bold;
        }

        .doctor-info-en {
            text-align: left;
            color: #4b5563;
            font-size: 12px;
            line-height: 1.6;
            direction: ltr;
        }

        /* Patient details bar */
        .patient-details-bar {
            background-color: {{ ($template && !$template->show_patient_box) ? 'transparent' : '#f8fafc' }};
            border: {{ ($template && !$template->show_patient_box) ? 'none' : '1px solid #e2e8f0' }};
            border-bottom: {{ ($template && !$template->show_patient_box) ? '2px solid var(--primary-color)' : '1px solid #e2e8f0' }};
            padding: {{ ($template && !$template->show_patient_box) ? '10px 0' : '15px' }};
            border-radius: {{ ($template && !$template->show_patient_box) ? '0' : '8px' }};
            display: grid;
            grid-template-cols: 2fr 1fr 1fr;
            gap: 15px;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .patient-details-bar div {
            display: flex;
            gap: 5px;
        }

        .label {
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Rx symbol */
        .rx-symbol {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        /* Medications Table */
        .medications-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .medications-table th {
            border-bottom: 2px solid var(--primary-color);
            text-align: right;
            padding: 10px;
            font-size: 14px;
            color: var(--primary-color);
            font-weight: bold;
        }

        .medications-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .med-name {
            font-weight: 700;
            color: #111;
            font-size: 15px;
        }

        .med-details {
            color: #4b5563;
        }

        .med-instructions {
            font-style: italic;
            color: #6b7280;
            font-size: 12px;
            margin-top: 3px;
        }

        /* Watermark Background */
        .watermark {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 44px;
            font-weight: bold;
            color: rgba(0, 40, 85, 0.04);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            user-select: none;
            text-align: center;
        }

        /* Notes and Footer */
        .prescription-notes {
            background-color: #f8fafc;
            border-right: 4px solid var(--secondary-color);
            padding: 12px;
            font-size: 13px;
            margin-top: auto;
            margin-bottom: 30px;
            border-radius: 0 6px 6px 0;
            position: relative;
            z-index: 1;
        }

        .signature-area {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: auto;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            font-size: 12px;
            color: #666;
            position: relative;
            z-index: 1;
        }

        .signature-line {
            text-align: center;
        }

        .signature-line-draw {
            width: 150px;
            border-bottom: 1px solid var(--primary-color);
            margin-bottom: 8px;
        }

        /* Clinic Custom Footer Details */
        .custom-footer-text {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            margin-top: 20px;
            line-height: 1.6;
        }

        .footer-col-ar {
            text-align: right;
            white-space: pre-line;
        }

        .footer-col-en {
            text-align: left;
            direction: ltr;
            white-space: pre-line;
        }

        /* Print styles */
        @media print {
            body {
                padding: 0;
                background-color: #fff;
            }
            .prescription-page {
                box-shadow: none;
                padding: 0;
                min-height: 100vh;
                border-radius: 0;
            }
            .preview-banner,
            .letterhead-placeholder,
            .letterhead-placeholder-bottom {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    @if($isPreview)
        <div class="preview-banner">
            <div>
                <span>👁️ شاشة معاينة قالب الروشتة: </span>
                <span style="text-decoration: underline;">{{ $template->name }}</span>
            </div>
            <div>
                <button onclick="window.print()">🖨️ طباعة تجريبية</button>
                <button onclick="window.close()">❌ إغلاق المعاينة</button>
            </div>
        </div>
    @endif

    <div class="prescription-page">
        
        <!-- Watermark -->
        @if($template && $template->show_watermark && $template->watermark_text)
            <div class="watermark">{{ $template->watermark_text }}</div>
        @endif

        <div class="margin-wrapper">
            
            <div>
                <!-- Pre-printed Header Space Reservation in Preview Mode -->
                @if($isPreview && $template && $template->hide_header)
                    <div class="letterhead-placeholder">
                        <strong>🏷️ ترويسة مطبوعة مسبقاً (Pre-printed Header Area)</strong>
                        <span>تم حجز مسافة علوية قدرها ({{ $template->margin_top ?: 0 }} mm) للورق الجاهز. سيتم إخفاء هذا الهيكل بالكامل عند الطباعة الفعلية.</span>
                    </div>
                @endif

                <!-- Header -->
                @if(!$template || !$template->hide_header)
                    @if($template && $template->header_image_path)
                        <div class="full-width-header-image" style="margin-bottom: 25px; text-align: center; width: 100%;">
                            <img src="{{ asset('storage/' . $template->header_image_path) }}" alt="Clinic Header" style="width: 100%; max-height: 180px; object-fit: contain; display: block; margin: 0 auto;">
                        </div>
                    @else
                        <div class="clinic-header">
                            <div class="clinic-logo-area">
                                @if($template && $template->logo_path)
                                    <img src="{{ asset('storage/' . $template->logo_path) }}" alt="Logo" class="clinic-logo-img">
                                @endif
                                <div class="clinic-logo-text">
                                    <h1>
                                        @if($template && $template->header_left_ar)
                                            {!! nl2br(e($template->header_left_ar)) !!}
                                        @else
                                            عيادة الأستاذ الدكتور محمد هلال
                                        @endif
                                    </h1>
                                    @if(!$template || !$template->header_left_ar)
                                        <p>استشاري طب وجراحة أمراض النساء والتوليد والمناظير والحقن المجهري</p>
                                    @endif
                                </div>
                            </div>
                            <div class="doctor-info-en">
                                @if($template && $template->header_right_en)
                                    {!! nl2br(e($template->header_right_en)) !!}
                                @else
                                    <strong>Dr. Mohamed Helal Shahata Clinic</strong><br>
                                    Andrology & Male Infertility Specialist<br>
                                    Tel: 01234567890 / 01098765432
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Patient details -->
                <div class="patient-details-bar">
                    <div>
                        <span class="label">اسم المريض:</span>
                        <span>{{ $prescription->patient->name }}</span>
                    </div>
                    <div>
                        <span class="label">العمر:</span>
                        <span>{{ $prescription->patient->age ? $prescription->patient->age . ' سنة' : 'غير محدد' }}</span>
                    </div>
                    <div>
                        <span class="label">التاريخ:</span>
                        <span>{{ $prescription->prescribed_at ? $prescription->prescribed_at->format('Y-m-d') : now()->format('Y-m-d') }}</span>
                    </div>
                </div>

                <div class="rx-symbol">℞</div>

                <!-- Table of meds -->
                <table class="medications-table">
                    <thead>
                        <tr>
                            <th style="width: 45%">اسم الدواء Medication</th>
                            <th style="width: 15%">الجرعة Dosage</th>
                            <th style="width: 20%">التكرار Frequency</th>
                            <th style="width: 20%">المدة Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prescription->items as $item)
                            <tr>
                                <td>
                                    <div class="med-name">{{ $item->medication_name }}</div>
                                    @if($item->instructions)
                                        <div class="med-instructions">{{ $item->instructions }}</div>
                                    @endif
                                </td>
                                <td class="med-details">{{ $item->dosage ?: '-' }}</td>
                                <td class="med-details">{{ $item->frequency ?: '-' }}</td>
                                <td class="med-details">{{ $item->duration ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                <!-- Prescription Notes -->
                @if($prescription->notes)
                    <div class="prescription-notes">
                        <span class="label block mb-1">تعليمات الطبيب الإضافية:</span>
                        <span>{{ $prescription->notes }}</span>
                    </div>
                @endif

                <!-- Footer / Signature -->
                @if(!$template || $template->show_signature)
                    <div class="signature-area">
                        <div>
                            تمنياتنا بالشفاء العاجل والذكر الطيب الدائم 🌟
                        </div>
                        <div class="signature-line">
                            <div class="signature-line-draw"></div>
                            <span>توقيع وختم الطبيب المعالج</span>
                        </div>
                    </div>
                @endif

                <!-- Pre-printed Footer Space Reservation in Preview Mode -->
                @if($isPreview && $template && $template->hide_footer)
                    <div class="letterhead-placeholder-bottom">
                        <strong>🏷️ تذييل مطبوع مسبقاً (Pre-printed Footer Area)</strong><br>
                        <span>محجوز له مسافة سفلية قدرها ({{ $template->margin_bottom ?: 0 }} mm).</span>
                    </div>
                @endif

                <!-- Clinic Details Footer -->
                @if(!$template || !$template->hide_footer)
                    @if(($template && ($template->footer_text_ar || $template->footer_text_en)) || !$template)
                        <div class="custom-footer-text">
                            <div class="footer-col-ar">
                                @if($template && $template->footer_text_ar)
                                    {!! $template->footer_text_ar !!}
                                @else
                                    المركز الطبي التخصصي - المهندسين - الجيزة<br>
                                    مواعيد الاستشارة: السبت والاثنين والأربعاء من ٦م إلى ١٠م
                                @endif
                            </div>
                            <div class="footer-col-en">
                                @if($template && $template->footer_text_en)
                                    {!! $template->footer_text_en !!}
                                @else
                                    Clinic Hotline: 19999 / Whatsapp: 01011223344<br>
                                    Web: www.drmohamedragab.com
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>

    @if(!$isPreview)
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    @endif
</body>
</html>
