<?php

namespace App\Libraries;

/**
 * GDriveLibrary — Wrapper Google Drive API untuk SIMPEG CI4
 * Kredensial diambil dari .env (bukan hardcoded)
 */
class GDriveLibrary
{
    protected \Google_Service_Drive $driveService;

    /** @var array<string, string> */
    private array $folderCache = [];

    public function __construct(?\Google_Service_Drive $driveService = null)
    {
        if ($driveService !== null) {
            $this->driveService = $driveService;
            return;
        }

        $clientId     = env('gdrive.clientId');
        $clientSecret = env('gdrive.clientSecret');
        $refreshToken = env('gdrive.refreshToken');

        $client = new \Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->refreshToken($refreshToken);
        $client->addScope(\Google_Service_Drive::DRIVE);

        $this->driveService = new \Google_Service_Drive($client);
    }

    /**
     * Cari atau buat struktur folder hierarkis:
     * Folder Induk (gdrive.folderIdPegawai) -> Folder Nama Pegawai -> Folder Tahun Aktif.
     */
    public function getOrCreateEmployeeYearFolder(?string $namaPegawai, ?string $parentFolderId = null, ?string $tahun = null): string
    {
        $rootFolderId = trim((string) ($parentFolderId ?: env('gdrive.folderIdPegawai')));
        if ($rootFolderId === '') {
            throw new \RuntimeException('Folder induk Google Drive (gdrive.folderIdPegawai) belum dikonfigurasi.');
        }

        $cleanNama = trim((string) preg_replace('/\s+/', ' ', (string) $namaPegawai));
        if ($cleanNama === '') {
            $cleanNama = 'Pegawai';
        }

        $yearName = trim((string) ($tahun ?: date('Y')));

        try {
            $employeeFolderId = $this->getOrCreateFolder($cleanNama, $rootFolderId);
            return $this->getOrCreateFolder($yearName, $employeeFolderId);
        } catch (\Throwable $e) {
            log_message('error', 'GDrive Folder Error: ' . $e->getMessage());
            throw new \RuntimeException('Gagal menyiapkan folder Google Drive: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Cari folder dengan nama tertentu di dalam parent folder; buat baru jika belum ada.
     */
    private function getOrCreateFolder(string $folderName, string $parentFolderId): string
    {
        $cleanName = trim((string) preg_replace('/\s+/', ' ', $folderName));
        $cacheKey  = $parentFolderId . '|' . $cleanName;
        if (isset($this->folderCache[$cacheKey])) {
            return $this->folderCache[$cacheKey];
        }

        $escapedName   = str_replace(['\\', "'"], ['\\\\', "\\'"], $cleanName);
        $escapedParent = str_replace(['\\', "'"], ['\\\\', "\\'"], $parentFolderId);

        $query = sprintf(
            "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
            $escapedName,
            $escapedParent
        );

        $results = $this->driveService->files->listFiles([
            'q'                         => $query,
            'spaces'                    => 'drive',
            'fields'                    => 'files(id, name)',
            'pageSize'                  => 1,
            'supportsAllDrives'         => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $files = $results->getFiles();
        if (! empty($files)) {
            $folderId = (string) $files[0]->getId();
            $this->folderCache[$cacheKey] = $folderId;
            return $folderId;
        }

        $folderMetadata = new \Google_Service_Drive_DriveFile([
            'name'     => $cleanName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => [$parentFolderId],
        ]);

        $created = $this->driveService->files->create($folderMetadata, [
            'fields'            => 'id',
            'supportsAllDrives' => true,
        ]);

        $folderId = (string) $created->getId();
        if ($folderId === '') {
            throw new \RuntimeException("Gagal membuat folder Google Drive '{$cleanName}'.");
        }

        $this->folderCache[$cacheKey] = $folderId;
        return $folderId;
    }

    /**
     * Upload file ke Google Drive
     *
     * @param  string $tmpPath    Path file sementara (dari $_FILES['tmp_name'])
     * @param  string $fileName   Nama file asli
     * @param  string $mimeType   MIME type
     * @param  string $folderId   ID Folder tujuan di GDrive
     * @return string|false       URL publik file atau false jika gagal
     */
    public function upload(string $tmpPath, string $fileName, string $mimeType, string $folderId)
    {
        try {
            $fileMetadata = new \Google_Service_Drive_DriveFile([
                'name'    => $fileName,
                'parents' => [$folderId],
            ]);

            $content  = file_get_contents($tmpPath);
            $uploaded = $this->driveService->files->create($fileMetadata, [
                'data'              => $content,
                'mimeType'          => $mimeType,
                'uploadType'        => 'multipart',
                'fields'            => 'id',
                'supportsAllDrives' => true,
            ]);

            $fileId = $uploaded->getId();

            // Set permission public (readable by anyone with link)
            $perm = new \Google_Service_Drive_Permission();
            $perm->setType('anyone');
            $perm->setRole('reader');
            $this->driveService->permissions->create($fileId, $perm, [
                'supportsAllDrives' => true,
            ]);

            return "https://drive.google.com/file/d/{$fileId}/view";
        } catch (\Exception $e) {
            log_message('error', 'GDrive Upload Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus file dari Google Drive berdasarkan URL
     */
    public function delete(string $fileUrl): bool
    {
        $fileId = $this->extractFileId($fileUrl);
        if (! $fileId) return false;

        try {
            $this->driveService->files->delete($fileId, ['supportsAllDrives' => true]);
            return true;
        } catch (\Exception $e) {
            log_message('error', 'GDrive Delete Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ekstrak ID file dari URL Google Drive
     */
    public function extractFileId(string $url): ?string
    {
        if (empty($url)) return null;
        if (preg_match('/[-\w]{25,}/', $url, $matches)) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Validasi file upload (ekstensi + ukuran)
     */
    public static function validateFile(\CodeIgniter\HTTP\Files\UploadedFile $file, int $maxMb = 5): bool
    {
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        $ext     = strtolower($file->getClientExtension());

        if (! in_array($ext, $allowed, true)) return false;
        if ($file->getSize() > $maxMb * 1024 * 1024) return false;

        return true;
    }
}
