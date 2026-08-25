<?php

namespace App\Http\Requests;

use App\Models\Bahan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk modul Stok Masuk (manager).
 *
 * Seluruh aturan validasi disamakan persis dengan modul Flask
 * modules/stok_masuk.py (fungsi validate_form):
 *   - tanggal : wajib, format tanggal valid (Y-m-d)
 *   - shift   : wajib, harus termasuk daftar shift di config lotra.shift_list
 *   - barista : wajib, tidak boleh kosong
 *   - item bahan (kolom dinamis dari Master Barang / Bahan aktif):
 *       * opsional, tapi bila diisi harus berupa angka (numeric)
 *       * minimal SATU item harus diisi
 *
 * Daftar kolom item dibaca dari Master Barang (Bahan::activeKeys()) sehingga
 * tidak ada data hardcode — persis seperti Flask yang membaca
 * master_bahan.get_active_keys().
 */
class StokMasukRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Akses sudah dijaga oleh middleware 'role:manager' di route.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Kolom item bahan dibangun secara dinamis dari Master Barang aktif.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'tanggal' => ['required', 'date', 'date_format:Y-m-d'],
            'barista' => ['required', 'string', 'max:100'],
        ];

        // Item bahan: kolom dinamis (nama kolom = kode bahan aktif).
        // Opsional, tetapi bila diisi harus numeric.
        foreach (Bahan::activeKeys() as $kode) {
            $rules[$kode] = ['nullable', 'numeric'];
        }

        return $rules;
    }

    /**
     * Pesan error dalam Bahasa Indonesia, homolog dengan modul Flask.
     */
    public function messages(): array
    {
        $messages = [
            'tanggal.required' => 'Tanggal harus diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'tanggal.date_format' => 'Format tanggal tidak valid.',
            'barista.required' => 'Nama barista harus diisi.',
        ];

        // Pesan per-item: "<Nama Barang> harus berupa angka."
        foreach (Bahan::activeItems() as $item) {
            $label = str_replace('_', ' ', ucwords($item['nama'], " \t\r\n\f\v"));
            $messages[$item['kode'].'.numeric'] = $label.' harus berupa angka.';
        }

        return $messages;
    }

    /**
     * Configure the validator instance.
     *
     * Menambahkan aturan "minimal satu item harus diisi" (sama seperti Flask)
     * via after-hook, karena jumlah item bersifat dinamis.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasItem = false;
            foreach (Bahan::activeKeys() as $kode) {
                $val = $this->input($kode);
                if ($val !== null && trim((string) $val) !== '') {
                    $hasItem = true;
                    break;
                }
            }

            if (! $hasItem) {
                $validator->errors()->add('__items__', 'Minimal satu item harus diisi.');
            }
        });
    }

    /**
     * Ambil hanya data yang diizinkan (tanggal, shift, barista + kolom item
     * aktif) agar mass-assignment lewat Eloquent aman.
     *
     * @return array<string, mixed>
     */
    public function validatedStokData(): array
    {
        $data = [
            'tanggal' => $this->input('tanggal'),
            'shift' => '-', // Hardcoded as manager no longer inputs shift
            'barista' => $this->input('barista'),
            'barista_id' => auth()->id(),
        ];

        foreach (Bahan::activeKeys() as $kode) {
            $val = $this->input($kode);
            $data[$kode] = ($val === null || trim((string) $val) === '') ? null : $val;
        }

        return $data;
    }
}