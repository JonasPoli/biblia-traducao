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
    #[Route('/admin/global-references', name: 'app_global_reference_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        GlobalReferenceRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        // Handle Add
        if ($request->isMethod('POST')) {
            $term = $request->request->get('term');
            $referenceText = $request->request->get('referenceText');

            if ($term && $referenceText) {
                $ref = new GlobalReference();
                $ref->setTerm($term);
                $ref->setReferenceText($referenceText);

                $entityManager->persist($ref);
                $entityManager->flush();

                $this->addFlash('success', 'Referência Global adicionada!');
                return $this->redirectToRoute('app_global_reference_index');
            }
        }

        $references = $repository->findAll();

        return $this->render('global_reference/index.html.twig', [
            'references' => $references,
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
