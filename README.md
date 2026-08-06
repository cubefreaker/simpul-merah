# SIMPUL MERAH 

**Sistem Informasi Pengusulan Produk Hukum Daerah**

Aplikasi berbasis web untuk digitalisasi proses pengusulan, pembahasan, dan pengundangan produk hukum daerah (Peraturan Bupati dan Surat Keputusan Bupati). Dibangun dengan menggunakan ekosistem Laravel, Livewire, Tailwind CSS, dan komponen antarmuka Flux UI.

## Fitur Utama

- **Pengajuan Usulan Digital**: SKPD dapat mengusulkan produk hukum secara online dan memantau status secara *real-time*.
- **Alur Disposisi Berjenjang**: Sistem disposisi yang mengalir secara hierarkis (SEKDA ➡ Asisten 1 ➡ Kabag Hukum ➡ Staf JF).
- **Verifikasi & Persetujuan Sistem**: Verifikasi berjenjang dari tingkat Staf JF hingga persetujuan akhir oleh Bupati yang terintegrasi di dalam sistem.
- **Auto-Generate NPKMD**: Sistem akan meng-generate dokumen Nota Pengajuan (NPKMD) secara otomatis berdasarkan metadata usulan saat verifikasi akhir di tingkat SEKDA.
- **Manajemen Dokumen**: Validasi dan pengelolaan draf berbasis dokumen (mendukung format `.doc`/`.docx`).
- **Global Loading Overlay**: Indikator *loading* interaktif terpusat menggunakan Livewire untuk memastikan kenyamanan pengguna (UX) selama request diproses.
- **Role-based Access Control (RBAC)**: Pengaturan hak akses yang ketat untuk 6 peran pengguna spesifik (Pengusul, Kabag Hukum, Staf JF, Asisten I, SEKDA, Bupati).

## Aktor dan Peran Pengguna

| Role | Keterangan | Fungsi Utama |
| :--- | :--- | :--- |
| **U1** | **User SKPD** (Pengusul) | Mengisi formulir usulan, mengunggah draf usulan, dan memantau progres produk hukum. |
| **U2** | **Kabag Hukum** (Super Admin)| Menerima disposisi, mendelegasikan tugas ke staf, memverifikasi draf hasil kajian staf. |
| **U3** | **Staf JF** (Admin) | Pengkajian (di luar sistem), unggah draf akhir yang sudah dibahas, *update* status pengundangan. |
| **U4** | **Asisten I** | Meneruskan disposisi dari SEKDA ke Kabag Hukum, memverifikasi draf sebelum ke SEKDA. |
| **U5** | **SEKDA** | Melakukan disposisi awal usulan, memverifikasi draf akhir, trigger fitur auto-generate NPKMD. |
| **U6** | **Bupati** | Memberikan persetujuan akhir penetapan produk hukum. |

## Alur Bisnis (Ringkasan)

1. **Pengusulan**: SKPD (U1) mengajukan draf awal usulan produk hukum (Perbup/SK).
2. **Disposisi (Turun)**: Usulan didisposisikan secara berjenjang dari SEKDA (U5) ➡ Asisten I (U4) ➡ Kabag Hukum (U2) ➡ Staf JF (U3).
3. **Kajian**: Staf JF (U3) melakukan kajian dan harmonisasi di luar sistem bersama SKPD pengusul, kemudian mengunggah Draf Final ke aplikasi.
4. **Verifikasi (Naik)**: Draf diverifikasi berjenjang oleh Kabag Hukum (U2) ➡ Asisten I (U4) ➡ SEKDA (U5).
5. **Persetujuan**: Setelah verifikasi SEKDA dan sistem men-generate dokumen NPKMD, Bupati (U6) memberikan persetujuan ("Setuju") melalui sistem.
6. **Pengundangan**: Setelah ditandatangani dan ditetapkan, Staf JF (U3) mengupdate status akhir usulan menjadi "Telah Diundangkan".

*(Untuk detail lebih lanjut terkait alur bisnis, silakan lihat dokumen referensi: [BUSINESS FLOW](PRD/BUSINESS_FLOW.md))*

## Instalasi dan Setup Development

1. *Clone* repositori ini.
2. Install dependensi PHP dan Node.js:
   ```bash
   composer install
   npm install
   ```
3. Salin dan sesuaikan konfigurasi *environment*:
   ```bash
   cp .env.example .env
   ```
4. Generate key aplikasi:
   ```bash
   php artisan key:generate
   ```
5. Jalankan migrasi beserta *seeder* untuk mengisi role, izin, dan *dummy user*:
   ```bash
   php artisan migrate --seed
   ```
6. Build aset frontend (Tailwind & Vite):
   ```bash
   npm run build
   ```
7. Jalankan server lokal:
   ```bash
   php artisan serve
   ```

## Catatan Tambahan (Pengembang)

Aplikasi ini telah mengimplementasikan sistem *loading indicator* khusus menggunakan `wire:loading.delay.longest` bawaan Livewire. Komponen tersebut dapat ditemukan pada `@livewire('App\Livewire\LoadingWrapper')` dan disarankan untuk selalu diaplikasikan pada setiap interaksi asinkron (*wire:click*, *wire:submit*, form, tabel).

---
**Lisensi**: [MIT License](https://opensource.org/licenses/MIT)