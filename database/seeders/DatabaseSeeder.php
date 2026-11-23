<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\MataKuliah;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Dosen Pengampu Default
        $dosen = User::updateOrCreate(
            ['email' => 'dosen@celoe.test'],
            [
                'name' => 'Dr. Ir. Budi Santoso, M.T.',
                'password' => Hash::make('dosen123'),
                'role' => 'dosen',
                'nomor_induk' => '198501012015041001',
            ]
        );

        // 2. Akun Mahasiswa Default
        $mahasiswa1 = User::updateOrCreate(
            ['email' => 'mahasiswa@celoe.test'],
            [
                'name' => 'Ahmad Pratama',
                'password' => Hash::make('mahasiswa123'),
                'role' => 'mahasiswa',
                'nomor_induk' => '1301210001',
            ]
        );

        // 3. Mata Kuliah Dasar (Modul 3)
        $course1 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF301'],
            [
                'nama_mk' => 'Pengembangan Aplikasi Website',
                'deskripsi' => 'Mata kuliah yang membahas arsitektur aplikasi web modern menggunakan framework Laravel, MVC, Eloquent ORM, dan keamanan web.',
                'sks' => 4,
                'semester' => 'Ganjil 2026/2027',
                'dosen_id' => $dosen->id,
            ]
        );

        // 4. Daftarkan Mahasiswa ke Mata Kuliah Pertama (status approved)
        $course1->students()->syncWithoutDetaching([
            $mahasiswa1->id => ['status' => 'approved'],
        ]);

        // 5. Tambahkan Materi Contoh
        Material::updateOrCreate(
            ['course_id' => $course1->id, 'minggu_ke' => 1, 'urutan' => 1],
            [
                'judul' => 'Pengenalan Arsitektur MVC & Routing di Laravel 12',
                'konten' => 'Mempelajari alur HTTP Request, Route Middleware, Controller, dan pembuatan View menggunakan Blade Template.',
            ]
        );

        // TODO 8: Tambahkan minimal 2 mata kuliah lagi dengan semester berbeda, 1 mahasiswa tambahan, 2 tugas, dan contoh data submission
        // 6. Akun Mahasiswa Tambahan
        $mahasiswa2 = User::updateOrCreate(
            ['email' => 'siti@celoe.test'],
            [
                'name' => 'Siti Rahmawati',
                'password' => Hash::make('mahasiswa123'),
                'role' => 'mahasiswa',
                'nomor_induk' => '1301210002',
            ]
        );

        // 7. Mata Kuliah Tambahan (termasuk semester yang berbeda)
        $course2 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF204'],
            [
                'nama_mk' => 'Struktur Data',
                'deskripsi' => 'Mata kuliah yang membahas konsep struktur data seperti list, stack, queue, tree, dan graph beserta implementasinya.',
                'sks' => 3,
                'semester' => 'Ganjil 2026/2027',
                'dosen_id' => $dosen->id,
            ]
        );

        $course3 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF402'],
            [
                'nama_mk' => 'Kecerdasan Buatan',
                'deskripsi' => 'Mata kuliah pengantar kecerdasan buatan yang membahas pencarian, representasi pengetahuan, dan dasar machine learning.',
                'sks' => 2,
                'semester' => 'Genap 2026/2027',
                'dosen_id' => $dosen->id,
            ]
        );

        // 8. Daftarkan mahasiswa ke mata kuliah tambahan (status approved)
        $course1->students()->syncWithoutDetaching([
            $mahasiswa2->id => ['status' => 'approved'],
        ]);

        $course2->students()->syncWithoutDetaching([
            $mahasiswa1->id => ['status' => 'approved'],
            $mahasiswa2->id => ['status' => 'approved'],
        ]);

        // 9. Tugas Contoh pada Mata Kuliah Pertama
        $assignment1 = Assignment::updateOrCreate(
            ['course_id' => $course1->id, 'judul' => 'Tugas 1: Analisis Arsitektur MVC pada Laravel'],
            [
                'deskripsi' => 'Jelaskan alur request pada pola MVC di Laravel mulai dari route, controller, model, hingga view. Sertakan contoh nyata dari proyek CeLOE LMS ini.',
                'deadline' => now()->addDays(7),
            ]
        );

        $assignment2 = Assignment::updateOrCreate(
            ['course_id' => $course1->id, 'judul' => 'Tugas 2: Implementasi Eloquent ORM dan Relasi'],
            [
                'deskripsi' => 'Buatlah contoh implementasi relasi Eloquent (hasMany dan belongsTo) pada studi kasus bebas, lalu jelaskan perbedaan lazy loading dan eager loading.',
                'deadline' => now()->addDays(14),
            ]
        );

        // 10. Contoh Pengumpulan Tugas (satu sudah dinilai, satu belum dinilai)
        Submission::updateOrCreate(
            ['assignment_id' => $assignment1->id, 'user_id' => $mahasiswa1->id],
            [
                'jawaban' => 'Alur MVC pada Laravel dimulai saat request masuk ke route, lalu diteruskan ke controller. Controller memanggil model untuk mengambil atau mengolah data dari database, kemudian hasilnya dikirim ke view untuk ditampilkan ke pengguna. Pada CeLOE LMS, contohnya route /courses diteruskan ke MataKuliahController@index, model MataKuliah mengambil data mata kuliah, dan view courses/index menampilkannya dalam bentuk katalog.',
                'nilai' => 88,
                'catatan_dosen' => 'Analisisnya lengkap dan runtut, contoh alurnya juga tepat. Pertahankan.',
                'submitted_at' => now()->subDay(),
            ]
        );

        Submission::updateOrCreate(
            ['assignment_id' => $assignment1->id, 'user_id' => $mahasiswa2->id],
            [
                'jawaban' => 'MVC adalah pola arsitektur yang memisahkan aplikasi menjadi tiga bagian. Model bertugas mengelola data, View bertugas menampilkan data ke pengguna, dan Controller menjadi penghubung antara Model dan View. Di Laravel, request dari pengguna diterima route, diproses controller, datanya diambil lewat model Eloquent, lalu ditampilkan menggunakan template Blade.',
                'submitted_at' => now()->subHours(5),
            ]
        );
    }
}
