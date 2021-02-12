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

namespace Markocupic\ContaoBundleCreatorBundle\Subscriber\Maker;

use Markocupic\ContaoBundleCreatorBundle\Event\AddMakerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContinuousIntegrationMaker extends AbstractMaker implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AddMakerEvent::NAME => ['addFilesToStorage', 960],
        ];
    }

    /**
     * Add unit tests to the file storage.
     *
     * @throws \Exception
     */
    public function addFilesToStorage(AddMakerEvent $event): void
    {
        parent::addFilesToStorage($event);

        // Add phpunit.xml.dist
        $source = sprintf(
            '%s/phpunit.xml.tpl.dist',
            $this->skeletonPath
        );

        $target = sprintf(
            '%s/vendor/%s/%s/phpunit.xml.dist',
            $this->projectDir,
            $this->arrInput['vendorname'],
            $this->arrInput['repositoryname']
        );

        if (!$this->fileStorage->hasFile($target)) {
            $this->fileStorage->addFile($source, $target)->replaceTags($this->tagStorage, ['.tpl.']);
        }

        // Add plugin test
        $source = sprintf(
            '%s/tests/ContaoManager/PluginTest.tpl.php',
            $this->skeletonPath
        );

        $target = sprintf(
            '%s/vendor/%s/%s/tests/ContaoManager/PluginTest.php',
            $this->projectDir,
            $this->arrInput['vendorname'],
            $this->arrInput['repositoryname']
        );

        if (!$this->fileStorage->hasFile($target)) {
            $this->fileStorage->addFile($source, $target)->replaceTags($this->tagStorage, ['.tpl.']);
        }

        // Add .travis.yml
        $source = sprintf(
            '%s/.travis.tpl.yml',
            $this->skeletonPath
        );

        $target = sprintf(
            '%s/vendor/%s/%s/.travis.yml',
            $this->projectDir,
            $this->arrInput['vendorname'],
            $this->arrInput['repositoryname']
        );

        if (!$this->fileStorage->hasFile($target)) {
            $this->fileStorage->addFile($source, $target)->replaceTags($this->tagStorage, ['.tpl.']);
        }

        // Add github workflow file
        $source = sprintf(
            '%s/.github/workflows/ci.tpl.yml',
            $this->skeletonPath
        );

        $target = sprintf(
            '%s/vendor/%s/%s/.github/workflows/ci.yml',
            $this->projectDir,
            $this->arrInput['vendorname'],
            $this->arrInput['repositoryname']
        );

        if (!$this->fileStorage->hasFile($target)) {
            $this->fileStorage->addFile($source, $target)->replaceTags($this->tagStorage, ['.tpl.']);
        }
    }
}
