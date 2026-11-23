<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!Auth::user()->isMahasiswa()) {
            abort(403, 'Akses ditolak. Pengumpulan tugas hanya dapat dilakukan oleh Mahasiswa.');
        }

        $user = Auth::user();

        $enrollment = $assignment->course->students()->where('users.id', $user->id)->first();
        if (!$enrollment || $enrollment->pivot->status !== 'approved') {
            abort(403, 'Akses ditolak. Anda belum disetujui oleh Dosen untuk mengikuti mata kuliah tugas ini.');
        }

        if ($assignment->isOverdue()) {
            return back()->with('error', 'Gagal mengumpulkan: Batas waktu pengumpulan (deadline) tugas telah berakhir.');
        }

        $validated = $request->validate([
            'jawaban' => ['required', 'string', 'min:10', 'max:20000'],
        ], [
            'jawaban.required' => 'Teks jawaban tugas wajib diisi.',
            'jawaban.min' => 'Jawaban terlalu pendek. Tuliskan jawaban minimal 10 karakter.',
            'jawaban.max' => 'Jawaban melebihi batas maksimal 20.000 karakter.',
        ]);

        Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
            ],
            [
                'jawaban' => $validated['jawaban'],
                'submitted_at' => now(),
            ]
        );

        return back()->with('success', 'Jawaban tugas Anda berhasil disimpan ke sistem.');
    }

    public function grade(Request $request, Submission $submission): RedirectResponse
    {
        // TODO 5a (grade): Cek apakah dosen adalah pengampu mata kuliah, validasi nilai (0-100) & catatan_dosen, lalu update submission.
        // 1. Pastikan $submission->assignment->course->dosen_id === Auth::id(), jika tidak abort(403).
        // 2. Validasi input 'nilai' (required|integer|min:0|max:100) dan 'catatan_dosen' (nullable|string|max:1000).
        // 3. Simpan perubahan menggunakan $submission->update(...) dan redirect back() dengan pesan sukses.

        // 1. Hanya dosen pengampu mata kuliah tugas ini yang boleh memberi nilai
        if ($submission->assignment->course->dosen_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda bukan dosen pengampu mata kuliah ini.');
        }

        // 2. Validasi nilai (0-100) dan catatan dosen
        $validated = $request->validate([
            'nilai' => ['required', 'integer', 'min:0', 'max:100'],
            'catatan_dosen' => ['nullable', 'string', 'max:1000'],
        ], [
            'nilai.required' => 'Nilai wajib diisi.',
            'nilai.integer' => 'Nilai harus berupa angka bulat.',
            'nilai.min' => 'Nilai minimal adalah 0.',
            'nilai.max' => 'Nilai maksimal adalah 100.',
            'catatan_dosen.max' => 'Catatan dosen maksimal 1000 karakter.',
        ]);

        // 3. Simpan penilaian ke submission
        $submission->update([
            'nilai' => $validated['nilai'],
            'catatan_dosen' => $validated['catatan_dosen'] ?? null,
        ]);

        return back()->with('success', "Nilai untuk {$submission->user->name} berhasil disimpan.");
    }

    public function exportCsv(Assignment $assignment): StreamedResponse
    {
        // TODO 5b (exportCsv): Cek kepemilikan dosen, lalu gunakan response()->streamDownload() dan fputcsv() untuk mengunduh data nilai mahasiswa.
        // 1. Pastikan $assignment->course->dosen_id === Auth::id(), jika tidak abort(403).
        // 2. Muat relasi course.approvedStudents dan submissions dari $assignment.
        // 3. Kembalikan response()->streamDownload(...) yang menulis header & baris CSV menggunakan fopen('php://output', 'w') dan fputcsv().

        // 1. Hanya dosen pengampu mata kuliah tugas ini yang boleh mengekspor rekap nilai
        if ($assignment->course->dosen_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda bukan dosen pengampu mata kuliah ini.');
        }

        // 2. Muat daftar mahasiswa resmi kelas beserta seluruh submission tugas ini
        $assignment->load(['course.approvedStudents', 'submissions']);
        $students = $assignment->course->approvedStudents;
        $submissions = $assignment->submissions->keyBy('user_id');

        $fileName = 'rekap-nilai-' . $assignment->course->kode_mk . '-' . $assignment->id . '.csv';

        // 3. Unduh sebagai file CSV (header + satu baris per mahasiswa)
        return response()->streamDownload(function () use ($students, $submissions) {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['NIM', 'Nama Mahasiswa', 'Status Pengumpulan', 'Waktu Pengumpulan', 'Nilai', 'Status Nilai', 'Catatan Dosen']);

            foreach ($students as $student) {
                $sub = $submissions->get($student->id);

                if ($sub) {
                    fputcsv($output, [
                        $student->nomor_induk ?? '-',
                        $student->name,
                        'Sudah Mengumpulkan',
                        $sub->submitted_at ? $sub->submitted_at->format('d-m-Y H:i') : '-',
                        $sub->nilai ?? '-',
                        $sub->status_nilai,
                        $sub->catatan_dosen ?? '',
                    ]);
                } else {
                    fputcsv($output, [
                        $student->nomor_induk ?? '-',
                        $student->name,
                        'Belum Mengumpulkan',
                        '-',
                        '-',
                        'Belum Dinilai',
                        '',
                    ]);
                }
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
