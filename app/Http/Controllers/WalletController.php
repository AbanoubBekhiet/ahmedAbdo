<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Wallets\AddToWalletRequest;
use App\Http\Requests\Wallets\WithdrawFromWalletRequest;
use App\Models\Wallet;
class WalletController extends Controller
{
    public function myWallet(){
        $user=User::where('id',Auth::user()->id)->first();
        return $this->successResponse(
            [
                "message"=>"تم استرجاع محفظة المستخدم بنجاح",
                "userWallet"=>$user->wallet,
            ]
            ,200);
    }
    public function addToWallet(AddToWalletRequest $request,$userId){
        $validatedData=$request->validated();
        $wallet=Wallet::where('user_id',$userId)->first();
        if(!$wallet){
            return $this->errorResponse(
                "المحفظة غير موجودة",
                404);
        }
        $wallet->update([
            'balance'=>$wallet->balance+$validatedData['amount'],
        ]);
        return $this->successResponse(
            [
                "message"=>"تم اضافة المبلغ الى المحفظة بنجاح",
                "userWallet"=>$wallet,
            ]
            ,200);
    }
    public function withdrawFromWallet(WithdrawFromWalletRequest $request,$userId){
        $validatedData=$request->validated();
        $wallet=Wallet::where('user_id',$userId)->first();
        if(!$wallet){
            return $this->errorResponse(
                "المحفظة غير موجودة",
                404);
        }
        if($wallet->balance<$validatedData['amount']){
            return $this->errorResponse(
                "المبلغ المطلوب سحبه اكثر من رصيدك في المحفظة",
                400);
        }
        $wallet->update([
            'balance'=>$wallet->balance-$validatedData['amount'],
        ]);
        return $this->successResponse(
            [
                "message"=>"تم سحب المبلغ من المحفظة بنجاح",
                "userWallet"=>$wallet,
            ]
            ,200);
    }
    
}
