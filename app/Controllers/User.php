<?php

namespace App\Controllers;

use App\Models\UserModel;

class User extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function tambah()
    {
        return view('user/tambah', [
            'page_title' => 'Tambah Akun User',
            'title_icon' => 'fas fa-user-shield',
            'back_url'   => 'dashboard/super-admin',
        ]);
    }

    public function simpan()
    {
        $rules = [
            'nip'      => 'required|is_unique[user.nip]',
            'nama'     => 'required',
            'role'     => 'required|in_list[super_admin,admin,kepala,pegawai]',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return view('user/tambah', [
                'errors'     => $this->validator->getErrors(),
                'page_title' => 'Tambah Akun User',
                'title_icon' => 'fas fa-user-shield',
                'back_url'   => 'dashboard/super-admin',
            ]);
        }

        $this->userModel->save([
            'nip'      => trim((string) $this->request->getPost('nip')),
            'nama'     => trim((string) $this->request->getPost('nama')),
            'role'     => $this->request->getPost('role'),
            'password' => $this->userModel->hashPassword((string) $this->request->getPost('password')),
        ]);

        return redirect()->to('/dashboard/super-admin')->with('success', 'User berhasil ditambahkan dengan password ter-enkripsi (Bcrypt).');
    }

    public function editPassword(string $nip)
    {
        $sessionRole = (string) session()->get('role');
        $sessionNip  = (string) session()->get('nip');

        // Hanya super_admin atau pemilik akun sendiri yang boleh mengakses
        if (!in_array($sessionRole, ['super_admin']) && $nip !== $sessionNip) {
            return redirect()->to('/pegawai/profil/' . urlencode($sessionNip))
                ->with('error', 'Anda hanya dapat mengubah password akun Anda sendiri.');
        }

        $user = $this->userModel->findByNip($nip);
        if (! $user) {
            return redirect()->to($sessionRole === 'super_admin' ? '/dashboard/super-admin' : '/pegawai/profil/' . urlencode($sessionNip))
                ->with('error', 'Akun user tidak ditemukan.');
        }

        $isOwnAccount = ($nip === $sessionNip);
        $mustChange   = $isOwnAccount && ((bool) session()->get('must_change_password') || $this->userModel->isDefaultPassword($user));
        $flashWarn    = (string) (session()->getFlashdata('warning_password_default') ?? '');

        return view('user/edit_password', [
            'user'             => $user,
            'is_hashed'        => $this->userModel->isHashed((string) ($user['password'] ?? '')),
            'is_super'         => $sessionRole === 'super_admin',
            'must_change'      => $mustChange,
            'show_popup_alert' => ($flashWarn !== '' || $mustChange),
            'alert_message'    => $flashWarn !== ''
                ? $flashWarn
                : ($mustChange ? 'Password akun Anda saat ini masih sama dengan NIP. Silakan masukkan password baru terlebih dahulu.' : ''),
            'page_title'       => $mustChange ? 'Buat Password Baru' : 'Manajemen Akun & Hashing Password',
            'title_icon'       => 'fas fa-key',
            'back_url'         => $mustChange ? null : 'pegawai/profil/' . urlencode($nip),
        ]);
    }

    public function updatePassword(string $nip)
    {
        $sessionRole = (string) session()->get('role');
        $sessionNip  = (string) session()->get('nip');

        if ($sessionRole !== 'super_admin' && $nip !== $sessionNip) {
            return redirect()->to('/pegawai/profil/' . urlencode($sessionNip))
                ->with('error', 'Anda hanya dapat mengubah password akun Anda sendiri.');
        }

        $user = $this->userModel->findByNip($nip);
        if (! $user) {
            return redirect()->to('/dashboard/super-admin')->with('error', 'Akun user tidak ditemukan.');
        }

        $isSuper      = $sessionRole === 'super_admin';
        $isOwnAccount = ($nip === $sessionNip);
        $mustChange   = $isOwnAccount && ((bool) session()->get('must_change_password') || $this->userModel->isDefaultPassword($user));
        $newPassword  = (string) $this->request->getPost('password');

        $rules = [];
        if ($isSuper) {
            $rules['nama'] = 'required';
            $rules['role'] = 'required|in_list[super_admin,admin,kepala,pegawai]';
        }

        if (! $isSuper || $mustChange) {
            $rules['password']         = 'required|min_length[6]';
            $rules['password_confirm'] = 'required|matches[password]';
        } elseif ($newPassword !== '') {
            $rules['password']         = 'min_length[6]';
            $rules['password_confirm'] = 'matches[password]';
        }

        if (! empty($rules) && ! $this->validate($rules)) {
            return view('user/edit_password', [
                'user'             => $user,
                'is_hashed'        => $this->userModel->isHashed((string) ($user['password'] ?? '')),
                'is_super'         => $isSuper,
                'must_change'      => $mustChange,
                'show_popup_alert' => false,
                'alert_message'    => $mustChange ? 'Password akun Anda saat ini masih sama dengan NIP. Silakan masukkan password baru terlebih dahulu.' : '',
                'errors'           => $this->validator->getErrors(),
                'page_title'       => $mustChange ? 'Buat Password Baru' : 'Manajemen Akun & Hashing Password',
                'title_icon'       => 'fas fa-key',
                'back_url'         => $mustChange ? null : 'pegawai/profil/' . urlencode($nip),
            ]);
        }

        if ($newPassword !== '' && trim($newPassword) === trim($nip)) {
            return view('user/edit_password', [
                'user'             => $user,
                'is_hashed'        => $this->userModel->isHashed((string) ($user['password'] ?? '')),
                'is_super'         => $isSuper,
                'must_change'      => $mustChange,
                'show_popup_alert' => true,
                'alert_message'    => 'Password baru tidak boleh sama dengan NIP. Silakan buat password baru yang berbeda.',
                'errors'           => ['password' => 'Password baru tidak boleh sama dengan NIP. Silakan buat password baru yang berbeda.'],
                'page_title'       => $mustChange ? 'Buat Password Baru' : 'Manajemen Akun & Hashing Password',
                'title_icon'       => 'fas fa-key',
                'back_url'         => $mustChange ? null : 'pegawai/profil/' . urlencode($nip),
            ]);
        }

        $updateData = [];
        if ($isSuper) {
            $updateData['nama'] = trim((string) $this->request->getPost('nama'));
            $updateData['role'] = (string) $this->request->getPost('role');
        }

        if ($newPassword !== '') {
            $updateData['password'] = $this->userModel->hashPassword($newPassword);
        } elseif (! $this->userModel->isHashed((string) ($user['password'] ?? '')) && ($user['password'] ?? '') !== '') {
            // Jika password tidak diubah namun password lama masih plain-text, otomatis enkripsi!
            $updateData['password'] = $this->userModel->hashPassword((string) $user['password']);
        }

        if (! empty($updateData)) {
            $this->userModel->updateByNip($nip, $updateData);
        }

        if ($isOwnAccount && $newPassword !== '') {
            session()->set('must_change_password', false);
        }

        if ($mustChange) {
            $targetUrl = match ($sessionRole) {
                'super_admin' => '/dashboard/super-admin',
                'admin'       => '/dashboard/admin',
                'kepala'      => '/dashboard/kepala',
                default       => '/pegawai/profil/' . urlencode($nip),
            };
            return redirect()->to($targetUrl)
                ->with('success', 'Password baru berhasil disimpan. Selamat datang!');
        }

        return redirect()->to('/pegawai/profil/' . urlencode($nip))
            ->with('success', 'Data akun & password berhasil diperbarui dan di-hash (Bcrypt).');
    }

    /**
     * Enkripsi (hash) seluruh password plain-text yang ada di tabel user sekaligus.
     */
    public function hashAll()
    {
        $count = $this->userModel->hashAllPlainPasswords();
        $msg   = $count > 0
            ? "Berhasil meng-hash {$count} password plain-text menjadi Bcrypt."
            : 'Seluruh password user di database sudah ter-enkripsi (Bcrypt).';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Reset seluruh password user menjadi sama dengan NIP masing-masing (di-hash Bcrypt).
     */
    public function resetAllToNip()
    {
        $count = $this->userModel->resetAllPasswordsToNip();
        return redirect()->back()->with('success', "Berhasil menyamakan {$count} password akun user dengan NIP masing-masing (ter-hash Bcrypt).");
    }
}
