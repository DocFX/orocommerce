<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller with action for get/download digital asset source file in WYSIWYG fields.
 */
class WysiwygDigitalAssetController extends AbstractController
{
    /**
     * @Route(
     *     "/{action}/{id}",
     *     name="oro_cms_wysiwyg_digital_asset",
     *     requirements={"id"="\d+", "action"="(get|download)"},
     *     defaults={"action"="get"}
     * )
     * @param int    $id
     * @param string $action
     *
     * @return Response
     */
    public function getSourceFileAction(int $id, string $action): Response
    {
        $file = $this->getSourceFile($id);

        $response = new Response();
        $response->headers->set('Cache-Control', 'public');

        if ($action === FileUrlProviderInterface::FILE_ACTION_GET) {
            $response->headers->set('Content-Type', $file->getMimeType() ?: 'application/force-download');
        } else {
            $response->headers->set('Content-Type', 'application/force-download');

            $contentDisposition = 'attachment';
            if ($file->getOriginalFilename()) {
                $contentDisposition .= sprintf(';filename="%s"', addslashes($file->getOriginalFilename()));
            }
            $response->headers->set('Content-Disposition', $contentDisposition);
        }

        $response->headers->set('Content-Length', $file->getFileSize());
        $response->setContent($this->getFileManager()->getContent($file));

        return $response;
    }

    /**
     * @param int $digitalAssetId
     *
     * @return File
     */
    private function getSourceFile(int $digitalAssetId): File
    {
        try {
            $file = $this->getDoctrine()->getRepository(DigitalAsset::class)
                ->findSourceFile($digitalAssetId);
        } catch (NonUniqueResultException | NoResultException $e) {
            throw $this->createNotFoundException('File not found');
        }

        if (!$this->isGranted('VIEW', $file)) {
            throw $this->createAccessDeniedException();
        }

        return $file;
    }

    private function getFileManager(): FileManager
    {
        return $this->get('oro_attachment.file_manager');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'oro_attachment.file_manager' => FileManager::class
        ]);
    }
}
