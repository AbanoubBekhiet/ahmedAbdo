<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Product;
use App\Http\Requests\Offers\StoreOfferRequest;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
class OffersController extends Controller
{
    public function index()
    {
        $offers = Offer::with('product')->where('end_date', '>=', date('Y-m-d H:i:s'))->get();
        return $this->successResponse([
            'status_code' => 200,
            'message' => 'تم جلب العروض بنجاح',
            'data' => $offers
        ]);
    }
    public function store(StoreOfferRequest $request)
    {
        $offer = Offer::create([
            'title' => $request->title,
            'description' => $request->description,
            'end_date' => $request->end_date,
            'price_after_discount' => $request->price_after_discount,
            'product_id' => $request->product_id,
        ]);
        app(NotificationController::class)->sendGlobalOfferNotification(new Request([
            'title'    => $offer->title,
            'body'     => $offer->description,
            'offer_id' => $offer->id
        ]));
        return $this->successResponse([
            'status_code' => 201,
            'message' => 'تم اضافة العرض بنجاح',
            'data' => $offer
        ]);
    }
    public function destroy($id)
    {
        $offer = Offer::find($id);
        if (!$offer) {
            return $this->errorResponse(
                'العرض غير موجود',
                404,
            );
        }
        $offer->delete();
        return $this->successResponse([
            'status_code' => 200,
            'message' => 'تم حذف العرض بنجاح',
        ]);
    }
    public function show($id)
    {
        $offer = Offer::with('product')->find($id);
        if (!$offer) {
            return $this->errorResponse(
                'العرض غير موجود',
                404,
            );
        }
        return $this->successResponse([
            'status_code' => 200,
            'message' => 'تم جلب العرض بنجاح',
            'data' => $offer
        ]);
    }
}
