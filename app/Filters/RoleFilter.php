<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RoleFilter — Validasi role pengguna per route
 * Contoh penggunaan: ['filter' => 'role:super_admin']
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Belum login
        if (! session()->get('nip')) {
            return redirect()->to('/login');
        }

        $role = session()->get('role');

        // Cek apakah role user ada di daftar role yang diizinkan
        if ($arguments && ! in_array($role, $arguments)) {
            // Redirect ke dashboard sesuai role masing-masing
            return redirect()->to($this->getDashboardUrl($role));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }

    private function getDashboardUrl(string $role): string
    {
        return match ($role) {
            'super_admin' => '/dashboard/super-admin',
            'admin'       => '/dashboard/admin',
            'kepala'      => '/dashboard/kepala',
            default       => '/pegawai/profil/' . session()->get('nip'),
        };
    }
}
