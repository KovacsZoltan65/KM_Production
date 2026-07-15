<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/**
 * A `Document` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class DocumentPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: dokumentum listájának megtekintése.
     *
     * A művelethez a `documents.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('documents.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: dokumentum adatlapjának megtekintése.
     *
     * A művelethez a `documents.view` permission szükséges.
     */
    public function view(User $user, Document $document): bool
    {
        return $user->can('documents.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: dokumentum létrehozása.
     *
     * A művelethez a `documents.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('documents.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: dokumentum módosítása.
     *
     * A művelethez a `documents.update` permission szükséges.
     */
    public function update(User $user, Document $document): bool
    {
        return $user->can('documents.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: dokumentum törlése.
     *
     * A művelethez a `documents.delete` permission szükséges.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->can('documents.delete');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: dokumentum letöltése.
     *
     * A művelethez a `documents.download` permission szükséges.
     */
    public function download(User $user, Document $document): bool
    {
        return $user->can('documents.download');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: dokumentum jóváhagyása.
     *
     * A művelethez a `documents.approve` permission szükséges.
     */
    public function approve(User $user, Document $document): bool
    {
        return $user->can('documents.approve');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: dokumentum aktuális verzióvá tétele.
     *
     * A művelethez a `documents.version` permission szükséges.
     */
    public function makeCurrent(User $user, Document $document): bool
    {
        return $user->can('documents.version');
    }
}
