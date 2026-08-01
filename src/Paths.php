<?
declare(strict_types=1);

namespace HypherText;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Error;
use Symfony\Component\Filesystem\Path;

class Paths {
    public static function getAppPath(): string {
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $prefixes = $loader->getPrefixesPsr4();
            if (isset($prefixes['App\\'])) {
                return $prefixes['App\\'][0];
            }
        }
        throw new \Error('HypherText requires an "App\\\"-prefix.');
    }

    public static function getVendorFile(string $package, string $file): string {
        return Path::join(InstalledVersions::getInstallPath($package), $file);
    }

    public static function getCalleeFile(): string {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $trace[0]['file'] ?? null;

        if ($file === null) {
            throw new \Error('Unable to determine caller file.');
        }

        return Path::makeAbsolute($file);
    }

    public static function toUrlPath(string $filePath, ?string $pagesDir = null): string {
        $pagesDir ??= Path::join(self::getAppPath(), "pages");
        return Path::makeAbsolute(Path::makeRelative($filePath, $pagesDir), "/");
    }

    public static function getChunkType(string $chunk): ChunkType {
        if (str_contains($chunk, "/") || $chunk === "." || $chunk === "..")
            return ChunkType::NotAChunk;
        if (preg_match("/^\\[\\.\\.\\..+?\\]$/", $chunk))
            return ChunkType::Rest;
        elseif (preg_match("/^\\[.+?\\]$/", $chunk))
            return ChunkType::Parameter;
        elseif (preg_match("/^\\(.+?\\)$/", $chunk))
            return ChunkType::Group;
        else return ChunkType::Named;
    }
}

enum ChunkType {
    case NotAChunk;
    case Named;
    case Group;
    case Parameter;
    case Rest;
}
