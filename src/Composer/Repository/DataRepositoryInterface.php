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

/**
 * Data storable repository interface.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
interface DataRepositoryInterface
{
    /**
     * Returns an extra data for a package
     *
     * @param PackageInterface $package The package instance
     *
     * @return array The data for a package
     *
     * @throws \InvalidArgumentException if the repository doesn't contain a package
     */
    public function getPackageData(PackageInterface $package);

    /**
     * Sets an extra data for a package
     *
     * @param PackageInterface $package The package instance
     * @param array            $data    The package data
     *
     * @throws \InvalidArgumentException if the repository doesn't contain a package
     */
    public function setPackageData(PackageInterface $package, array $data);
}
