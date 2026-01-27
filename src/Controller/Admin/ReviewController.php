<?php

namespace App\Controller\Admin;

use App\Entity\Paratext;
use App\Entity\ParatextReview;
use App\Entity\User;
use App\Entity\Verse;
use App\Entity\VerseReview;
use App\Repository\ParatextRepository;
use App\Repository\ParatextReviewRepository;
use App\Repository\VerseRepository;
use App\Repository\VerseReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/review')]
#[IsGranted('ROLE_USER')] // Or specific role like ROLE_TRANSLATOR?
class ReviewController extends AbstractController
{
    #[Route('/toggle/{type}/{id}', name: 'app_admin_review_toggle', methods: ['POST'])]
    public function toggle(
        string $type,
        int $id,
        VerseRepository $verseRepository,
        ParatextRepository $paratextRepository,
        VerseReviewRepository $verseReviewRepository,
        ParatextReviewRepository $paratextReviewRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        if ($type === 'verse') {
            $verse = $verseRepository->find($id);
            if (!$verse) {
                return $this->json(['error' => 'Verse not found'], 404);
            }

            $currentReview = $verseReviewRepository->findOneBy(['user' => $user, 'verse' => $verse]);

            if ($currentReview) {
                // Un-approve
                $entityManager->remove($currentReview);
                $approved = false;
            } else {
                // Approve
                $review = new VerseReview();
                $review->setUser($user);
                $review->setVerse($verse);
                $entityManager->persist($review);
                $approved = true;
            }
        } elseif ($type === 'paratext') {
            $paratext = $paratextRepository->find($id);
            if (!$paratext) {
                return $this->json(['error' => 'Paratext not found'], 404);
            }

            $currentReview = $paratextReviewRepository->findOneBy(['user' => $user, 'paratext' => $paratext]);

            if ($currentReview) {
                // Un-approve
                $entityManager->remove($currentReview);
                $approved = false;
            } else {
                // Approve
                $review = new ParatextReview();
                $review->setUser($user);
                $review->setParatext($paratext);
                $entityManager->persist($review);
                $approved = true;
            }
        } else {
            return $this->json(['error' => 'Invalid type. Use "verse" or "paratext".'], 400);
        }

        $entityManager->flush();

        return $this->json([
            'status' => 'success',
            'approved' => $approved,
            'type' => $type,
            'id' => $id
        ]);
    }
}
