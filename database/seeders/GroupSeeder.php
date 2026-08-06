<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;

class GroupSeeder extends Seeder
{
    /**
     * Daftar OPD / Instansi Kabupaten Sampang.
     * Group ini dipakai sebagai pengelompokan U1 (SKPD) pengusul produk hukum.
     */
    public function run(): void
    {
        $opd = [
            ['name' => 'Sekretariat Daerah',                      'description' => 'Setda Kabupaten Sampang'],
            ['name' => 'Sekretariat DPRD',                        'description' => 'Sekretariat DPRD Kabupaten Sampang'],
            ['name' => 'Inspektorat Daerah',                      'description' => 'Inspektorat Kabupaten Sampang'],
            ['name' => 'Dinas Pendidikan',                        'description' => 'Dinas Pendidikan Kabupaten Sampang'],
            ['name' => 'Dinas Kesehatan',                         'description' => 'Dinas Kesehatan Kabupaten Sampang'],
            ['name' => 'Dinas Pekerjaan Umum dan Penataan Ruang', 'description' => 'DPUPR Kabupaten Sampang'],
            ['name' => 'Dinas Perumahan Rakyat dan Kawasan Permukiman', 'description' => 'Dinas Perkimtan Kabupaten Sampang'],
            ['name' => 'Satuan Polisi Pamong Praja',              'description' => 'Satpol PP Kabupaten Sampang'],
            ['name' => 'Dinas Sosial',                            'description' => 'Dinas Sosial Kabupaten Sampang'],
            ['name' => 'Dinas Tenaga Kerja',                      'description' => 'Disnaker Kabupaten Sampang'],
            ['name' => 'Dinas Pemberdayaan Perempuan dan Perlindungan Anak', 'description' => 'DP3A Kabupaten Sampang'],
            ['name' => 'Dinas Ketahanan Pangan',                  'description' => 'Dinas Ketahanan Pangan Kabupaten Sampang'],
            ['name' => 'Dinas Lingkungan Hidup',                  'description' => 'DLH Kabupaten Sampang'],
            ['name' => 'Dinas Kependudukan dan Pencatatan Sipil', 'description' => 'Disdukcapil Kabupaten Sampang'],
            ['name' => 'Dinas Pemberdayaan Masyarakat dan Desa',  'description' => 'DPMD Kabupaten Sampang'],
            ['name' => 'Dinas Pengendalian Penduduk dan KB',      'description' => 'DPPKB Kabupaten Sampang'],
            ['name' => 'Dinas Perhubungan',                       'description' => 'Dishub Kabupaten Sampang'],
            ['name' => 'Dinas Komunikasi dan Informatika',        'description' => 'Diskominfo Kabupaten Sampang'],
            ['name' => 'Dinas Koperasi, UKM, Perindustrian dan Perdagangan', 'description' => 'Diskopumperindag Kabupaten Sampang'],
            ['name' => 'Dinas Penanaman Modal dan PTSP',          'description' => 'DPMPTSP Kabupaten Sampang'],
            ['name' => 'Dinas Kepemudaan, Olahraga dan Pariwisata', 'description' => 'Dispopar Kabupaten Sampang'],
            ['name' => 'Dinas Pertanian dan Ketahanan Pangan',    'description' => 'Dinas Pertanian Kabupaten Sampang'],
            ['name' => 'Dinas Perikanan',                         'description' => 'Dinas Perikanan Kabupaten Sampang'],
            ['name' => 'Badan Perencanaan Pembangunan Daerah',    'description' => 'Bappeda Kabupaten Sampang'],
            ['name' => 'Badan Pengelolaan Keuangan dan Aset Daerah', 'description' => 'BPKAD Kabupaten Sampang'],
            ['name' => 'Badan Kepegawaian dan Pengembangan SDM',  'description' => 'BKPSDM Kabupaten Sampang'],
            ['name' => 'Badan Penanggulangan Bencana Daerah',     'description' => 'BPBD Kabupaten Sampang'],
            ['name' => 'Badan Kesatuan Bangsa dan Politik',       'description' => 'Bakesbangpol Kabupaten Sampang'],
            ['name' => 'Rumah Sakit Umum Daerah',                 'description' => 'RSUD Kabupaten Sampang'],
            ['name' => 'Kecamatan Sampang',                       'description' => 'Kecamatan Sampang'],
            ['name' => 'Kecamatan Camplong',                      'description' => 'Kecamatan Camplong'],
            ['name' => 'Kecamatan Omben',                         'description' => 'Kecamatan Omben'],
            ['name' => 'Kecamatan Kedungdung',                    'description' => 'Kecamatan Kedungdung'],
            ['name' => 'Kecamatan Tambelangan',                   'description' => 'Kecamatan Tambelangan'],
            ['name' => 'Kecamatan Robatal',                       'description' => 'Kecamatan Robatal'],
        ];

        foreach ($opd as $item) {
            Group::updateOrCreate(
                ['name' => $item['name']],
                ['description' => $item['description']]
            );
        }
    }
}
