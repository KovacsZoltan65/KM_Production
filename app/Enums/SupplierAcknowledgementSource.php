<?php

namespace App\Enums;

/**
 * A beszállítói visszaigazolások forrásaként szolgáló
 * kommunikációs csatornákat határozza meg.
 *
 * A forrás jelzi, hogy a rendszerben rögzített beszállítói
 * visszaigazolás milyen kommunikációs módon érkezett vagy
 * milyen módon került rögzítésre.
 */
enum SupplierAcknowledgementSource: string
{
    /** A beszállítói visszaigazolás e-mailben érkezett. */
    case Email = 'email';

    /** A beszállítói visszaigazolás telefonos egyeztetés során érkezett. */
    case Phone = 'phone';

    /** A beszállítói visszaigazolást manuálisan rögzítették. */
    case Manual = 'manual';

    /** A beszállítói visszaigazolás más, külön nem nevesített forrásból származik. */
    case Other = 'other';
}