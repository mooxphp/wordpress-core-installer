<?php

/**
 * WordPress Core Installer - A Composer installer to install WordPress in a webroot subdirectory
 * Copyright (C) 2013 John P. Bloch
 * Modified by Moox Developers, 2025
 *
 * Licensed under the GPLv2 or later.
 */

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

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $installationDir = false;
        $prettyName      = $package->getPrettyName();

        // 1️⃣ Read from root package (top-level composer.json)
        if ($this->composer->getPackage()) {
            $topExtra = $this->composer->getPackage()->getExtra();
            if (!empty($topExtra['wordpress-install-dir'])) {
                $installationDir = $topExtra['wordpress-install-dir'];
                if (is_array($installationDir)) {
                    $installationDir = empty($installationDir[$prettyName]) ? false : $installationDir[$prettyName];
                }
            }
        }

        // 2️⃣ Read from dependent packages (like moox/press) - SAFE version
        if (
            !$installationDir &&
            $this->composer &&
            method_exists($this->composer, 'getRepositoryManager') &&
            $this->composer->getRepositoryManager() &&
            method_exists($this->composer->getRepositoryManager(), 'getLocalRepository') &&
            $this->composer->getRepositoryManager()->getLocalRepository()
        ) {
            foreach ($this->composer->getRepositoryManager()->getLocalRepository()->getPackages() as $pkg) {
                $pkgExtra = $pkg->getExtra();
                if (!empty($pkgExtra['wordpress-install-dir'])) {
                    $installationDir = $pkgExtra['wordpress-install-dir'];
                    break;
                }
            }
        }

        // 3️⃣ Read from the WordPress package itself (rare case)
        $extra = $package->getExtra();
        if (!$installationDir && !empty($extra['wordpress-install-dir'])) {
            $installationDir = $extra['wordpress-install-dir'];
        }

        // 4️⃣ Fallback default
        if (!$installationDir) {
            $installationDir = 'public/wp';
        }

        // 5️⃣ Safety checks
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
            $conflict_message = $this->getConflictMessage($prettyName, self::$_installedPaths[$installationDir]);
            throw new \InvalidArgumentException($conflict_message);
        }

        self::$_installedPaths[$installationDir] = $prettyName;

        return $installationDir;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return self::TYPE === $packageType;
    }

    /**
     * Get the exception message with conflicting packages.
     */
    private function getConflictMessage($attempted, $alreadyExists)
    {
        return sprintf(self::MESSAGE_CONFLICT, $attempted, $alreadyExists);
    }

    /**
     * Get the exception message for attempted sensitive directories.
     */
    private function getSensitiveDirectoryMessage($attempted, $packageName)
    {
        return sprintf(self::MESSAGE_SENSITIVE, $attempted, $packageName);
    }
}
