<?php declare(strict_types=1);

namespace Clue\GraphComposer\Graph;

use Graphp\Graph\Graph;
use Graphp\Graph\Vertex;
use Graphp\GraphViz\GraphViz;
use JMS\Composer\DependencyAnalyzer;
use JMS\Composer\Graph\DependencyGraph;
use JMS\Composer\Graph\PackageNode;

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
        'style' => 'dashed',
        'fontcolor' => '#767676',
        'fontsize' => 10,
        'color' => '#1A2833'
    );

    private DependencyGraph $dependencyGraph;

    private GraphViz $graphviz;

    /**
     * The maximum depth of dependency to display.
     */
    private int $maxDepth;

    private Graph $graph;

    public function __construct(
        string $dir,
        GraphViz $graphviz = null,
        int $maxDepth = PHP_INT_MAX
    ) {
        if ($graphviz === null) {
            $graphviz = new GraphViz();
            $graphviz->setFormat('svg');
        }

        $analyzer = new DependencyAnalyzer();
        $this->dependencyGraph = $analyzer->analyze($dir);
        $this->graphviz = $graphviz;
        $this->maxDepth = $maxDepth;

        $this->graph = new Graph();
    }

    public function createGraph(): Graph
    {
        $rootPackage = $this->dependencyGraph->getRootPackage();
        $this->drawPackageNode($rootPackage, $this->layoutVertexRoot);

        return $this->graph;
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

    /**
     * @param array<string, string|int> $layoutVertex
     */
    private function drawPackageNode(
        PackageNode $packageNode,
        ?array $layoutVertex = null,
        int $depth = 0
    ): ?Vertex {
        static $drawnPackages = [];

        $name = $packageNode->getName();
        // ensure that packages are only drawn once
        // if two packages in the tree require a package twice
        // then this dependency does not need to be drawn twice
        // and the vertex is returned directly (so an edge can be added)
        if (isset($drawnPackages[$name])) {
            return $drawnPackages[$name];
        }

        if ($depth > $this->maxDepth) {
            return null;
        }

        if ($layoutVertex === null) {
            $layoutVertex = $this->layoutVertex;
        }

        $vertex = $drawnPackages[$name] = $this->graph->createVertex(['id' => $name]);

        $label = $name;
        if ($packageNode->getVersion()) {
            $label .= ': ' .$packageNode->getVersion();
        }

        foreach (array('label' => $label) + $layoutVertex as $key => $value) {
            $vertex->setAttribute('graphviz.' . $key, $value);
        }

        // this foreach will loop over the dependencies of the current package
        foreach ($packageNode->getOutEdges() as $dependency) {
            // never show dev dependencies of dependencies:
            // they are not relevant for the current application and are ignored by composer
            if ($depth > 0 && $dependency->isDevDependency()) {
                continue;
            }

            $targetVertex = $this->drawPackageNode($dependency->getDestPackage(), null, $depth + 1);

            // drawPackageNode will return null if the package should not be shown
            // also the dependencies of a package will be only drawn if max depth is not reached
            // this ensures that packages in a deeper level will not have any dependency
            if ($targetVertex && $depth < $this->maxDepth) {
                $label = $dependency->getVersionConstraint();
                $layoutEdge = $dependency->isDevDependency() ? $this->layoutEdgeDev : $this->layoutEdge;
                $edge = $this->graph->createEdgeDirected($vertex, $targetVertex);
                foreach (array('label' => $label) + $layoutEdge as $key => $value) {
                    $edge->setAttribute('graphviz.' . $key, $value);
                }
            }
        }

        return $vertex;
    }

    public function setFormat(string $format): static
    {
        $this->graphviz->setFormat($format);

        return $this;
    }
}
