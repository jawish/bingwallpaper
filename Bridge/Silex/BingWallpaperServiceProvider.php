<?php

/**
 * This file is part of jawish/bingwallpaper.
 *
 * (c) Jawish Hameed <jawish@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jawish\BingWallpaper\Bridge\Silex;

use awish\BingWallpaper\BingWallpaper;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * BingWallpaperServiceProvider
 *
 * @package   org.jawish.bingwallpaper
 * @author    Jawish Hameed <jawish@gmail.com>
 * @copyright 2015 Jawish Hameed
 * @license   http://www.opensource.org/licenses/MIT The MIT License
 */
class BingWallpaperServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['bing_wallpaper'] = $app->share(function (Application $app) {
            $app->flush();

            return new BingWallpaper();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }
}
