<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoStarterSeeder;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Bersihkan seluruh data transaksi dummy, lalu isi data starter untuk pengujian manual.
 *
 * TIDAK menyentuh data master: `divisions`, `roles`/`permissions` beserta tabel pivotnya,
 * `knowledge_topics`, `learning_categories`, `kpi_settings`, `google_drive_tokens`, dan tabel
 * `migrations` (tidak ada migrate:fresh, jadi catatan migrasi tetap utuh).
 */
class ResetDemoData extends Command
{
    use ConfirmableTrait;

    protected $signature = 'cps:reset-demo-data {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Hapus data transaksi dummy (CAPA, learning, misi, poin, notifikasi, akun) lalu isi data starter untuk pengujian manual';

    /**
     * Urutan penting: anak dihapus sebelum induknya supaya foreign key tidak menolak.
     */
    private const TRANSACTIONAL_TABLES = [
        'quiz_attempts',
        'quiz_options',
        'quiz_questions',
        'quizzes',
        'user_achievements',
        'achievements',
        'user_learning_progress',
        'learning_materials',
        'user_video_views',
        'user_bookmarks',
        'knowledge_documents',
        'videos',
        'ba_activity_logs',
        'ba_incident_file_backups',
        'ba_incidents',
        'point_transactions',
        'notifications',
        'user_kpi_yearly',
        'media',
        'password_reset_tokens',
        'sessions',
    ];

    /**
     * Master yang kolom `created_by`-nya menunjuk ke users dan TIDAK boleh ikut terhapus.
     * `knowledge_topics` memakai ON DELETE CASCADE, jadi kepemilikannya wajib dipindah dulu.
     */
    private const OWNED_MASTER_TABLES = [
        'learning_categories' => 'created_by',
        'knowledge_topics' => 'created_by',
        'google_drive_tokens' => 'connected_by',
    ];

    public function handle(): int
    {
        if (! $this->confirmToProceed('Perintah ini menghapus SELURUH data transaksi dan akun yang ada')) {
            return self::FAILURE;
        }

        $before = $this->counts();

        DB::transaction(function (): void {
            foreach (self::TRANSACTIONAL_TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            $anchor = $this->keepAnchorUser();
            $seeder = app(DemoStarterSeeder::class);

            // Misi dibuat sebelum akun uji: QuizObserver mengirim notifikasi "misi baru" ke semua
            // user, jadi urutan ini membuat kotak notifikasi akun uji kosong di awal.
            $seeder->seedMissions();

            $admin = $seeder->seedAccounts();

            // Data master kembali dimiliki admin demo, lalu jangkar sementara ikut dihapus
            // (notifikasi misi yang sempat dikirim ke jangkar ikut terhapus lewat cascade).
            foreach (self::OWNED_MASTER_TABLES as $table => $column) {
                DB::table($table)->update([$column => $admin->id]);
            }

            if ($anchor) {
                $this->deleteUsers(User::whereKey($anchor->id));
            }

            $seeder->seedLearningMaterials($admin);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::table('cache')->delete();

        $this->renderSummary($before, $this->counts());

        return self::SUCCESS;
    }

    /**
     * Sisakan satu user lama sebagai pemilik sementara data master (kolom `created_by` NOT NULL),
     * lalu hapus sisanya. Emailnya diubah supaya tidak bentrok dengan akun demo yang akan dibuat.
     */
    private function keepAnchorUser(): ?User
    {
        $anchor = User::orderBy('id')->first();

        if (! $anchor) {
            return null;
        }

        $anchor->forceFill([
            'email' => 'jangkar-sementara@cps.invalid',
            'employee_id' => null,
        ])->save();

        foreach (self::OWNED_MASTER_TABLES as $table => $column) {
            DB::table($table)->update([$column => $anchor->id]);
        }

        $this->deleteUsers(User::whereKeyNot($anchor->id));

        return $anchor;
    }

    /**
     * @param  Builder<User>  $users
     */
    private function deleteUsers($users): void
    {
        $ids = $users->pluck('id');

        // Penghapusan massal tidak memicu event model, jadi pivot role dibersihkan manual.
        DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();
        DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();
        User::whereIn('id', $ids)->delete();
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        $tables = array_merge(
            self::TRANSACTIONAL_TABLES,
            ['users', 'divisions', 'roles', 'learning_categories', 'knowledge_topics', 'google_drive_tokens', 'migrations'],
        );

        $counts = [];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $counts[$table] = DB::table($table)->count();
            }
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $before
     * @param  array<string, int>  $after
     */
    private function renderSummary(array $before, array $after): void
    {
        $rows = [];
        foreach ($after as $table => $count) {
            $rows[] = [$table, $before[$table] ?? 0, $count];
        }

        $this->table(['Tabel', 'Sebelum', 'Sesudah'], $rows);

        $this->info('Akun uji (kata sandi: '.DemoStarterSeeder::DEMO_PASSWORD.', wajib diganti saat login pertama):');
        foreach (DemoStarterSeeder::ACCOUNTS as $account) {
            $this->line("  - {$account['email']} ({$account['role']}, divisi {$account['divisi']})");
        }
    }
}
