<?php

namespace App\Console\Commands;

use App\Models\QuickReply;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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

        Cache::forget('best_ai_quick_reply_knowledge');

        $this->info("Import selesai. Dibuat: {$created}, diperbarui: {$updated}.");
        $this->info('Cache knowledge BEST AI berhasil dibersihkan.');

        return self::SUCCESS;
    }

    private function getFaqs(string $sourceTag): array
    {
        return array_merge(
            $this->getCompanyFaqs($sourceTag),
            $this->getMarketingPlanFaqs($sourceTag),
            $this->getProductFaqs($sourceTag),
            $this->getBlogFaqs($sourceTag),
            $this->getContactFaqs($sourceTag),
        );
    }

    private function getCompanyFaqs(string $sourceTag): array
    {
        return [
            [
                'title' => 'Apa itu PT BEST',
                'content' => $sourceTag . ' PT Bandung Eco Sinergi Teknologi (PT BEST) adalah perusahaan penjualan langsung atau direct selling yang memasarkan produk-produk berkualitas yang dibutuhkan masyarakat. Perusahaan ini disebut menjalankan program pemasaran dengan sistem syariah.',
            ],
            [
                'title' => 'Siapa owner PT BEST',
                'content' => $sourceTag . ' Owner PT BEST yang tercantum pada knowledge ini adalah Bapak H. Febrian Agung Budi Prasetyo.',
            ],
            [
                'title' => 'Nama lengkap PT BEST',
                'content' => $sourceTag . ' Nama lengkap PT BEST yang sering ditampilkan pada materi perusahaan adalah PT Bandung Eco Sinergi Teknologi.',
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
                'title' => 'Visi PT BEST',
                'content' => $sourceTag . ' Menurut draft FAQ support bisnisraksasa.com per 30 April 2026, visi perusahaan yang ditampilkan adalah menjadi perusahaan bebas riba yang membantu umat terbebas dari hutang dan riba.',
            ],
            [
                'title' => 'Misi PT BEST',
                'content' => $sourceTag . ' Menurut draft FAQ support bisnisraksasa.com, misi yang ditampilkan meliputi menyediakan produk berkualitas karya putra bangsa, menciptakan pengusaha sukses berakhlak mulia, melahirkan SDM yang bermanfaat, memberi peluang usaha bagi masyarakat, dan membantu meningkatkan perekonomian bangsa.',
            ],
            [
                'title' => 'Motto PT BEST',
                'content' => $sourceTag . ' Menurut draft FAQ support bisnisraksasa.com, motto perusahaan yang ditampilkan adalah Go Berkah No Riba.',
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
                'title' => 'Alasan bergabung PT BEST',
                'content' => $sourceTag . ' Beberapa alasan yang disebut di FAQ untuk bergabung adalah legalitas lengkap, owner jelas, kantor milik sendiri, produk berkualitas, marketing plan bagus, dan support system yang aktif.',
            ],
            [
                'title' => 'Kantor PT BEST',
                'content' => $sourceTag . ' Kantor PT BEST beralamat di Grand Surapati Core Blok B 9-10 B 23-25, Jl. K.H.P. Hasan Mustopa No. 39, Pasirlayung, Kec. Cibeunying Kidul, Kota Bandung, Jawa Barat 40192. Materi FAQ juga menyebut bangunan kantor milik sendiri dan sudah memiliki perwakilan kantor regional, distributor, dan stokis di berbagai kota.',
            ],
            [
                'title' => 'Alamat PT BEST',
                'content' => $sourceTag . ' Alamat PT BEST yang tercantum pada materi FAQ perusahaan adalah Grand Surapati Core Blok B 9-10 B 23-25, Jl. K.H.P. Hasan Mustopa No. 39, Pasirlayung, Kec. Cibeunying Kidul, Kota Bandung, Jawa Barat 40192.',
            ],
            [
                'title' => 'Apakah bisnisraksasa.com website resmi PT BEST',
                'content' => $sourceTag . ' Menurut disclaimer yang ditampilkan pada draft FAQ bisnisraksasa.com, situs tersebut bukan website official PT BEST. Situs itu diposisikan sebagai web support mentor BKB Team untuk membantu member berkembang secara online maupun offline.',
            ],
            [
                'title' => 'Alamat kantor pusat PT BEST versi support',
                'content' => $sourceTag . ' Menurut halaman kontak bisnisraksasa.com per 30 April 2026, kantor pusat yang ditampilkan adalah Grand Surapati Core Blok B 9-11, Jl. PH. H. Mustofa No. 39, Kota Bandung, Jawa Barat. Jika ada perbedaan detail alamat, sarankan pelanggan konfirmasi lagi ke admin atau agent.',
            ],
        ];
    }

    private function getMarketingPlanFaqs(string $sourceTag): array
    {
        return [
            [
                'title' => 'Marketing plan PT BEST',
                'content' => $sourceTag . ' Marketing plan PT BEST disebut berbasis direct selling atau penjualan langsung dengan pendekatan syariah. Pada materi FAQ perusahaan juga disebut memakai hybrid system, yaitu kombinasi konsep penjualan retail, MLM binary, dan MLM breakaway.',
            ],
            [
                'title' => 'Cara gabung reseller BEST',
                'content' => $sourceTag . ' Menurut draft FAQ bisnisraksasa.com, calon mitra diarahkan memilih salah satu paket reseller lalu melanjutkan pendaftaran melalui tombol WhatsApp atau kanal konsultasi yang tersedia.',
            ],
            [
                'title' => 'Paket reseller BEST',
                'content' => $sourceTag . ' Draft FAQ bisnisraksasa.com menampilkan lima paket reseller utama: Basic, Reguler, Bussines, Executive, dan Priority.',
            ],
            [
                'title' => 'Harga paket reseller BEST',
                'content' => $sourceTag . ' Menurut halaman marketing plan pada draft FAQ bisnisraksasa.com per 30 April 2026, kisaran harga paket yang ditampilkan mulai sekitar Rp2.850.000 untuk Basic hingga Rp85.350.000 untuk Priority. Karena harga bisa berubah, sarankan cek ulang ke admin atau agent.',
            ],
            [
                'title' => 'Perbedaan paket reseller BEST',
                'content' => $sourceTag . ' Perbedaan utama antar paket disebut ada pada jumlah hak usaha, jumlah produk yang didapat, potensi bonus pengembangan harian, dan potensi bonus prestasi pada masing-masing paket.',
            ],
            [
                'title' => 'Hak usaha BEST',
                'content' => $sourceTag . ' Pada materi paket bisnis, hak usaha ditampilkan sebagai besaran kepemilikan paket usaha. Semakin tinggi paket yang dipilih, semakin besar jumlah hak usaha yang disebutkan.',
            ],
            [
                'title' => 'Produk dalam paket BEST',
                'content' => $sourceTag . ' Menurut draft FAQ bisnisraksasa.com, setiap paket disertai produk bebas pilih dalam jumlah tertentu. Contoh yang disebut adalah paket Basic sekitar 16 box, sedangkan paket Priority sekitar 496 box.',
            ],
            [
                'title' => 'Reward PT BEST',
                'content' => $sourceTag . ' Materi FAQ PT BEST menjelaskan bahwa mitra berpeluang mendapatkan reward seperti motor, mobil, rumah, liburan ke luar negeri, dan haji atau umroh melalui marketing plan perusahaan. Sampaikan sebagai potensi, bukan hasil yang pasti.',
            ],
            [
                'title' => 'Komponen bonus PT BEST',
                'content' => $sourceTag . ' Menurut draft FAQ bisnisraksasa.com, komponen bonus yang disebut meliputi bonus sponsor, bonus pembinaan, bonus penjualan langsung, bonus pengembangan, daily overflow, group rank, ambassador rank, monthly sales performance, dan leading monthly sales.',
            ],
            [
                'title' => 'Bonus sponsor PT BEST',
                'content' => $sourceTag . ' Bonus sponsor dijelaskan sebagai komisi dari penjualan produk PT BEST. Pada materi FAQ tertulis bonus sponsor sebesar 16 persen dari nilai peringkat kemitraan, dengan contoh Rp400.000 per 14 box produk yang terjual.',
            ],
            [
                'title' => 'Bonus penjualan dan pembinaan PT BEST',
                'content' => $sourceTag . ' Draft FAQ bisnisraksasa.com menyebut adanya bonus pembinaan dan bonus penjualan langsung sebagai bagian dari marketing plan. Jika pelanggan meminta nominal detail, arahkan untuk konfirmasi ke admin atau agent karena rincian skema dapat berubah mengikuti materi resmi terbaru.',
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
                'title' => 'Apakah penghasilan BEST pasti',
                'content' => $sourceTag . ' Materi support menampilkan potensi bonus dan reward dalam nominal besar, tetapi sebaiknya selalu dijelaskan sebagai potensi dengan syarat dan ketentuan berlaku. Jangan menyampaikan seolah hasilnya pasti untuk setiap mitra.',
            ],
            [
                'title' => 'Training reseller BEST',
                'content' => $sourceTag . ' Menurut draft FAQ bisnisraksasa.com, support yang disebut untuk reseller baru meliputi training center PT BEST Bogor, materi pengembangan bisnis, panduan Google Ads, zoom training produk, dan kelas pembinaan tertentu untuk beberapa paket.',
            ],
            [
                'title' => 'Bisnis BEST dari rumah',
                'content' => $sourceTag . ' Menurut informasi pada halaman utama support bisnisraksasa.com, bisnis ini diposisikan bisa dijalankan dari rumah dan dari HP dengan pendekatan pemasaran online maupun offline.',
            ],
            [
                'title' => 'Apakah modal hangus',
                'content' => $sourceTag . ' Materi FAQ menegaskan bahwa dana yang dikeluarkan dianggap sebagai transaksi jual beli produk karena mitra menerima produk dengan nilai yang setara, sehingga tidak dijelaskan sebagai modal yang hangus.',
            ],
        ];
    }

    private function getProductFaqs(string $sourceTag): array
    {
        return [
            [
                'title' => 'Produk unggulan PT BEST',
                'content' => $sourceTag . ' Pada halaman FAQ, PT BEST menekankan bahwa produknya berada dalam kategori yang dibutuhkan pasar. Menurut draft FAQ bisnisraksasa.com, kategori yang sering ditampilkan meliputi additif kendaraan, pupuk pertanian dan perkebunan, herbal kesehatan, skincare dan kecantikan, minuman kesehatan, serta pembersih area tubuh.',
            ],
            [
                'title' => 'Kategori produk PT BEST',
                'content' => $sourceTag . ' Kategori produk yang ditampilkan di bisnisraksasa.com meliputi produk additif untuk kendaraan, produk pupuk untuk pertanian dan perkebunan, produk herbal untuk kesehatan, produk skincare dan kecantikan, produk minuman untuk kesehatan tubuh, serta produk pembersih untuk kesehatan area tubuh.',
            ],
            [
                'title' => 'Produk otomotif PT BEST',
                'content' => $sourceTag . ' Produk kendaraan yang ditampilkan pada draft FAQ bisnisraksasa.com antara lain Eco Racing, Eco Diesel, dan Eco Racing Nano Tech atau Nano Oil.',
            ],
            [
                'title' => 'Produk additif untuk kendaraan',
                'content' => $sourceTag . ' Pada kategori produk additif untuk kendaraan di bisnisraksasa.com, produk yang ditampilkan antara lain Eco Racing, Eco Diesel, dan Eco Racing Nano Tech atau Nano Oil.',
            ],
            [
                'title' => 'Manfaat additif kendaraan BEST',
                'content' => $sourceTag . ' Menurut materi support bisnisraksasa.com, manfaat umum produk additif kendaraan disebut meliputi membantu pembakaran, membersihkan dan merawat mesin, meningkatkan akselerasi, mengurangi knocking, dan membantu menekan emisi gas buang.',
            ],
            [
                'title' => 'Produk pupuk PT BEST',
                'content' => $sourceTag . ' Pada kategori pertanian, draft FAQ bisnisraksasa.com paling menonjolkan Eco Farming sebagai pupuk organik super aktif untuk membantu kesuburan tanah dan efisiensi penggunaan pupuk anorganik.',
            ],
            [
                'title' => 'Produk pertanian dan perkebunan PT BEST',
                'content' => $sourceTag . ' Pada kategori produk pupuk untuk pertanian dan perkebunan, bisnisraksasa.com menonjolkan Eco Farming sebagai pupuk organik super aktif.',
            ],
            [
                'title' => 'Produk Agrosawit PT BEST',
                'content' => $sourceTag . ' Menurut artikel bisnisraksasa.com berjudul "Pupuk Agrosawit, Rahasia Panen Melimpah dari Brilian Biz", Agrosawit dijelaskan sebagai Premium Water Soluble Fertilizer yang mudah larut dan cepat diserap tanaman. Produk ini diposisikan untuk membantu meningkatkan fungsi akar, batang, dan daun pada tanaman sawit.',
            ],
            [
                'title' => 'Manfaat Eco Farming',
                'content' => $sourceTag . ' Menurut materi support bisnisraksasa.com, manfaat Eco Farming yang ditampilkan antara lain membantu menekan biaya produksi, mempercepat masa panen, meningkatkan imunitas tanaman, menambah unsur hara tanah, dan membantu meningkatkan hasil serta kualitas produksi.',
            ],
            [
                'title' => 'Produk herbal PT BEST',
                'content' => $sourceTag . ' Beberapa produk herbal untuk kesehatan yang muncul pada bisnisraksasa.com antara lain HABSPRO, ECO VICO, B-MAXX, dan RED ONE BOOST.',
            ],
            [
                'title' => 'Posisi produk herbal PT BEST',
                'content' => $sourceTag . ' Saat menjelaskan produk herbal PT BEST, gunakan bahasa aman seperti produk ini dipasarkan sebagai suplemen herbal atau menurut informasi di situs, produk ini ditujukan untuk kebutuhan tertentu. Hindari membuat klaim medis seolah sudah terbukti klinis bila detail verifikasinya belum tersedia.',
            ],
            [
                'title' => 'Produk herbal untuk kesehatan',
                'content' => $sourceTag . ' Di kategori produk herbal untuk kesehatan, bisnisraksasa.com menampilkan HABSPRO, ECO VICO, B-MAXX, dan RED ONE BOOST.',
            ],
            [
                'title' => 'Produk skincare PT BEST',
                'content' => $sourceTag . ' Pada draft FAQ bisnisraksasa.com, produk skincare yang ditampilkan antara lain LVN Lipcream, LVN Skin Serum, dan LVN Day and Night Cream.',
            ],
            [
                'title' => 'Produk skincare dan kecantikan',
                'content' => $sourceTag . ' Pada kategori produk skincare dan kecantikan, bisnisraksasa.com menampilkan LVN Lipcream, LVN Skin Serum, dan LVN Day and Night Cream.',
            ],
            [
                'title' => 'Keunggulan skincare PT BEST',
                'content' => $sourceTag . ' Narasi umum pada materi support bisnisraksasa.com menekankan tampilan natural, kelembapan, kecerahan kulit, perlindungan kulit, dan pada beberapa produk disebut ada nomor BPOM atau label halal.',
            ],
            [
                'title' => 'Produk minuman kesehatan PT BEST',
                'content' => $sourceTag . ' Beberapa produk minuman untuk kesehatan tubuh yang disebut di bisnisraksasa.com antara lain EVITGO 100, ECOMAXX Coffee, dan ECONAXX Coffee.',
            ],
            [
                'title' => 'Produk minuman untuk kesehatan tubuh',
                'content' => $sourceTag . ' Pada kategori produk minuman untuk kesehatan tubuh, bisnisraksasa.com menampilkan EVITGO 100 serta ECOMAXX Coffee dan ECONAXX Coffee.',
            ],
            [
                'title' => 'Produk kebersihan area tubuh PT BEST',
                'content' => $sourceTag . ' Pada kategori produk pembersih untuk kesehatan area tubuh, bisnisraksasa.com menampilkan LVN Hygiene for Gentle Man, LVN Hygiene Spray for Man, LVN Crystal-V, LVN Crystal-Q, dan LVN Hand Moist.',
            ],
            [
                'title' => 'Produk pembersih untuk kesehatan area tubuh',
                'content' => $sourceTag . ' Pada kategori produk pembersih untuk kesehatan area tubuh, bisnisraksasa.com menampilkan LVN Hygiene for Gentle Man, LVN Hygiene Spray for Man, LVN Crystal-V, LVN Crystal-Q, dan LVN Hand Moist.',
            ],
            [
                'title' => 'BPOM dan halal produk BEST',
                'content' => $sourceTag . ' Menurut draft FAQ bisnisraksasa.com, beberapa halaman produk menampilkan nomor BPOM, PIRT, dan atau nomor halal. Jika pelanggan meminta nomor spesifik per produk, sarankan verifikasi lagi ke admin atau agent agar datanya sesuai materi resmi terbaru.',
            ],
        ];
    }


    private function getBlogFaqs(string $sourceTag): array
    {
        return [
            [
                'title' => 'Blog PT BEST',
                'content' => $sourceTag . ' PT BEST memiliki blog resmi yang dapat diakses melalui https://bestcorporation.co.id/blog/. Blog ini berisi artikel terkait produk, tips bisnis, dan informasi seputar perusahaan.',
            ],
        ];
    }

    private function getContactFaqs(string $sourceTag): array
    {
        return [
            [
                'title' => 'Kontak bisnisraksasa',
                'content' => $sourceTag . ' Menurut halaman kontak bisnisraksasa.com, kanal kontak yang ditampilkan meliputi WhatsApp, panggilan telepon, dan email.',
            ],
            [
                'title' => 'WhatsApp bisnisraksasa',
                'content' => $sourceTag . ' Nomor WhatsApp yang ditampilkan di bisnisraksasa.com adalah +62 812-8260-7833.',
            ],
            [
                'title' => 'Email bisnisraksasa',
                'content' => $sourceTag . ' Email yang ditampilkan di bisnisraksasa.com adalah admin@bisnisraksasa.com.',
            ],
        ];
    }
}
