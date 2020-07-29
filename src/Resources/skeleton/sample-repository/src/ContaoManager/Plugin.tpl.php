<?= "<?php\n" ?>

<?= $phpdoc ?>
declare(strict_types=1);

namespace <?= $toplevelnamespace ?>\<?= $sublevelnamespace ?>\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
<?php if($addcustomroute == "1"): ?>
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
<?php endif; ?>
use Symfony\Component\Config\Loader\LoaderInterface;
<?php if($addcustomroute == "1"): ?>
use Symfony\Component\Config\Loader\LoaderResolverInterface;
<?php endif; ?>
<?php if($addcustomroute == "1"): ?>
use Symfony\Component\HttpKernel\KernelInterface;
<?php endif; ?>

/**
 * Class Plugin
 * @package <?= $toplevelnamespace ?>\<?= $sublevelnamespace ?>\ContaoManager
 */
class Plugin implements BundlePluginInterface, <?php if($addcustomroute == "1"): ?>RoutingPluginInterface, <?php endif; ?>ConfigPluginInterface
{
    /**
     * @param ParserInterface $parser
     * @return array
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create('<?= $toplevelnamespace ?>\<?= $sublevelnamespace ?>\<?= $toplevelnamespace ?><?= $sublevelnamespace ?>')
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle']),
        ];
    }

    /**
     * @param LoaderInterface $loader
     * @param array $managerConfig
     * @throws \Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig)
    {
        $loader->load(__DIR__ . '/../Resources/config/parameters.yml');
        $loader->load(__DIR__ . '/../Resources/config/services.yml');
        $loader->load(__DIR__ . '/../Resources/config/listener.yml');
    }

<?php if($addcustomroute == "1"): ?>
    /**
     * @param LoaderResolverInterface $resolver
     * @param KernelInterface $kernel
     * @return null|\Symfony\Component\Routing\RouteCollection
     * @throws \Exception
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver
            ->resolve(__DIR__ . '/../Resources/config/routes.yml')
            ->load(__DIR__ . '/../Resources/config/routes.yml');
    }

<?php endif; ?>
}

