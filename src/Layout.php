<?
namespace HypherText;

use Error;

class Layout
{
    private $file;

    /** @param string $name */
    function __construct($name)
    {
        $this->file = Paths::getBase() . "/layouts/{$name}.php";
        if (!file_exists($this->file)) {
            throw new Error("Layout {$name} does not exist at {$this->file}.");
        }
    }

    function start(): void
    {
        ob_start();
    }

    function end(): void
    {
        $children = ob_get_clean();
        include $this->file;
    }
}
