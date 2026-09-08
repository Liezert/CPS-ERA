<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class StageTwoBaseComponentLibraryTest extends TestCase
{
    /**
     * DoD #1: Button primary & secondary conforming to Design System §5.
     */
    public function test_button_primary_and_secondary_render_correctly_without_arrows(): void
    {
        // Primary button
        $primaryHtml = Blade::render('<x-ui.button variant="primary">Simpan Laporan</x-ui.button>');
        $this->assertStringContainsString('bg-brand', $primaryHtml);
        $this->assertStringContainsString('text-white', $primaryHtml);
        $this->assertStringContainsString('rounded-md', $primaryHtml);
        $this->assertStringNotContainsString('→', $primaryHtml, 'Tombol tidak boleh memiliki panah!');

        // Secondary button
        $secondaryHtml = Blade::render('<x-ui.button variant="secondary">Batal</x-ui.button>');
        $this->assertStringContainsString('border-neutral-200', $secondaryHtml);
        $this->assertStringContainsString('text-neutral-900', $secondaryHtml);
        $this->assertStringContainsString('rounded-md', $secondaryHtml);

        // Render as link
        $linkHtml = Blade::render('<x-ui.button href="/dashboard">Ke Dashboard</x-ui.button>');
        $this->assertStringContainsString('<a href="/dashboard"', $linkHtml);
    }

    /**
     * DoD #2: Badge status tag kotak bersudut tegas, border 1px, tanpa fill solid.
     */
    public function test_badge_status_tag_renders_sharp_corner_and_transparent_fill(): void
    {
        // Status: Created
        $createdHtml = Blade::render('<x-ui.badge status="created" />');
        $this->assertStringContainsString('rounded-badge', $createdHtml, 'Badge harus memiliki radius 2px (rounded-badge)');
        $this->assertStringContainsString('bg-transparent', $createdHtml, 'Badge tidak boleh memiliki fill solid');
        $this->assertStringContainsString('border-neutral-200', $createdHtml);
        $this->assertStringContainsString('font-mono', $createdHtml);

        // Status: Reviewed
        $reviewedHtml = Blade::render('<x-ui.badge status="reviewed" />');
        $this->assertStringContainsString('border-neutral-500', $reviewedHtml);
        $this->assertStringContainsString('text-neutral-900', $reviewedHtml);

        // Status: Closed (brand accent)
        $closedHtml = Blade::render('<x-ui.badge status="closed" />');
        $this->assertStringContainsString('border-brand', $closedHtml);
        $this->assertStringContainsString('text-brand-dark', $closedHtml);
    }

    /**
     * DoD #3: Metric card label kecil di atas, angka besar di bawah, tanpa shadow berat.
     */
    public function test_metric_card_renders_label_and_value(): void
    {
        $html = Blade::render('<x-ui.metric-card label="Total Aset" value="142" meta="Update hari ini" />');

        $this->assertStringContainsString('Total Aset', $html);
        $this->assertStringContainsString('142', $html);
        $this->assertStringContainsString('text-2xl font-semibold', $html);
        $this->assertStringContainsString('text-xs font-sans font-medium text-neutral-500', $html);
        $this->assertStringContainsString('border-neutral-200', $html);
        $this->assertStringNotContainsString('shadow-lg', $html);
        $this->assertStringNotContainsString('shadow-xl', $html);
    }

    /**
     * DoD #4: List row dengan hairline divider tipis, bukan shadow card.
     */
    public function test_list_row_renders_hairline_divider(): void
    {
        $html = Blade::render('<x-ui.list-row><span>Item Dokumen</span></x-ui.list-row>');

        $this->assertStringContainsString('border-b border-neutral-200', $html);
        $this->assertStringContainsString('Item Dokumen', $html);
        $this->assertStringNotContainsString('shadow', $html);
    }

    /**
     * DoD #5: Responsive table transforms to cards on mobile (< 820px).
     */
    public function test_responsive_table_has_desktop_table_and_mobile_card_slots(): void
    {
        $html = Blade::render('
            <x-ui.responsive-table>
                <x-slot:head>
                    <th>Kolom 1</th>
                </x-slot:head>
                <x-slot:body>
                    <tr><td>Baris Desktop</td></tr>
                </x-slot:body>
                <x-slot:mobile>
                    <x-ui.table-card>Card Mobile</x-ui.table-card>
                </x-slot:mobile>
            </x-ui.responsive-table>
        ');

        $this->assertStringContainsString('hidden tablet:block', $html, 'Tabel desktop harus tersembunyi di mobile dan aktif pada tablet/desktop');
        $this->assertStringContainsString('block tablet:hidden', $html, 'Card mobile harus aktif di mobile dan tersembunyi pada tablet/desktop');
        $this->assertStringContainsString('Baris Desktop', $html);
        $this->assertStringContainsString('Card Mobile', $html);
    }

    /**
     * DoD #6: Preview page /components-preview accessible and renders all components.
     */
    public function test_components_preview_page_renders_successfully(): void
    {
        $response = $this->get('/components-preview');

        $response->assertOk();
        $response->assertSee('CPS ERA — Base Component Library');
        $response->assertSee('Simpan Laporan BA');
        $response->assertSee('Created');
        $response->assertSee('Reviewed');
        $response->assertSee('Closed');
        $response->assertSee('Total Knowledge Assets');
        $response->assertSee('SOP Penanganan Mesin CNC Saat Overheat');
    }
}
