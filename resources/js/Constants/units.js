/**
 * Mértékegység-választó listaeleme.
 * @typedef {Object} UnitOption
 * @property {string} label A megjelenített mértékegység.
 * @property {string} value Az űrlapban tárolt mértékegység.
 */

/**
 * A gyártási és készletűrlapokon választható mértékegységek.
 * @type {ReadonlyArray<UnitOption>}
 */
export const UNIT_OPTIONS = [
    { label: "db", value: "db" },
    { label: "kg", value: "kg" },
    { label: "m", value: "m" },
    { label: "m2", value: "m2" },
    { label: "m3", value: "m3" },
    { label: "l", value: "l" },
    { label: "ml", value: "ml" },
    { label: "mm", value: "mm" },
    { label: "cm", value: "cm" },
];
