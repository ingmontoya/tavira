<?php

namespace App\Exceptions;

use Exception;

class InvoiceGenerationException extends Exception
{
    public static function duplicatePeriod(int $year, int $month, int $existingCount): self
    {
        return new self(
            "Ya existen {$existingCount} facturas para el período {$month}/{$year}. Use la opción --force para regenerar o elimínelas manualmente.",
            422
        );
    }

    public static function noActiveConjunto(): self
    {
        return new self('No se encontró configuración activa del conjunto.', 404);
    }

    public static function noOccupiedApartments(): self
    {
        return new self('No se encontraron apartamentos elegibles (ocupados o disponibles) para generar facturas.', 404);
    }

    public static function noPaymentConcepts(): self
    {
        return new self('No se encontraron conceptos de pago activos y recurrentes para generar facturas.', 404);
    }

    public static function apartmentNotFound(int $apartmentId): self
    {
        return new self("El apartamento con ID {$apartmentId} no fue encontrado o no está ocupado.", 404);
    }

    public static function paymentConceptNotApplicable(int $apartmentId, string $apartmentType): self
    {
        return new self("No hay conceptos de pago aplicables al apartamento {$apartmentId} de tipo {$apartmentType}.", 422);
    }

    public static function databaseError(string $message): self
    {
        return new self("Error en la base de datos durante la generación de facturas: {$message}", 500);
    }
}
