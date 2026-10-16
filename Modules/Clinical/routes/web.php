<?php
use Illuminate\Support\Facades\{Route, Gate};
use Modules\Clinical\Models\{Prescription, PrintTemplate};
use App\Support\ClinicFeatures;
if (ClinicFeatures::enabled('Clinical')) {
    Route::middleware('auth')->group(function (): void {
        Route::get('/admin/prescriptions/{prescription}/print', function (Prescription $prescription) {
            Gate::authorize('view',$prescription->patient);
            $template=PrintTemplate::where('is_default',true)->first() ?? PrintTemplate::first();
            return view('filament.resources.patient-resource.pages.print-prescription',compact('prescription','template')+['isPreview'=>false]);
        })->name('admin.prescriptions.print');
        Route::get('/admin/print-templates/{printTemplate}/preview', function (PrintTemplate $printTemplate) {
            Gate::authorize('view',$printTemplate);
            $patient=new \Modules\PatientCore\Models\Patient(['name'=>'مريضة للمعاينة','phone'=>'01000000000']);
            $prescription=new Prescription(['prescribed_at'=>now()]);
            $prescription->setRelation('patient',$patient)->setRelation('items',collect());
            return view('filament.resources.patient-resource.pages.print-prescription',['prescription'=>$prescription,'template'=>$printTemplate,'isPreview'=>true]);
        })->name('admin.print-templates.preview');
    });
}
