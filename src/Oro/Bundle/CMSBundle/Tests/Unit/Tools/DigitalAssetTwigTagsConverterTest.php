<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Tools;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Entity\Repository\DigitalAssetRepository;
use Symfony\Component\Yaml\Yaml;

class DigitalAssetTwigTagsConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private static array $fixturesData = [];

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileUrlProvider;

    /** @var DigitalAssetTwigTagsConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);

        $this->converter = $this->getMockBuilder(DigitalAssetTwigTagsConverter::class)
            ->setConstructorArgs([$this->managerRegistry, $this->fileUrlProvider])
            ->onlyMethods(['generateUuid'])
            ->getMock();

        $this->converter
            ->expects($this->any())
            ->method('generateUuid')
            ->willReturnCallback(static fn () => 'new-uuid');
    }

    /**
     * @param string $name
     * @return array
     */
    private static function getFixturesData(string $name): array
    {
        if (!self::$fixturesData) {
            $fixturePath = sprintf(
                '%1$s%2$s..%2$sFixtures%2$sdigital_asset_twig_tags_converter_data.yml',
                __DIR__,
                DIRECTORY_SEPARATOR
            );
            self::$fixturesData = Yaml::parse(file_get_contents($fixturePath));
        }

        return self::$fixturesData[$name] ?? [];
    }

    /**
     * @dataProvider convertToUrlsDataProvider
     *
     * @param string $contentWithTwigTags
     * @param string $expected
     */
    public function testConvertToUrls(string $contentWithTwigTags, string $expected): void
    {
        $this->fileUrlProvider
            ->expects($this->any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(
                function (File $file, string $filterName) {
                    $this->assertEquals('wysiwyg_original', $filterName);

                    return sprintf(
                        '/media/cache/attachment/resize/wysiwyg_original/filterMd5/%1$d/file%1$d.jpg',
                        $file->getId()
                    );
                }
            );

        $this->fileUrlProvider
            ->expects($this->any())
            ->method('getFileUrl')
            ->willReturnCallback(
                function (File $file, string $actionName) {
                    $this->assertEquals(FileUrlProviderInterface::FILE_ACTION_DOWNLOAD, $actionName);

                    return sprintf('/attachment/download/%1$d/file%1$d.jpg', $file->getId());
                }
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->managerRegistry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $fileRepository = $this->createMock(FileRepository::class);

        $digitalAssetRepository = $this->createMock(DigitalAssetRepository::class);
        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [File::class, $fileRepository],
                    [DigitalAsset::class, $digitalAssetRepository],
                ]
            );

        $fileRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) {
                    $uuid = $criteria['uuid'];

                    if ($uuid === 'new-uuid') {
                        // File with such uuid does not exist.
                        return null;
                    }

                    return (new TestFile())->setId(explode('-', $uuid)[1]);
                }
            );

        $digitalAssetRepository
            ->expects($this->any())
            ->method('findSourceFile')
            ->willReturnCallback(
                static function (int $digitalAssetId) {
                    if ($digitalAssetId < 1000) {
                        // Digital asset with such id does not exist.
                        return null;
                    }

                    return (new TestFile())->setId($digitalAssetId * 10);
                }
            );

        $this->assertEquals($expected, $this->converter->convertToUrls($contentWithTwigTags));
    }

    /**
     * @return array
     */
    public function convertToUrlsDataProvider(): array
    {
        return self::getFixturesData('convertToUrls');
    }

    /**
     * @dataProvider convertToUrlsWithExceptionDataProvider
     *
     * @param string $contentWithTwigTags
     * @param string $expected
     */
    public function testConvertToUrlsWhenDatabaseException(string $contentWithTwigTags, string $expected): void
    {
        $this->fileUrlProvider
            ->expects($this->any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(
                function (File $file, string $filterName) {
                    $this->assertEquals('wysiwyg_original', $filterName);

                    return sprintf(
                        '/media/cache/attachment/resize/wysiwyg_original/filterMd5/%1$d/file%1$d.jpg',
                        $file->getId()
                    );
                }
            );

        $this->fileUrlProvider
            ->expects($this->any())
            ->method('getFileUrl')
            ->willReturnCallback(
                function (File $file, string $actionName) {
                    $this->assertEquals(FileUrlProviderInterface::FILE_ACTION_DOWNLOAD, $actionName);

                    return sprintf('/attachment/download/%1$d/file%1$d.jpg', $file->getId());
                }
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->managerRegistry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $fileRepository = $this->createMock(FileRepository::class);

        $digitalAssetRepository = $this->createMock(DigitalAssetRepository::class);
        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [File::class, $fileRepository],
                    [DigitalAsset::class, $digitalAssetRepository],
                ]
            );

        $fileRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willThrowException(new \Exception());

        $digitalAssetRepository
            ->expects($this->any())
            ->method('findSourceFile')
            ->willReturnCallback(
                static function (int $digitalAssetId) {
                    if ($digitalAssetId < 1000) {
                        // Digital asset with such id does not exist.
                        throw new NoResultException();
                    }

                    return (new TestFile())->setId($digitalAssetId * 10);
                }
            );

        $this->assertEquals($expected, $this->converter->convertToUrls($contentWithTwigTags));
    }

    /**
     * @return array
     */
    public function convertToUrlsWithExceptionDataProvider(): array
    {
        return self::getFixturesData('convertToUrlsWithException');
    }

    /**
     * @dataProvider convertToTwigTagsDataProvider
     *
     * @param string $contentWithUrls
     * @param string $expected
     */
    public function testConvertToTwigTags(string $contentWithUrls, string $expected): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->managerRegistry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $digitalAssetRepository = $this->createMock(DigitalAssetRepository::class);
        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(DigitalAsset::class)
            ->willReturn($digitalAssetRepository);

        $digitalAssetRepository
            ->expects($this->any())
            ->method('getFileDataForTwigTag')
            ->willReturnCallback(
                static function (int $fileId) {
                    if ($fileId < 500) {
                        // File does not exist.
                        return [];
                    }

                    if ($fileId < 1000) {
                        // File is regular - not a digital asset source or child.
                        return [];
                    }

                    if ($fileId < 2000) {
                        // File is a child of a digital asset.
                        return [
                            'parentEntityClass' => \stdClass::class,
                            'parentEntityId' => 42,
                            'uuid' => 'uuid-' . $fileId,
                            'digitalAssetId' => $fileId / 10,
                        ];
                    }

                    // File is a source file of a digital asset.
                    return [
                        'parentEntityClass' => DigitalAsset::class,
                        'parentEntityId' => $fileId / 10,
                        'uuid' => 'uuid-' . $fileId,
                        'digitalAssetId' => null,
                    ];
                }
            );

        $this->assertEquals($expected, $this->converter->convertToTwigTags($contentWithUrls));
    }

    /**
     * @return array
     */
    public function convertToTwigTagsDataProvider(): array
    {
        return self::getFixturesData('convertToTwigTags');
    }
}
