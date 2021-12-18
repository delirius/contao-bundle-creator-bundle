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

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\System;
use Markocupic\ContaoBundleCreatorBundle\BundleMaker\BundleMaker;
use Markocupic\ContaoBundleCreatorBundle\Model\ContaoBundleCreatorModel;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/*
 * Table tl_contao_bundle_creator
 */
$GLOBALS['TL_DCA']['tl_contao_bundle_creator'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
        'onload_callback' => [
            ['tl_contao_bundle_creator', 'downloadZipFile'],
        ],
        'onsubmit_callback' => [
            ['tl_contao_bundle_creator', 'runCreator'],
        ],
    ],
    'edit' => [
        'buttons_callback' => [
            ['tl_contao_bundle_creator', 'buttonsCallback'],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['bundlename'],
            'flag' => 1,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['bundlename'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.gif',
                'attributes' => 'style="margin-right:3px"',
            ],
        ],
    ],
    // Palettes
    'palettes' => [
        '__selector__' => ['editRootComposer', 'addBackendModule', 'addFrontendModule', 'addContentElement'],
        'default' => '
		    {bundle_settings_legend},bundlename,vendorname,repositoryname,overwriteexisting;
            {composer_settings_legend},composerdescription,composerlicense,composerauthorname,composerauthoremail,composerauthorwebsite,composerpackageversion;
            {rootcomposer_settings_legend},editRootComposer;
            {dcatable_settings_legend},addBackendModule;
            {frontendmodule_settings_legend},addFrontendModule;
            {contentelement_settings_legend},addContentElement;
            {custom_route_settings_legend},addCustomRoute;
            {custom_session_attribute_settings_legend},addSessionAttribute;
            {friendly_configuration_settings_legend},addFriendlyConfiguration;
            {coding_style_legend},addEasyCodingStandard
            ',
    ],
    // Subpalettes
    'subpalettes' => [
        'editRootComposer' => 'rootcomposerextendrepositorieskey',
        'addBackendModule' => 'dcatable,backendmodulecategory,backendmodulecategorytrans,backendmoduletype,backendmoduletrans',
        'addFrontendModule' => 'frontendmodulecategory,frontendmodulecategorytrans,frontendmoduletype,frontendmoduletrans',
        'addContentElement' => 'contentelementcategory,contentelementcategorytrans,contentelementtype,contentelementtrans',
    ],
    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'bundlename' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'clr', 'rgxp' => 'alnum'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'vendorname' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'nospace' => true, 'decodeEntities' => true, 'rgxp' => 'cbcb_vendorname'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'repositoryname' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'doNotCopy' => true, 'decodeEntities' => true, 'rgxp' => 'cbcb_repositoryname', 'placeholder' => 'e.g. contao-pet-collection-bundle'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'overwriteexisting' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'sorting' => true,
            'eval' => ['tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'composerdescription' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'rgxp' => 'cbcb_composerdescription'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'composerpackageversion' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 16, 'tl_class' => 'w50', 'rgxp' => 'alnum'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'composerlicense' => [
            'inputType' => 'select',
            'exclude' => true,
            'sorting' => true,
            'options_callback' => ['tl_contao_bundle_creator', 'getLicenses'],
            'default' => 'GPL-3.0-or-later',
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'rgxp' => 'alnum'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'composerauthorname' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'rgxp' => 'alpha'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'composerauthoremail' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'rgxp' => 'email'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'composerauthorwebsite' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'rgxp' => 'url'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'editRootComposer' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'sorting' => true,
            'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'rootcomposerextendrepositorieskey' => [
            'inputType' => 'select',
            'exclude' => true,
            'options' => ['path', 'vcs-github'],
            'sorting' => true,
            'eval' => ['includeBlankOption' => false, 'tl_class' => 'clr'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'addBackendModule' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'dcatable' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'clr', 'nospace' => true, 'decodeEntities' => true, 'rgxp' => 'cbcb_dcatable', 'placeholder' => 'e.g. tl_pets'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'backendmodulecategory' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'nospace' => true, 'decodeEntities' => true, 'rgxp' => 'cbcb_backendmodulecategory', 'placeholder' => 'e.g. pet_modules'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'backendmodulecategorytrans' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50', 'rgxp' => 'alnum'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'backendmoduletype' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'nospace' => true, 'decodeEntities' => true, 'rgxp' => 'cbcb_backendmoduletype', 'placeholder' => 'e.g. pet_collection'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'backendmoduletrans' => [
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'multiple' => true, 'size' => 2, 'decodeEntities' => true, 'rgxp' => 'alnum', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'addFrontendModule' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'frontendmodulecategory' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'nospace' => true, 'decodeEntities' => true, 'rgxp' => 'cbcb_frontendmodulecategory', 'placeholder' => 'e.g. pet_modules'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'frontendmodulecategorytrans' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50', 'rgxp' => 'alnum'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'frontendmoduletype' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'nospace' => true, 'decodeEntities' => true, 'rgxp' => 'cbcb_frontendmoduletype', 'placeholder' => 'e.g. pet_listing_module'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'frontendmoduletrans' => [
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'multiple' => true, 'size' => 2, 'decodeEntities' => true, 'rgxp' => 'alnum', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'addContentElement' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'contentelementcategory' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'nospace' => true, 'decodeEntities' => true, 'rgxp' => 'cbcb_contentelementcategory', 'placeholder' => 'e.g. image_elements'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'contentelementcategorytrans' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50', 'rgxp' => 'alnum'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'contentelementtype' => [
            'inputType' => 'text',
            'exclude' => true,
            'sorting' => true,
            'flag' => 1,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'nospace' => true, 'decodeEntities' => true, 'rgxp' => 'cbcb_contentelementtype', 'placeholder' => 'e.g. heroimage_element'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'contentelementtrans' => [
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'multiple' => true, 'size' => 2, 'decodeEntities' => true, 'rgxp' => 'alnum', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'addCustomRoute' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'addEasyCodingStandard' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'addSessionAttribute' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'addFriendlyConfiguration' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];

/**
 * Class tl_contao_bundle_creator.
 */
class tl_contao_bundle_creator extends Backend
{
    /**
     * tl_contao_bundle_creator constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * onsubmit callback
     * Run the bundle maker.
     *
     * @throws Exception
     */
    public function runCreator(DataContainer $dc): void
    {
        if ('' !== Input::get('id') && '' === Input::post('createBundle') && 'tl_contao_bundle_creator' === Input::post('FORM_SUBMIT') && 'auto' !== Input::post('SUBMIT_TYPE')) {
            if (null !== ($objModel = ContaoBundleCreatorModel::findByPk(Input::get('id')))) {
                /** @var BundleMaker $bundleMakerService */
                $bundleMakerService = System::getContainer()->get('markocupic.contao_bundle_creator_bundle.bundle_maker.bundle_maker');
                $bundleMakerService->run($objModel);
            }
        }
    }

    /**
     * onload callback
     * Download extension as zip file when clicking on the download button.
     */
    public function downloadZipFile(DC_Table $dc): void
    {
        if ('' !== Input::get('id') && '' === Input::post('downloadBundle') && 'tl_contao_bundle_creator' === Input::post('FORM_SUBMIT') && 'auto' !== Input::post('SUBMIT_TYPE')) {
            /** @var SessionInterface $session */
            $session = System::getContainer()->get('session');

            if ($session->has('CONTAO-BUNDLE-CREATOR.LAST-ZIP')) {
                $zipSrc = $session->get('CONTAO-BUNDLE-CREATOR.LAST-ZIP');
                $session->remove('CONTAO-BUNDLE-CREATOR.LAST-ZIP');

                $projectDir = System::getContainer()->getParameter('kernel.project_dir');

                $filepath = $projectDir.'/'.$zipSrc;
                $filename = basename($filepath);
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="'.$filename.'"');
                header('Content-Length: '.filesize($filepath));
                readfile($filepath);
                exit();
            }
        }
    }

    /**
     * @param $arrButtons
     *
     * @return mixed
     */
    public function buttonsCallback($arrButtons, DC_Table $dc)
    {
        if ('edit' === Input::get('act')) {
            $arrButtons['createBundle'] = '<button type="submit" name="createBundle" id="createBundle" class="tl_submit createBundle" accesskey="x">'.$GLOBALS['TL_LANG']['tl_contao_bundle_creator']['createBundleButton'].'</button>';

            /** @var SessionInterface $session */
            $session = System::getContainer()->get('session');

            if ($session->has('CONTAO-BUNDLE-CREATOR.LAST-ZIP')) {
                $arrButtons['downloadBundle'] = '<button type="submit" name="downloadBundle" id="downloadBundle" class="tl_submit downloadBundle" accesskey="d" onclick="this.style.display = \'none\'">'.$GLOBALS['TL_LANG']['tl_contao_bundle_creator']['downloadBundleButton'].'</button>';
            }
        }

        return $arrButtons;
    }

    public function getLicenses(): array
    {
        $arrLicenses = [];

        foreach ($GLOBALS['contao_bundle_creator']['licenses'] as $k => $v) {
            $arrLicenses[$k] = "$k   ($v)";
        }

        return $arrLicenses;
    }
}
