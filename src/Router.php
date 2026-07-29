<?
declare(strict_types=1);

namespace HypherText;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class Router {
    public string $pagesPath;

    /** @var array<Route> */
    private array $routes;

    public function __construct() {
        $this->pagesPath = Path::join(Paths::getBase(), "pages");
    }

    public static function render(): void {
        $router = new self();
        $router->generateRoutes();

        if (!isset($_SERVER["REQUEST_URI"])) {
            throw new \Error("No REQUEST_URI when rendering (are we running in a CLI?)");
        }

        $pathname = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        foreach ($router->routes as $route) {
            if ($route->match($pathname, $params)) {
                $route->render(Path::join($router->pagesPath, $route->file), $params);
                return;
            }
        }
        http_response_code(404);
        include Path::join($router->pagesPath, "404.php");
    }

    public function generateRoutes(): void {
        /** @var array<Route> $routes */
        $this->routes = [];
        $finder = new Finder()->in($this->pagesPath)->name(["index.php"]);
        foreach ($finder as $file) {
            $relativePath = Path::makeRelative($file->getPathname(), $this->pagesPath);
            switch ($file->getFilename()) {
                case "index.php":
                    $this->routes[] = new IndexRoute($relativePath);
                    break;
            }
        }

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

abstract class Route {
    public string $file;
    public string $regex = "";

    /** @var array<int> */
    public array $specificity = [];

    public function __construct(string $file) {
        $this->file = $file;
        $this->constructRegex();
    }

    public function match(string $path, ?array &$params): bool {
        return (bool) preg_match($this->regex, $path, $params);
    }

    abstract public function constructRegex(): void;

    /** @param array<string> $params */
    abstract public function render(string $file, array $params): void;
}

class IndexRoute extends Route {
    public function render(string $file, array $params): void {
        (function() use ($file, $params) { require $file; })();
    }

    public function constructRegex(): void {
        /** @var array<string> */
        $chunks = explode("/", $this->file);

        $this->regex .= "/^";
        foreach ($chunks as $i => $chunk) {
            if (preg_match("/\\[(\\.{3})?([a-zA-Z\\-_0-9]+)\\]/", $chunk, $match)) {
                [$_, $rest, $parameter] = $match;
                if ($rest) {
                    $this->regex .= "\\/(?<{$parameter}>.+?)";
                    $this->specificity[$i] = 0;
                } else {
                    $this->regex .= "\\/(?<{$parameter}>[^\\/]+)";
                    $this->specificity[$i] = 1;
                }
            } else {
                if ($chunk !== "index.php") {
                    $this->regex .= "\\/".preg_quote($chunk, "/");
                }
                $this->specificity[$i] = 2;
            }
        }
        $this->regex .= "\\/?$/";
    }
}
