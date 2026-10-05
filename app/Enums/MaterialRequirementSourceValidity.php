<?php

namespace App\Enums;

enum MaterialRequirementSourceValidity: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Undetermined = 'undetermined';
}
