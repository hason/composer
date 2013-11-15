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

namespace Composer\Test\Repository;

use Composer\Package\Dumper\ArrayDumper;
use Composer\Package\Package;
use Composer\Repository\InstalledFilesystemRepository;
use Composer\TestCase;

class FilesystemRepositoryTest extends TestCase
{
    public function testReadPackageData()
    {
        $json = $this->createJsonFileMock();

        $repository = new InstalledFilesystemRepository($json);

        $json
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue(array(
                array(
                    'package' => array('name' => 'package1', 'version' => '1.0.0-beta', 'type' => 'vendor'),
                    'data' => array('foo' => 'foo', 'install-path' => '../package'),
                )
            )));
        $json
            ->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));

        $packages = $repository->getPackages();

        $this->assertSame(1, count($packages));
        $this->assertSame('package1', $packages[0]->getName());
        $this->assertSame(array('foo' => 'foo', 'install-path' => '../package'), $repository->getPackageData($packages[0]));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The package "foo/bar-1.0.0.0" is not in the repository.
     */
    public function testGetPackageDataForUnknownPackage()
    {
        $json = $this->createJsonFileMock();

        $repository = new InstalledFilesystemRepository($json);

        $json
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue(array()));
        $json
            ->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));

        $repository->getPackageData(new Package('foo/bar', '1.0.0.0', '1.0'));
        $this->fail();
    }

    public function testWritePackageData()
    {
        $json = $this->createJsonFileMock();

        $repository = new InstalledFilesystemRepository($json);

        $json
            ->expects($this->once())
            ->method('write')
            ->with(array(
                array(
                    'package' => array(
                        'name' => 'a/a',
                        'version' => '1.0',
                        'version_normalized' => '1.0',
                        'type' => 'library',
                    ),
                    'data' => array(
                        'foo' => 'foo',
                        'install-path' => '../foo',
                    )
                ),
                array(
                    'package' => array(
                        'name' => 'b/b',
                        'version' => '2.0',
                        'version_normalized' => '2.0',
                        'type' => 'library',
                    ),
                    'data' => array(
                    )
                )
            ));

        $repository->addPackage($a = new Package('a/a', '1.0', '1.0'));
        $repository->addPackage($b = new Package('b/b', '2.0', '2.0'));
        $repository->setPackageData($a, array('foo' => 'foo', 'install-path' => '../foo'));

        $repository->write();
    }

    /**
     * @dataProvider getInstallPath
     */
    public function testInstallPath($file, $setpath, $getpath, $data)
    {
        $json = $this->createJsonFileMock();

        $repository = new InstalledFilesystemRepository($json);

        $package = new Package('a/a/', '1.0', '1.0');

        $dumper = new ArrayDumper();
        $installed = array(array('package' => $dumper->dump($package), 'data' => $data));

        $json
            ->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($file));
        $json
            ->expects($this->once())
            ->method('write')
            ->with($installed)
            ->will($this->returnValue(null));

        $repository->addPackage($package);
        $repository->setInstallPath($package, $setpath);

        $this->assertSame($getpath, $repository->getInstallPath($package));
        $repository->write();
    }

    public function getInstallPath()
    {
        return array(
            array('/var/composer/installed.json', '/var/a/a', '/var/a/a', array('install-path' => '../a/a')),
            array('d:/var/composer/installed.json', 'c:/var/a/a', 'c:/var/a/a', array('install-path' => 'c:/var/a/a')),
            array('d:\var\composer\installed.json', 'c:\var\a\a', 'c:/var/a/a', array('install-path' => 'c:/var/a/a')),
        );
    }

    private function createJsonFileMock()
    {
        return $this->getMockBuilder('Composer\Json\JsonFile')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
