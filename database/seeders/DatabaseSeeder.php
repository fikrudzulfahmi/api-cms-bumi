<?php

namespace Database\Seeders;

use App\Models\Extracurricular;
use App\Models\Facility;
use App\Models\Feedback;
use App\Models\Gallery;
use App\Models\Major;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);
        $this->seedSettings();
        $this->seedProfile();
        $this->seedTeachers();
        $this->seedPosts();
        $this->seedFeedbacks();
        $this->seedMajors();
        $this->seedFacilities();
        $this->seedExtracurriculars();
        $this->seedGalleries();
    }


    protected function seedSettings(): void
    {
        $settings = [
            'nama_sekolah' => "MA Bustanul Muta'allimin",
            'nama_panjang' => "Madrasah Aliyah Bustanul Muta'allimin",
            'akronim' => 'BUMI',
            'motto' => "Mencetak Generasi Qur'ani, Berprestasi, dan Berakhlakul Karimah",
            'tagline' => 'Madrasah Hebat, Generasi Bermartabat',
            'deskripsi_singkat' => "Madrasah Aliyah Bustanul Muta'allimin adalah lembaga pendidikan Islam yang berkomitmen memadukan ilmu pengetahuan umum, ilmu agama, dan pembentukan karakter mulia.",
            'alamat' => 'Jl. Pendidikan No. 123, Kec. — , Kab. — , Jawa Timur',
            'telepon' => '0812-3456-7890',
            'whatsapp' => '6281234567890',
            'email' => 'info@bustanulmutaallimin.sch.id',
            'logo' => null,
            'tahun_berdiri' => '1985',
            'akreditasi' => 'A',
            'jumlah_siswa' => '450',
            'jumlah_guru' => '35',
            'jumlah_ekstra' => '12',
            'jam_operasional' => 'Senin – Sabtu, 07.00 – 15.30 WIB',
            'informasi_pendaftaran' => '<p>Penerimaan Peserta Didik Baru (PPDB) MA Bustanul Muta\'allimin dibuka setiap tahun ajaran baru. Calon peserta didik dapat mendaftar secara daring maupun langsung ke sekretariat PPDB.</p><p>Syarat pendaftaran: fotokopi ijazah/SKL, pas foto, dan mengisi formulir pendaftaran. Informasi lengkap hubungi nomor WhatsApp kami.</p>',
            'link_ppdb' => 'https://psb.bustanulmutaallimin.com',
            'sosmed_facebook' => 'https://facebook.com/',
            'sosmed_instagram' => 'https://instagram.com/',
            'sosmed_youtube' => 'https://youtube.com/',
            'sosmed_tiktok' => 'https://tiktok.com/',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    protected function seedProfile(): void
    {
        Profile::updateOrCreate(
            ['id' => 1],
            [
                'sejarah' => '<p>Madrasah Aliyah Bustanul Muta\'allimin berdiri pada tahun 1985 berawal dari kepedulian para tokoh masyarakat dan ulama setempat terhadap pentingnya pendidikan Islam berjenjang menengah. Madrasah ini hadir untuk menjawab kebutuhan masyarakat akan lembaga pendidikan yang memadukan kurikulum nasional dengan nilai-nilai kepesantrenan.</p><p>Seiring perjalanannya, madrasah terus berkembang, baik dari segi jumlah peserta didik, sarana prasarana, maupun prestasi akademik dan non-akademik. Kini MA Bustanul Muta\'allimin menjadi salah satu madrasah rujukan di wilayahnya.</p>',
                'visi' => 'Terwujudnya generasi Qur\'ani yang unggul dalam ilmu pengetahuan, teknologi, dan berakhlakul karimah.',
                'misi' => '<ul><li>Menyelenggarakan pendidikan yang memadukan imtaq dan iptek.</li><li>Menumbuhkan budaya membaca, menulis, dan berkarya.</li><li>Membina peserta didik berprestasi di bidang akademik dan non-akademik.</li><li>Mengembangkan karakter disiplin, jujur, dan bertanggung jawab.</li><li>Menjalin kemitraan dengan orang tua dan masyarakat.</li></ul>',
            ]
        );
    }

    protected function seedTeachers(): void
    {
        $teachers = [
            ['nama' => 'KH. Ahmad Fauzi, S.Ag., M.Pd.', 'jabatan' => 'Kepala Madrasah', 'urutan' => 1],
            ['nama' => 'Hj. Siti Maryam, S.Pd.', 'jabatan' => 'Waka Kurikulum', 'urutan' => 2],
            ['nama' => 'Ust. Muhammad Ridwan, M.Pd.I.', 'jabatan' => 'Waka Kesiswaan', 'urutan' => 3],
            ['nama' => 'Dra. Nurul Hidayah', 'jabatan' => 'Guru Bahasa Indonesia', 'urutan' => 4],
            ['nama' => 'Drs. Bambang Sutrisno, M.Si.', 'jabatan' => 'Guru Matematika', 'urutan' => 5],
            ['nama' => 'Ustzh. Fatimah Az-Zahra, S.Ag.', 'jabatan' => 'Guru Al-Qur\'an Hadis', 'urutan' => 6],
            ['nama' => 'Rina Kurniawati, S.Pd.', 'jabatan' => 'Guru Bahasa Inggris', 'urutan' => 7],
            ['nama' => 'Andi Prasetyo, S.Kom.', 'jabatan' => 'Guru TIK / Kepala Lab', 'urutan' => 8],
        ];

        foreach ($teachers as $index => $teacher) {
            Teacher::create(array_merge($teacher, [
                'foto' => 'https://i.pravatar.cc/300?img='.($index + 10),
            ]));
        }
    }

    protected function seedPosts(): void
    {
        $posts = [
            [
                'judul' => 'Peserta Didik MA Bustanul Muta\'allimin Raih Juara 1 Olimpiade Matematika Tingkat Provinsi',
                'kategori' => 'prestasi',
                'ringkasan' => 'Prestasi membanggakan kembali diraih peserta didik kami dalam ajang olimpiade matematika tingkat provinsi.',
                'tanggal' => '2026-09-18',
            ],
            [
                'judul' => 'Penerimaan Peserta Didik Baru (PPDB) Tahun Ajaran 2026/2027 Resmi Dibuka',
                'kategori' => 'pengumuman',
                'ringkasan' => 'PPDB MA Bustanul Muta\'allimin resmi dibuka. Segera daftarkan putra-putri Anda.',
                'tanggal' => '2026-09-15',
            ],
            [
                'judul' => 'Kegiatan Masa Ta\'aruf Siswa Madrasah (MATSAMA) Berlangsung Meriah',
                'kategori' => 'berita',
                'ringkasan' => 'MATSAMA menjadi ajang pengenalan lingkungan madrasah bagi peserta didik baru.',
                'tanggal' => '2026-09-10',
            ],
            [
                'judul' => 'Tim Paskibra MA Bustanul Muta\'allimin Tampil Gemilang di Upacara HUT RI',
                'kategori' => 'prestasi',
                'ringkasan' => 'Tim paskibra madrasah dipercaya menjadi pasukan pengibar bendera tingkat kecamatan.',
                'tanggal' => '2026-08-17',
            ],
            [
                'judul' => 'Sosialisasi Program Tahfidz dan Pembinaan Karakter kepada Wali Murid',
                'kategori' => 'berita',
                'ringkasan' => 'Madrasah menggelar sosialisasi program unggulan tahfidz dan pembinaan karakter.',
                'tanggal' => '2026-08-05',
            ],
            [
                'judul' => 'Pengumuman Hasil Seleksi Beasiswa Prestasi Semester Ganjil',
                'kategori' => 'pengumuman',
                'ringkasan' => 'Berikut daftar penerima beasiswa prestasi untuk semester ganjil tahun ajaran berjalan.',
                'tanggal' => '2026-07-28',
            ],
            [
                'judul' => 'Ekstrakurikuler Robotik Raih Juara Harapan 1 Lomba Kreativitas Teknologi',
                'kategori' => 'prestasi',
                'ringkasan' => 'Tim robotik madrasah kembali mengharumkan nama sekolah di tingkat kabupaten.',
                'tanggal' => '2026-07-12',
            ],
            [
                'judul' => 'Peringatan Hari Santri Nasional: Meneladani Semangat Perjuangan Ulama',
                'kategori' => 'berita',
                'ringkasan' => 'Seluruh civitas madrasah mengikuti upacara dan rangkaian kegiatan Hari Santri.',
                'tanggal' => '2026-06-22',
            ],
        ];

        foreach ($posts as $index => $post) {
            Post::create(array_merge($post, [
                'slug' => \Illuminate\Support\Str::slug($post['judul']),
                'gambar' => 'https://picsum.photos/seed/bumi-'.$index.'/900/600',
                'konten' => '<p>'.$post['ringkasan'].'</p><p>Kegiatan ini merupakan bagian dari komitmen madrasah dalam mengembangkan potensi peserta didik secara menyeluruh, baik dari sisi akademik, keagamaan, maupun keterampilan hidup.</p>',
                'is_published' => true,
            ]));
        }
    }

    protected function seedFeedbacks(): void
    {
        $feedbacks = [
            ['nama' => 'Ahmad Fajar Ramadhan', 'tahun_lulus' => '2023', 'jurusan' => 'MIPA', 'pesan' => 'Alhamdulillah, ilmu dan pembinaan akhlak di madrasah sangat membantu saya diterima di perguruan tinggi negeri impian.'],
            ['nama' => 'Siti Nur Aisyah', 'tahun_lulus' => '2022', 'jurusan' => 'Keagamaan', 'pesan' => 'Selain ilmu agama, saya juga dibekali keterampilan yang menunjang studi lanjut saya di kampus Islam.'],
            ['nama' => 'Muhammad Ilham Saputra', 'tahun_lulus' => '2024', 'jurusan' => 'IPS', 'pesan' => 'Guru-guru sangat perhatian dan lingkungan madrasahnya nyaman untuk belajar. Terima kasih MA Bustanul Muta\'allimin.'],
            ['nama' => 'Dewi Lestari', 'tahun_lulus' => '2021', 'jurusan' => 'Bahasa', 'pesan' => 'Pembiasaan sholat berjamaah dan tahfidz membuat saya lebih disiplin dan mencintai Al-Qur\'an hingga sekarang.'],
            ['nama' => 'Rizky Aditya Pratama', 'tahun_lulus' => '2023', 'jurusan' => 'MIPA', 'pesan' => 'Bimbingan ekstrakurikuler sains di madrasah benar-benar membentuk kemampuan riset saya.'],
        ];

        foreach ($feedbacks as $index => $feedback) {
            Feedback::create(array_merge($feedback, [
                'foto' => 'https://i.pravatar.cc/150?img='.($index + 20),
            ]));
        }
    }

    protected function seedMajors(): void
    {
        $majors = [
            [
                'nama' => 'MIPA',
                'slug' => 'mipa',
                'akreditasi' => 'A',
                'deskripsi' => '<p>Jurusan Matematika dan Ilmu Pengetahuan Alam membekali peserta didik dengan kemampuan analisis, logika, dan metode ilmiah sebagai bekal melanjutkan ke perguruan tinggi bidang sains, teknik, dan kedokteran.</p>',
            ],
            [
                'nama' => 'IPS',
                'slug' => 'ips',
                'akreditasi' => 'A',
                'deskripsi' => '<p>Jurusan Ilmu Pengetahuan Sosial mengembangkan pemahaman terhadap kehidupan bermasyarakat, ekonomi, dan sejarah sebagai bekal studi lanjut di bidang sosial-humaniora, hukum, dan ekonomi.</p>',
            ],
            [
                'nama' => 'Keagamaan',
                'slug' => 'keagamaan',
                'akreditasi' => 'A',
                'deskripsi' => '<p>Jurusan Ilmu-ilmu Keagamaan memperdalam Al-Qur\'an, Hadis, Fikih, dan bahasa Arab untuk mencetak generasi yang siap melanjutkan ke perguruan tinggi agama dan menjadi kader ulama.</p>',
            ],
            [
                'nama' => 'Bahasa',
                'slug' => 'bahasa',
                'akreditasi' => 'B',
                'deskripsi' => '<p>Jurusan Bahasa mengembangkan kompetensi berbahasa Indonesia, Inggris, dan Arab secara mendalam untuk mendukung karier di bidang bahasa, komunikasi, dan diplomasi.</p>',
            ],
        ];

        foreach ($majors as $index => $major) {
            Major::create(array_merge($major, [
                'gambar' => 'https://picsum.photos/seed/jurusan-'.$index.'/800/500',
            ]));
        }
    }

    protected function seedFacilities(): void
    {
        $facilities = [
            ['nama' => 'Masjid / Mushola', 'deskripsi' => 'Tempat ibadah yang nyaman untuk sholat berjamaah dan kegiatan keagamaan.'],
            ['nama' => 'Perpustakaan', 'deskripsi' => 'Koleksi buku lengkap dan ruang baca yang nyaman untuk menumbuhkan budaya literasi.'],
            ['nama' => 'Laboratorium Komputer', 'deskripsi' => 'Lab komputer modern untuk pembelajaran TIK dan praktik digital.'],
            ['nama' => 'Laboratorium IPA', 'deskripsi' => 'Sarana praktikum fisika, kimia, dan biologi yang memadai.'],
            ['nama' => 'Lapangan Olahraga', 'deskripsi' => 'Lapangan multifungsi untuk olahraga dan kegiatan ekstrakurikuler.'],
            ['nama' => 'Kantin Sehat', 'deskripsi' => 'Menyediakan makanan dan minuman sehat serta higienis bagi warga madrasah.'],
        ];

        foreach ($facilities as $index => $facility) {
            Facility::create(array_merge($facility, [
                'gambar' => 'https://picsum.photos/seed/fasilitas-'.$index.'/800/500',
            ]));
        }
    }

    protected function seedExtracurriculars(): void
    {
        $extras = [
            ['nama' => 'Pramuka', 'pembina' => 'Ust. Muhammad Ridwan'],
            ['nama' => 'Paskibra', 'pembina' => 'Bpk. Andi Prasetyo'],
            ['nama' => 'PMR (Palang Merah Remaja)', 'pembina' => 'Ibu Rina Kurniawati'],
            ['nama' => 'Rohis / SKI', 'pembina' => 'Ustzh. Fatimah Az-Zahra'],
            ['nama' => 'Tahfidz Al-Qur\'an', 'pembina' => 'Ust. Ahmad Fauzi'],
            ['nama' => 'Futsal & Olahraga', 'pembina' => 'Bpk. Bambang Sutrisno'],
            ['nama' => 'Tilawah & Kaligrafi', 'pembina' => 'Ustzh. Nurul Hidayah'],
            ['nama' => 'Robotik & Karya Ilmiah', 'pembina' => 'Bpk. Andi Prasetyo'],
        ];

        foreach ($extras as $index => $extra) {
            Extracurricular::create(array_merge($extra, [
                'deskripsi' => 'Wadah pengembangan minat dan bakat peserta didik di luar jam pelajaran dengan bimbingan pembina berpengalaman.',
                'gambar' => 'https://picsum.photos/seed/ekstra-'.$index.'/800/500',
            ]));
        }
    }

    protected function seedGalleries(): void
    {
        $galleries = [
            ['judul' => 'Kegiatan MATSAMA', 'kategori' => 'kegiatan'],
            ['judul' => 'Upacara Hari Santri', 'kategori' => 'kegiatan'],
            ['judul' => 'Praktikum Laboratorium', 'kategori' => 'kegiatan'],
            ['judul' => 'Kegiatan Belajar Mengajar', 'kategori' => 'murid'],
            ['judul' => 'Pembiasaan Sholat Berjamaah', 'kategori' => 'murid'],
            ['judul' => 'Lomba Paskibra', 'kategori' => 'kegiatan'],
            ['judul' => 'Gedung Madrasah', 'kategori' => 'fasilitas'],
            ['judul' => 'Perpustakaan Madrasah', 'kategori' => 'fasilitas'],
        ];

        foreach ($galleries as $index => $gallery) {
            Gallery::create(array_merge($gallery, [
                'gambar' => 'https://picsum.photos/seed/galeri-'.$index.'/800/600',
            ]));
        }
    }
}
