<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supplier_acknowledgement_id', 'purchase_order_item_id'])]
class SupplierAcknowledgementScopeItem extends Model
{
    use AppendOnly;

    public function acknowledgement(): BelongsTo
    {
        return $this->belongsTo(SupplierAcknowledgement::class, 'supplier_acknowledgement_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class)->withTrashed();
    }
}
