<?php

namespace App\Console\Commands;

use App\Models\QuickReply;
use Illuminate\Console\Command;

class ImportBestFaq extends Command
{
    protected $signature = 'best:import-faq {--reset : Hapus FAQ PT BEST yang pernah diimport sebelumnya sebelum import ulang}';

    protected $description = 'Import FAQ PT BEST CORPORATION SYARIAH ke knowledge base quick replies.';

    public function handle(): int
    {
        $sourceTag = '[Sumber FAQ PT BEST]';

        if ($this->option('reset')) {
            QuickReply::where('content', 'like', $sourceTag . '%')->delete();
            $this->info('FAQ PT BEST lama berhasil dihapus.');
        }

        $faqs = $this->getFaqs($sourceTag);
        $created = 0;
        $updated = 0;

        foreach ($faqs as $faq) {
            $existing = QuickReply::where('title', $faq['title'])->first();

            if ($existing) {
                $existing->update(['content' => $faq['content']]);
                $updated++;
                continue;
            }

            QuickReply::create($faq);
            $created++;
        }

        $this->info("Import selesai. Dibuat: {$created}, diperbarui: {$updated}.");

        return self::SUCCESS;
    }

    private function getFaqs(string $sourceTag): array
    {
        return [
            [
                'title' => 'Apa itu PT BEST',
                'content' => $sourceTag . ' PT Bandung Eco Sinergi Teknologi (PT BEST) adalah perusahaan penjualan langsung atau direct selling yang memasarkan produk-produk berkualitas yang dibutuhkan masyarakat. Perusahaan ini disebut menjalankan program pemasaran dengan sistem syariah.',
            ],
            [
                'title' => 'Bidang usaha PT BEST',
                'content' => $sourceTag . ' Best Corporation Syariah bergerak di bidang perdagangan produk otomotif, pertanian, kecantikan, peternakan, dan kesehatan.',
            ],
            [
                'title' => 'Konsep bisnis PT BEST',
                'content' => $sourceTag . ' PT BEST menggunakan konsep network marketing sebagai strategi bisnis untuk membuka peluang usaha bagi masyarakat luas dan membantu meningkatkan taraf hidup mitra bisnisnya.',
            ],
            [
                'title' => 'Siapa yang bisa gabung',
                'content' => $sourceTag . ' Menurut materi FAQ perusahaan, bisnis PT BEST dapat dijalankan oleh siapa pun, termasuk pengusaha, pegawai, buruh, pelajar, maupun yang belum bekerja, selama mau menjalankan sistem bisnis dengan benar dan konsisten.',
            ],
            [
                'title' => 'Kenapa direct selling',
                'content' => $sourceTag . ' Sistem direct selling dipakai agar masyarakat punya peluang menjadi mitra usaha PT BEST, memperoleh keuntungan dari penjualan produk, dan membangun jaringan pemasaran tanpa batasan latar belakang dan waktu kerja yang kaku.',
            ],
            [
                'title' => 'Apakah PT BEST syariah',
                'content' => $sourceTag . ' Materi perusahaan menyebut PT BEST berkomitmen menjalankan sistem syariah sesuai Fatwa DSN MUI Nomor 75/DSN-MUI/VII/2009 tentang Pedoman Penjualan Langsung Berjenjang Syariah.',
            ],
            [
                'title' => 'Legalitas PT BEST',
                'content' => $sourceTag . ' Situs FAQ PT BEST menyebut perusahaan memiliki legalitas lengkap, termasuk NIB 8120001861974, SIUPL 99/SIPT/SIUPL/04/2021, PSE 001027.01/DJAU.PSE/06/2021, dan terdaftar sebagai anggota AP2LI.',
            ],
            [
                'title' => 'NPWP PT BEST',
                'content' => $sourceTag . ' NPWP PT BEST yang tercantum pada materi FAQ adalah 82.642.266.9-429.000.',
            ],
            [
                'title' => 'Akta pendirian PT BEST',
                'content' => $sourceTag . ' Akta pendirian yang tercantum pada materi FAQ adalah Akta Pendirian Nomor 05 tanggal 17 Mei 2017, dengan notaris Drs. Yudi Priadi, S.H.',
            ],
            [
                'title' => 'Produk unggulan PT BEST',
                'content' => $sourceTag . ' Pada halaman FAQ, PT BEST menekankan bahwa produknya bermanfaat, berkualitas, dan berada dalam kategori yang dibutuhkan pasar. Untuk daftar produk detail, pelanggan dapat menanyakan kategori seperti otomotif, kesehatan, kecantikan, pertanian, atau kategori lain yang tersedia di materi perusahaan.',
            ],
            [
                'title' => 'Alasan bergabung PT BEST',
                'content' => $sourceTag . ' Beberapa alasan yang disebut di FAQ untuk bergabung adalah legalitas lengkap, owner jelas, kantor milik sendiri, produk berkualitas, marketing plan bagus, dan support system yang aktif.',
            ],
            [
                'title' => 'Kantor PT BEST',
                'content' => $sourceTag . ' Kantor PT BEST beralamat di Grand Surapati Core Blok B 9-10 B 23-25, Jl. K.H.P. Hasan Mustopa No. 39, Pasirlayung, Kec. Cibeunying Kidul, Kota Bandung, Jawa Barat 40192. Materi FAQ juga menyebut bangunan kantor milik sendiri dan sudah memiliki perwakilan kantor regional, distributor, dan stokis di berbagai kota.',
            ],
            [
                'title' => 'Owner PT BEST',
                'content' => $sourceTag . ' Pada halaman FAQ disebut owner perusahaan jelas dan mudah ditemui. Materi itu juga menyebut rumah owner masih satu kompleks dengan kantor.',
            ],
            [
                'title' => 'Reward PT BEST',
                'content' => $sourceTag . ' FAQ PT BEST menjelaskan bahwa mitra berpeluang mendapatkan reward seperti motor, mobil, rumah, liburan ke luar negeri, dan haji atau umroh melalui marketing plan perusahaan.',
            ],
            [
                'title' => 'Marketing plan PT BEST',
                'content' => $sourceTag . ' Marketing plan PT BEST pada materi FAQ disebut menggunakan hybrid system, yaitu kombinasi konsep penjualan retail, MLM binary, dan MLM breakaway.',
            ],
            [
                'title' => 'Bonus sponsor PT BEST',
                'content' => $sourceTag . ' Bonus sponsor dijelaskan sebagai komisi dari penjualan produk PT BEST. Pada materi FAQ tertulis bonus sponsor sebesar 16 persen dari nilai peringkat kemitraan, dengan contoh Rp400.000 per 14 box produk yang terjual.',
            ],
            [
                'title' => 'Bonus pengembangan PT BEST',
                'content' => $sourceTag . ' Bonus pengembangan atau bonus pasangan diperoleh saat mitra membangun tim kiri dan kanan. Materi FAQ mencontohkan bonus 1 pasang sebesar Rp100.000, dengan batas bonus pasangan harian atau flush out sebanyak 12 pasang per hari.',
            ],
            [
                'title' => 'Flush out PT BEST',
                'content' => $sourceTag . ' Pada FAQ disebut ada batasan flush out 12 pasang per hari untuk bonus pasangan, atau setara Rp1.200.000 per hari per ID kemitraan.',
            ],
            [
                'title' => 'Apakah modal hangus',
                'content' => $sourceTag . ' Materi FAQ menegaskan bahwa dana yang dikeluarkan dianggap sebagai transaksi jual beli produk karena mitra menerima produk dengan nilai yang setara, sehingga tidak dijelaskan sebagai modal yang hangus.',
            ],
            [
                'title' => 'Alamat PT BEST',
                'content' => $sourceTag . ' Alamat PT BEST adalah Grand Surapati Core Blok B 9-10 B 23-25, Jl. K.H.P. Hasan Mustopa No. 39, Pasirlayung, Kec. Cibeunying Kidul, Kota Bandung, Jawa Barat 40192.',
            ],
        ];
    }
}
