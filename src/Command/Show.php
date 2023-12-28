<?php

namespace Clue\GraphComposer\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\GraphComposer\Graph\GraphComposer;

class Show extends Command
{
    protected function configure(): void
    {
        $this->setName('show')
             ->setDescription('Show dependency graph image for given project directory')
             ->addArgument('dir', InputArgument::OPTIONAL, 'Path to project directory to scan', '.')
             ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Image format (svg, png, jpeg)', 'svg')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $input->getArgument('dir');
        if (!is_string($dir)) {
            return 1;
        }
        $format = $input->getOption('format');
        if (!is_string($format)) {
            return 1;
        }

        $graph = new GraphComposer($dir);
        $graph->setFormat($format);
        $graph->displayGraph();

        return 0;
    }
}
