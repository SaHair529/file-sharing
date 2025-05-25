<?php

namespace App\Command;

use App\Entity\Token;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:remove-expired-tokens',
    description: '',
)]
class RemoveExpiredTokensCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $expiredTokens = $this->em->getRepository(Token::class)
            ->createQueryBuilder('t')
            ->where('t.expires_in < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
            
        $filesystem = new Filesystem();
        
        foreach ($expiredTokens as $token) {
            $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/' . $token->getValue();
            $uploadZip = $this->kernel->getProjectDir() . '/public/uploads/' . $token->getValue() . '.zip';
            if ($filesystem->exists($uploadZip)) {
                $filesystem->remove($uploadZip);
            }
            if ($filesystem->exists($uploadDir)) {
                $filesystem->remove($uploadDir);
            }
            $this->em->remove($token);
        }
        $this->em->flush();

        $output->writeln('Expired files removed successfully.');

        return Command::SUCCESS;
    }
}
