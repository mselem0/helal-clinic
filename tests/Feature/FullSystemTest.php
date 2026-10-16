<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\AI\Models\AiConversation;
use Modules\Booking\Models\Booking;
use Modules\Clinical\Models\Prescription;
use Modules\PatientCore\Filament\Resources\PatientResource\Pages\ViewPatientProfile;
use Modules\PatientCore\Models\Patient;
use Modules\PatientCore\Models\VisitRecord;
use Modules\Whatsapp\Models\WhatsappLog;
use Tests\TestCase;

class FullSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed(RolesSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
    }

    public function test_all_model_relations_resolve_and_clinical_records_persist(): void
    {
        $patient = Patient::create(['name' => 'Test patient', 'phone' => '01012345678', 'gender' => 'female']);
        $visit = VisitRecord::create(['patient_id' => $patient->id, 'visit_date' => now(), 'chief_complaint' => 'Test complaint', 'created_by' => auth()->id()]);
        $prescription = Prescription::create(['patient_id' => $patient->id, 'visit_record_id' => $visit->id, 'prescribed_at' => now(), 'created_by' => auth()->id()]);
        $this->assertTrue($prescription->patient->is($patient));
        $this->assertTrue($prescription->visitRecord->is($visit));
        $this->assertTrue($prescription->creator->is(auth()->user()));
        $this->assertCount(1, $patient->visitRecords);
        $this->assertCount(1, $patient->prescriptions);
        $this->assertInstanceOf(User::class, (new AiConversation)->user()->getRelated());
        $this->assertInstanceOf(Booking::class, (new WhatsappLog)->booking()->getRelated());
    }

    public function test_profile_creates_visit_and_prescription_as_one_operation(): void
    {
        $patient = Patient::create(['name' => 'Test patient', 'phone' => '01012345678']);
        Livewire::test(ViewPatientProfile::class, ['record' => $patient->id])->call('openNewVisitModal')->set('chiefComplaint', 'Test complaint')->set('prescriptionNotes', 'Test note')->call('saveVisit')->assertHasNoErrors();
        $this->assertDatabaseCount('visit_records', 1);
        $this->assertDatabaseCount('prescriptions', 1);
    }

    public function test_profile_cannot_delete_another_patients_visit(): void
    {
        $a = Patient::create(['name' => 'A', 'phone' => '01012345678']);
        $b = Patient::create(['name' => 'B', 'phone' => '01112345678']);
        $visit = VisitRecord::create(['patient_id' => $b->id, 'visit_date' => now(), 'chief_complaint' => 'Other patient']);
        try {
            Livewire::test(ViewPatientProfile::class, ['record' => $a->id])->call('deleteVisit', $visit->id);
            $this->fail('Cross-patient action must be rejected.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(VisitRecord::class, $exception->getModel());
        }
        $this->assertModelExists($visit);
    }

    public function test_receptionist_cannot_read_or_print_medical_records(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('receptionist');
        $this->actingAs($user);
        $patient = Patient::create(['name' => 'A', 'phone' => '01012345678']);
        $prescription = Prescription::create(['patient_id' => $patient->id, 'prescribed_at' => now()]);
        $this->assertFalse(Gate::allows('view', $patient));
        $this->get('/admin/prescriptions/'.$prescription->id.'/print')->assertForbidden();
    }

    public function test_super_admin_can_open_clinical_print_view(): void
    {
        $patient = Patient::create(['name' => 'A', 'phone' => '01012345678']);
        $prescription = Prescription::create(['patient_id' => $patient->id, 'prescribed_at' => now()]);
        $this->get('/admin/prescriptions/'.$prescription->id.'/print')->assertOk()->assertSee('A');
    }
}
