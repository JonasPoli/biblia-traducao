<?php

namespace App\Controller\admin;

use App\Entity\GlobalReference;
use App\Repository\GlobalReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GlobalReferenceController extends AbstractController
{
    #[Route('/admin/global-references', name: 'app_global_reference_index', methods: ['GET'])]
    public function index(
        Request $request,
        GlobalReferenceRepository $repository
    ): Response {
        $page = $request->query->getInt('page', 1);
        $limit = 50;
        $search = $request->query->get('search');

        $paginator = $repository->search($search, $page, $limit);
        $total = count($paginator);
        $pages = ceil($total / $limit);

        return $this->render('admin/global_reference/index.html.twig', [
            'references' => $paginator,
            'page' => $page,
            'pages' => $pages,
            'search' => $search,
        ]);
    }

    #[Route('/admin/global-references/new', name: 'app_global_reference_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $ref = new GlobalReference();

        if ($request->isMethod('POST')) {
            $term = $request->request->get('term');
            $referenceText = $request->request->get('referenceText');
            $foreignWord = $request->request->get('foreignWord');
            $strongId = $request->request->get('strongId');

            if ($term && $referenceText) {
                $ref->setTerm($term);
                $ref->setReferenceText($referenceText);
                $ref->setForeignWord($foreignWord);
                $ref->setStrongId($strongId);

                $entityManager->persist($ref);
                $entityManager->flush();

                $this->addFlash('success', 'Referência Global adicionada!');
                return $this->redirectToRoute('app_global_reference_index');
            }
        }

        return $this->render('admin/global_reference/new.html.twig', [
            'reference' => $ref,
        ]);
    }

    #[Route('/admin/global-references/{id}/edit', name: 'app_global_reference_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        GlobalReferenceRepository $repository,
        \App\Repository\VerseTextRepository $verseTextRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $ref = $repository->find($id);
        if (!$ref) {
            throw $this->createNotFoundException('Referência não encontrada');
        }

        if ($request->isMethod('POST')) {
            $term = $request->request->get('term');
            $referenceText = $request->request->get('referenceText');
            $foreignWord = $request->request->get('foreignWord');
            $strongId = $request->request->get('strongId');

            if ($term && $referenceText) {
                $ref->setTerm($term);
                $ref->setReferenceText($referenceText);
                $ref->setForeignWord($foreignWord);
                $ref->setStrongId($strongId);

                $entityManager->flush();

                $this->addFlash('success', 'Referência Global atualizada!');
                return $this->redirectToRoute('app_global_reference_index');
            }
        }

        // Fetch affected verses (Version 17 - Haroldo Dutra as per request context, or 17 as target version)
        // The user said "based on the translation (version_id=17)"
        $verses = $verseTextRepository->findVersesByTerm($ref->getTerm(), 17);

        // Group verses by book
        $groupedVerses = [];
        foreach ($verses as $vt) {
            $bookName = $vt->getVerse()->getBook()->getName();
            $groupedVerses[$bookName][] = $vt;
        }

        return $this->render('admin/global_reference/edit.html.twig', [
            'reference' => $ref,
            'groupedVerses' => $groupedVerses,
        ]);
    }

    #[Route('/admin/global-references/delete/{id}', name: 'app_global_reference_delete', methods: ['POST'])]
    public function delete(
        int $id,
        GlobalReferenceRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $ref = $repository->find($id);
        if ($ref) {
            $entityManager->remove($ref);
            $entityManager->flush();
            $this->addFlash('success', 'Referência removida.');
        }

        return $this->redirectToRoute('app_global_reference_index');
    }
}
