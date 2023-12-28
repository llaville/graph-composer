<?php

namespace Clue\GraphComposer\Graph;

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Attribute\AttributeAware;
use Fhaculty\Graph\Attribute\AttributeBagNamespaced;
use Graphp\GraphViz\GraphViz;
use JMS\Composer\DependencyAnalyzer;
use JMS\Composer\Graph\DependencyGraph;

class GraphComposer
{
    /**
     * @var array<string, string|int>
     */
    private array $layoutVertex = array(
        'fillcolor' => '#eeeeee',
        'style' => 'filled, rounded',
        'shape' => 'box',
        'fontcolor' => '#314B5F'
    );

    /**
     * @var array<string, string|int>
     */
    private array $layoutVertexRoot = array(
        'style' => 'filled, rounded, bold'
    );

    /**
     * @var array<string, string|int>
     */
    private array $layoutEdge = array(
        'fontcolor' => '#767676',
        'fontsize' => 10,
        'color' => '#1A2833'
    );

    /**
     * @var array<string, string|int>
     */
    private array $layoutEdgeDev = array(
        'style' => 'dashed'
    );

    private DependencyGraph $dependencyGraph;

    private GraphViz $graphviz;

    public function __construct(string $dir, GraphViz $graphviz = null)
    {
        if ($graphviz === null) {
            $graphviz = new GraphViz();
            $graphviz->setFormat('svg');
        }
        $analyzer = new DependencyAnalyzer();
        $this->dependencyGraph = $analyzer->analyze($dir);
        $this->graphviz = $graphviz;
    }

    public function createGraph(): Graph
    {
        $graph = new Graph();

        foreach ($this->dependencyGraph->getPackages() as $package) {
            $name = $package->getName();
            // @phpstan-ignore-next-line
            $start = $graph->createVertex($name, true);

            $label = $name;
            if ($package->getVersion() !== null) {
                $label .= ': ' . $package->getVersion();
            }

            $this->setLayout($start, array('label' => $label) + $this->layoutVertex);

            foreach ($package->getOutEdges() as $requires) {
                $targetName = $requires->getDestPackage()->getName();
                // @phpstan-ignore-next-line
                $target = $graph->createVertex($targetName, true);

                $label = $requires->getVersionConstraint();

                $edge = $start->createEdgeTo($target);
                $this->setLayout($edge, array('label' => $label) + $this->layoutEdge);

                if ($requires->isDevDependency()) {
                    $this->setLayout($edge, $this->layoutEdgeDev);
                }
            }
        }

        $root = $graph->getVertex($this->dependencyGraph->getRootPackage()->getName());
        $this->setLayout($root, $this->layoutVertexRoot);

        return $graph;
    }

    /**
     * @param array<string, string|int> $layout
     */
    private function setLayout(AttributeAware $entity, array $layout): void
    {
        $bag = new AttributeBagNamespaced($entity->getAttributeBag(), 'graphviz.');
        $bag->setAttributes($layout);
    }

    public function displayGraph(): void
    {
        $graph = $this->createGraph();

        $this->graphviz->display($graph);
    }

    public function getImagePath(): string
    {
        $graph = $this->createGraph();

        return $this->graphviz->createImageFile($graph);
    }

    public function setFormat(string $format): static
    {
        $this->graphviz->setFormat($format);

        return $this;
    }
}
