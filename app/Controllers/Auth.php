<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function index()
    {
        // Jika sudah login, redirect ke dashboard
        if (session()->get('nip')) {
            return $this->redirectByRole(session()->get('role'));
        }

        return view('auth/login');
    }

    public function login()
    {
        $nip      = trim($this->request->getPost('nip') ?? '');
        $password = $this->request->getPost('password') ?? '';
        $error    = '';

        if (empty($nip) || empty($password)) {
            $error = 'NIP dan password wajib diisi.';
        } else {
            $userModel = new UserModel();
            $user      = $userModel->findByNip($nip);

            if ($user) {
                if ($userModel->verifyPassword($password, $user['password'])) {
                    // Auto-upgrade (hash/rehash) jika password di DB masih plain-text atau algoritma lama
                    if ($userModel->needsRehash($user['password'])) {
                        $userModel->updateByNip($user['nip'], [
                            'password' => $userModel->hashPassword($password),
                        ]);
                    }

                    // Regenerate session ID untuk keamanan
                    session()->regenerate();
                    session()->set([
                        'nip'  => $user['nip'],
                        'role' => $user['role'],
                        'nama' => $user['nama'],
                    ]);

                    return $this->redirectByRole($user['role']);
                } else {
                    $error = 'Password salah. Silakan coba lagi.';
                }
            } else {
                $error = 'NIP tidak ditemukan dalam sistem.';
            }
        }

        return view('auth/login', ['error' => $error, 'old_nip' => $nip]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }

    private function redirectByRole(string $role)
    {
        return match ($role) {
            'super_admin' => redirect()->to('/dashboard/super-admin'),
            'admin'       => redirect()->to('/dashboard/admin'),
            'kepala'      => redirect()->to('/dashboard/kepala'),
            default       => redirect()->to('/pegawai/profil/' . session()->get('nip')),
        };
    }
}
