<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Group;

class SuperadminSeeder extends Seeder
{
    /**
     * Seed semua 7 role user untuk SIMPUL MERAH.
     * Group ID 1 = Sekretariat Daerah (di-seed oleh GroupSeeder terlebih dahulu).
     */
    public function run(): void
    {
        // Ambil group Dinas Pendidikan untuk contoh SKPD
        $groupDiknas   = Group::where('name', 'Dinas Pendidikan')->first();
        $groupKesehatan = Group::where('name', 'Dinas Kesehatan')->first();
        $groupSetda    = Group::where('name', 'Sekretariat Daerah')->first();

        // ── U2 — Kepala Bagian Hukum (Super Admin) ─────────────────────
        User::updateOrCreate(
            ['email' => 'kabag.hukum@sampang.go.id'],
            [
                'name'     => 'Kepala Bagian Hukum',
                'password' => bcrypt('password'),
                'role'     => 'superadmin',
            ]
        );

        // ── U3 — Staf JF Penyusun Perundang-undangan (Admin) ───────────
        User::updateOrCreate(
            ['email' => 'staf.hukum@sampang.go.id'],
            [
                'name'     => 'Staf Penyusun Perundangan',
                'password' => bcrypt('password'),
                'role'     => 'admin',
            ]
        );

        // ── U4 — Asisten I Bupati ───────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'asisten1@sampang.go.id'],
            [
                'name'     => 'Asisten I Bidang Pemerintahan',
                'password' => bcrypt('password'),
                'role'     => 'asisten',
            ]
        );

        // ── U5 — Sekretaris Daerah ──────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'sekda@sampang.go.id'],
            [
                'name'     => 'Sekretaris Daerah Kab. Sampang',
                'password' => bcrypt('password'),
                'role'     => 'sekda',
            ]
        );

        // ── U6 — Bupati ─────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'bupati@sampang.go.id'],
            [
                'name'     => 'Bupati Sampang',
                'password' => bcrypt('password'),
                'role'     => 'bupati',
            ]
        );

        // ── U1 — SKPD Pengusul (contoh: Dinas Pendidikan) ───────────────
        User::updateOrCreate(
            ['email' => 'diknas@sampang.go.id'],
            [
                'name'     => 'Operator Dinas Pendidikan',
                'password' => bcrypt('password'),
                'role'     => 'user',
                'group_id' => $groupDiknas?->id,
                'opd_name' => 'Dinas Pendidikan',
            ]
        );

        // ── U1 — SKPD Pengusul (contoh: Dinas Kesehatan) ────────────────
        User::updateOrCreate(
            ['email' => 'kesehatan@sampang.go.id'],
            [
                'name'     => 'Operator Dinas Kesehatan',
                'password' => bcrypt('password'),
                'role'     => 'user',
                'group_id' => $groupKesehatan?->id,
                'opd_name' => 'Dinas Kesehatan',
            ]
        );

        // ── Dev — Developer Account (non-production only) ────────────────
        User::updateOrCreate(
            ['email' => 'dev@localhost'],
            [
                'name'     => 'Developer',
                'password' => bcrypt('devpassword'),
                'role'     => 'dev',
            ]
        );
    }
}
