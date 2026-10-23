<?php

namespace App\Services;

use App\Models\MemberOrder;

class OrderCodeGenerator
{
    public function for(MemberOrder $order): string
    {
        return 'ORD-'.now()->format('ym').'-'.str_pad((string) $order->getKey(), 6, '0', STR_PAD_LEFT);
    }
}
