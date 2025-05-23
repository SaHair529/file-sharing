<?php

namespace App\Controller;

use App\Entity\UploadedFile;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FileController extends AbstractController
{
    #[Route('/', name: 'main', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('file/upload.html.twig');
    }

    #[Route('/', name: 'upload_file', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $uploadedFiles = $request->files->get('files');
        if (!is_array($uploadedFiles)) {
            throw $this->createNotFoundException('No files uploaded.');
        }

        $token = bin2hex(random_bytes(16));
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $token;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($uploadedFiles as $uploadedFile) {
            $uploadedFile->move($uploadDir, $uploadedFile->getClientOriginalName());
        }
        $em->flush();

        return $this->json([
            'link' => $this->generateUrl('download_file', [
                'token' => $token
            ], 0)
        ]);
    }

    #[Route('/download/{token}', name: 'download_file')]
    public function download(string $token): Response
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $token;

        $filesystem = new Filesystem();
        if (!$filesystem->exists($uploadDir)) {
            throw $this->createNotFoundException('Folder not found');
        }

        $zipFilePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $token . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create zip file');
        }

        $files = scandir($uploadDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $zip->addFile($uploadDir . '/' . $file, $file);
            }
        }

        $zip->close();

        return new BinaryFileResponse($zipFilePath);
    }
}
