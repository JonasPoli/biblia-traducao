<?php

use App\Kernel;
use App\Entity\VerseText;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$repo = $em->getRepository(VerseText::class);

// Find texts with G3772 in version 22
$texts = $repo->createQueryBuilder('vt')
    ->where('vt.version = 22')
    ->andWhere('vt.text LIKE :strong')
    ->setParameter('strong', '%G3772%')
    ->setMaxResults(5)
    ->getQuery()
    ->getResult();

foreach ($texts as $vt) {
    echo "ID: " . $vt->getId() . "\n";
    echo "Text: " . $vt->getText() . "\n";
    echo "--------------------------------------------------\n";
}
