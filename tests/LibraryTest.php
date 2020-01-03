<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class LibraryValidationTest extends TestCaseSymconValidation
{
    public function testValidateLibrary(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateIO(): void
    {
        $this->validateModule(__DIR__ . '/../ServerWMC IO');
    }

    public function testValidateChannels(): void
    {
        $this->validateModule(__DIR__ . '/../ServerWMC Channels');
    }

    public function testValidateRecordings(): void
    {
        $this->validateModule(__DIR__ . '/../ServerWMC Recordings');
    }

    public function testValidateTimers(): void
    {
        $this->validateModule(__DIR__ . '/../ServerWMC Timers');
    }
    public function testValidateSeriesTimers(): void
    {
        $this->validateModule(__DIR__ . '/../ServerWMC SeriesTimers');
    }
}