<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Utility;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use Psr\Http\Message\UriInterface;

use function explode;

class UriUtility
{
    public static function normalizeUri(UriInterface $uri): UriInterface
    {
        if (empty($uri->getScheme())) {
            $uri = $uri->withScheme('https');
        }
        if (empty($uri->getHost())) {
            $path = $uri->getPath();
            $parts = explode('/', $path, 2);
            $host = $parts[0];
            $path = $parts[1] ?? '';
            $uri = $uri->withHost($host)->withPath($path);
        }
        return $uri;
    }
}
