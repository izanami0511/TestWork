<?php

namespace App\Controller;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(ImageRepository $images): Response
    {
        $images = $images->findAll();
        return $this->render('index/index.html.twig',
            compact('images'));
    }

    #[Route('/image_sort', name: 'image_sort')]
    public function sort(Request $request, EntityManagerInterface $em): Response
    {
        $sort = $request->query->get('sort', 'name');
        $repository = $em->getRepository(Image::class);

        $images = $repository->findBy([], [$sort => 'ASC']);

        return $this->render('index/index.html.twig', [
            'images' => $images,
        ]);
    }

    #[Route('/api/total', name: 'total')]
    public function getTotal(EntityManagerInterface $em):JsonResponse
    {
        $repository = $em->getRepository(Image::class);
        $total = $repository->count([]);

        return $this->json(['total' => $total]);
    }

    #[Route('/api/image/{id}', name: 'id_api')]
    public function getImageId(int $id, EntityManagerInterface $em):JsonResponse
    {
        $repository = $em->getRepository(Image::class);
        $image = $repository->find($id);

        if (!$image) {
            throw $this->createNotFoundException('Image not found');
        }

        return $this->json([
            'id' => $image->getId(),
            'path' => $image->getFile()
        ]);
    }

    #[Route('/api/images', name: 'images_api')]
    public function getImageList(Request $request, EntityManagerInterface $em):JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = 10;

        $repository = $em->getRepository(Image::class);
        $images = $repository->findBy([], null, $perPage, ($page - 1) * $perPage);

        $formattedImages = [];
        foreach ($images as $image) {
            $formattedImages[] = [
                'id' => $image->getId(),
                'path' => $image->getFile(),
            ];
        }

        return $this->json([
            'page' => $page,
            'list' => $formattedImages,
        ]);
    }
}
