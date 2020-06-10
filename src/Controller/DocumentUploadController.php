<?php

namespace App\Controller;

use App\Entity\DocumentUpload;
use App\Form\DocumentUploadType;
use App\Repository\DocumentUploadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/document/upload")
 */
class DocumentUploadController extends AbstractController
{
    const UPLOAD_PATH = 'uploads/';

    /**
     * @Route("/", name="document_upload_index", methods={"GET"})
     */
    public function index(DocumentUploadRepository $documentUploadRepository): Response
    {
        return $this->render('document_upload/index.html.twig', [
            'document_uploads' => $documentUploadRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="document_upload_new", methods={"GET","POST"})
     */
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $documentUpload = new DocumentUpload();
        $form = $this->createForm(DocumentUploadType::class, $documentUpload);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $pdfFile */
            $pdfFile = $form->get('path')->getData();
            if ($pdfFile) {
                $originalFileName = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = $slugger->slug($originalFileName).'-'.uniqid().''.$pdfFile->guessExtension();

                try {
                    $pdfFile->move(static::UPLOAD_PATH, $safeName);
                    $documentUpload->setPath($safeName);
                } catch (\Throwable $exception) {
                    echo "Error uploading pdf! Please retry!";sleep (5);
                    return $this->redirectToRoute('document_upload_index');
                }
            }

            if (null === $documentUpload->getCreated()) {
                $documentUpload->setCreated(new \DateTime());
            }

            $documentUpload->setModified(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($documentUpload);
            $entityManager->flush();

            return $this->redirectToRoute('document_upload_index');
        }

        return $this->render('document_upload/new.html.twig', [
            'document_upload' => $documentUpload,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="document_upload_show", methods={"GET"})
     */
    public function show(DocumentUpload $documentUpload): Response
    {
        $response = new Response(
            $this->render('document_upload/show.html.twig', [
                'document_upload' => $documentUpload,
            ])
        );

        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }

    /**
     * @Route("/pdf/{documentPath}", name="show_pdf", methods={"GET"})
     */
    public function showPdf(string $documentPath): Response
    {
        return new BinaryFileResponse(static::UPLOAD_PATH . $documentPath);
    }

    /**
     * @Route("/{id}/edit", name="document_upload_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, DocumentUpload $documentUpload): Response
    {
        $form = $this->createForm(DocumentUploadType::class, $documentUpload);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('document_upload_index');
        }

        return $this->render('document_upload/edit.html.twig', [
            'document_upload' => $documentUpload,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="document_upload_delete", methods={"DELETE"})
     */
    public function delete(Request $request, DocumentUpload $documentUpload): Response
    {
        if ($this->isCsrfTokenValid('delete'.$documentUpload->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($documentUpload);
            $entityManager->flush();
        }

        return $this->redirectToRoute('document_upload_index');
    }
}
