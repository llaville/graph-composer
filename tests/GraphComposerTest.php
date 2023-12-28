<?php

use Clue\GraphComposer\Graph\GraphComposer;
use Fhaculty\Graph\Graph;
use Graphp\GraphViz\GraphViz;
use PHPUnit\Framework\TestCase;

class GraphVizMockDisplay extends GraphViz
{
    public int $called = 0;

    public function display(Graph $graph): void
    {
        ++$this->called;
    }
}

class GraphVizMockCreateImageFile extends GraphViz
{
    public int $called = 0;

    public function createImageFile(Graph $graph): string
    {
        return 'test' . ++$this->called . '.png';
    }
}

class GraphVizMockSetFormat extends GraphViz
{
    public ?string $called = null;

    public function setFormat($format): void
    {
        $this->called = $format;
    }
}

class GraphComposerTest extends TestCase
{
    public function testCreateGraph()
    {
        $dir = __DIR__ . '/../';

        $graphComposer = new GraphComposer($dir);
        $graph = $graphComposer->createGraph();

        $this->assertInstanceOf('Fhaculty\Graph\Graph', $graph);
        $this->assertTrue(count($graph->getVertices()) > 0);
    }

    public function testDisplayGraphCallsDisplayGraphViz()
    {
        $dir = __DIR__ . '/../';

        // mocking with PHP 7.4 reports error with legacy PHPUnit, create manual mock classes instead
        $graphviz = new GraphVizMockDisplay();

        $graphComposer = new GraphComposer($dir, $graphviz);
        $graphComposer->displayGraph();

        $this->assertEquals(1, $graphviz->called);
    }

    public function testGetImagePathWillCreateTemporaryImageFileViaGraphViz()
    {
        $dir = __DIR__ . '/../';

        // mocking with PHP 7.4 reports error with legacy PHPUnit, create manual mock classes instead
        $graphviz = new GraphVizMockCreateImageFile();

        $graphComposer = new GraphComposer($dir, $graphviz);
        $ret = $graphComposer->getImagePath();

        $this->assertEquals('test1.png', $ret);
    }

    public function testSetFormatWillSetFormatOnGraphViz()
    {
        $dir = __DIR__ . '/../';

        // mocking with PHP 7.4 reports error with legacy PHPUnit, create manual mock classes instead
        $graphviz = new GraphVizMockSetFormat();

        $graphComposer = new GraphComposer($dir, $graphviz);
        $ret = $graphComposer->setFormat('gif');

        $this->assertEquals($graphComposer, $ret);
        $this->assertEquals('gif', $graphviz->called);
    }
}
