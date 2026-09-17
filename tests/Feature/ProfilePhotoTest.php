<?php

namespace Tests\Feature;

use App\Livewire\Profile\Index as ProfileIndex;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $division = Division::firstOrFail();

        $this->user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@cps.co.id',
            'employee_id' => 'CPS-00101',
            'division_id' => $division->id,
            'jabatan' => 'Staff Produksi',
        ]);
        $this->user->assignRole('employee');

        Storage::fake('public');
    }

    /**
     * Test 1: User dapat mengunggah file foto profil baru.
     */
    public function test_user_can_upload_profile_photo(): void
    {
        $file = UploadedFile::fake()->image('my-photo.jpg', 300, 300);

        Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->set('photo', $file)
            ->call('saveProfilePhoto')
            ->assertHasNoErrors()
            ->assertSee('Foto profil berhasil diperbarui.');

        $this->user->refresh();

        $this->assertNotNull($this->user->avatar_url);
        $this->assertStringStartsWith('storage/avatars/', $this->user->avatar_url);

        $relativePath = str_replace('storage/', '', $this->user->avatar_url);
        Storage::disk('public')->assertExists($relativePath);
    }

    /**
     * Test 2: Validasi upload foto menolak file non-gambar.
     */
    public function test_profile_photo_upload_rejects_non_image_files(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->set('photo', $file)
            ->call('saveProfilePhoto')
            ->assertHasErrors(['photo']);

        $this->user->refresh();
        $this->assertNull($this->user->avatar_url);
    }

    /**
     * Test 3: Validasi upload foto menolak file berukuran lebih dari 2MB.
     */
    public function test_profile_photo_upload_rejects_files_larger_than_2mb(): void
    {
        $file = UploadedFile::fake()->image('large.png')->size(3000); // 3MB

        Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->set('photo', $file)
            ->call('saveProfilePhoto')
            ->assertHasErrors(['photo']);

        $this->user->refresh();
        $this->assertNull($this->user->avatar_url);
    }

    /**
     * Test 4: User dapat menghapus foto profil dan kembali ke avatar inisial default.
     */
    public function test_user_can_delete_profile_photo(): void
    {
        // Setup initial photo
        $dummyPath = 'avatars/dummy.jpg';
        Storage::disk('public')->put($dummyPath, 'fake content');

        $this->user->update([
            'avatar_url' => 'storage/'.$dummyPath,
        ]);

        Storage::disk('public')->assertExists($dummyPath);

        Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->call('deleteProfilePhoto')
            ->assertHasNoErrors()
            ->assertSee('Foto profil berhasil dihapus.');

        $this->user->refresh();
        $this->assertNull($this->user->avatar_url);
        Storage::disk('public')->assertMissing($dummyPath);
    }

    /**
     * Test 5: User dapat membuat avatar kustom vektor (SVG) dengan inisial dan warna pilihan.
     */
    public function test_user_can_generate_custom_svg_avatar(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->set('selectedBgColor', '#0B7840')
            ->set('customInitials', 'BS')
            ->call('generateCustomAvatar')
            ->assertHasNoErrors()
            ->assertSee('Avatar kustom berhasil dibuat dan diterapkan.');

        $this->user->refresh();

        $this->assertNotNull($this->user->avatar_url);
        $this->assertStringEndsWith('.svg', $this->user->avatar_url);

        $relativePath = str_replace('storage/', '', $this->user->avatar_url);
        Storage::disk('public')->assertExists($relativePath);

        $svgContent = Storage::disk('public')->get($relativePath);
        $this->assertStringContainsString('#0B7840', $svgContent);
        $this->assertStringContainsString('BS', $svgContent);
    }

    /**
     * Test 6: Halaman profil, header, dan dashboard merender gambar avatar user jika tersedia.
     */
    public function test_profile_and_dashboard_render_user_avatar_image(): void
    {
        $dummyPath = 'avatars/profile-test.jpg';
        Storage::disk('public')->put($dummyPath, 'content');

        $this->user->update([
            'avatar_url' => 'storage/'.$dummyPath,
        ]);

        $this->actingAs($this->user)
            ->get('/profile')
            ->assertOk()
            ->assertSee(asset('storage/'.$dummyPath));

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(asset('storage/'.$dummyPath));
    }

    /**
     * Test 7: Halaman leaderboard merender gambar avatar user jika tersedia.
     */
    public function test_leaderboard_renders_user_avatar_image(): void
    {
        $dummyPath = 'avatars/leaderboard-test.jpg';
        Storage::disk('public')->put($dummyPath, 'content');

        $this->user->update([
            'avatar_url' => 'storage/'.$dummyPath,
        ]);

        $this->actingAs($this->user)
            ->get('/leaderboard')
            ->assertOk()
            ->assertSee(asset('storage/'.$dummyPath));
    }

    /**
     * Test 8: User dapat membatalkan pengeditan form profil dengan cancelProfileEdit.
     */
    public function test_user_can_cancel_profile_information_edit(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->set('name', 'Nama Berubah')
            ->set('email', 'berubah@cps.co.id')
            ->call('cancelProfileEdit')
            ->assertSet('name', 'Budi Santoso')
            ->assertSet('email', 'budi@cps.co.id')
            ->assertHasNoErrors();
    }

    /**
     * Test 9: Halaman profil merender semantik aksesibilitas ARIA dan target sentuh.
     */
    public function test_profile_page_renders_accessibility_attributes(): void
    {
        $this->actingAs($this->user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('role="tab"', false)
            ->assertSee('role="progressbar"', false)
            ->assertSee('aria-valuenow', false);

        Livewire::actingAs($this->user)
            ->test(ProfileIndex::class)
            ->set('avatarMode', 'generate')
            ->assertSee('aria-pressed="true"', false)
            ->assertSee('aria-label="Pilih warna latar', false);
    }
}
