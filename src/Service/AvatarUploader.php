<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Grava/substitui/remove a foto (avatar) do usuário em public/uploads/avatars.
 */
class AvatarUploader
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%/public/uploads/avatars')]
        private readonly string $avatarDir,
    ) {
    }

    /**
     * Salva a nova foto e apaga a anterior. Retorna false se não conseguiu gravar.
     */
    public function replace(User $user, UploadedFile $file): bool
    {
        $base = $this->slugger->slug((string) ($user->getName() ?: 'avatar'))->lower()->truncate(40);
        $newFilename = sprintf('%s-%s.%s', $base, bin2hex(random_bytes(6)), $file->guessExtension() ?: 'jpg');

        try {
            if (!is_dir($this->avatarDir)) {
                mkdir($this->avatarDir, 0775, true);
            }
            $file->move($this->avatarDir, $newFilename);
        } catch (\Throwable $e) {
            $this->logger->error('Falha ao gravar avatar', ['userId' => $user->getId(), 'error' => $e->getMessage()]);

            return false;
        }

        $this->deleteFile($user->getImage());
        $user->setImage($newFilename);

        return true;
    }

    public function remove(User $user): void
    {
        $this->deleteFile($user->getImage());
        $user->setImage(null);
    }

    private function deleteFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $path = $this->avatarDir . '/' . basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
