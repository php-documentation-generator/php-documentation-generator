<?php
declare(strict_types=1);

namespace PDG\Tests\TestBundle\EventSubscriber;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheClearerEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Kernel $kernel)
    {
    }

    public static function getSubscribedEvents(): array
    {
       return [
           ConsoleEvents::TERMINATE => 'clearCache',
           ConsoleEvents::ERROR => 'clearCache',
           ConsoleEvents::SIGNAL => 'clearCache'
       ];
    }

    public function clearCache(): void
    {
//        $application = new Application($this->kernel);
//        $application->setAutoExit(false);
//
//        $input = new ArrayInput([
//            'command' => 'cache:clear',
//            '-e' => 'test',
//            '--no-warmup' => true,
//        ]);
//
//        $output = new NullOutput();
//        $application->run($input, $output);

        $directory = $this->kernel->getCacheDir();
        $this->deleteDir($directory);

    }

    private function deleteDir(string $directory): bool
    {
        if (!\file_exists($directory)) {
            return true;
        }

        if (!\is_dir($directory)) {
            return \unlink($directory);
        }

        foreach (\scandir($directory) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (!$this->deleteDir($directory.\DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }

        return \rmdir($directory);
    }

}
