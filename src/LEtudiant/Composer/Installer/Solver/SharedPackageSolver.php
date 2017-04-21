<?php

/*
 * This file is part of the "Composer Shared Package Plugin" package.
 *
 * https://github.com/Letudiant/composer-shared-package-plugin
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LEtudiant\Composer\Installer\Solver;

use Composer\Package\PackageInterface;
use LEtudiant\Composer\Installer\Config\SharedPackageInstallerConfig;
use LEtudiant\Composer\Installer\SharedPackageInstaller;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class SharedPackageSolver
{
    /**
     * @var array
     */
    protected $packageCallbacks = array();

    /**
     * @var array
     */
    protected $packageIncludeList = array();

    /**
     * @var array
     */
    protected $packageExcludeList = array();

    /**
     * @var bool
     */
    protected $areAllShared = false;


    /**
     * @param SharedPackageInstallerConfig $config
     */
    public function __construct(SharedPackageInstallerConfig $config)
    {
        $packageList = $config->getPackageList();
        $this->areAllShared = in_array("*", $packageList);

        if (!$this->areAllShared) {
            $this->packageCallbacks = $this->createCallbacks($packageList);
        }

        $this->packageIncludeList = $config->getPackageIncludeList();
        $this->packageExcludeList = $config->getPackageExcludeList();
    }

    /**
     * @param PackageInterface $package
     *
     * @return bool
     */
    public function isSharedPackage(PackageInterface $package)
    {
        $prettyName = $package->getPrettyName();
        $return = true;

        // Avoid putting this package into dependencies folder, because on the first installation the package won't be
        // installed in dependencies folder but in the vendor folder.
        // So I prefer keeping this behavior for further installs.
        if (SharedPackageInstaller::PACKAGE_PRETTY_NAME === $prettyName) {
            return false;
        }

        if ($this->areAllShared || SharedPackageInstaller::PACKAGE_TYPE === $package->getType()) {
            return true;
        }

        foreach ($this->packageCallbacks as $equalityCallback) {
            if ($equalityCallback($prettyName)) {
                return true;
            }
        }

        if (!empty($this->packageIncludeList) || !empty($this->packageExcludeList)) {

            foreach($this->packageIncludeList as $packageIncluded) {
                $return = false;
                $packageIncluded = str_replace("*", "", $packageIncluded);
                if (strpos($packageIncluded, $prettyName) {
                    $return = true;
                }
            }

            foreach($this->packageExcludeList as $packageExcluded) {
                $packageExcluded = str_replace("*", "", $packageExcluded);
                if (strpos($packageExcluded, $prettyName) {
                    $return = false;
                }
            }
        }

        return $return;
    }

    /**
     * @param array $packageList
     *
     * @return array
     */
    protected function createCallbacks(array $packageList)
    {
        $callbacks = array();

        foreach ($packageList as $packageName) {
            // Has wild card (*)
            if (false !== strpos($packageName, '*')) {
                $pattern = str_replace('*', '[a-zA-Z0-9-_]+', str_replace('/', '\/', $packageName));

                $callbacks[] = function ($packagePrettyName) use ($pattern) {
                    return 1 === preg_match('/' . $pattern . '/', $packagePrettyName);
                };
            // Raw package name
            } else {
                $callbacks[] = function ($packagePrettyName) use ($packageName) {
                    return $packageName === $packagePrettyName;
                };
            }
        }

        return $callbacks;
    }
}
