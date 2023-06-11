<?php

namespace App\Controller;

use App\Entity\Image;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\String_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Count;
use Cocur\Slugify\Slugify;

class ImageController extends AbstractController
{
    #[Route('/image', name: 'app_image')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        {
            $form = $this->createFormBuilder()
                ->add('images', FileType::class, [
                    'label' => 'Изображения',
                    'multiple' => true,
                    'attr' => ['accept' => 'image/*'],
                    'constraints' => [
                        new Count([
                            'max' => 5,
                            'maxMessage' => 'Вы можете загрузить максимум 5 изображений.',
                        ]),
                    ],
                ])
                ->add('submit', SubmitType::class, ['label' => 'Загрузить'])
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Обработка загрузки изображений
                $uploadedFiles = $form->get('images')->getData();

                foreach ($uploadedFiles as $uploadedFile) {
                    $image = new Image();

                    $originalName = $uploadedFile->getClientOriginalName();
                    $extension = $uploadedFile->getClientOriginalExtension();
                    $slugify = new Slugify();
                    $imageName = $slugify->slugify(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

                    $i = 1;
                    while (file_exists($this->getParameter('images_directory') . '/' . $imageName)) {
                        $imageName = $slugify->slugify(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . $i . '.' . $extension;
                        $i++;
                    }

                    $uploadedFile->move($this->getParameter('images_directory'), $imageName);

                    $image->setName($imageName);
                    $image->setFile($this->getParameter('images_directory') . '/' . $imageName);
                    $image->getCreatedAt()->format('string');

                    $em->persist($image);
                    $em->flush();
                }
            }

            return $this->render('image/index.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }
}
