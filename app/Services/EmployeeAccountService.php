<?php

namespace App\Services;

use App\Models\Division;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Pembuatan akun karyawan oleh admin (form panel maupun import CSV). Tidak ada registrasi mandiri:
 * setiap akun baru memakai kata sandi sementara acak per akun dan wajib diganti saat login pertama.
 */
class EmployeeAccountService
{
    public const ROLES = [
        'employee' => 'Employee',
        'supervisor' => 'Supervisor',
        'quality' => 'Quality / HRGA',
        'admin' => 'Admin',
    ];

    /**
     * Kolom CSV yang dikenali (huruf kecil) beserta field tujuannya.
     */
    public const CSV_COLUMNS = [
        'nama' => 'name',
        'email' => 'email',
        'nik' => 'employee_id',
        'nama divisi' => 'division',
        'jabatan' => 'jabatan',
        'role' => 'role',
    ];

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?User $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignore)],
            'employee_id' => ['required', 'string', 'max:20', Rule::unique('users', 'employee_id')->ignore($ignore)],
            // HRGA boleh dipilih di data master identitas karyawan (mis. staf HR ber-role quality).
            // Pembatasan HRGA hanya berlaku di form pelaporan CAPA (lihat Division::reportable()).
            'division_id' => ['required', Rule::exists('divisions', 'id')],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ];
    }

    /**
     * Kata sandi sementara acak per akun. Plaintext hanya dikembalikan ke pemanggil untuk
     * ditampilkan sekali ke admin; jangan disimpan, di-log, atau dimasukkan ke notifikasi/session.
     */
    public static function generateTemporaryPassword(): string
    {
        return Str::password(12, symbols: false);
    }

    /**
     * @param  array{name: string, email: string, employee_id: string, division_id: int, jabatan?: ?string, role: string}  $data
     * @param  string|null  $password  Kata sandi awal; default kata sandi sementara acak.
     * @return array{user: User, password: string}
     */
    public function create(array $data, ?string $password = null): array
    {
        $password ??= self::generateTemporaryPassword();

        $user = DB::transaction(function () use ($data, $password): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'employee_id' => $data['employee_id'],
                'division_id' => $data['division_id'],
                'jabatan' => $data['jabatan'] ?? null,
                'password' => $password,
                'must_change_password' => true,
            ]);

            // Email sudah pasti milik karyawan (didaftarkan admin), jadi tidak perlu verifikasi.
            $user->markEmailAsVerified();
            $user->syncRoles([$data['role']]);

            return $user;
        });

        return ['user' => $user, 'password' => $password];
    }

    /**
     * Ganti ke kata sandi sementara acak yang baru; karyawan wajib membuat kata sandi baru saat login.
     *
     * @return string Kata sandi sementara (plaintext) untuk ditampilkan sekali ke admin.
     */
    public function resetTemporaryPassword(User $user): string
    {
        $password = self::generateTemporaryPassword();

        $user->update([
            'password' => $password,
            'must_change_password' => true,
        ]);

        return $password;
    }

    /**
     * Import semua baris sekaligus: kalau ada satu baris pun yang tidak valid, tidak ada akun yang
     * dibuat, supaya admin cukup memperbaiki file lalu mengunggah ulang tanpa duplikat.
     *
     * @return array{created: int, errors: list<string>, credentials: list<array{name: string, email: string, employee_id: string, password: string}>}
     */
    public function importCsv(string $path): array
    {
        [$rows, $errors] = $this->readCsv($path);

        if ($errors !== []) {
            return ['created' => 0, 'errors' => $errors, 'credentials' => []];
        }

        $divisionIds = Division::pluck('id', 'name')
            ->mapWithKeys(fn (int $id, string $name): array => [mb_strtolower($name) => $id]);

        $seen = ['email' => [], 'employee_id' => []];
        $valid = [];

        foreach ($rows as $line => $row) {
            $row['role'] = mb_strtolower($row['role']);
            $row['division_id'] = $divisionIds[mb_strtolower($row['division'])] ?? null;

            $validator = Validator::make($row, self::rules(), [
                'division_id.required' => "divisi \"{$row['division']}\" tidak dikenal",
            ]);

            $messages = $validator->errors()->all();

            foreach (['email', 'employee_id'] as $field) {
                $key = mb_strtolower($row[$field]);
                if ($key !== '' && isset($seen[$field][$key])) {
                    $messages[] = "{$field} {$row[$field]} dobel dengan baris {$seen[$field][$key]}";
                }
                $seen[$field][$key] = $line;
            }

            if ($messages !== []) {
                $errors[] = "Baris {$line}: ".implode('; ', $messages);
            } else {
                $valid[] = $row;
            }
        }

        if ($errors !== []) {
            return ['created' => 0, 'errors' => $errors, 'credentials' => []];
        }

        $credentials = DB::transaction(function () use ($valid): array {
            $credentials = [];

            foreach ($valid as $row) {
                $credentials[] = [
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'employee_id' => $row['employee_id'],
                    'password' => $this->create($row)['password'],
                ];
            }

            return $credentials;
        });

        return ['created' => count($credentials), 'errors' => [], 'credentials' => $credentials];
    }

    /**
     * Baca CSV (pemisah koma atau titik koma, seperti ekspor Excel berlokal Indonesia).
     *
     * @return array{0: array<int, array<string, string>>, 1: list<string>}
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $firstLine = (string) fgets($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, null, $delimiter, '"', '');
        $header = array_map(
            fn ($column): string => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $column))),
            $header ?: []
        );

        $missing = array_diff(array_keys(self::CSV_COLUMNS), $header);
        if ($missing !== []) {
            fclose($handle);

            return [[], ['Kolom wajib tidak ditemukan di baris judul: '.implode(', ', $missing).'.']];
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;

            if ($values === [null] || trim(implode('', $values)) === '') {
                continue; // baris kosong
            }

            $record = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), ''));
            $row = [];
            foreach (self::CSV_COLUMNS as $column => $field) {
                $row[$field] = trim((string) $record[$column]);
            }
            $row['jabatan'] = $row['jabatan'] === '' ? null : $row['jabatan'];
            $rows[$line] = $row;
        }

        fclose($handle);

        return $rows === [] ? [[], ['File CSV tidak berisi data karyawan.']] : [$rows, []];
    }
}
