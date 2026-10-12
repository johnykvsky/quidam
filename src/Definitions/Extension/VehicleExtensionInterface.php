<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface VehicleExtensionInterface extends ExtensionInterface
{
    /**
     * Manufacturer and matching model, e.g. "Toyota Corolla"
     */
    public function vehicle(): string;

    /**
     * e.g. "Volkswagen"
     */
    public function manufacturer(): string;

    /**
     * Model of given manufacturer, or of a random one, e.g. "Golf"
     */
    public function model(?string $manufacturer = null): string;

    /**
     * Body type, e.g. "SUV"
     */
    public function type(): string;

    /**
     * Fuel, picked by popularity, e.g. "Diesel"
     */
    public function fuel(): string;

    /**
     * Vehicle Identification Number (ISO 3779) with real manufacturer prefix and valid check digit (9th character),
     * e.g. "1HGCM82633A004352"
     */
    public function vin(?string $manufacturer = null): string;

    /**
     * e.g. "KXP-4821"
     */
    public function licensePlate(): string;
}
