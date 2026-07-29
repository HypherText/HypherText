<?
declare(strict_types=1);

namespace HypherText;

use Composer\Autoload\ClassLoader;

class Paths {
    public static function getBase(): string {
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $prefixes = $loader->getPrefixesPsr4();
            if (isset($prefixes['Base\\'])) {
                return $prefixes['Base\\'][0];
            }
        }
    }
}
