<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter — Redirect ke login jika belum login
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('nip')) {
            return redirect()->to('/login');
        }

        if (session()->get('must_change_password')) {
            $uriPath = trim($request->getUri()->getPath(), '/');
            if (! str_contains($uriPath, 'user/edit-password/') && ! str_ends_with($uriPath, 'logout')) {
                return redirect()->to('/user/edit-password/' . urlencode((string) session()->get('nip')))
                    ->with('warning_password_default', 'Silakan masukkan password baru terlebih dahulu sebelum mengakses halaman lain.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
