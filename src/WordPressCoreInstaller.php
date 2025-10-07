<?php

namespace Moox\Composer;

use Composer\Config;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

class WordPressCoreInstaller extends LibraryInstaller
{
    const TYPE = 'wordpress-core';

    const MESSAGE_CONFLICT  = 'Two packages (%s and %s) cannot share the same directory!';
    const MESSAGE_SENSITIVE = 'Warning! %s is an invalid WordPress install directory (from %s)!';

    private static $_installedPaths = [];

    private $sensitiveDirectories = ['.'];

    public function getInstallPath(PackageInterface $package)
    {
        $installationDir = false;
        $prettyName      = $package->getPrettyName();

        if ($this->composer->getPackage()) {
            $topExtra = $this->composer->getPackage()->getExtra();
            if (!empty($topExtra['wordpress-install-dir'])) {
                $installationDir = $topExtra['wordpress-install-dir'];
                if (is_array($installationDir)) {
                    $installationDir = empty($installationDir[$prettyName]) ? false : $installationDir[$prettyName];
                }
            }
        }

        if (!$installationDir) {
            try {
                $repoManager = $this->composer->getRepositoryManager();
                if ($repoManager && method_exists($repoManager, 'getLocalRepository')) {
                    $localRepo = $repoManager->getLocalRepository();
                    if ($localRepo && method_exists($localRepo, 'getPackages')) {
                        foreach ($localRepo->getPackages() as $pkg) {
                            $pkgExtra = $pkg->getExtra();
                            if (!empty($pkgExtra['wordpress-install-dir'])) {
                                $installationDir = $pkgExtra['wordpress-install-dir'];
                                break;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        $extra = $package->getExtra();
        if (!$installationDir && !empty($extra['wordpress-install-dir'])) {
            $installationDir = $extra['wordpress-install-dir'];
        }

        if (!$installationDir) {
            $installationDir = 'public/wp';
        }

        $vendorDir = $this->composer->getConfig()->get('vendor-dir', Config::RELATIVE_PATHS) ?: 'vendor';
        if (
            in_array($installationDir, $this->sensitiveDirectories) ||
            ($installationDir === $vendorDir)
        ) {
            throw new \InvalidArgumentException($this->getSensitiveDirectoryMessage($installationDir, $prettyName));
        }

        if (
            !empty(self::$_installedPaths[$installationDir]) &&
            $prettyName !== self::$_installedPaths[$installationDir] &&
            $package->getType() !== self::TYPE
        ) {
            throw new \InvalidArgumentException(
                $this->getConflictMessage($prettyName, self::$_installedPaths[$installationDir])
            );
        }

        self::$_installedPaths[$installationDir] = $prettyName;

        return $installationDir;
    }

    public function supports($packageType)
    {
        return self::TYPE === $packageType;
    }

    private function getConflictMessage($attempted, $alreadyExists)
    {
        return sprintf(self::MESSAGE_CONFLICT, $attempted, $alreadyExists);
    }

    private function getSensitiveDirectoryMessage($attempted, $packageName)
    {
        return sprintf(self::MESSAGE_SENSITIVE, $attempted, $packageName);
    }
}
