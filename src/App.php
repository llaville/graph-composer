<?php declare(strict_types=1);

namespace Clue\GraphComposer;

use Symfony\Component\Console\Application as BaseApplication;

class App extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('clue/graph-composer', '@dev');

        $this->addCommands([
            new Command\Show(),
            new Command\Export(),
        ]);
    }
}
