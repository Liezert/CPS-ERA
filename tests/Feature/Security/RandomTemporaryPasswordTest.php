<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Division;
use App\Models\User;
use App\Services\EmployeeAccountService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit pre-launch: kata sandi sementara acak per akun (bukan 'SandiLamaDihapus2026' untuk semua), ditampilkan
 * sekali ke admin lewat modal/berkas unduhan, dan plaintext-nya tidak pernah masuk log, DB, atau session.
 */
class RandomTemporaryPasswordTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeAccountService $service;

    private Division $produksi;

    private User $admin;

    /** @var list<string> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->service = app(EmployeeAccountService::class);
        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->admin = User::factory()->create(['division_id' => $this->produksi->id]);
        $this->admin->assignRole('admin');

        Log::listen(function (MessageLogged $event): void {
            $this->logged[] = $event->message.' '.json_encode($event->context);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function accountData(string $suffix): array
    {
        return [
            'name' => "Karyawan {$suffix}",
            'email' => "karyawan{$suffix}@caturpilar.com",
            'employee_id' => "CPS-9{$suffix}",
            'division_id' => $this->produksi->id,
            'jabatan' => 'Operator',
            'role' => 'employee',
        ];
    }

    private function csvUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('karyawan.csv', "Nama,Email,NIK,Nama Divisi,Jabatan,Role\n"
            ."Andi,andi@caturpilar.com,CPS-02001,Produksi,Operator,employee\n"
            ."Rina,rina@caturpilar.com,CPS-02002,Produksi,Operator,employee\n");
    }

    private function mountedActionArguments(Testable $component, string $action): array
    {
        $mounted = collect($component->get('mountedActions'))->last();
        $this->assertSame($action, $mounted['name'] ?? null);

        return $mounted['arguments'];
    }

    private function assertPasswordNotInSession(string $password): void
    {
        $this->assertStringNotContainsString($password, json_encode(session()->all()));
    }

    public function test_each_new_account_gets_a_distinct_random_temporary_password(): void
    {
        $first = $this->service->create($this->accountData('001'));
        $second = $this->service->create($this->accountData('002'));

        $this->assertNotSame($first['password'], $second['password']);

        foreach ([$first, $second] as $account) {
            $this->assertSame(12, strlen($account['password']));
            $this->assertTrue(Hash::check($account['password'], $account['user']->password));
            $this->assertFalse(Hash::check('SandiLamaDihapus2026', $account['user']->password));
            $this->assertTrue($account['user']->must_change_password);
        }
    }

    public function test_login_with_the_old_shared_password_fails(): void
    {
        $account = $this->service->create($this->accountData('003'));

        $this->post('/login', ['email' => $account['user']->email, 'password' => 'SandiLamaDihapus2026'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_reset_issues_a_new_random_password(): void
    {
        $account = $this->service->create($this->accountData('004'));
        $account['user']->update(['password' => 'SandiPribadi2026!', 'must_change_password' => false]);

        $newPassword = $this->service->resetTemporaryPassword($account['user']);

        $this->assertNotSame($account['password'], $newPassword);
        $this->assertTrue(Hash::check($newPassword, $account['user']->fresh()->password));
        $this->assertTrue($account['user']->fresh()->must_change_password);
    }

    public function test_csv_import_returns_a_distinct_password_for_every_row(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $this->csvUpload()->getContent());

        $result = $this->service->importCsv($path);

        $this->assertSame(2, $result['created']);
        $this->assertCount(2, $result['credentials']);
        $this->assertNotSame($result['credentials'][0]['password'], $result['credentials'][1]['password']);

        foreach ($result['credentials'] as $credential) {
            $user = User::where('email', $credential['email'])->firstOrFail();
            $this->assertTrue(Hash::check($credential['password'], $user->password));
            $this->assertTrue($user->must_change_password);
        }
    }

    public function test_panel_create_shows_the_password_once_in_a_modal(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm($this->accountData('005'))
            ->call('create')
            // Nama form eksplisit: tanpa itu helper memakai skema action yang sedang terbuka (modal tanpa form).
            ->assertHasNoFormErrors(form: 'form')
            ->assertActionMounted('temporaryPassword');

        $password = $this->mountedActionArguments($component, 'temporaryPassword')['password'];
        $user = User::where('email', 'karyawan005@caturpilar.com')->firstOrFail();

        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertPasswordNotInSession($password);
    }

    public function test_panel_reset_shows_the_new_password_in_a_modal_on_edit_page_and_table_row(): void
    {
        $user = $this->service->create($this->accountData('006'))['user'];

        $edit = Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('resetPassword')
            ->assertActionMounted('temporaryPassword');
        $fromEdit = $this->mountedActionArguments($edit, 'temporaryPassword')['password'];
        $this->assertTrue(Hash::check($fromEdit, $user->fresh()->password));
        $this->assertPasswordNotInSession($fromEdit);

        $list = Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('resetPassword', $user)
            ->assertActionMounted('temporaryPassword');
        $fromTable = $this->mountedActionArguments($list, 'temporaryPassword')['password'];
        $this->assertNotSame($fromEdit, $fromTable);
        $this->assertTrue(Hash::check($fromTable, $user->fresh()->password));
        $this->assertPasswordNotInSession($fromTable);
    }

    public function test_panel_csv_import_downloads_a_file_with_the_passwords(): void
    {
        Storage::fake('local');

        $component = Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callAction('importCsv', data: ['file' => $this->csvUpload()])
            ->assertHasNoActionErrors()
            ->assertFileDownloaded();

        $rows = array_map(fn (string $line): array => str_getcsv($line, ',', '"', ''), array_filter(explode("\n", base64_decode(data_get($component->effects, 'download.content')))));
        $header = array_shift($rows);
        $this->assertContains('Kata Sandi Sementara', $header);

        $passwordColumn = array_search('Kata Sandi Sementara', $header, true);
        $emailColumn = array_search('Email', $header, true);
        $this->assertCount(2, $rows);

        foreach ($rows as $row) {
            $user = User::where('email', $row[$emailColumn])->firstOrFail();
            $this->assertTrue(Hash::check($row[$passwordColumn], $user->password));
            $this->assertPasswordNotInSession($row[$passwordColumn]);
        }
    }

    public function test_plaintext_passwords_never_reach_logs_or_the_database(): void
    {
        Storage::fake('local');

        $passwords = [];
        $account = $this->service->create($this->accountData('007'));
        $passwords[] = $account['password'];
        $passwords[] = $this->service->resetTemporaryPassword($account['user']);

        $create = Livewire::actingAs($this->admin)->test(CreateUser::class)
            ->fillForm($this->accountData('008'))->call('create');
        $passwords[] = $this->mountedActionArguments($create, 'temporaryPassword')['password'];

        $import = Livewire::actingAs($this->admin)->test(ListUsers::class)
            ->callAction('importCsv', data: ['file' => $this->csvUpload()]);
        foreach (array_slice(explode("\n", base64_decode(data_get($import->effects, 'download.content'))), 1) as $line) {
            if (trim($line) !== '') {
                $passwords[] = array_values(array_slice(str_getcsv($line, ',', '"', ''), -1))[0];
            }
        }

        $this->assertCount(5, array_unique($passwords));

        $dump = collect(Schema::getTables())
            ->map(fn (array $table): string => json_encode(DB::table($table['name'])->get()))
            ->implode("\n");
        $logs = implode("\n", $this->logged);

        foreach ($passwords as $password) {
            $this->assertStringNotContainsString($password, $dump, 'Plaintext ditemukan di database.');
            $this->assertStringNotContainsString($password, $logs, 'Plaintext ditemukan di log.');
            $this->assertPasswordNotInSession($password);
        }
    }
}
