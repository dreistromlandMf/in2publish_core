<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

interface FinishablePublisher
{
    public function finish(): void;
}