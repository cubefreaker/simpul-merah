<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'group_id',
        'opd_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the form submissions for this user
     */
    public function formSubmissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Get the group for this user (OPD/Instansi, khusus U1-SKPD)
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // ============================================================
    // ROLE HELPERS — Backward Compatible
    // ============================================================

    /** @deprecated Gunakan isSkpd() untuk konteks PRD */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /** @deprecated Gunakan isStaf() untuk konteks PRD */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** @deprecated Gunakan isKabagHukum() untuk konteks PRD */
    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    // ============================================================
    // ROLE HELPERS — PRD Context (U1–U6 + Dev)
    // ============================================================

    /** U1 — SKPD / OPD Pengusul */
    public function isSkpd(): bool
    {
        return $this->role === 'user';
    }

    /** U2 — Kepala Bagian Hukum (Super Admin) */
    public function isKabagHukum(): bool
    {
        return $this->role === 'superadmin';
    }

    /** U3 — Staf JF Penyusun Perundang-undangan (Admin) */
    public function isStaf(): bool
    {
        return $this->role === 'admin';
    }

    /** U4 — Asisten I Bupati */
    public function isAsisten(): bool
    {
        return $this->role === 'asisten';
    }

    /** U5 — Sekretaris Daerah */
    public function isSekda(): bool
    {
        return $this->role === 'sekda';
    }

    /** U6 — Bupati */
    public function isBupati(): bool
    {
        return $this->role === 'bupati';
    }

    /** Developer — akses penuh + impersonation (non-production only) */
    public function isDev(): bool
    {
        return $this->role === 'dev';
    }

    /**
     * Cek apakah user adalah developer dengan akses aktif.
     * Otomatis false di environment production.
     */
    public function hasDevAccess(): bool
    {
        return $this->isDev() && !app()->environment('production');
    }

    /**
     * Cek apakah user adalah verifikator / bisa melakukan disposisi.
     * Berlaku untuk U2, U4, U5.
     */
    public function isVerifikator(): bool
    {
        return in_array($this->role, ['superadmin', 'asisten', 'sekda']);
    }

    /**
     * Cek apakah user bisa melihat semua usulan (monitoring).
     * Berlaku untuk U2, U5, U6, dan Dev.
     */
    public function canMonitorAll(): bool
    {
        return in_array($this->role, ['superadmin', 'sekda', 'bupati', 'dev'])
            || $this->hasDevAccess();
    }

    /**
     * Dapatkan label role yang mudah dibaca untuk tampilan UI.
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'user'       => 'SKPD / Pengusul',
            'superadmin' => 'Kabag Hukum',
            'admin'      => 'Staf JF Penyusun',
            'asisten'    => 'Asisten I',
            'sekda'      => 'SEKDA',
            'bupati'     => 'Bupati',
            'dev'        => 'Developer',
            default      => ucfirst($this->role),
        };
    }
}
