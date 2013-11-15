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
class InstalledFilesystemRepository extends FilesystemRepository implements InstalledRepositoryInterface, DataRepositoryInterface
{
    private $packageData = array();
    private $fs;

    /**
     * {@inheritdoc}
     */
    public function getPackageData(PackageInterface $package)
    {
        if (!$this->hasPackage($package)) {
            throw new \InvalidArgumentException('The package "'.$package.'" is not in the repository.');
        }

        $packageId = $package->getUniqueName();

        return isset($this->packageData[$packageId]) ? $this->packageData[$packageId] : array();
    }

    /**
     * {@inheritdoc}
     */
    public function setPackageData(PackageInterface $package, array $extra)
    {
        if (!$this->hasPackage($package)) {
            throw new \InvalidArgumentException('The package "'.$package.'" is not in the repository.');
        }

        $this->packageData[$package->getUniqueName()] = $extra;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $data = $this->getPackageData($package);
        $fs = $this->getFilesystem();

        $path = isset($data['install-path']) ? $data['install-path'] : null;
        if (null !== $path && !$fs->isAbsolutePath($path)) {
            $path = dirname($this->file->getPath()) . '/' . $path;
        }

        return null !== $path ? $fs->normalizePath($path) : $path;
    }

    /**
     * {@inheritdoc}
     */
    public function setInstallPath(PackageInterface $package, $path)
    {
        $data = $this->getPackageData($package);

        if (null !== $path) {
            $fs = $this->getFilesystem();
            $path = $fs->findShortestPath(dirname($fs->normalizePath($this->file->getPath())), $path, true);
        }

        $data['install-path'] = $path;
        $this->setPackageData($package, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function removePackage(PackageInterface $package)
    {
        $packageId = $package->getUniqueName();
        unset($this->packageData[$packageId]);

        return parent::removePackage($package);
    }

    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        $this->packageData = array();

        return parent::reload();
    }

    /**
     * {@inheritdoc}
     */
    public function write()
    {
        $data = array();
        $dumper = new ArrayDumper();

        foreach ($this->getCanonicalPackages() as $package) {
            $data[] = array(
                'package' => $dumper->dump($package),
                'data' => $this->getPackageData($package),
            );
        }

        $this->file->write($data);
    }

    protected function loadPackages($packages)
    {
        $loader = new ArrayLoader();
        foreach ($packages as $data) {
            // load old format data
            if (!isset($data['package'])) {
                $this->addPackage($loader->load($data));
                continue;
            }

            $package = $loader->load($data['package']);
            $this->addPackage($package);
            $this->setPackageData($package, isset($data['data']) ? $data['data'] : array());
        }
    }

    private function getFilesystem()
    {
        if (null === $this->fs) {
            $this->fs = new Filesystem();
        }

        return $this->fs;
    }
}
