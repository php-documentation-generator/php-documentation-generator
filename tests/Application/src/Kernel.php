<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\PDGBundle\Tests\TestBundle\Metadata\Resource\Factory\StaticResourceNameCollectionFactory;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

use function App\DependencyInjection\configure;
use function App\Playground\request;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug, private string $guide = '')
    {
        parent::__construct($environment ?? 'test', $debug ?? true);
        $this->guide = $_ENV['GUIDE_NAME'] ?? 'test';
    }

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $configDir = $this->getConfigDir();

        $container->import($configDir.'/{packages}/*.{php,yaml}');
        $container->import($configDir.'/{packages}/'.$this->environment.'/*.{php,yaml}');

        $services = $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure();

        $services->load('ApiPlatform\PDGBundle\Tests\TestBundle\\', '../../TestBundle/');

        $classes = get_declared_classes();
        $resources = [];

        foreach ($classes as $class) {
            $refl = new \ReflectionClass($class);
            $ns = $refl->getNamespaceName();
            if (0 !== strpos($ns, 'App')) {
                continue;
            }

            $services->set($class);

            if ($refl->getAttributes(ApiResource::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                $resources[] = $class;
            }
        }

        $services->set(StaticResourceNameCollectionFactory::class)->args(['$classes' => $resources]);

        $container->parameters()->set(
            'database_url',
            sprintf('sqlite:///%s/%s', $this->getCacheDir(), 'data.db')
        );

        if (\function_exists('App\DependencyInjection\configure')) {
            configure($container);
        }
    }

    public function request(?Request $request = null): void
    {
        if (null === $request && \function_exists('App\Playground\request')) {
            $request = request();
        }

        $request = $request ?? Request::create('/docs.json');
        $response = $this->handle($request);
        $response->send();
        $this->terminate($request, $response);
    }

    public function getCacheDir(): string
    {
        return (new \ApiPlatform\PDGBundle\Kernel('test', true))->getCacheDir().$this->guide;
    }

    public function executeMigrations(string $direction = Direction::UP): void
    {
        $migrationClasses = $this->getDeclaredClassesForNamespace('DoctrineMigrations');

        if (!$migrationClasses) {
            return;
        }
        $this->boot();

        foreach ($migrationClasses as $migrationClass) {
            if ("Doctrine\Migrations\AbstractMigration" !== (new \ReflectionClass($migrationClass))->getParentClass()->getName()) {
                continue;
            }
            $conf = new Configuration();
            $conf->addMigrationClass($migrationClass);
            $conf->setTransactional(true);
            $conf->setCheckDatabasePlatform(true);
            $meta = new TableMetadataStorageConfiguration();
            $meta->setTableName('doctrine_migration_versions');
            $conf->setMetadataStorageConfiguration($meta);

            $confLoader = new ExistingConfiguration($conf);
            /** @var EntityManagerInterface $em */
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');
            $loader = new ExistingEntityManager($em);
            $dependencyFactory = DependencyFactory::fromEntityManager($confLoader, $loader);

            $dependencyFactory->getMetadataStorage()->ensureInitialized();
            $executed = $dependencyFactory->getMetadataStorage()->getExecutedMigrations();

            if ($executed->hasMigration(new Version($migrationClass)) && Direction::DOWN !== $direction) {
                continue;
            }

            $planCalculator = $dependencyFactory->getMigrationPlanCalculator();
            $plan = $planCalculator->getPlanForVersions([new Version($migrationClass)], $direction);
            $migrator = $dependencyFactory->getMigrator();
            $migratorConfigurationFactory = $dependencyFactory->getConsoleInputMigratorConfigurationFactory();
            $migratorConfiguration = $migratorConfigurationFactory->getMigratorConfiguration(new ArrayInput([]));

            $migrator->migrate($plan, $migratorConfiguration);
        }
    }

    public function loadFixtures(): void
    {
        $fixtureClasses = $this->getDeclaredClassesForNamespace('App\Fixtures');
        if (!$fixtureClasses) {
            return;
        }
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        foreach ($fixtureClasses as $class) {
            if ("Doctrine\Bundle\FixturesBundle\Fixture" !== (new \ReflectionClass($class))->getParentClass()->getName()) {
                continue;
            }
            (new $class())->load($em);
        }
    }

    private function getDeclaredClassesForNamespace(string $namespace): array
    {
        return array_filter(get_declared_classes(), static function (string $class) use ($namespace): bool {
            return str_starts_with($class, $namespace);
        });
    }
}
