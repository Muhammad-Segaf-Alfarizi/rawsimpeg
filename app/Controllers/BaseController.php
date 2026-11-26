<?php

namespace App\Controllers;

use App\Libraries\GDriveLibrary;
use App\Models\PegawaiModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Model;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Cek apakah user saat ini berhak menambah/mengubah data pegawai milik $targetNip.
     * super_admin dan admin berhak penuh; kepala dan pegawai hanya untuk NIP miliknya sendiri.
     */
    protected function canModifyEmployeeData(string $targetNip): bool
    {
        $role = session()->get('role');
        if (in_array($role, ['super_admin', 'admin'], true)) {
            return true;
        }

        $nipSession = (string) session()->get('nip');
        return $nipSession !== '' && $targetNip === $nipSession && in_array($role, ['kepala', 'pegawai'], true);
    }

    /**
     * Pastikan role pegawai hanya membuka data miliknya sendiri.
     */
    protected function guardEmployeeViewAccess(string $targetNip, string $routePrefix): ?RedirectResponse
    {
        $role       = session()->get('role');
        $nipSession = (string) session()->get('nip');

        if ($role === 'pegawai' && $targetNip !== $nipSession) {
            return redirect()->to('/' . trim($routePrefix, '/') . '/' . urlencode($nipSession));
        }

        return null;
    }

    /**
     * Pastikan user berhak memodifikasi data milik $targetNip sebelum eksekusi aksi tulis/hapus.
     */
    protected function guardEmployeeModifyAccess(string $targetNip, string $routePrefix): ?RedirectResponse
    {
        if ($redirect = $this->guardEmployeeViewAccess($targetNip, $routePrefix)) {
            return $redirect;
        }

        if (! $this->canModifyEmployeeData($targetNip)) {
            $msg = session()->get('role') === 'kepala'
                ? 'Akses ditolak. Role Pimpinan hanya dapat mengubah data miliknya sendiri.'
                : 'Akses ditolak.';

            return redirect()->to('/' . trim($routePrefix, '/') . '/' . urlencode($targetNip))
                ->with('error', $msg);
        }

        return null;
    }

    /**
     * Ambil record berdasarkan primary key dan pastikan kolom nip sesuai dengan $nip target.
     */
    protected function findOwnedRecord(Model $model, int $id, string $nip): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $model->find($id);
        if (! is_array($row) || (string) ($row['nip'] ?? '') !== $nip) {
            return null;
        }

        return $row;
    }

    protected ?GDriveLibrary $gdriveLibrary = null;

    /**
     * Dapatkan instance GDriveLibrary (lazy-loaded).
     */
    protected function createGDrive(): GDriveLibrary
    {
        return $this->gdriveLibrary ??= new GDriveLibrary();
    }

    /**
     * Ambil nama pegawai untuk penamaan folder Google Drive, fallback ke NIP bila kosong.
     */
    protected function resolveEmployeeFolderName(PegawaiModel $pegawaiModel, string $nip, ?array $pegawai = null): string
    {
        $row  = $pegawai ?? $pegawaiModel->findByNip($nip);
        $nama = trim((string) ($row['nama'] ?? ''));

        return $nama !== '' ? $nama : $nip;
    }

    /**
     * Validasi dan upload lampiran pegawai (ABK/TNA/Diklat) ke folder Nama Pegawai -> Tahun di GDrive.
     */
    protected function uploadEmployeeAttachment(
        PegawaiModel $pegawaiModel,
        string $nip,
        string $inputName,
        string $fileTag,
        ?string $existingUrl = null,
        ?string $tahun = null
    ): ?string {
        $file = $this->request->getFile($inputName);
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $existingUrl;
        }

        if (! GDriveLibrary::validateFile($file)) {
            return $existingUrl;
        }

        $namaPegawai = $this->resolveEmployeeFolderName($pegawaiModel, $nip);

        try {
            $gdrive         = $this->createGDrive();
            $targetFolderId = $gdrive->getOrCreateEmployeeYearFolder($namaPegawai, null, $tahun);
            $fileName       = $nip . '_' . $fileTag . '_' . time() . '.' . $file->getClientExtension();

            $url = $gdrive->upload(
                $file->getTempName(),
                $fileName,
                $file->getMimeType(),
                $targetFolderId
            );

            if ($url === false) {
                return $existingUrl;
            }

            if (! empty($existingUrl)) {
                $gdrive->delete($existingUrl);
            }

            return $url;
        } catch (\Throwable $e) {
            log_message('error', "Upload {$fileTag} GDrive Error: " . $e->getMessage());
            return $existingUrl;
        }
    }
}
