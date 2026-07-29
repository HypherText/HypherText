<?
declare(strict_types=1);

namespace HypherText;

class Layout {
    private $file;

    public function __construct(string $name) {
        $this->file = Paths::getBase()."/layouts/{$name}.php";
        if (!file_exists($this->file)) {
            throw new \Error("Layout {$name} does not exist at {$this->file}.");
        }
    }

    public function start(): void {
        ob_start();
    }

    public function end(): void {
        $children = ob_get_clean();
        include $this->file;
    }
}
