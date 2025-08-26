<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Chat;
use App\Http\Requests\ChatRequest;
use Illuminate\Support\Facades\DB;


class ChatController extends Controller
{
    public function transaction(Request $request)
    {
        $user_id = auth()->id();

        $myUser=User::find($user_id);

        $transaction_id=$request->transaction_id;
        $transaction=Transaction::find($transaction_id);
        $isBuyer=$user_id== $transaction->buyer_id;
        $isSeller=$user_id== $transaction->seller_id;

        $anotherUser = $isBuyer ? User::find($transaction->seller_id) : User::find($transaction->buyer_id);

        // $anotherTransactions = Transaction::with('product')->where(function($query) use ($user_id) {
        //     $query->where('buyer_id', $user_id)
        //     ->orWhere('seller_id', $user_id);
        // })
        // ->where('id', '!=', $transaction_id)
        // ->withMax('chats', 'updated_at')
        // ->orderBy('chats_max_updated_at', 'desc')
        // ->get();


        $anotherTransactions = Transaction::with('product')
            ->where(function ($query) use ($user_id) {
                $query->where('buyer_id', $user_id)
                    ->orWhere('seller_id', $user_id);
            })
            ->where('id', '!=', $transaction_id)
            ->addSelect([
                'latest_other_user_chat' => DB::table('chats')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('transaction_id', 'transactions.id')
                    ->where('sender_id', '!=', $user_id)
            ])
            ->orderByDesc('latest_other_user_chat')
            ->get();

        $product=Product::find($transaction->product_id);

        $chats=Chat::with('user')
        ->where('transaction_id', $transaction_id)
        ->orderBy('created_at','asc')
        ->get()
        ->each(function($chat){
            $chat->isSender=$chat->sender_id===auth()->id();
        });

        Chat::where('transaction_id', $transaction_id)
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $isCompleted=$transaction->status==="completed";

        $isValued=$transaction->values->where('sender_id',auth()->id())->isEmpty();

        return view('transaction',compact('user_id','transaction','myUser','anotherUser','anotherTransactions', 'isSeller','product','chats','isCompleted','isValued'));
    }

    public function send_chat(ChatRequest $request)
    {
        $filename=null;
        if ($request->hasFile('image')) {
            $destinationPath = storage_path('app/public/img/chat');

            $filename = $request->file('image')->getClientOriginalName();

            $request->file('image')->move($destinationPath, $filename);
        }

        Chat::create([
            'transaction_id'=>$request->transaction_id,
            'sender_id' => auth()->id(),
            'message' => $request->input('message'),
            'image' => $filename,
        ]);

        return redirect("/chat/{$request->transaction_id}");
    }

    public function chat_edit(ChatRequest $request)
    {
        $action=$request->input('action');
        if($action==='edit'){
            Chat::find($request->chat_id)->update([
                'message'=> $request->message,
            ]);
        }elseif($action==='delete'){
            Chat::find($request->chat_id)->delete();
        }
        return redirect("/chat/{$request->transaction_id}");
    }

    public function make_transaction(Request $request)
    {
        $transaction=Transaction::create([
            'product_id'=>$request->product_id,
            'buyer_id'=>auth()->id(),
            'seller_id'=>$request->seller_id
        ]);
        return redirect("/chat/{$transaction->id}");
    }

}
