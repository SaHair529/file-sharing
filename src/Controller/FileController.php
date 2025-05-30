<?php

namespace App\Controller;

use App\Entity\Token;
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
        
        $totalSize = 0;
        foreach ($uploadedFiles as $uploadedFile) {
            $totalSize += $uploadedFile->getSize();
        }
        $maxSize = 1024 * 1024 * 1024;
        if ($totalSize > $maxSize) {
            return $this->json([
                'error' => 'Суммарный размер файлов превышает 1 ГБ.'
            ], Response::HTTP_BAD_REQUEST);
        }
        $token = bin2hex(random_bytes(16));
        while ($em->getRepository(Token::class)->findOneBy(['value' => $token])) {
            $token = bin2hex(random_bytes(16));
        }
        $tokenEntity = new Token();
        $tokenEntity->setValue($token);
        $tokenEntity->setExpiresIn(new \DateTimeImmutable('+1 hour'));
        $em->persist($tokenEntity);
        $em->flush();

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $token;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($uploadedFiles as $uploadedFile) {
            $uploadedFile->move($uploadDir, $uploadedFile->getClientOriginalName());
        }
        $em->flush();

        return $this->json([
            'link' => $this->generateUrl('download_page', [
                'token' => $token
            ], 0)
        ]);
    }

    #[Route('/download/{token}', name: 'download_page', methods: ['GET'])]
    public function download(string $token, EntityManagerInterface $em): Response
    {
        $tokenEntity = $em->getRepository(Token::class)->findOneBy(['value' => $token]);
        if (!$tokenEntity || $tokenEntity->isExpired())
            return $this->render('/file/download.html.twig', [
                'error' => 'Файлы не найдены либо истек срок действия'
            ])->setStatusCode(Response::HTTP_NOT_FOUND);

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $token;

        $filesystem = new Filesystem();
        if (!$filesystem->exists($uploadDir))
            return $this->render('/file/download.html.twig', [
                'error' => 'Папка с файлами не найдена'
            ])->setStatusCode(Response::HTTP_NOT_FOUND);

        $files = array_diff(scandir($uploadDir), ['..', '.']);

        return $this->render('/file/download.html.twig', [
            'files' => $files,
            'token' => $token
        ]);
    }

    #[Route('/download/{token}/{filename}', name: 'download_file', methods: ['GET'])]
    public function downloadFile(string $token, string $filename, EntityManagerInterface $em): Response
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $token;
        $tokenEntity = $em->getRepository(Token::class)->findOneBy(['value' => $token]);
        if (!$tokenEntity || $tokenEntity->isExpired())
            return new Response(null, Response::HTTP_NOT_FOUND);

        $filesystem = new Filesystem();
        if (!$filesystem->exists($uploadDir))
            return new Response(null, Response::HTTP_NOT_FOUND);

        $filePath = $uploadDir . '/' . $filename;
        if (!$filesystem->exists($filePath))
            return new Response(null, Response::HTTP_NOT_FOUND);

        return new BinaryFileResponse($filePath);
    }

    #[Route('/download_all/{token}', name: 'download_all', methods: ['GET'])]
    public function downloadAll(string $token, EntityManagerInterface $em): Response
    {
        $tokenEntity = $em->getRepository(Token::class)->findOneBy(['value' => $token]);
        if (!$tokenEntity || $tokenEntity->isExpired())
            return new Response(null, Response::HTTP_NOT_FOUND);

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $token;

        $filesystem = new Filesystem();
        if (!$filesystem->exists($uploadDir))
            return new Response(null, Response::HTTP_NOT_FOUND);

        $zipFilePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $token . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
            return new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR);

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
