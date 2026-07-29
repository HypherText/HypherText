<?
declare(strict_types=1);

namespace HypherText;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class Router {
    public string $path;

    /** @var array<Route> */
    private array $routes;

    public function __construct() {
        $this->path = Path::join(Paths::getBase(), "pages");
    }

    public static function render(): void {
        $router = new self();
        $router->generateRoutes();

        $pathname = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        foreach ($router->routes as $route) {
            if ($route->match($pathname)) {
                $route->render(Path::join($router->path, $route->file));
                return;
            }
        }
    }

    public function generateRoutes(): void {
        /** @var array<Route> $routes */
        $this->routes = [];
        $finder = new Finder()->in($this->path)->name(["index.php"]);
        foreach ($finder as $file) {
            $relativePath = Path::makeRelative($file->getPathname(), $this->path);
            switch ($file->getFilename()) {
                case "index.php":
                    $this->routes[] = new IndexRoute($relativePath);
                    break;
            }
        }
    }
}

abstract class Route {
    public string $file;
    public string $regex = "";

    public function __construct(string $file) {
        $this->file = $file;
        $this->constructRegex();
    }

    public function match(string $path): bool {
        return (bool) preg_match($this->regex, $path);
    }

    abstract public function constructRegex(): void;

    abstract public function render(string $file): void;
}

class IndexRoute extends Route {
    public function render(string $file): void {
        readfile($file);
    }

    public function constructRegex(): void {
        /** @var array<string> */
        $chunks = explode("/", $this->file) ?: [];

        $this->regex .= "/^";
        foreach ($chunks as $i => $chunk) {
            if (preg_match("/\\[(...)([a-zA-Z\\-_0-9]+)\\]/", $chunk, $match)) {
                [$_, $rest, $parameter] = $match;
                $this->regex .= "\\/(?<{$parameter}>[a-zA-Z\\-_0-9]+)";
            } elseif ($chunk !== "index.php") {
                $this->regex .= "\\/{$chunk}";
            }
        }
        $this->regex .= "\\/?$/";
    }
}
