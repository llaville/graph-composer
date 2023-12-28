<?php declare(strict_types=1);

namespace Clue\GraphComposer\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\GraphComposer\Graph\GraphComposer;

class Export extends Command
{
    protected function configure(): void
    {
        $this->setName('export')
            ->setDescription('Export dependency graph image for given project directory')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Path to project directory to scan', '.')
            ->addArgument('output', InputArgument::OPTIONAL, 'Path to output image file')

            // add output format option. default value MUST NOT be given, because default is to overwrite with output extension
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Image format (svg, png, jpeg)')
            ->addOption('depth', null, InputOption::VALUE_REQUIRED, 'Set the maximum depth of dependency graph', PHP_INT_MAX)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $input->getArgument('dir');
        if (!is_string($dir)) {
            return 1;
        }
        $graph = new GraphComposer($dir, null, $input->getOption('depth'));

        $target = $input->getArgument('output');
        if (is_string($target)) {
            if (is_dir($target)) {
                $target = rtrim($target, '/') . '/graph-composer.svg';
            }

            $filename = basename($target);
            $pos = strrpos($filename, '.');
            if ($pos !== false && isset($filename[$pos + 1])) {
                // extension found and not empty
                $graph->setFormat(substr($filename, $pos + 1));
            }
        }

        $format = $input->getOption('format');
        if (is_string($format)) {
            $graph->setFormat($format);
        }

        $path = $graph->getImagePath();

        if (is_string($target)) {
            rename($path, $target);
        } else {
            readfile($path);
        }

        return 0;
    }
}
