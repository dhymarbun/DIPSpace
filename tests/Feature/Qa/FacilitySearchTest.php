<?php

namespace Tests\Feature\Qa;

/**
 * US-02 — Pencarian & filter fasilitas (tipe, lokasi, kapasitas minimal).
 */
class FacilitySearchTest extends QaTestCase
{
    private function seedFacilities(): void
    {
        $this->makeFacility(['nama' => 'Aula Besar', 'tipe' => 'Aula', 'lokasi' => 'Gedung Rektorat', 'kapasitas' => 300]);
        $this->makeFacility(['nama' => 'Lab Kecil', 'tipe' => 'Laboratorium', 'lokasi' => 'Gedung Teknik', 'kapasitas' => 30]);
        $this->makeFacility(['nama' => 'Lab Besar', 'tipe' => 'Laboratorium', 'lokasi' => 'Gedung Teknik', 'kapasitas' => 60]);
    }

    public function test_filter_by_type(): void
    {
        $this->seedFacilities();

        $this->get(route('home').'?tipe=Aula')
            ->assertOk()
            ->assertSee('Aula Besar')
            ->assertDontSee('Lab Kecil')
            ->assertDontSee('Lab Besar');
    }

    public function test_filter_by_location(): void
    {
        $this->seedFacilities();

        $this->get(route('home').'?lokasi='.urlencode('Gedung Teknik'))
            ->assertOk()
            ->assertSee('Lab Kecil')
            ->assertSee('Lab Besar')
            ->assertDontSee('Aula Besar');
    }

    public function test_min_capacity_equal_is_included(): void
    {
        $this->seedFacilities();

        // Kapasitas 60 harus ikut muncul saat minimal = 60.
        $this->get(route('home').'?kapasitas=60')
            ->assertOk()
            ->assertSee('Lab Besar')
            ->assertSee('Aula Besar')
            ->assertDontSee('Lab Kecil');
    }

    public function test_min_capacity_one_unit_greater_excludes_equal(): void
    {
        $this->seedFacilities();

        $this->get(route('home').'?kapasitas=61')
            ->assertOk()
            ->assertSee('Aula Besar')
            ->assertDontSee('Lab Besar')
            ->assertDontSee('Lab Kecil');
    }

    public function test_combined_filters(): void
    {
        $this->seedFacilities();

        $this->get(route('home').'?tipe=Laboratorium&lokasi='.urlencode('Gedung Teknik').'&kapasitas=50')
            ->assertOk()
            ->assertSee('Lab Besar')
            ->assertDontSee('Lab Kecil')
            ->assertDontSee('Aula Besar');
    }

    public function test_empty_filters_show_all(): void
    {
        $this->seedFacilities();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Aula Besar')
            ->assertSee('Lab Kecil')
            ->assertSee('Lab Besar');
    }

    public function test_capacity_zero_or_negative_shows_all(): void
    {
        $this->seedFacilities();

        foreach (['0', '-5'] as $value) {
            $this->get(route('home').'?kapasitas='.$value)
                ->assertOk()
                ->assertSee('Lab Kecil');
        }
    }

    public function test_capacity_decimal_is_compared_numerically(): void
    {
        $this->seedFacilities();

        $this->get(route('home').'?kapasitas=30.5')
            ->assertOk()
            ->assertSee('Lab Besar')
            ->assertSee('Aula Besar')
            ->assertDontSee('Lab Kecil');
    }

    public function test_very_large_capacity_returns_no_facility(): void
    {
        $this->seedFacilities();

        $this->get(route('home').'?kapasitas=999999999999')
            ->assertOk()
            ->assertDontSee('Aula Besar')
            ->assertDontSee('Lab Kecil');
    }

    public function test_malformed_capacity_input_is_handled_without_server_error(): void
    {
        $this->seedFacilities();

        // Nilai non-numerik diabaikan (filter tidak diterapkan) => semua tampil, bukan 500.
        $this->get(route('home').'?kapasitas=abc')
            ->assertOk()
            ->assertSee('Aula Besar')
            ->assertSee('Lab Kecil');

        // Parameter array (kapasitas[]) juga tidak boleh menjatuhkan server.
        $this->assertNotSame(
            500,
            $this->get(route('home').'?kapasitas[]=1')->status(),
            'Parameter array pada filter kapasitas seharusnya ditangani, bukan menimbulkan error 500.'
        );
    }

    public function test_sql_injection_pattern_in_filters_is_safe(): void
    {
        $this->seedFacilities();

        $this->get(route('home').'?tipe=Aula%27%20OR%201%3D1--&lokasi=%27%20OR%20%271%27%3D%271')
            ->assertOk();

        // Data tetap utuh.
        $this->assertDatabaseCount('facilities', 3);
    }

    public function test_percent_and_underscore_in_location_are_treated_as_literals(): void
    {
        $this->seedFacilities();

        // BUG-11 diperbaiki: '%' dan '_' tidak lagi menjadi wildcard LIKE.
        $this->get(route('home').'?lokasi=%25')
            ->assertOk()
            ->assertDontSee('Aula Besar')
            ->assertDontSee('Lab Kecil');

        $this->get(route('home').'?lokasi=_')
            ->assertOk()
            ->assertDontSee('Aula Besar')
            ->assertDontSee('Lab Kecil');
    }
}
