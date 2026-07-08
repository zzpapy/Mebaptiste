<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoOverlap extends Constraint
{
    public string $message = 'Cette disponibilité chevauche une disponibilité existante pour ce même jour/horaire.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}