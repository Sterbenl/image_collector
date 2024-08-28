<?php
// src/Controller/ImageController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Goutte\Client;

class ImageController extends AbstractController
{
    public function index(Request $request): Response
    {
        return $this->render('image/index.html.twig');
    }
  
    public function process(Request $request): Response
    {
        $url = $request->request->get('url');

        if (empty($url)) {
            return $this->redirectToRoute('image_index');
        }

        $images = $this->getImages($url);
        $imageSizes = $this->getImageSizes($images);
        list($filteredImages, $filteredImageSizes) = $this->filterImages($images, $imageSizes);
        $totalImageSize = $this->getTotalImageSize($filteredImageSizes);

        return $this->render('image/result.html.twig', [
            'images' => $filteredImages,
            'imageSizes' => $filteredImageSizes,
            'totalImageSize' => $totalImageSize,
        ]);
    }


    private function getImageClient(): Client
    {
        return new Client();
    }

    private function getImages(string $url): array
    {
        $client = $this->getImageClient();
        $crawler = $client->request('GET', $url);

        $images = [];
        $crawler->filter('img')->each(function ($node) use (&$images) {
            $images[] = $node->attr('src');
        });

        return $images;
    }

    private function getImageSizes(array $images): array
    {
        $imageSizes = [];
        foreach ($images as $image) {
            if (!empty($image) && filter_var($image, FILTER_VALIDATE_URL)) {
                try {
                    $headers = get_headers($image, 1);
                    $imageSize = $headers['Content-Length'];
                    $imageSizes[] = $imageSize;
                } catch (\Exception $e) {
                    $imageSizes[] = 0;
                }
            } else {
                $imageSizes[] = 0;
            }
        }

        return $imageSizes;
    }

    private function getTotalImageSize(array $imageSizes): string
    {
        $totalSize = array_sum($imageSizes);
        $totalSizeInMb = number_format($totalSize / (1024 * 1024), 2);

        return sprintf('%d байт (%s МБ)', $totalSize, $totalSizeInMb);
    }
    private function filterImages(array $images, array $imageSizes): array
    {
        $filteredImages = [];
        $filteredImageSizes = [];

        foreach ($images as $key => $image) {
            if ($imageSizes[$key] > 1) {
                $filteredImages[] = $image;
                $filteredImageSizes[] = $imageSizes[$key];
            }
        }

        return [$filteredImages, $filteredImageSizes];
    }
}