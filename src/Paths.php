<?
namespace HypherText;
use Composer\Autoload\ClassLoader;

class Paths {
    static function getBase() {
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $prefixes = $loader->getPrefixesPsr4();
            if (isset($prefixes['Base\\'])) {
                return $prefixes['Base\\'][0];
            }
        }
    }
}
