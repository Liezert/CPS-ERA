<?php

namespace Tests\Feature;

use App\Livewire\Ba\Create;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Form CAPA memakai pola "clean & guided": tanpa placeholder contoh kalimat, dengan helper text
 * singkat + tooltip kerangka isian dari BaIncident::FIELD_GUIDES.
 */
class CapaFieldGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_field_guide_renders_helper_and_tooltip_without_example_placeholders(): void
    {
        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
        $employee = User::factory()->create(['division_id' => Division::where('name', 'Produksi')->value('id')]);
        $employee->assignRole('employee');

        $html = Livewire::actingAs($employee)->test(Create::class)->html();

        $this->assertSame(count(BaIncident::FIELD_GUIDES), substr_count($html, 'role="tooltip"'));
        foreach (BaIncident::FIELD_GUIDES as $guide) {
            $this->assertStringContainsString(e($guide['helper']), $html);
            foreach ($guide['guide'] as $label => $text) {
                $this->assertStringContainsString(e($text), $html, $label);
            }
        }
        $this->assertStringNotContainsString('placeholder="Contoh:', $html);
    }
}
