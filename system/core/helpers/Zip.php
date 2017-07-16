<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\helpers;

use InvalidArgumentException;

/**
 * Contains methods to work with zipped files
 */
class Zip
{

    /**
     * ZipArchive instance
     * @var \ZipArchive $zip
     */
    protected $zip;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!class_exists('ZipArchive')) {
            throw new InvalidArgumentException('Class ZipArchive does not exist');
        }

        $this->zip = new \ZipArchive;
    }

    /**
     * Sets a file path
     * @param string $path
     * @throws InvalidArgumentException
     */
    public function set($path, $create = true)
    {
        $zip = $this->zip;
        $flag = $create ? $zip::CREATE : null;

        if ($this->zip->open($path, $flag) !== true) {
            throw new InvalidArgumentException("Cannot open ZIP file $path");
        }

        return $this;
    }

    /**
     * Adds a file to a ZIP archive from the given path
     * @return boolean
     */
    public function add($file)
    {
        return $this->zip->addFile($file);
    }

    /**
     * Zip a whole folder
     * @param string $source
     * @param string $destination
     * @param string $wrapper Wrapping local directory in the archive
     * @return boolean
     */
    public function folder($source, $destination, $wrapper = '')
    {
        $files = gplcart_file_scan_recursive($source);

        if (empty($files)) {
            return false;
        }

        $this->set($destination);

        $added = 0;
        foreach ($files as $file) {

            if (is_dir($file)) {
                $added ++;
                continue;
            }

            $prefix = $wrapper === '' ? '' : $wrapper . DIRECTORY_SEPARATOR;
            $relative = $prefix . substr($file, strlen($source) + 1);
            $added += (int) $this->zip->addFile($file, $relative);
        }

        $result = count($files) == $added;
        $this->zip->close();
        return $result;
    }

    /**
     * Removes a file from the archive
     * @param string $file
     * @return boolean
     */
    public function remove($file)
    {
        return $this->zip->deleteName($file);
    }

    /**
     * Extract the complete archive or the given files to the specified destination
     * @param string $path
     * @param array $files
     * @return boolean
     */
    public function extract($path, $files = array())
    {
        if (empty($files)) {
            return $this->zip->extractTo($path);
        }

        return $this->zip->extractTo($path, $files);
    }

    /**
     * Returns an array of files in the archive
     * @return array
     */
    public function getList()
    {
        $files = array();
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $files[] = $this->zip->getNameIndex($i);
        }
        return $files;
    }

    /**
     * Returns original ZipArchive class instance
     * @return \ZipArchive
     */
    public function getInstance()
    {
        return $this->zip;
    }

}
