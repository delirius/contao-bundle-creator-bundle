<?php

declare(strict_types=1);

/*
 * This file is part of Contao Bundle Creator Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-bundle-creator-bundle
 */

namespace Markocupic\ContaoBundleCreatorBundle\BundleMaker;

use Contao\Date;
use Contao\StringUtil;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\BundleClassMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\ComposerJsonMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\ContaoBackendModuleMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\ContaoContentElementMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\ContaoFrontendModuleMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\ContaoManagerPluginClassMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\ContinuousIntegrationMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\CustomRouteMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\DependencyInjectionExtensionClassMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\EasyCodingStandardMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Maker\MiscFilesMaker;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Message\Message;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\ParseToken\ParsePhpToken;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Storage\FileStorage;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Storage\TagStorage;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Str\Str;
use Markocupic\ContaoBundleCreatorBundle\Model\ContaoBundleCreatorModel;
use Markocupic\ZipBundle\Zip\Zip;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class BundleMaker.
 */
class BundleMaker
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var FileStorage
     */
    protected $fileStorage;

    /**
     * @var TagStorage
     */
    protected $tagStorage;

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var Zip
     */
    protected $zip;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var ContaoBundleCreatorModel
     */
    protected $input;

    /**
     * @var string
     */
    protected $skeletonPath;

    /**
     * BundleMaker constructor.
     */
    public function __construct(Session $session, FileStorage $fileStorage, TagStorage $tagStorage, Message $message, Zip $zip, string $projectDir)
    {
        $this->session = $session;
        $this->fileStorage = $fileStorage;
        $this->tagStorage = $tagStorage;
        $this->message = $message;
        $this->zip = $zip;
        $this->projectDir = $projectDir;
        $this->skeletonPath = realpath(__DIR__.'/../Resources/skeleton');
    }

    /**
     * Run contao bundle creator.
     *
     * @throws \Exception
     */
    public function run(ContaoBundleCreatorModel $input): void
    {
        $this->input = $input;

        if ($this->bundleExists() && !$this->input->overwriteexisting) {
            $this->message->addError('An extension with the same name already exists. Please set the "override extension flag".');

            return;
        }

        $this->message->addInfo(sprintf('Started generating "%s/%s" bundle.', $this->input->vendorname, $this->input->repositoryname));

        // Set the php template tags
        $this->setTags();

        // Add the composer.json file to file storage
        (new ComposerJsonMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();

        // Add the bundle class to file storage
        (new BundleClassMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();

        // Add the Dependency Injection Extension class to file storage
        (new DependencyInjectionExtensionClassMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();

        // Add the Contao Manager Plugin class to file storage
        (new ContaoManagerPluginClassMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();

        // Add unit tests to file storage
        (new ContinuousIntegrationMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();

        // Config files, assets, etc.
        (new MiscFilesMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();

        // Add ecs config files to the bundle
        if ($this->input->addEasyCodingStandard) {
            (new EasyCodingStandardMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();
        }

        // Add backend module files to file storage
        if ($this->input->addBackendModule && '' !== $this->input->dcatable) {
            (new ContaoBackendModuleMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();
        }

        // Add frontend module files to file storage
        if ($this->input->addFrontendModule) {
            (new ContaoFrontendModuleMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();
        }

        // Add content element files to file storage
        if ($this->input->addContentElement) {
            (new ContaoContentElementMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();
        }

        // Add a custom route to the file storage
        if ($this->input->addCustomRoute) {
            (new CustomRouteMaker($this->tagStorage, $this->fileStorage))->addToFileStorage();
        }
        // Create a backup of the old bundle that will be overwritten now
        if ($this->bundleExists()) {
            $this->createBackup();
        }

        // Copy all the bundle files from the storage to the destination directories in vendor/vendorname/bundlename
        $this->createBundleFiles();

        // Store new bundle also as a zip-package in system/tmp for downloading it after the generating process
        $this->generateZipArchive();

        // Optionally extend the composer.json file located in the root directory
        $this->editRootComposerJson();
    }

    /**
     * Check if an extension with the same name already exists.
     */
    protected function bundleExists(): bool
    {
        return is_dir($this->projectDir.'/vendor/'.$this->input->vendorname.'/'.$this->input->repositoryname);
    }

    /**
     * Set all the tags here.
     *
     * @throws \Exception
     *
     * @todo add a contao hook
     */
    protected function setTags(): void
    {
        // Store input values into the tag storage
        $arrModel = $this->input->row();

        foreach ($arrModel as $fieldname => $value) {
            $this->tagStorage->set((string) $fieldname, (string) $value);
        }

        // Tags
        $this->tagStorage->set('vendorname', (string) $this->input->vendorname);
        $this->tagStorage->set('repositoryname', (string) $this->input->repositoryname);
        $this->tagStorage->set('dependencyinjectionextensionclassname', Str::asDependencyInjectionExtensionClassName((string) $this->input->vendorname, (string) $this->input->repositoryname));

        // Namespaces
        $this->tagStorage->set('toplevelnamespace', Str::asClassName((string) $this->input->vendorname));
        $this->tagStorage->set('sublevelnamespace', Str::asClassName((string) $this->input->repositoryname));

        // Twig namespace @Vendor/Bundlename
        $this->tagStorage->set('twignamespace', Str::asTwigNameSpace((string) $this->input->vendorname, (string) $this->input->repositoryname));

        // Composer
        $this->tagStorage->set('composerdescription', (string) $this->input->composerdescription);
        $this->tagStorage->set('composerlicense', (string) $this->input->composerlicense);
        $this->tagStorage->set('composerauthorname', (string) $this->input->composerauthorname);
        $this->tagStorage->set('composerauthoremail', (string) $this->input->composerauthoremail);
        $this->tagStorage->set('composerauthorwebsite', (string) $this->input->composerauthorwebsite);

        // Phpdoc
        $this->tagStorage->set('bundlename', (string) $this->input->bundlename);
        $this->tagStorage->set('phpdoc', Str::generateHeaderCommentFromString($this->getContentFromPartialFile('phpdoc.tpl.txt')));
        $phpdoclines = explode(PHP_EOL, $this->getContentFromPartialFile('phpdoc.tpl.txt'));
        $ecsphpdoc = preg_replace("/[\r\n|\n]+/", '', implode('', array_map(static function ($line) {return $line.'\n'; }, $phpdoclines)));
        $this->tagStorage->set('ecsphpdoc', rtrim($ecsphpdoc, '\\n'));

        // Current year
        $this->tagStorage->set('year', date('Y'));

        // Dca table and backend module
        if ($this->input->addBackendModule && '' !== $this->input->dcatable) {
            $this->tagStorage->set('dcatable', (string) $this->input->dcatable);
            $this->tagStorage->set('modelclassname', (string) Str::asContaoModelClassName((string) $this->input->dcatable));
            $this->tagStorage->set('backendmoduletype', (string) $this->input->backendmoduletype);
            $this->tagStorage->set('backendmodulecategory', (string) $this->input->backendmodulecategory);
            $arrLabel = StringUtil::deserialize($this->input->backendmoduletrans, true);
            $this->tagStorage->set('backendmoduletrans_0', $arrLabel[0]);
            $this->tagStorage->set('backendmoduletrans_1', $arrLabel[1]);
        }

        // Frontend module
        if ($this->input->addFrontendModule) {
            $this->tagStorage->set('frontendmoduleclassname', Str::asContaoFrontendModuleClassName((string) $this->input->frontendmoduletype));
            $this->tagStorage->set('frontendmoduletype', (string) $this->input->frontendmoduletype);
            $this->tagStorage->set('frontendmodulecategory', (string) $this->input->frontendmodulecategory);
            $this->tagStorage->set('frontendmoduletemplate', Str::asContaoFrontendModuleTemplateName((string) $this->input->frontendmoduletype));
            $arrLabel = StringUtil::deserialize($this->input->frontendmoduletrans, true);
            $this->tagStorage->set('frontendmoduletrans_0', $arrLabel[0]);
            $this->tagStorage->set('frontendmoduletrans_1', $arrLabel[1]);
        }

        // Content element
        if ($this->input->addContentElement) {
            $this->tagStorage->set('contentelementclassname', Str::asContaoContentElementClassName((string) $this->input->contentelementtype));
            $this->tagStorage->set('contentelementtype', (string) $this->input->contentelementtype);
            $this->tagStorage->set('contentelementcategory', (string) $this->input->contentelementcategory);
            $this->tagStorage->set('contentelementtemplate', Str::asContaoContentElementTemplateName((string) $this->input->contentelementtype));
            $arrLabel = StringUtil::deserialize($this->input->contentelementtrans, true);
            $this->tagStorage->set('contentelementtrans_0', $arrLabel[0]);
            $this->tagStorage->set('contentelementtrans_1', $arrLabel[1]);
        }

        // Custom route
        $subject = sprintf(
            '%s_%s',
            strtolower($this->input->vendorname),
            strtolower($this->input->repositoryname)
        );
        $subject = preg_replace('/-bundle$/', '', $subject);
        $routeId = preg_replace('/-/', '_', $subject);
        $this->tagStorage->set('routeid', $routeId);

        if ($this->input->addCustomRoute) {
            $this->tagStorage->set('addCustomRoute', '1');
        } else {
            $this->tagStorage->set('addCustomRoute', '0');
        }
    }

    /**
     * Replace php tags and return content from partials.
     *
     * @throws \Exception
     */
    protected function getContentFromPartialFile(string $strFilename): string
    {
        $sourceFile = $this->skeletonPath.'/partials/'.$strFilename;

        if (!is_file($sourceFile)) {
            throw new FileNotFoundException(sprintf('Partial file "%s" not found.', $sourceFile));
        }

        if (false === ($content = file_get_contents($sourceFile))) {
            throw new \Exception(sprintf('Could not read content from file "%s".', $sourceFile));
        }

        $templateParser = new ParsePhpToken($this->tagStorage);

        return $templateParser->parsePhpTokensFromString($content);
    }

    protected function createBackup(): void
    {
        $zipSource = sprintf(
            '%s/vendor/%s/%s',
            $this->projectDir,
            $this->input->vendorname,
            $this->input->repositoryname
        );

        $zipTarget = sprintf(
            '%s/system/tmp/%s.zip',
            $this->projectDir,
            $this->input->repositoryname.'_backup_'.Date::parse('Y-m-d_H-i-s', time())
        );

        $this->zip
            ->stripSourcePath($zipSource)
            ->addDirRecursive($zipSource)
            ->run($zipTarget)
        ;
    }

    protected function generateZipArchive(): void
    {
        // Store new bundle also as a zip-package in system/tmp for downloading it after the generating process
        $zipSource = sprintf(
            '%s/vendor/%s/%s',
            $this->projectDir,
            $this->input->vendorname,
            $this->input->repositoryname
        );

        $zipTarget = sprintf(
            '%s/system/tmp/%s.zip',
            $this->projectDir,
            $this->input->repositoryname
        );

        $zip = $this->zip
            ->ignoreDotFiles(false)
            ->stripSourcePath($zipSource)
            ->addDirRecursive($zipSource)
        ;

        if ($zip->run($zipTarget)) {
            $this->session->set('CONTAO-BUNDLE-CREATOR.LAST-ZIP', str_replace($this->projectDir.'/', '', $zipTarget));
        }
    }

    /**
     * Write files from the file storage to the filesystem.
     *
     * @throws \Exception
     *
     * @todo add a contao hook
     */
    protected function createBundleFiles(): void
    {
        $arrFiles = $this->fileStorage->getAll();

        /*
         * @todo add a contao hook here
         * Manipulate, remove or add files to the storage
         */
        foreach ($arrFiles as $arrFile) {
            if (false !== $this->fileStorage->createFile($arrFile['target'])) {
                // Display message in the backend
                $this->message->addInfo(sprintf('Created file "%s/%s".', $this->projectDir, $arrFile['target']));
            } else {
                // Display message in the backend
                $this->message->addError(sprintf('Could not create file "%s/%s".', $this->projectDir, $arrFile['target']));
            }
        }

        // Display message in the backend
        $this->message->addInfo('Added one or more files to the bundle. Please run at least "composer install" or even "composer update", if you have made changes to the root composer.json.');
    }

    /**
     * Optionally edit the composer.json file located in the root directory.
     *
     * @throws \Exception
     */
    protected function editRootComposerJson(): void
    {
        $blnModified = false;

        $content = file_get_contents($this->projectDir.'/composer.json');
        $objJSON = json_decode($content);

        if ('' !== $this->input->editRootComposer) {
            if (!isset($objJSON->repositories)) {
                $objJSON->repositories = [];
            }

            $objRepositories = new \stdClass();

            if ('path' === $this->input->rootcomposerextendrepositorieskey) {
                $objRepositories->type = 'path';
                $objRepositories->url = sprintf(
                    'vendor/%s/%s',
                    $this->input->vendorname,
                    $this->input->repositoryname
                );

                // Prevent duplicate entries
                if (!\in_array($objRepositories, $objJSON->repositories, true)) {
                    $blnModified = true;
                    $objJSON->repositories[] = $objRepositories;
                    $this->message->addInfo('Extended the repositories section in the root composer.json. Please check!');
                }
            }

            if ('vcs-github' === $this->input->rootcomposerextendrepositorieskey) {
                $objRepositories->type = 'vcs';
                $objRepositories->url = sprintf(
                    'https://github.com/%s/%s',
                    $this->input->vendorname,
                    $this->input->repositoryname
                );

                // Prevent duplicate entries
                if (!\in_array($objRepositories, $objJSON->repositories, true)) {
                    $blnModified = true;
                    $objJSON->repositories[] = $objRepositories;
                    $this->message->addInfo('Extended the repositories section in the root composer.json. Please check!');
                }
            }
            // Extend require key
            $blnModified = true;
            $objJSON->require->{sprintf('%s/%s', $this->input->vendorname, $this->input->repositoryname)} = 'dev-main';
            $this->message->addInfo('Extended the require section in the root composer.json. Please check!');
        }

        if ($blnModified) {
            // Make a backup first
            $strBackupPath = sprintf(
                'system/tmp/composer_backup_%s.json',
                Date::parse('Y-m-d _H-i-s', time())
            );

            copy(
                $this->projectDir.\DIRECTORY_SEPARATOR.'composer.json',
                $this->projectDir.\DIRECTORY_SEPARATOR.$strBackupPath
            );

            $this->message->addInfo(sprintf('Created backup of composer.json in "%s"', $strBackupPath));

            // Append modifications
            $content = json_encode($objJSON, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            file_put_contents($this->projectDir.'/composer.json', $content);
        }
    }
}
