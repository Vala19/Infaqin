<?php

// Indonesian validation messages for the rules used on public and donor pages.
// Any rule not listed here falls back to Laravel's English message.

return [

    'accepted' => ':Attribute wajib disetujui.',
    'after' => ':Attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => ':Attribute harus berupa tanggal yang sama dengan atau setelah :date.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'before' => ':Attribute harus berupa tanggal sebelum :date.',
    'boolean' => ':Attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'different' => ':Attribute dan :other harus berbeda.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'max' => [
        'numeric' => ':Attribute tidak boleh lebih dari :max.',
        'string' => ':Attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'password' => [
        'letters' => ':Attribute harus mengandung setidaknya satu huruf.',
        'mixed' => ':Attribute harus mengandung setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus mengandung setidaknya satu angka.',
        'symbols' => ':Attribute harus mengandung setidaknya satu simbol.',
        'uncompromised' => ':Attribute yang Anda masukkan pernah muncul dalam kebocoran data. Silakan pilih :attribute lain.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah digunakan.',

    'attributes' => [
        'amount' => 'nominal',
        'body' => 'doa',
        'current_password' => 'kata sandi saat ini',
        'date_of_birth' => 'tanggal lahir',
        'email' => 'email',
        'from' => 'tanggal mulai',
        'name' => 'nama',
        'on_behalf_of' => 'atas nama',
        'password' => 'kata sandi',
        'payment_method' => 'metode pembayaran',
        'phone' => 'nomor telepon',
        'q' => 'kata kunci',
        'terms' => 'syarat dan ketentuan',
        'to' => 'tanggal akhir',
        'username' => 'nama pengguna',
    ],

];
