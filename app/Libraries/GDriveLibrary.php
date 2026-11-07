<?php

namespace App\Libraries;

/**
 * GDriveLibrary — Wrapper Google Drive API untuk SIMPEG CI4
 * Kredensial diambil dari .env (bukan hardcoded)
 */
class GDriveLibrary
{
    protected \Google_Service_Drive $driveService;

    public function __construct()
    {
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
                'data'       => $content,
                'mimeType'   => $mimeType,
                'uploadType' => 'multipart',
                'fields'     => 'id',
            ]);

            $fileId = $uploaded->getId();

            // Set permission public (readable by anyone with link)
            $perm = new \Google_Service_Drive_Permission();
            $perm->setType('anyone');
            $perm->setRole('reader');
            $this->driveService->permissions->create($fileId, $perm);

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
            $this->driveService->files->delete($fileId);
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
