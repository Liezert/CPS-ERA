<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Division;
use App\Models\User;
use App\Services\EmployeeAccountService;
use Database\Seeders\CustomProductionUserSeeder;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Manajemen akun karyawan oleh admin: form panel, import CSV, dan kata sandi sementara yang wajib
 * diganti saat login pertama.
 */
class EmployeeAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Division $produksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->admin = User::factory()->create(['division_id' => $this->produksi->id]);
        $this->admin->assignRole('admin');
    }

    private function employeeWithTemporaryPassword(string $role = 'employee'): User
    {
        return app(EmployeeAccountService::class)->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@caturpilar.com',
            'employee_id' => 'CPS-01001',
            'division_id' => $this->produksi->id,
            'jabatan' => 'Operator',
            'role' => $role,
        ]);
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_new_account_gets_temporary_password_role_and_verified_email(): void
    {
        $user = $this->employeeWithTemporaryPassword('supervisor');

        $this->assertTrue(Hash::check(EmployeeAccountService::TEMPORARY_PASSWORD, $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('supervisor'));
    }

    public function test_user_with_temporary_password_is_locked_to_the_change_password_page(): void
    {
        $user = $this->employeeWithTemporaryPassword('admin');

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('password.change'));
        $this->actingAs($user)->get(route('ba.index'))->assertRedirect(route('password.change'));
        $this->actingAs($user)->get('/admin')->assertRedirect(route('password.change'));
        $this->actingAs($user)->getJson('/api/ba-incidents')->assertForbidden();

        $this->actingAs($user)->get(route('password.change'))->assertOk()->assertSee('Ganti Kata Sandi');
        $this->actingAs($user)->post(route('logout'))->assertRedirect();
    }

    public function test_changing_the_temporary_password_unlocks_the_account(): void
    {
        $user = $this->employeeWithTemporaryPassword();

        $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'salah-total',
            'password' => 'SandiBaru2026!',
            'password_confirmation' => 'SandiBaru2026!',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => EmployeeAccountService::TEMPORARY_PASSWORD,
            'password' => EmployeeAccountService::TEMPORARY_PASSWORD,
            'password_confirmation' => EmployeeAccountService::TEMPORARY_PASSWORD,
        ])->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);

        $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => EmployeeAccountService::TEMPORARY_PASSWORD,
            'password' => 'SandiBaru2026!',
            'password_confirmation' => 'SandiBaru2026!',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('SandiBaru2026!', $user->password));
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_users_without_the_flag_are_not_affected(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('password.change'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_creates_employee_from_the_panel(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Siti Aminah',
                'email' => 'siti@caturpilar.com',
                'employee_id' => 'CPS-01002',
                'division_id' => $this->produksi->id,
                'jabatan' => 'QC Inspector',
                'role' => 'quality',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'siti@caturpilar.com')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->hasRole('quality'));
        $this->assertTrue(Hash::check(EmployeeAccountService::TEMPORARY_PASSWORD, $user->password));
    }

    public function test_panel_form_rejects_duplicate_email_and_nik(): void
    {
        $this->employeeWithTemporaryPassword();

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Duplikat',
                'email' => 'budi@caturpilar.com',
                'employee_id' => 'CPS-01001',
                'division_id' => $this->produksi->id,
                'role' => 'employee',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique', 'employee_id' => 'unique']);
    }

    /**
     * HRGA dibatasi hanya di form pelaporan CAPA, bukan di data master identitas karyawan.
     */
    public function test_hrga_division_is_allowed_for_employee_accounts(): void
    {
        $hrga = Division::where('name', Division::HRGA)->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Sari HRGA',
                'email' => 'sari@caturpilar.com',
                'employee_id' => 'CPS-01003',
                'division_id' => $hrga->id,
                'role' => 'quality',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($hrga->id, User::where('email', 'sari@caturpilar.com')->value('division_id'));

        // Tapi HRGA tetap tidak boleh jadi divisi pelapor di form CAPA.
        $this->assertFalse(Division::reportable()->whereKey($hrga->id)->exists());
    }

    public function test_admin_resets_a_forgotten_password_back_to_temporary(): void
    {
        $user = $this->employeeWithTemporaryPassword();
        $user->update(['password' => 'SandiPribadi2026!', 'must_change_password' => false]);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('resetPassword')
            ->assertHasNoActionErrors();

        $user->refresh();
        $this->assertTrue(Hash::check(EmployeeAccountService::TEMPORARY_PASSWORD, $user->password));
        $this->assertTrue($user->must_change_password);

        // Karyawan terkunci lagi sampai membuat kata sandi baru.
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('password.change'));
    }

    public function test_reset_password_is_available_from_the_user_table_row(): void
    {
        $user = $this->employeeWithTemporaryPassword();
        $user->update(['password' => 'SandiPribadi2026!', 'must_change_password' => false]);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertTableActionVisible('resetPassword', $user)
            ->callTableAction('resetPassword', $user);

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_admin_edits_employee_role_without_touching_the_password(): void
    {
        $user = $this->employeeWithTemporaryPassword();
        $passwordHash = $user->password;

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertFormSet(['role' => 'employee', 'employee_id' => 'CPS-01001'])
            ->fillForm(['role' => 'supervisor', 'jabatan' => 'Kepala Shift'])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertTrue($user->hasRole('supervisor'));
        $this->assertFalse($user->hasRole('employee'));
        $this->assertSame('Kepala Shift', $user->jabatan);
        $this->assertSame($passwordHash, $user->password);
        $this->assertTrue($user->must_change_password);
    }

    public function test_non_admin_cannot_manage_employee_accounts(): void
    {
        $supervisor = User::factory()->create(['division_id' => $this->produksi->id]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor)->get('/admin/users/create')->assertForbidden();
    }

    public function test_csv_import_creates_every_row_with_temporary_password(): void
    {
        // Ekspor Excel berlokal Indonesia: BOM UTF-8 dan pemisah titik koma.
        $path = $this->csv("\xEF\xBB\xBFNama;Email;NIK;Nama Divisi;Jabatan;Role\n"
            ."Andi;andi@caturpilar.com;CPS-02001;produksi;Operator;employee\n"
            ."\n"
            ."Rina;rina@caturpilar.com;CPS-02002;Quality Control;;Supervisor\n"
            ."Sari;sari@caturpilar.com;CPS-02003;HRGA;Staf HR;quality\n");

        $result = app(EmployeeAccountService::class)->importCsv($path);

        $this->assertSame(['created' => 3, 'errors' => []], $result);
        $this->assertSame(Division::HRGA, User::where('email', 'sari@caturpilar.com')->firstOrFail()->division->name);
        $rina = User::where('email', 'rina@caturpilar.com')->firstOrFail();
        $this->assertTrue($rina->hasRole('supervisor'));
        $this->assertNull($rina->jabatan);
        $this->assertTrue($rina->must_change_password);
        $this->assertSame('Quality Control', $rina->division->name);
    }

    public function test_csv_import_is_all_or_nothing_and_reports_each_bad_row(): void
    {
        $this->employeeWithTemporaryPassword();

        $path = $this->csv("Nama,Email,NIK,Nama Divisi,Jabatan,Role\n"
            ."Andi,andi@caturpilar.com,CPS-02001,Produksi,Operator,employee\n"
            ."Budi Lagi,budi@caturpilar.com,CPS-02002,Produksi,,employee\n"
            ."Cici,cici@caturpilar.com,CPS-02003,Divisi Fiktif,,employee\n"
            ."Eko,eko@caturpilar.com,CPS-02005,Produksi,,direktur\n"
            ."Andi Kembar,andi@caturpilar.com,CPS-02006,Produksi,,employee\n");

        $result = app(EmployeeAccountService::class)->importCsv($path);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, User::where('email', 'like', '%@caturpilar.com')->count());

        $errors = implode("\n", $result['errors']);
        $this->assertStringContainsString('Baris 3:', $errors);
        $this->assertStringContainsString('Baris 4: divisi "Divisi Fiktif" tidak dikenal', $errors);
        $this->assertStringContainsString('Baris 5:', $errors);
        $this->assertStringContainsString('Baris 6: email andi@caturpilar.com dobel dengan baris 2', $errors);
        $this->assertStringNotContainsString('Baris 2:', $errors);
    }

    public function test_csv_import_rejects_file_without_required_columns(): void
    {
        $result = app(EmployeeAccountService::class)->importCsv($this->csv("Nama,Email\nAndi,andi@caturpilar.com\n"));

        $this->assertSame(0, $result['created']);
        $this->assertStringContainsString('nik, nama divisi, jabatan, role', $result['errors'][0]);
    }

    public function test_import_action_in_the_panel_creates_accounts_and_discards_the_file(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('karyawan.csv', "Nama,Email,NIK,Nama Divisi,Jabatan,Role\n"
            ."Andi,andi@caturpilar.com,CPS-02001,Produksi,Operator,employee\n");

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callAction('importCsv', data: ['file' => $file])
            ->assertHasNoActionErrors();

        $this->assertTrue(User::where('email', 'andi@caturpilar.com')->firstOrFail()->must_change_password);
        $this->assertSame([], Storage::disk('local')->allFiles('imports'));
    }

    public function test_production_seeder_template_is_empty_and_not_registered(): void
    {
        $before = User::count();

        $this->seed(CustomProductionUserSeeder::class);

        $this->assertSame($before, User::count());
        $this->assertStringNotContainsString(
            'CustomProductionUserSeeder',
            file_get_contents(database_path('seeders/DatabaseSeeder.php'))
        );
    }
}
