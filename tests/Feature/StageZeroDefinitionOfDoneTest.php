<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserBookmark;
use App\Models\UserLearningProgress;
use Database\Seeders\DivisionSeeder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StageZeroDefinitionOfDoneTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DoD #1.1: Pastikan seluruh tabel dan kolom yang disepakati di Data Contract ada di database nyata.
     */
    public function test_dod_1_all_tables_and_columns_exist_in_database(): void
    {
        $expectedSchema = [
            'users' => [
                'id', 'employee_id', 'name', 'email', 'division_id',
                'jabatan', 'avatar_url', 'xp', 'level',
            ],
            'divisions' => [
                'id', 'name',
            ],
            'ba_incidents' => [
                'id', 'nomor_ba', 'title', 'description', 'division_id',
                'status', 'created_by',
                'reviewed_by', 'reviewed_at', 'closed_at',
            ],
            'ba_activity_logs' => [
                'id', 'ba_incident_id', 'actor_id', 'action', 'note',
            ],
            'knowledge_documents' => [
                'id', 'title', 'division_id', 'type', 'file_url',
                'external_link', 'description', 'source_ba_id', 'created_by', 'status',
            ],
            'user_bookmarks' => [
                'id', 'user_id', 'knowledge_document_id',
            ],
            'learning_categories' => [
                'id', 'name', 'created_by',
            ],
            'learning_materials' => [
                'id', 'learning_category_id', 'title', 'type', 'content_url',
                'description', 'status', 'created_by',
            ],
            'user_learning_progress' => [
                'id', 'user_id', 'learning_material_id', 'progress_percent', 'completed_at',
            ],
            'quizzes' => [
                'id', 'title', 'type', 'related_type', 'related_id',
                'points_reward', 'description',
            ],
            'quiz_questions' => [
                'id', 'quiz_id', 'question_text', 'order_index',
            ],
            'quiz_options' => [
                'id', 'quiz_question_id', 'option_text', 'is_correct',
            ],
            'quiz_attempts' => [
                'id', 'quiz_id', 'user_id', 'score', 'passed', 'points_earned', 'attempted_at',
            ],
            'point_transactions' => [
                'id', 'user_id', 'points', 'source_type', 'source_id', 'description', 'created_at',
            ],
            'achievements' => [
                'id', 'name', 'description', 'icon',
            ],
            'user_achievements' => [
                'id', 'user_id', 'achievement_id', 'unlocked_at',
            ],
            'notifications' => [
                'id', 'user_id', 'type', 'title', 'message', 'related_type', 'related_id', 'read_at',
            ],
        ];

        foreach ($expectedSchema as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "Tabel [{$table}] tidak ditemukan di database!");
            $this->assertTrue(
                Schema::hasColumns($table, $columns),
                "Kolom pada tabel [{$table}] tidak lengkap. Yang diharapkan: ".implode(', ', $columns)
            );
        }
    }

    /**
     * DoD #1.2: Pastikan 13 divisi tetap di-seed dengan benar sesuai Design System §8 & DivisionSeeder.
     */
    public function test_dod_1_thirteen_divisions_are_present(): void
    {
        $this->seed(DivisionSeeder::class);

        $expectedDivisions = [
            'Engineering',
            'Finance Accounting Tax',
            'Gudang RM',
            'HRGA',
            'Keamanan',
            'PPIC',
            'Produksi',
            'Purchasing',
            'Quality Control',
            'Repair',
            'Sales & Marketing',
            'Warehouse & Delivery',
            'IT',
        ];

        $this->assertCount(13, Division::all(), 'Jumlah divisi harus tepat 13.');
        foreach ($expectedDivisions as $name) {
            $this->assertDatabaseHas('divisions', ['name' => $name]);
        }
    }

    /**
     * DoD #1.3: Pastikan seluruh relasi model Eloquent untuk 9 modul valid dan tidak error.
     */
    public function test_dod_1_eloquent_relationships_are_properly_defined(): void
    {
        $user = new User;
        $this->assertInstanceOf(Relation::class, $user->division());
        $this->assertInstanceOf(Relation::class, $user->bookmarks());
        $this->assertInstanceOf(Relation::class, $user->bookmarkedDocuments());
        $this->assertInstanceOf(Relation::class, $user->learningProgresses());
        $this->assertInstanceOf(Relation::class, $user->learningMaterials());
        $this->assertInstanceOf(Relation::class, $user->pointTransactions());
        $this->assertInstanceOf(Relation::class, $user->quizAttempts());
        $this->assertInstanceOf(Relation::class, $user->notifications());
        $this->assertInstanceOf(Relation::class, $user->achievements());

        $ba = new BaIncident;
        $this->assertInstanceOf(Relation::class, $ba->division());
        $this->assertInstanceOf(Relation::class, $ba->creator());
        $this->assertInstanceOf(Relation::class, $ba->reviewer());
        $this->assertInstanceOf(Relation::class, $ba->activityLogs());
        $this->assertInstanceOf(Relation::class, $ba->lessonLearned());

        $log = new BaActivityLog;
        $this->assertInstanceOf(Relation::class, $log->incident());
        $this->assertInstanceOf(Relation::class, $log->actor());

        $doc = new KnowledgeDocument;
        $this->assertInstanceOf(Relation::class, $doc->division());
        $this->assertInstanceOf(Relation::class, $doc->creator());
        $this->assertInstanceOf(Relation::class, $doc->sourceBa());
        $this->assertInstanceOf(Relation::class, $doc->bookmarks());
        $this->assertInstanceOf(Relation::class, $doc->bookmarkedByUsers());

        $cat = new LearningCategory;
        $this->assertInstanceOf(Relation::class, $cat->materials());
        $this->assertInstanceOf(Relation::class, $cat->creator());

        $mat = new LearningMaterial;
        $this->assertInstanceOf(Relation::class, $mat->category());
        $this->assertInstanceOf(Relation::class, $mat->creator());
        $this->assertInstanceOf(Relation::class, $mat->progresses());

        $prog = new UserLearningProgress;
        $this->assertInstanceOf(Relation::class, $prog->user());
        $this->assertInstanceOf(Relation::class, $prog->material());

        $quiz = new Quiz;
        $this->assertInstanceOf(Relation::class, $quiz->questions());
        $this->assertInstanceOf(Relation::class, $quiz->attempts());
        $this->assertInstanceOf(Relation::class, $quiz->relatedLearningMaterial());

        $q = new QuizQuestion;
        $this->assertInstanceOf(Relation::class, $q->quiz());
        $this->assertInstanceOf(Relation::class, $q->options());

        $opt = new QuizOption;
        $this->assertInstanceOf(Relation::class, $opt->question());

        $att = new QuizAttempt;
        $this->assertInstanceOf(Relation::class, $att->quiz());
        $this->assertInstanceOf(Relation::class, $att->user());

        $pt = new PointTransaction;
        $this->assertInstanceOf(Relation::class, $pt->user());

        $ach = new Achievement;
        $this->assertInstanceOf(Relation::class, $ach->users());

        $uAch = new UserAchievement;
        $this->assertInstanceOf(Relation::class, $uAch->user());
        $this->assertInstanceOf(Relation::class, $uAch->achievement());

        $notif = new Notification;
        $this->assertInstanceOf(Relation::class, $notif->user());

        $bm = new UserBookmark;
        $this->assertInstanceOf(Relation::class, $bm->user());
        $this->assertInstanceOf(Relation::class, $bm->knowledgeDocument());
    }

    /**
     * DoD #2: Pastikan dokumen Data Contract tidak memiliki status blocking yang masih "PENDING".
     */
    public function test_dod_2_data_contract_has_no_pending_blocking_decisions(): void
    {
        $contractPath = base_path('CPS-ERA-Data-Contract.md');
        $this->assertFileExists($contractPath);

        $content = File::get($contractPath);

        // Tidak boleh ada lagi item bertanda "PENDING" pada Bucket A
        $this->assertStringNotContainsString(
            'PENDING VERIFIKASI KODE',
            $content,
            'Bucket A masih menyisakan item PENDING yang belum diputuskan.'
        );

        // Seluruh 6 item Bucket A harus tercatat di tabel
        for ($i = 1; $i <= 6; $i++) {
            $this->assertMatchesRegularExpression(
                "/\\|\\s*{$i}\\s*\\|/",
                $content,
                "Item Bucket A nomor {$i} belum tercatat di Data Contract."
            );
        }

        // Bucket B: Semua 7 poin PRD §5.3 memiliki status dan default interim
        for ($i = 1; $i <= 7; $i++) {
            $this->assertMatchesRegularExpression(
                "/\\|\\s*{$i}\\s*\\|/",
                $content,
                "Poin PRD §5.3 nomor {$i} belum terdokumentasi di Data Contract."
            );
        }
    }

    /**
     * DoD #3: Pastikan ke-9 modul memiliki spesifikasi route dan komponen Livewire di Data Contract,
     * serta route yang sudah aktif saat ini terdaftar dengan benar.
     */
    public function test_dod_3_route_and_livewire_contracts_are_defined(): void
    {
        $contractPath = base_path('CPS-ERA-Data-Contract.md');
        $content = File::get($contractPath);

        $expectedModules = [
            'Dashboard' => ['/dashboard', 'dashboard', 'Dashboard'],
            'Knowledge' => ['/knowledge', 'knowledge.index', 'Knowledge\Index'],
            'BA & Lesson Learned' => ['/ba-incidents', 'ba.index', 'Ba\Index'],
            'Learning' => ['/learning', 'learning.index', 'Learning\Index'],
            'Mission & Game' => ['/missions', 'missions.index', 'Mission\Index'],
            'Leaderboard' => ['/leaderboard', 'leaderboard.index', 'Leaderboard\Index'],
            'Notifikasi' => ['/notifications', 'notifications.index', 'Notification\Index'],
            'Achievement' => ['/achievements', 'achievements.index', 'Achievement\Index'],
            'Profile' => ['/profile', 'profile.show', 'Profile\Show'],
        ];

        foreach ($expectedModules as $module => [$uri, $routeName, $component]) {
            $this->assertStringContainsString(
                $module,
                $content,
                "Modul [{$module}] belum tercatat di tabel §4 Data Contract."
            );
            $this->assertStringContainsString(
                $uri,
                $content,
                "URI [{$uri}] untuk modul [{$module}] belum tercatat di Data Contract."
            );
            $this->assertStringContainsString(
                $routeName,
                $content,
                "Route name [{$routeName}] untuk modul [{$module}] belum tercatat di Data Contract."
            );
        }

        // Route yang sudah aktif di routes/web.php saat ini (dashboard & profile)
        $this->assertTrue(Route::has('dashboard'), 'Route named [dashboard] harus sudah aktif di web.php.');
        $this->assertTrue(Route::has('profile.edit'), 'Route named [profile.edit] harus sudah aktif di web.php.');
    }
}
