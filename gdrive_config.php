<?php
// gdrive_config.php
require_once 'vendor/autoload.php';

function getDriveService() {
    $clientId = '254949287359-6i2ce8gp08isfjg09nj5g7pu95pa70oo.apps.googleusercontent.com';
    $clientSecret = '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';
    $refreshToken = '1//04Eh9kLeNpG83CgYIARAAGAQSNwF-L9Ir8FGLZulizxCtUOlhQoRlmiHC0yQ9W83Jm1o2H0okkWS0Vf2-z6jt8vCa562AS_xVyrA';
    
    $client = new Google_Client();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->refreshToken($refreshToken);
    $client->addScope(Google_Service_Drive::DRIVE);
    
    return new Google_Service_Drive($client);
}

// Fungsi mengekstrak ID dari link GDrive (Versi Paling Akurat)
function getGDriveFileId($url) {
    if (empty($url)) return null;
    
    // Ambil kombinasi karakter 25+ digit dari URL (Ciri khas ID GDrive)
    if (preg_match('/[-\w]{25,}/', $url, $matches)) {
        return $matches[0];
    }
    return null;
}

// Fungsi mengeksekusi penghapusan ke API Google Drive
function deleteFromGDrive($fileUrl) {
    $fileId = getGDriveFileId($fileUrl);
    
    if (!$fileId) {
        return "Gagal: ID File tidak ditemukan di URL.";
    }

    try {
        $driveService = getDriveService();
        $driveService->files->delete($fileId);
        return true; // Berhasil
    } catch (Exception $e) {
        // Kembalikan pesan error dari Google API
        return "Error Google API: " . $e->getMessage();
    }
}
?>