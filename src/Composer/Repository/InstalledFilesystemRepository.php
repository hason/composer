<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Repository;

use Composer\Package\PackageInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Util\Filesystem;

/**
 * Installed filesystem repository.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class InstalledFilesystemRepository extends FilesystemRepository implements InstalledRepositoryInterface
{
    private $installPaths = array();

    /**
     * {@inheritdoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        if (!$this->hasPackage($package)) {
            throw new \InvalidArgumentException('The package "'.$package.'" is not in the repository.');
        }

        $packageId = $package->getUniqueName();

        return isset($this->installPaths[$packageId]) ? $this->installPaths[$packageId] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function setInstallPath(PackageInterface $package, $path)
    {
        if (!$this->hasPackage($package)) {
            throw new \InvalidArgumentException('The package "'.$package.'" is not in the repository.');
        }

        $fs = new Filesystem();
        if (null !== $path && !$fs->isAbsolutePath($path)) {
            throw new \InvalidArgumentException('The installation path of a package must be absolute.');
        }

        $this->installPaths[$package->getUniqueName()] = $path !== null ? $fs->normalizePath($path) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function removePackage(PackageInterface $package)
    {
        $packageId = $package->getUniqueName();
        unset($this->installPaths[$packageId]);

        return parent::removePackage($package);
    }

    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        $this->installPaths = array();

        return parent::reload();
    }

    /**
     * {@inheritdoc}
     */
    public function write()
    {
        $data = array();
        $dumper = new ArrayDumper();
        $fs = new Filesystem();
        $fileDir = dirname($fs->normalizePath($this->file->getPath()));

        foreach ($this->getCanonicalPackages() as $package) {
            $path = $this->getInstallPath($package);
            $data[] = array(
                'package' => $dumper->dump($package),
                'install-path' => null !== $path ? $fs->findShortestPath($fileDir, $path, true) : null,
            );
        }

        $this->file->write($data);
    }

    protected function loadPackages($packages)
    {
        $loader = new ArrayLoader();
        $fs = new Filesystem();
        $fileDir = dirname($fs->normalizePath($this->file->getPath()));

        foreach ($packages as $data) {
            // load old format data
            if (!isset($data['package'])) {
                $this->addPackage($loader->load($data));
                continue;
            }

            $package = $loader->load($data['package']);
            $path = isset($data['install-path']) ? $data['install-path'] : null;
            if (null !== $path && !$fs->isAbsolutePath($path)) {
                $path = $fileDir . '/' . $path;
            }

            $this->addPackage($package);
            $this->setInstallPath($package, $path);
        }
    }
}
