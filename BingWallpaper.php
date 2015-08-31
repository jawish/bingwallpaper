<?php

/**
 * This file is part of jawish/bingwallpaper.
 *
 * (c) Jawish Hameed <jawish@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jawish\BingWallpaper;

/**
 * BingWallpaper
 *
 * @package   org.jawish.bingwallpaper
 * @author    Jawish Hameed <jawish@gmail.com>
 * @copyright 2015 Jawish Hameed
 * @license   http://www.opensource.org/licenses/MIT The MIT License
 */
class BingWallpaper
{
    private $bingBaseUrl = 'http://bing.com';
    private $bingResolutions = [
        [ 220, 176 ],
        [ 240, 240 ],
        [ 320, 240 ],
        [ 400, 240 ],
        [ 320, 320 ],
        [ 640, 360 ],
        [ 640, 480 ],
        [ 800, 480 ],
        [ 800, 600 ],
        [ 1024, 768 ],
        [ 1280, 720 ],
        [ 1280, 768 ],
        [ 1366, 768 ],
        [ 1920, 1080 ]
    ];

    /**
     *
     * @param array[][]     $resolutions            Array of arrays of the form [width, height].
     * @param string        $url                    Base URL absolute path.
     */
    public function __construct($bingResolutions = null, $bingBaseUrl = null)
    {
        if ($bingResolutions != null) {
            $this->bingResolutions = $bingResolutions;
        }

        if ($bingBaseUrl != null) {
            $this->bingBaseUrl = $bingBaseUrl;
        }
    }

    /**
     * Returns the current list of Bing image resolutions to use.
     *
     * @return array                                Array of arrays of the form [width, height].
     */
    public function getBingResolutions()
    {
        return $this->bingResolutions;
    }

    /**
     * Sets the list of Bing image resolutions to use.
     *
     * @param array         $resolutions            Array of arrays of the form [width, height].
     */
    public function setBingResolutions(array $resolutions)
    {
        $this->bingResolutions = $resolutions;
    }

    /**
     * Returns the Bing base URL.
     *
     * @return string                               Base URL absolute path.
     */
    public function getBingBaseUrl()
    {
        return $this->bingBaseUrl;
    }

    /**
     * Sets the Bing base URL.
     *
     * @param string        $url                    Base URL absolute path.
     *
     * @throws \InvalidArgumentException            If the specified URL is invalid
     */
    public function setBingBaseUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(sprintf('URL %s is invalid or malformed.', $url));
        }

        $this->bingBaseUrl = $url;
    }

    /**
     * Gets wallpaper image(s) from Bing
     *
     * @param array         $resolution             Array of form [width, height].
     * @param integer       $imageCount             Number of images to request.
     * @param integer       $imageIndex             The starting index of the images.
     * @param string        $market                 Market targetting for images (e.g. en-us).
     *
     * @return array                                Images as an array
     *
     * @throws \Exception                            If there was an error fetching/processing the Bing wallpaper data
     */
    public function getWallpaper(array $resolution = [], $imageCount = 1, $imageIndex = 0, $market = null)
    {
        $images = [];

        // Check if a desired resolution was given and is valid
        if (!empty($resolution) && !in_array($resolution, $this->bingResolutions)) {
            // Invalid Bing resolution given, find and use the closest
            $resolution = $this->findNearestResolution( $resolution );
        }

        // Get the requested wallpaper(s) from Bing
        try {
            $result = file_get_contents(sprintf('%s/HPImageArchive.aspx?format=js&n=%d&idx=%d&mkt=%s', $this->bingBaseUrl, $imageCount, $imageIndex, $market));

            if (!$result || !($result = json_decode($result, true))) {
                return false;
            }
            
            // Format result
            $images = $this->getFormattedResults($result, $resolution);
        }
        catch (Exception $e) {
            throw new \Exception('An error occured while fetching and processing the Bing wallpaper data.');
        }

        return $images;
    }

    /**
     * Finds the nearest allowed Bing image resolution to the given resolution
     * 
     * @param array         $resolution             Array of form [width, height].
     *
     * @return array                                Array of form [width, height].
     *
     * @throws \InvalidArgumentException            If the specified resolution is invalid
     */
    public function findNearestResolution(array $resolution)
    {
        if (!$this->isValidResolution($resolution)) {
            throw new \InvalidArgumentException(sprintf('Resolution %s is invalid.', $resolution));
        }

        $validResolutions = array_filter($this->bingResolutions, function ($bingResolution) use ($resolution) {
            return !($bingResolution[0] > $resolution[0] || $bingResolution[1] > $resolution[1]);
        });

        return end($validResolutions);
    }

    /**
     * Rewrites image URL to use the given resolution
     *
     * @param string        $url                    URL of the wallpaper image.
     * @param array         $resolution             Array of form [width, height].
     * @return string                               Rewritten URL.
     */
    public function rewriteImageUrlForResolution($url, array $resolution)
    {
        if (!$this->isValidResolution($resolution)) {
            throw new \InvalidArgumentException(sprintf('URL %s is invalid or malformed.', $url));
        }

        return preg_replace(
            '/_(\d+)x(\d+)\./', 
            '_' . join('x', $resolution) . '.', 
            $url
        );
    }

    /**
     * Formats the Bing Wallpaper JSON endpoint result
     *
     * Converts the dates in the result to PHP dates and constructs full URLs to Bing
     * If forceResolution is used, rewrites the Wallpaper URL to use the given resolution
     *
     * @param array         $result                 Associative array of the JSON from the Bing API.
     * @param mixed         $forceResolution        Optional. If specified in the form [width, height] then wallpaper image 
     *                                              URLs are rewritten to use the given resolution. Otherwise, 
     *                                              URLs are left as is.
     * @return array                                Array of wallpaper images.
     */
    private function getFormattedResults($result, $forceResolution = false)
    {
        if (!isset($result['images'])) {
            return [];
        }

        $formattedResults = [];
        for ($i = 0, $max = count($result['images']); $i < $max; $i++) {
            $formattedResults[] = array(
                'startdate'     => date_create_from_format('Ymd|', $result['images'][$i]['startdate']),
                'fullstartdate' => date_create_from_format('YmdHi|', $result['images'][$i]['fullstartdate']),
                'enddate'       => date_create_from_format('Ymd|', $result['images'][$i]['enddate']),
                'url'           => $this->rewriteImageUrlForResolution($this->bingBaseUrl . $result['images'][$i]['url'], $forceResolution),
                'copyright'     => $result['images'][$i]['copyright'],
                'copyrightlink' => $result['images'][$i]['copyrightlink'],
            );
        }

        return $formattedResults;
    }

    /**
     * Checks whether a given resolution specification is valid
     *
     * @param array         $resolution             Array of form [width, height].
     * @return boolean                              True if valid, false otherwise.
     */
    private function isValidResolution($resolution)
    {
        return !(
            (!isset($resolution[0]) || !is_numeric($resolution[0])) && 
            (!isset($resolution[1])  || !is_numeric($resolution[1]))
        );
    }
}