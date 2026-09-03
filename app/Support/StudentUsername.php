<?php

namespace App\Support;

class StudentUsername
{
    public static function forNewStudent(string $registration, int|string $year): string
    {
        $registration = trim($registration);
        $yy = substr((string) $year, -2);

        return "AST-Y{$yy}-{$registration}";
    }

    public static function legacy(string $registration): string
    {
        return 'AST'.trim($registration);
    }
}
