<?php

declare(strict_types=1);

namespace In2code\In2publishCore\CommonInjection;

/*
 * Copyright notice
 *
 * (c) 2023 in2code.de and the following authors:
 * Daniel Hoffmann <daniel.hoffmann@in2code.de>
 *
 * All rights reserved
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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

trait BackendUserAuthenticationInjection
{
    protected BackendUserAuthentication $backendUserAuthentication;

    /**
     * @noinspection PhpUnused
     */
    public function injectBackendUserAuthentication(BackendUserAuthentication $backendUserAuthentication): void
    {
        $this->backendUserAuthentication = $backendUserAuthentication;
    }
}