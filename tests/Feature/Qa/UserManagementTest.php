<?php

namespace Tests\Feature\Qa;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * US-13 / US-14 / US-15 — Administrasi akun & verifikasi.
 */
class UserManagementTest extends QaTestCase
{
    private function admin(): User
    {
        return $this->makeUser('admin');
    }

    private function validUserPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nama QA',
            'email' => 'qa-baru@qa.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    // ---------- US-13: registrasi petugas ----------

    public function test_admin_can_create_petugas_who_can_log_in(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.storePetugas'), $this->validUserPayload(['email' => 'petugas-baru@qa.test']))
            ->assertRedirect(route('admin.dashboard'));

        $petugas = User::where('email', 'petugas-baru@qa.test')->firstOrFail();
        $this->assertSame('petugas', $petugas->role);
        $this->assertTrue($petugas->is_verified);

        // Log out admin, lalu login sebagai petugas baru.
        $this->post('/logout');
        $this->post('/login', ['email' => 'petugas-baru@qa.test', 'password' => 'rahasia123'])
            ->assertRedirect(route('petugas.dashboard', absolute: false));
        $this->assertAuthenticatedAs($petugas);
    }

    public function test_only_admin_can_access_staff_creation_endpoints(): void
    {
        $this->get(route('admin.users.createPetugas'))->assertRedirect('/login');

        foreach (['pengguna', 'petugas'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get(route('admin.users.createPetugas'))->assertForbidden();

            $this->actingAs($this->makeUser($role))
                ->post(route('admin.users.storePetugas'), $this->validUserPayload(['email' => "x-$role@qa.test"]))
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('users', ['email' => 'x-pengguna@qa.test']);
        $this->assertDatabaseMissing('users', ['email' => 'x-petugas@qa.test']);
    }

    public function test_admin_cannot_be_created_through_route_manipulation(): void
    {
        // Mengirim field role pada endpoint petugas tetap menghasilkan role petugas.
        $this->actingAs($this->admin())->post(route('admin.users.storePetugas'), $this->validUserPayload([
            'email' => 'sneaky@qa.test',
            'role' => 'admin',
            'is_verified' => 0,
        ]));

        $user = User::where('email', 'sneaky@qa.test')->firstOrFail();
        $this->assertSame('petugas', $user->role);
        $this->assertTrue($user->is_verified);
    }

    // ---------- US-14: registrasi pengguna ----------

    public function test_admin_can_create_pengguna(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.storePengguna'), $this->validUserPayload([
            'email' => 'pengguna-baru@qa.test',
            'user_type' => 'mahasiswa',
        ]))->assertRedirect(route('admin.dashboard'));

        $user = User::where('email', 'pengguna-baru@qa.test')->firstOrFail();
        $this->assertSame('pengguna', $user->role);
        $this->assertTrue($user->is_verified);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_user_type_is_required_but_not_persisted(): void
    {
        // Wajib diisi...
        $this->actingAs($this->admin())->from(route('admin.users.createPengguna'))
            ->post(route('admin.users.storePengguna'), $this->validUserPayload(['email' => 'tanpa-tipe@qa.test']))
            ->assertSessionHasErrors('user_type');

        // ...tetapi tidak tersimpan di basis data.
        $this->actingAs($this->admin())->post(route('admin.users.storePengguna'), $this->validUserPayload([
            'email' => 'dengan-tipe@qa.test',
            'user_type' => 'dosen',
        ]));

        $user = User::where('email', 'dengan-tipe@qa.test')->firstOrFail();
        $this->assertArrayNotHasKey('user_type', $user->getAttributes());
    }

    public function test_duplicate_email_and_weak_password_are_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.storePengguna'), $this->validUserPayload([
            'email' => 'dobel@qa.test',
            'user_type' => 'mahasiswa',
        ]));

        $this->actingAs($this->admin())->from(route('admin.users.createPengguna'))
            ->post(route('admin.users.storePengguna'), $this->validUserPayload([
                'email' => 'dobel@qa.test',
                'user_type' => 'mahasiswa',
            ]))
            ->assertSessionHasErrors('email');

        $this->actingAs($this->admin())->from(route('admin.users.createPengguna'))
            ->post(route('admin.users.storePengguna'), $this->validUserPayload([
                'email' => 'lemah@qa.test',
                'user_type' => 'mahasiswa',
                'password' => '123',
                'password_confirmation' => '123',
            ]))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'lemah@qa.test']);
    }

    // ---------- US-15: registrasi mandiri & verifikasi ----------

    public function test_public_registration_always_creates_unverified_pengguna(): void
    {
        $this->post('/register', [
            'name' => 'Daftar Mandiri',
            'email' => 'mandiri@qa.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'is_verified' => 1,
        ]);

        $user = User::where('email', 'mandiri@qa.test')->firstOrFail();
        $this->assertSame('pengguna', $user->role);
        $this->assertFalse($user->is_verified);
    }

    public function test_unverified_user_can_log_in_but_cannot_reserve(): void
    {
        // Catatan: PDF mensyaratkan "unverified cannot log in"; aplikasi ini
        // sengaja mengizinkan login namun mengunci fitur reservasi/laporan.
        $user = $this->makeUser('pengguna', false, ['email' => 'belum-verif@qa.test', 'password' => 'password']);

        $this->post('/login', ['email' => 'belum-verif@qa.test', 'password' => 'password']);
        $this->assertAuthenticatedAs($user);

        $this->get('/reservations')->assertRedirect(route('verification.notice'));
        $this->get('/reports')->assertRedirect(route('verification.notice'));

        // Halaman status akun dapat diakses.
        $this->get(route('verification.notice'))->assertOk();
    }

    public function test_admin_can_verify_and_then_user_can_reserve(): void
    {
        $user = $this->makeUser('pengguna', false, ['email' => 'verif@qa.test']);

        $this->actingAs($this->admin())
            ->post(route('admin.users.verify', $user->id))
            ->assertSessionHas('success');
        $this->assertTrue($user->fresh()->is_verified);

        $this->actingAs($user->fresh())->get('/reservations')->assertOk();
    }

    public function test_admin_can_reject_and_rejected_user_cannot_log_in(): void
    {
        $user = $this->makeUser('pengguna', false, ['email' => 'ditolak@qa.test', 'password' => 'password']);

        $this->actingAs($this->admin())
            ->post(route('admin.users.reject', $user->id), ['reason' => 'Data tidak lengkap'])
            ->assertSessionHas('success');

        $this->assertSame('Data tidak lengkap', $user->fresh()->rejection_reason);

        $this->post('/logout');
        $this->post('/login', ['email' => 'ditolak@qa.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rejected_user_with_active_session_is_logged_out(): void
    {
        $user = $this->makeUser('pengguna', false, ['email' => 'sesi-ditolak@qa.test']);
        $user->update(['rejection_reason' => 'Melanggar aturan']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_reject_requires_a_reason(): void
    {
        $user = $this->makeUser('pengguna', false);

        $this->actingAs($this->admin())
            ->from(route('admin.users.pending'))
            ->post(route('admin.users.reject', $user->id), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertNull($user->fresh()->rejection_reason);
    }

    public function test_only_admin_can_verify_or_reject(): void
    {
        $user = $this->makeUser('pengguna', false);
        $petugas = $this->makeUser('petugas');

        $this->actingAs($petugas)->post(route('admin.users.verify', $user->id))->assertForbidden();
        $this->actingAs($petugas)->post(route('admin.users.reject', $user->id), ['reason' => 'x'])->assertForbidden();
        $this->assertFalse($user->fresh()->is_verified);
    }

    public function test_repeated_verification_is_idempotent(): void
    {
        $user = $this->makeUser('pengguna', false);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.verify', $user->id));
        $this->actingAs($admin)->post(route('admin.users.verify', $user->id))->assertSessionHas('success');

        $this->assertTrue($user->fresh()->is_verified);
    }
}
