<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'user';
    protected $primaryKey       = 'id_user';
    protected $useAutoIncrement = true;

    protected $allowedFields = ['nip', 'nama', 'password', 'role'];

    protected $beforeInsert = ['hashPasswordCallback'];
    protected $beforeUpdate = ['hashPasswordCallback'];

    /**
     * Model callback: otomatis hash password dengan PASSWORD_DEFAULT (Bcrypt)
     * jika field password dikirim dan belum berformat hash.
     */
    protected function hashPasswordCallback(array $data): array
    {
        if (isset($data['data']['password']) && $data['data']['password'] !== '') {
            $pwd = (string) $data['data']['password'];
            if (! $this->isHashed($pwd)) {
                $data['data']['password'] = $this->hashPassword($pwd);
            }
        }

        return $data;
    }

    /**
     * Cek apakah string sudah berupa hash password_hash() (Bcrypt / Argon2).
     */
    public function isHashed(string $stored): bool
    {
        if ($stored === '') {
            return false;
        }
        $info = password_get_info($stored);

        return ! empty($info['algo']);
    }

    /**
     * Cek apakah password di database perlu di-hash / di-rehash (masih plain-text atau cost berubah).
     */
    public function needsRehash(string $stored): bool
    {
        if (! $this->isHashed($stored)) {
            return true;
        }

        return password_needs_rehash($stored, PASSWORD_DEFAULT);
    }

    /**
     * Buat hash password menggunakan algoritma default PHP (Bcrypt).
     */
    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public function findByNip(string $nip): ?array
    {
        return $this->where('nip', $nip)->first();
    }

    /**
     * Update user berdasarkan NIP
     */
    public function updateByNip(string $nip, array $data): bool
    {
        if (isset($data['password']) && $data['password'] !== '' && ! $this->isHashed((string) $data['password'])) {
            $data['password'] = $this->hashPassword((string) $data['password']);
        }

        return $this->where('nip', $nip)->set($data)->update();
    }

    /**
     * Verifikasi password (mendukung Bcrypt/Argon2 hash & fallback legacy plain-text).
     */
    public function verifyPassword(string $input, string $stored): bool
    {
        if ($this->isHashed($stored)) {
            return password_verify($input, $stored);
        }

        return hash_equals($stored, $input);
    }

    /**
     * Enkripsi (hash) seluruh password di tabel user yang masih berformat plain-text.
     * Mengembalikan jumlah akun yang di-hash.
     */
    public function hashAllPlainPasswords(): int
    {
        $users = $this->findAll();
        $count = 0;

        foreach ($users as $u) {
            $stored = (string) ($u['password'] ?? '');
            if ($stored !== '' && ! $this->isHashed($stored)) {
                $hashed = $this->hashPassword($stored);
                $this->db->table($this->table)
                    ->where($this->primaryKey, $u[$this->primaryKey])
                    ->update(['password' => $hashed]);
                $count++;
            }
        }

        return $count;
    }
}
