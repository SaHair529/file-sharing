<?php

namespace App\Controller;

use App\Entity\UploadedFile;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FileController extends AbstractController
{
    #[Route('/', name: 'upload_file', methods: ['GET', 'POST'])]
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $uploadedFile = $request->file->get('file');
            if ($uploadedFile) {
                $newFileName = bin2hex(random_bytes(16)) . '.' . $uploadedFile->guessExtension();
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                $uploadedFile->move($uploadDir, $newFileName);

                $fileEntity = new UploadedFile();
                $fileEntity->setFilename($newFileName);
                $fileEntity->setOriginalName($uploadedFile->getClientOriginalName);
                $fileEntity->setUploadedAt(new DateTimeImmutable());
                $fileEntity->setExpiresAt((new DateTimeImmutable())->modify('+ 1 day'));
                $fileEntity->setToken(bin2hex(random_bytes(16)));

                $em->persist($fileEntity);
                $em->flush();

                return $this->render('file/upload_success.html.twig', [
                    'download_link' => $this->generateUrl('download_file', [
                        'token' => $fileEntity->getToken()
                    ], 0)
                ]);
            }

        }

        return $this->render('file/upload.html.twig');
    }

    #[Route('/download/{token}', name: 'download_file')]
    public function download(string $token, EntityManagerInterface $em): Response
    {
        $fileEntity = $em->getRepository(UploadedFile::class)->findOneBy(['token' => $token]);
        if (!$fileEntity || $fileEntity->getExpiresAt() < new DateTimeImmutable()) {
            throw $this->createNotFoundException('File not found or expired.');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads' . $fileEntity->getFileName();

        $filesystem = new Filesystem();
        if (!$filesystem->exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        return new BinaryFileResponse($filePath);
    }
}
