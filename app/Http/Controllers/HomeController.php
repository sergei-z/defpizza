<?php

namespace App\Http\Controllers;

use App\Facades\Cart as CartFacade;
use App\Models\Address;
use App\Models\DeliveryType;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\Pizza;
use App\Models\Promo;
use App\Rules\LoginToUseEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function home()
    {
        return view('pages.home');
    }

    public function cart()
    {
        return view('pages.cart');
    }

    public function promos()
    {
        $promos = Promo::where('ended_at', '>=', Carbon::now())->get();

        return view('pages.promos')->withPromos($promos);
    }

    public function delivery()
    {
        $sum = collect(CartFacade::get()['items'])->sum('price');

        $empty = count(CartFacade::get()['items']) < 1;

        $deliveryTypes = DeliveryType::whereNull('min_sum')->orWhere('min_sum', '<=', $sum)->get();

        return view('pages.delivery')
            ->withEmpty($empty)
            ->withDeliveryTypes($deliveryTypes);
    }

    public function saveDeliveryInfo(Request $request)
    {
        $this->validator($request->all())->validate();

        $deliveryInfo = [
            'type' => ['id' => $request->type, 'title' => DeliveryType::find($request->type)->title, 'price' => DeliveryType::find($request->type)->price],
            'user' => ['name' => $request->first_name, 'email' => $request->email],
            'contacts' => ['street' => $request->street, 'city' => $request->city],
            'promo' => Promo::where('code', $request->promo)->first()
        ];

        request()->session()->put('delivery', $deliveryInfo);

        return redirect(route('checkout'));
    }

    public function checkout()
    {
        $items = CartFacade::get()['items'];

        $groupedItems = collect($items)
            ->sortBy('item_key')
            ->groupBy('item_key')
            ->toArray();

        $sum = collect($items)->sum('price');

        $deliveryInfo = request()->session()->get('delivery');

        $promo = $deliveryInfo['promo'];

        $total = $sum + $deliveryInfo['type']['price'];

        if ($promo) {
            $total = ceil($total - $total * ($promo->discount / 100));
        }

        return view('pages.checkout')
            ->withGroupedItems($groupedItems)
            ->withSum($sum)
            ->withDeliveryInfo($deliveryInfo)
            ->withPromo($promo)
            ->withTotal($total);
    }

    // TODO Make helper to fix this code duplication
    public function storeOrder(Request $request)
    {
        $items = CartFacade::get()['items'];

        $sum = collect($items)->sum('price');

        $deliveryInfo = request()->session()->get('delivery');

        $promo = $deliveryInfo['promo'];

        $total = $sum + $deliveryInfo['type']['price'];

        if ($promo) {
            $total = ceil($total - $total * ($promo->discount / 100));
        }

        $order = new Order();

        $order->email = Auth::check() ? Auth::user()->email : $deliveryInfo['user']['email'];
        $order->number = Str::random(10);
        $order->total = $total;
        if (Auth::check()) {
            $order->owner_id = Auth::id();
        }
        $order->state_id = OrderState::first()->id;
        $order->delivery_type_id = $deliveryInfo['type']['id'];
        if ($promo) {
            $order->applied_promo_id = $promo->id;
        }

        $address = Address::create([
            'street' => $deliveryInfo['contacts']['street'],
            'city' => $deliveryInfo['contacts']['city'],
            'owner_id' => $order->owner_id,
        ]);

        $order->address_id = $address->id;

        $order->save();

        foreach ($items as $item) {
            $pizza = Pizza::find(explode('-', $item['item_key'])[0]);

            $position = $pizza->items()->create(['title' => $item['title'], 'price_as_int' => $item['price']]);

            $order->items()->attach($position);
        }

        $request->session()->forget(['cart', 'delivery']);

        return redirect(route('success'));
    }

    protected function validator(array $data)
    {
        $rules = [
            'type' => ['required'],
            'street' => ['required', 'min:3'],
            'promo' => ['nullable', 'exists:promos,code']
        ];

        if (!Auth::check()) {
            $rules['first_name'] = ['required', 'string', 'max:255'];
            $rules['email'] = ['required', 'string', 'email', 'max:255', new LoginToUseEmail()];
        }

        return Validator::make($data, $rules);
    }
}
