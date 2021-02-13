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

use Contao\StringUtil;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\Str\Str;
use Markocupic\ContaoBundleCreatorBundle\Event\AddMakerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContaoContentElementMaker extends AbstractMaker implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AddMakerEvent::NAME => ['addFilesToStorage', 910],
        ];
    }

    /**
     * Add content element files to file storage.
     *
     * @throws \Exception
     */
    public function addFilesToStorage(AddMakerEvent $event): void
    {
        parent::addFilesToStorage($event);

        if (!$this->input->addContentElement) {
            return;
        }

        // Add tags
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        $this->tagStorage->set('contentelementclassname', Str::asContaoContentElementClassName((string) $this->input->contentelementtype));
        $this->tagStorage->set('contentelementtype', (string) $this->input->contentelementtype);
        $this->tagStorage->set('contentelementcategory', (string) $this->input->contentelementcategory);
        $this->tagStorage->set('contentelementtemplate', Str::asContaoContentElementTemplateName((string) $this->input->contentelementtype));
        $arrLabel = $stringUtilAdapter->deserialize($this->input->contentelementtrans, true);
        $this->tagStorage->set('contentelementtrans_0', $arrLabel[0]);
        $this->tagStorage->set('contentelementtrans_1', $arrLabel[1]);

        // Get the content element template name
        $strContentElementTemplateName = Str::asContaoContentElementTemplateName((string) $this->input->contentelementtype);

        // Get the content element classname
        $strContentElementClassname = Str::asContaoContentElementClassName((string) $this->input->contentelementtype);

        // Add content element class to src/Controller/ContentElement
        $source = sprintf(
            '%s/src/Controller/ContentElement/ContentElementController.tpl.php',
            $this->skeletonPath
        );

        $target = sprintf(
            '%s/vendor/%s/%s/src/Controller/ContentElement/%s.php',
            $this->projectDir,
            $this->input->vendorname,
            $this->input->repositoryname,
            $strContentElementClassname
        );

        if (!$this->fileStorage->hasFile($target)) {
            $this->fileStorage->addFile($source, $target);
        }

        // Add content element template
        $source = sprintf(
            '%s/src/Resources/contao/templates/ce_sample_element.tpl.html5',
            $this->skeletonPath
        );

        $target = sprintf(
            '%s/vendor/%s/%s/src/Resources/contao/templates/%s.html5',
            $this->projectDir,
            $this->input->vendorname,
            $this->input->repositoryname,
            $strContentElementTemplateName
        );

        if (!$this->fileStorage->hasFile($target)) {
            $this->fileStorage->addFile($source, $target);
        }

        // Add src/Resources/contao/dca/tl_content.php
        $source = sprintf(
            '%s/src/Resources/contao/dca/tl_content.tpl.php',
            $this->skeletonPath
        );

        $target = sprintf(
            '%s/vendor/%s/%s/src/Resources/contao/dca/tl_content.php',
            $this->projectDir,
            $this->input->vendorname,
            $this->input->repositoryname
        );

        if (!$this->fileStorage->hasFile($target)) {
            $this->fileStorage->addFile($source, $target);
        }

        // Add src/Resources/contao/languages/en/modules.php to file storage
        $target = sprintf(
            '%s/vendor/%s/%s/src/Resources/contao/languages/en/default.php',
            $this->projectDir,
            $this->input->vendorname,
            $this->input->repositoryname
        );

        $source = sprintf(
            '%s/src/Resources/contao/languages/en/default.tpl.php',
            $this->skeletonPath
        );

        if (!$this->fileStorage->hasFile($target)) {
            $this->fileStorage->addFile($source, $target);
        }
    }
}
