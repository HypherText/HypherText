<?
declare(strict_types=1);

namespace HypherText;

use DirectoryIterator;
use SplFileInfo;
use Symfony\Component\Filesystem\Path;

class Router {
    public string $pagesPath;

    /** @var array<Route> */
    private array $routes;

    public function __construct() {
        $this->pagesPath = Path::join(Paths::getAppPath(), "pages");
    }

    public static function render(): void {
        $router = new self();
        $router->generate();

        if (!isset($_SERVER["REQUEST_URI"])) {
            throw new \Error("No REQUEST_URI when rendering (are we running in a CLI?)");
        }

        $pathname = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        foreach ($router->routes as $route) {
            if ($route->match($pathname, $args)) {
                $route->render($args);
                return;
            }
        }
        http_response_code(404);
        include Path::join($router->pagesPath, "404.php");
    }

    public function generate(): void {
        $this->routes = [];

        $this->recursivelyTraverse($this->pagesPath, null);
        $this->sortRoutes();
    }

    public function recursivelyTraverse(string $currentPath, ?Layout $currentLayout): void {
        /** @var array<\SplFileInfo> */
        $pathQueue = [];

        /** @var array<\SplFileInfo> */
        $fileQueue = [];

        /** @var null|Layout */
        $layout = null;
        $iterator = new \DirectoryIterator($currentPath);
        foreach ($iterator as /** @var \SplFileInfo */ $info) {
            if (str_starts_with($info->getFilename(), ".")) continue;
            if ($info->isDir()) {
                $pathQueue[] = clone $info;
            } elseif ($info->getFilename() === "index.php") {
                $fileQueue[] = clone $info;
            } elseif ($info->getFilename() === "layout.php") {
                $layout = Layout::fromFileInfo($info);
            }
        }

        foreach ($fileQueue as $info) {
            $route = Route::fromFileInfo($info);
            $route->layout = $layout ?? $currentLayout;

            $this->routes[] = $route;
        }

        foreach ($pathQueue as $info) {
            if (preg_match("/^\\(.+\\)$/", $info->getFilename())) {
                $this->recursivelyTraverse($info->getPathname(), $currentLayout);
            } else {
                $this->recursivelyTraverse($info->getPathname(), $layout);
            }
        }
    }

    public function sortRoutes(): void {
        usort($this->routes, function(Route $a, Route $b) {
            $maxLen = max(\count($a->specificity), \count($b->specificity));
            for ($i = 0; $i < $maxLen; $i++) {
                $av = $a->specificity[$i] ?? -1;
                $bv = $b->specificity[$i] ?? -1;
                if ($av === $bv) continue;
                return $bv <=> $av;
            }
            return 0;
        });
    }
}

abstract class RouteLike {
    public string $file;
    public string $path;

    public function __construct(string $path, string $file) {
        $this->path = $path;
        $this->file = $file;
    }

    abstract public static function fromFileInfo(\SplFileInfo $info): self;
}

class Route extends RouteLike {
    public ?Layout $layout;
    public string $regex = "";

    /** @var array<int> */
    public array $specificity;

    public function __construct(string $path, string $file) {
        parent::__construct($path, $file);
        $this->makeRegex();
        $this->specificity = $this->calculateSpecificity();
    }

    public function render(mixed $args): void {
        $params = $args["params"] ?? [];

        ob_start();
        (function() use ($params) { require $this->file; })();
        $content = ob_get_clean();

        if ($this->layout !== null)
            $this->layout->render($content);
        else echo $content;
    }

    public function makeRegex(): void {
        /** @var array<string> */
        $chunks = explode("/", $this->path);

        $this->regex .= "/^";
        foreach ($chunks as $chunk) {
            $type = Paths::getChunkType($chunk);
            switch ($type) {
                case ChunkType::NotAChunk:
                case ChunkType::Group:
                    break;
                case ChunkType::Rest:
                    $this->regex .= "\\/(?<".substr($chunk, 4, -1).">.+?)";
                    break;
                case ChunkType::Parameter:
                    $this->regex .= "\\/(?<".substr($chunk, 1, -1).">[^\\/]+)";
                    break;
                case ChunkType::Named:
                    $this->regex .= preg_quote($chunk, "/");
                    break;
            }
        }
        $this->regex .= "\\/?$/";
    }

    /** @return array<int> */
    public function calculateSpecificity(): array {
        $chunks = explode("/", $this->path);

        $specificity = [];
        foreach ($chunks as $i => $chunk) {
            $type = Paths::getChunkType($chunk);
            switch ($type) {
                case ChunkType::NotAChunk:
                case ChunkType::Rest:
                    $specificity[$i] = 0;
                    break;
                case ChunkType::Parameter:
                    $specificity[$i] = 1;
                    break;
                case ChunkType::Group:
                    $specificity[$i] = 2;
                    break;
                case ChunkType::Named:
                    $specificity[$i] = 3;
                    break;
            }
        }

        return $specificity;
    }

    public function match(string $path, mixed &$args): bool {
        $isMatch = (bool) preg_match($this->regex, $path, $params);
        $args["params"] = $params;
        return $isMatch;
    }

    public static function fromFileInfo(\SplFileInfo $info): self {
        return new self(Paths::toUrlPath($info->getPath()), $info->getPathname());
    }
}

class Layout extends RouteLike {
    public function render(string $children): void {
        (function() use ($children) { require $this->file; })();
    }

    public function match(string $path, mixed &$args): bool {
        $relative = Path::makeRelative($path, Path::makeAbsolute($this->file, "/"));
        return false;
    }

    public static function fromFileInfo(\SplFileInfo $info): self {
        return new self(Paths::toUrlPath($info->getPath()), $info->getPathname());
    }
}
