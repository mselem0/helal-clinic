<?php

namespace Modules\PatientCore\Filament\Resources\PatientResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Clinical\Models\MedicalHistoryItem;
use Modules\Clinical\Models\PatientMedication;
use Modules\Clinical\Models\Prescription;
use Modules\Clinical\Models\PrescriptionItem;
use Modules\PatientCore\Filament\Resources\PatientResource;
use Modules\PatientCore\Models\Patient;
use Modules\PatientCore\Models\VisitRecord;
use Nwidart\Modules\Facades\Module;

class ViewPatientProfile extends Page
{
    use InteractsWithRecord;

    protected static string $resource = PatientResource::class;

    protected static string $view = 'filament.resources.patient-resource.pages.view-patient-profile';

    public string $activeTab = 'summary';

    // State for Visit Modal
    public bool $isVisitModalOpen = false;

    public ?int $editingVisitId = null;

    public string $visitDate = '';

    public string $chiefComplaint = '';

    public string $diagnosis = '';

    public string $examinationNotes = '';

    public string $treatmentPlan = '';

    public string $followUpDate = '';

    public array $prescriptionItems = []; // List of ['medication_name' => '', 'dosage' => '', 'frequency' => '', 'duration' => '', 'instructions' => '']

    public string $prescriptionNotes = '';

    // State for Medical History Modal
    public bool $isHistoryModalOpen = false;

    public ?int $editingHistoryId = null;

    public string $historyType = 'chronic_disease'; // chronic_disease, allergy, surgery, family_history, other

    public string $historyTitle = '';

    public string $historyDescription = '';

    public string $historyDiagnosedAt = '';

    public string $historySeverity = 'mild'; // mild, moderate, severe

    public bool $historyIsActive = true;

    public string $historyNotes = '';

    // State for Current Medication Modal
    public bool $isMedicationModalOpen = false;

    public ?int $editingMedicationId = null;

    public string $medicationName = '';

    public string $medicationDosage = '';

    public string $medicationFrequency = '';

    public string $medicationStartDate = '';

    public string $medicationEndDate = '';

    public string $medicationPrescribedBy = '';

    public bool $medicationIsActive = true;

    public string $medicationNotes = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        Gate::authorize('view', $this->record);
        $this->visitDate = now()->format('Y-m-d');
        $this->historyDiagnosedAt = now()->format('Y-m-d');
        $this->medicationStartDate = now()->format('Y-m-d');
    }

    public function getTitle(): string
    {
        return 'الملف الطبي للمريض: '.$this->record->name;
    }

    public function setActiveTab(string $tab): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('view', $this->record);
        $this->activeTab = $tab;
    }

    // --- VISIT METHODS ---

    public function openNewVisitModal(): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        $this->resetVisitForm();
        $this->isVisitModalOpen = true;
    }

    public function openEditVisitModal(int $id): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        $this->resetVisitForm();
        $visit = VisitRecord::where('patient_id', $this->record->id)->findOrFail($id);
        $this->editingVisitId = $visit->id;
        $this->visitDate = $visit->visit_date ? $visit->visit_date->format('Y-m-d') : '';
        $this->chiefComplaint = $visit->chief_complaint ?? '';
        $this->diagnosis = $visit->diagnosis ?? '';
        $this->examinationNotes = $visit->examination_notes ?? '';
        $this->treatmentPlan = $visit->treatment_plan ?? '';
        $this->followUpDate = $visit->follow_up_date ? $visit->follow_up_date->format('Y-m-d') : '';

        // Load prescription if exists
        $prescription = $visit->prescriptions()->first();
        if ($prescription) {
            $this->prescriptionNotes = $prescription->notes ?? '';
            foreach ($prescription->items as $item) {
                $this->prescriptionItems[] = [
                    'medication_name' => $item->medication_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'duration' => $item->duration,
                    'instructions' => $item->instructions,
                ];
            }
        }

        $this->isVisitModalOpen = true;
    }

    public function addPrescriptionItem(): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        $this->prescriptionItems[] = [
            'medication_name' => '',
            'dosage' => '',
            'frequency' => '',
            'duration' => '',
            'instructions' => '',
        ];
    }

    public function removePrescriptionItem(int $index): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        unset($this->prescriptionItems[$index]);
        $this->prescriptionItems = array_values($this->prescriptionItems);
    }

    public function saveVisit(): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        DB::transaction(function (): void {
            $this->validate([
                'visitDate' => 'required|date',
                'chiefComplaint' => 'required|string',
                'prescriptionItems.*.medication_name' => 'required_with:prescriptionItems.*.dosage|nullable|string',
            ], [
                'visitDate.required' => 'تاريخ الزيارة مطلوب',
                'chiefComplaint.required' => 'الشكوى الرئيسية مطلوبة',
                'prescriptionItems.*.medication_name.required_with' => 'اسم الدواء مطلوب في حالة تحديد الجرعة',
            ]);

            if ($this->editingVisitId) {
                $visit = VisitRecord::where('patient_id', $this->record->id)->findOrFail($this->editingVisitId);
                $visit->update([
                    'visit_date' => $this->visitDate,
                    'chief_complaint' => $this->chiefComplaint,
                    'diagnosis' => $this->diagnosis,
                    'examination_notes' => $this->examinationNotes,
                    'treatment_plan' => $this->treatmentPlan,
                    'follow_up_date' => $this->followUpDate ?: null,
                ]);

                // Recreate prescription
                $visit->prescriptions()->delete();
            } else {
                $visit = VisitRecord::create([
                    'patient_id' => $this->record->id,
                    'visit_date' => $this->visitDate,
                    'chief_complaint' => $this->chiefComplaint,
                    'diagnosis' => $this->diagnosis,
                    'examination_notes' => $this->examinationNotes,
                    'treatment_plan' => $this->treatmentPlan,
                    'follow_up_date' => $this->followUpDate ?: null,
                    'created_by' => auth()->id(),
                ]);
            }

            // Save prescription if items exist
            $hasItems = false;
            foreach ($this->prescriptionItems as $item) {
                if (! empty($item['medication_name'])) {
                    $hasItems = true;
                    break;
                }
            }

            if ($hasItems || ! empty($this->prescriptionNotes)) {
                $prescription = Prescription::create([
                    'patient_id' => $this->record->id,
                    'visit_record_id' => $visit->id,
                    'prescribed_at' => $this->visitDate,
                    'notes' => $this->prescriptionNotes,
                    'created_by' => auth()->id(),
                ]);

                foreach ($this->prescriptionItems as $item) {
                    if (! empty($item['medication_name'])) {
                        PrescriptionItem::create([
                            'prescription_id' => $prescription->id,
                            'medication_name' => $item['medication_name'],
                            'dosage' => $item['dosage'],
                            'frequency' => $item['frequency'],
                            'duration' => $item['duration'],
                            'instructions' => $item['instructions'],
                        ]);
                    }
                }
            }

            Notification::make()
                ->title($this->editingVisitId ? 'تم تعديل الزيارة بنجاح' : 'تم تسجيل الزيارة بنجاح')
                ->success()
                ->send();

            $this->isVisitModalOpen = false;
            $this->resetVisitForm();
            $this->record = Patient::find($this->record->id); // Refresh patient
        });
    }

    public function deleteVisit(int $id): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        VisitRecord::where('patient_id', $this->record->id)->findOrFail($id)->delete();
        Notification::make()
            ->title('تم حذف الزيارة')
            ->success()
            ->send();
        $this->record = Patient::find($this->record->id);
    }

    private function resetVisitForm(): void
    {
        $this->editingVisitId = null;
        $this->visitDate = now()->format('Y-m-d');
        $this->chiefComplaint = '';
        $this->diagnosis = '';
        $this->examinationNotes = '';
        $this->treatmentPlan = '';
        $this->followUpDate = '';
        $this->prescriptionItems = [];
        $this->prescriptionNotes = '';
    }

    // --- MEDICAL HISTORY METHODS ---

    public function openNewHistoryModal(string $type = 'chronic_disease'): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        $this->resetHistoryForm();
        $this->historyType = $type;
        $this->isHistoryModalOpen = true;
    }

    public function openEditHistoryModal(int $id): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        $this->resetHistoryForm();
        $item = MedicalHistoryItem::where('patient_id', $this->record->id)->findOrFail($id);
        $this->editingHistoryId = $item->id;
        $this->historyType = $item->type;
        $this->historyTitle = $item->title;
        $this->historyDescription = $item->description ?? '';
        $this->historyDiagnosedAt = $item->diagnosed_at ? $item->diagnosed_at->format('Y-m-d') : '';
        $this->historySeverity = $item->severity ?? 'mild';
        $this->historyIsActive = $item->is_active;
        $this->historyNotes = $item->notes ?? '';
        $this->isHistoryModalOpen = true;
    }

    public function saveHistory(): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        DB::transaction(function (): void {
            $this->validate([
                'historyTitle' => 'required|string',
                'historyType' => 'required|in:chronic_disease,allergy,surgery,family_history,other',
            ], [
                'historyTitle.required' => 'العنوان مطلوب',
            ]);

            if ($this->editingHistoryId) {
                $item = MedicalHistoryItem::where('patient_id', $this->record->id)->findOrFail($this->editingHistoryId);
                $item->update([
                    'type' => $this->historyType,
                    'title' => $this->historyTitle,
                    'description' => $this->historyDescription,
                    'diagnosed_at' => $this->historyDiagnosedAt ?: null,
                    'severity' => $this->historySeverity ?: null,
                    'is_active' => $this->historyIsActive,
                    'notes' => $this->historyNotes,
                ]);
            } else {
                MedicalHistoryItem::create([
                    'patient_id' => $this->record->id,
                    'type' => $this->historyType,
                    'title' => $this->historyTitle,
                    'description' => $this->historyDescription,
                    'diagnosed_at' => $this->historyDiagnosedAt ?: null,
                    'severity' => $this->historySeverity ?: null,
                    'is_active' => $this->historyIsActive,
                    'notes' => $this->historyNotes,
                ]);
            }

            Notification::make()
                ->title($this->editingHistoryId ? 'تم تعديل التاريخ المرضي' : 'تم إضافة التاريخ المرضي')
                ->success()
                ->send();

            $this->isHistoryModalOpen = false;
            $this->resetHistoryForm();
            $this->record = Patient::find($this->record->id);
        });
    }

    public function deleteHistory(int $id): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        MedicalHistoryItem::where('patient_id', $this->record->id)->findOrFail($id)->delete();
        Notification::make()
            ->title('تم حذف العنصر')
            ->success()
            ->send();
        $this->record = Patient::find($this->record->id);
    }

    private function resetHistoryForm(): void
    {
        $this->editingHistoryId = null;
        $this->historyTitle = '';
        $this->historyDescription = '';
        $this->historyDiagnosedAt = now()->format('Y-m-d');
        $this->historySeverity = 'mild';
        $this->historyIsActive = true;
        $this->historyNotes = '';
    }

    // --- CURRENT MEDICATION METHODS ---

    public function openNewMedicationModal(): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        $this->resetMedicationForm();
        $this->isMedicationModalOpen = true;
    }

    public function openEditMedicationModal(int $id): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        $this->resetMedicationForm();
        $med = PatientMedication::where('patient_id', $this->record->id)->findOrFail($id);
        $this->editingMedicationId = $med->id;
        $this->medicationName = $med->medication_name;
        $this->medicationDosage = $med->dosage ?? '';
        $this->medicationFrequency = $med->frequency ?? '';
        $this->medicationStartDate = $med->start_date ? $med->start_date->format('Y-m-d') : '';
        $this->medicationEndDate = $med->end_date ? $med->end_date->format('Y-m-d') : '';
        $this->medicationPrescribedBy = $med->prescribed_by ?? '';
        $this->medicationIsActive = $med->is_active;
        $this->medicationNotes = $med->notes ?? '';
        $this->isMedicationModalOpen = true;
    }

    public function saveMedication(): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        DB::transaction(function (): void {
            $this->validate([
                'medicationName' => 'required|string',
            ], [
                'medicationName.required' => 'اسم الدواء مطلوب',
            ]);

            if ($this->editingMedicationId) {
                $med = PatientMedication::where('patient_id', $this->record->id)->findOrFail($this->editingMedicationId);
                $med->update([
                    'medication_name' => $this->medicationName,
                    'dosage' => $this->medicationDosage,
                    'frequency' => $this->medicationFrequency,
                    'start_date' => $this->medicationStartDate ?: null,
                    'end_date' => $this->medicationEndDate ?: null,
                    'prescribed_by' => $this->medicationPrescribedBy,
                    'is_active' => $this->medicationIsActive,
                    'notes' => $this->medicationNotes,
                ]);
            } else {
                PatientMedication::create([
                    'patient_id' => $this->record->id,
                    'medication_name' => $this->medicationName,
                    'dosage' => $this->medicationDosage,
                    'frequency' => $this->medicationFrequency,
                    'start_date' => $this->medicationStartDate ?: null,
                    'end_date' => $this->medicationEndDate ?: null,
                    'prescribed_by' => $this->medicationPrescribedBy,
                    'is_active' => $this->medicationIsActive,
                    'notes' => $this->medicationNotes,
                ]);
            }

            Notification::make()
                ->title($this->editingMedicationId ? 'تم تعديل الدواء الحالي' : 'تم إضافة الدواء الحالي')
                ->success()
                ->send();

            $this->isMedicationModalOpen = false;
            $this->resetMedicationForm();
            $this->record = Patient::find($this->record->id);
        });
    }

    public function toggleMedicationStatus(int $id): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        $med = PatientMedication::where('patient_id', $this->record->id)->findOrFail($id);
        $med->update(['is_active' => ! $med->is_active]);
        Notification::make()
            ->title('تم تحديث حالة الدواء')
            ->success()
            ->send();
        $this->record = Patient::find($this->record->id);
    }

    public function deleteMedication(int $id): void
    {
        abort_unless(Module::find('PatientCore')?->isEnabled() && Module::find('Clinical')?->isEnabled(), 404);
        Gate::authorize('update', $this->record);
        PatientMedication::where('patient_id', $this->record->id)->findOrFail($id)->delete();
        Notification::make()
            ->title('تم حذف الدواء')
            ->success()
            ->send();
        $this->record = Patient::find($this->record->id);
    }

    private function resetMedicationForm(): void
    {
        $this->editingMedicationId = null;
        $this->medicationName = '';
        $this->medicationDosage = '';
        $this->medicationFrequency = '';
        $this->medicationStartDate = now()->format('Y-m-d');
        $this->medicationEndDate = '';
        $this->medicationPrescribedBy = '';
        $this->medicationIsActive = true;
        $this->medicationNotes = '';
    }
}
