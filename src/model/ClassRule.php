<?php

declare(strict_types=1);

namespace App\Model;

use InvalidArgumentException;

final class ClassRule
{
    public function __construct(
        private int $classId,
        private string $classCode,
        private int $schoolYearId,
        private float $supervisionFactor,
        private float $weeksHalfYear1,
        private float $weeksHalfYear2
    ) {
        if ($classId < 1 || $schoolYearId < 1) {
            throw new InvalidArgumentException('Identifiers must be positive.');
        }

        if ($supervisionFactor < 0 || $weeksHalfYear1 < 0 || $weeksHalfYear2 < 0) {
            throw new InvalidArgumentException('Class rule values must not be negative.');
        }

        if ($classCode === '') {
            throw new InvalidArgumentException('Class code must not be empty.');
        }
    }

    public function classId(): int
    {
        return $this->classId;
    }

    public function classCode(): string
    {
        return $this->classCode;
    }

    public function schoolYearId(): int
    {
        return $this->schoolYearId;
    }

    public function supervisionFactor(): float
    {
        return $this->supervisionFactor;
    }

    public function weeksFor(int $halfYear): float
    {
        if ($halfYear === 1) {
            return $this->weeksHalfYear1;
        }

        if ($halfYear === 2) {
            return $this->weeksHalfYear2;
        }

        throw new InvalidArgumentException('Half year must be 1 or 2.');
    }
}
