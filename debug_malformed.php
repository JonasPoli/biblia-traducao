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

// Search for the problematic patterns
$patterns = [
    '%PB%',
    '%G1097%',
];

foreach ($patterns as $pattern) {
    echo "Searching for: $pattern\n";
    $texts = $repo->createQueryBuilder('vt')
        ->where('vt.version = 22')
        ->andWhere('vt.text LIKE :pattern')
        ->setParameter('pattern', $pattern)
        ->setMaxResults(3)
        ->getQuery()
        ->getResult();

    echo "Found " . count($texts) . " results.\n";

    foreach ($texts as $vt) {
        echo "ID: " . $vt->getId() . "\n";
        echo "Text: " . $vt->getText() . "\n";
        echo "--------------------------------------------------\n";
    }
}
