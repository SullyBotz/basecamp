<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TanpaKataKasar implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1. Daftar kata kasar (Kamus Global)
        $kataKasar = ['bodoh', 'jelek', 'anjing', 'bangsat', 'anjay']; 

        // 2. Ubah teks inputan user menjadi huruf kecil semua
        $teksUser = strtolower($value);

        // 3. Kamus Leetspeak (Mengubah angka/simbol pengecoh ke huruf asli)
        $leetMap = [
            '4' => 'a', '@' => 'a',
            '1' => 'i', '!' => 'i', '|' => 'i',
            '3' => 'e',
            '0' => 'o',
            '5' => 's', '$' => 's',
            '7' => 't',
            '9' => 'g',
            '8' => 'b'
        ];

        // Terjemahkan leetspeak
        $teksNormalized = strtr($teksUser, $leetMap);

        // 4. HAPUS SEMUA SPASI DAN SIMBOL/BINTANG! 
        // Ini akan mengubah "an ji ng", "an*jing", "4n-j1n9" menjadi "anjing" secara utuh.
        $teksClean = preg_replace('/[^a-z]/', '', $teksNormalized);

        // 5. Cek langsung ke kamus kata kasar
        foreach ($kataKasar as $kata) {
            // Cek persis atau jika kata kasar tersebut terkandung di dalam teks bersih
            if (str_contains($teksClean, $kata)) {
                $fail("Teks yang Anda masukkan mengandung kata-kata yang dilarang oleh sistem.");
                return; // Langsung hentikan pengecekan
            }
        }
    }
}