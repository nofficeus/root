<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TransferException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExchangeTradeResource;
use App\Models\ExchangeTrade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ExchangeTradeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:manage_exchange');
    }

    /**
     * Paginate all exchange trade records
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function paginate(Request $request)
    {
        $query = ExchangeTrade::latest();

        $this->filterByUser($query, $request);

        return ExchangeTradeResource::collection(paginate($query));
    }

    /**
     * Complete pending buy
     *
     * @param ExchangeTrade $trade
     * @return mixed|void
     */
    public function completePendingBuy(ExchangeTrade $trade)
    {
        try {
            $record = $trade->completePendingBuy();
        } catch (TransferException $e) {
            return abort(422, $e->getMessage());
        }

        if (!$record instanceof ExchangeTrade) {
            return abort(403, trans('common.in_use'));
        }
    }

    /**
     * Cancel pending
     *
     * @param ExchangeTrade $trade
     * @return mixed|void
     */
    public function cancelPending(ExchangeTrade $trade)
    {
        $record = $trade->cancelPending();

        if (!$record instanceof ExchangeTrade) {
            return abort(403, trans("common.forbidden"));
        }
    }

    /**
     * Filter query by user
     *
     * @param Builder $query
     * @param Request $request
     */
    protected function filterByUser(Builder $query, Request $request)
    {
        if ($search = $request->get('searchUser')) {
            $query->whereHas('walletAccount.user', function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        }
    }
}
