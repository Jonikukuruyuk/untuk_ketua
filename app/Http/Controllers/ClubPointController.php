<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use App\ClubPointDetail;
use App\ClubPoint;
use App\Models\ClubPointAcumulate;
use App\Models\User;
use App\Models\Product;
use App\Models\Wallet;
use App\Models\Order;
use Auth;
use DB;

class ClubPointController extends Controller
{
    public function configure_index()
    {
        return view('club_points.config');
    }

    public function index()
    {
        // $club_points = ClubPoint::latest()->paginate(15);
        $club_points = ClubPoint::select(
            DB::raw('sum(points) as points'),
            'user_id'
        )->groupBy('user_id')->paginate(15);
        return view('club_points.index', compact('club_points'));
    }

    public function userpoint_index()
    {
        // $club_points = ClubPoint::where('user_id', Auth::user()->id)->latest()->paginate(15);
        $max = 15;
        $point = ClubPoint::select(DB::raw('sum(points) as points'))->where('user_id', Auth::id())->first()->points;
        for ($i=$point; $i>$max; $i-=$max) { 
            $point -= $max;
        }
        $club_points = [];
        $club_points_setting = BusinessSetting::where('type', 'club_point_setting')->first()->value;
        foreach (json_decode($club_points_setting) as $key => $cp) {
            $club_points[] = $cp;
        }
        return view('club_points.frontend.index', compact('club_points','point'));
    }

    public function set_point()
    {
        $products = Product::latest()->paginate(15);
        return view('club_points.set_point', compact('products'));
    }

    public function set_products_point(Request $request)
    {
        $products = Product::whereBetween('unit_price', [$request->min_price, $request->max_price])->get();
        foreach ($products as $product) {
            $product->earn_point = $request->point;
            $product->save();
        }
        flash(translate('Point has been inserted successfully for ').count($products).translate(' products'))->success();
        return redirect()->route('set_product_points');
    }

    public function set_all_products_point(Request $request)
    {
        $products = Product::all();
        foreach ($products as $product) {;
            $product->earn_point = $product->unit_price * $request->point;
            $product->save();
        }
        flash(translate('Point has been inserted successfully for ').count($products).translate(' products'))->success();
        return redirect()->route('set_product_points');
    }

    public function set_point_edit($id)
    {
        $product = Product::findOrFail(decrypt($id));
        return view('club_points.product_point_edit', compact('product'));
    }

    public function update_product_point(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->earn_point = $request->point;
        $product->save();
        flash(translate('Point has been updated successfully'))->success();
        return redirect()->route('set_product_points');
    }

    public function convert_rate_store(Request $request)
    {
        $cpSetting = [];
        $club_point_setting = BusinessSetting::where('type', $request->type)->first();
        if ($club_point_setting != null) {
            $jsonCs = json_decode($club_point_setting->value);
            foreach ($request->points as $key => $p) {
                $p['convert'] = $jsonCs[$key]->convert;
                $cpSetting[] = $p;
            }
            $club_point_setting->value = json_encode($cpSetting);
        }
        else {
            foreach ($request->points as $key => $p) {
                if(!isset($p['convert'])){
                    $p['convert'] = 0;
                }
                $cpSetting[] = $p;
            }

            $max_business_setting_id = BusinessSetting::max('id');
            $club_point_setting = new BusinessSetting;
            $club_point_setting->id = $max_business_setting_id+1;
            $club_point_setting->type = $request->type;
            $club_point_setting->value = json_encode($cpSetting);
        }
        $club_point_setting->save();

        flash(translate('Point convert rate has been updated successfully'))->success();
        return redirect()->route('club_points.configs');
    }

    public function processClubPoints(Order $order)
    {
        $club_point = new ClubPoint;
        $club_point->user_id = $order->user_id;
        $club_point->points = 0;
        foreach ($order->orderDetails as $key => $orderDetail) {
            $total_pts = ($orderDetail->product->earn_point) * $orderDetail->quantity;
            $club_point->points += $total_pts;
        }
        $club_point->order_id = $order->id;
        $club_point->convert_status = 0;
        $club_point->save();

        // $club_point_acumulate = new ClubPointAcumulate;
        // $club_point_acumulate->user_id = $order->user_id;
        // $club_point_acumulate->points = $total_pts;
        // $club_point_acumulate->save();

        foreach ($order->orderDetails as $key => $orderDetail) {
            $club_point_detail = new ClubPointDetail;
            $club_point_detail->club_point_id = $club_point->id;
            $club_point_detail->product_id = $orderDetail->product_id;
            $club_point_detail->point = ($orderDetail->product->earn_point) * $orderDetail->quantity;
            $club_point_detail->save();
        }
    }

    public function club_point_detail($id)
    {

        $club_point_details = ClubPointDetail::join('club_points as cp','cp.id','club_point_details.club_point_id')
            ->where('cp.user_id', decrypt($id))->paginate(12);
        return view('club_points.club_point_details', compact('club_point_details'));
    }

    public function convert_point_into_wallet(Request $request)
    {
        // $club_point_convert_rate = BusinessSetting::where('type', 'club_point_convert_rate')->first()->value;
        // $club_point = ClubPoint::findOrFail($request->el);
        // $wallet = new Wallet;
        // $wallet->user_id = Auth::user()->id;
        // $wallet->amount = floatval($club_point->points / $club_point_convert_rate);
        // $wallet->payment_method = 'Club Point Convert';
        // $wallet->payment_details = 'Club Point Convert';
        // $wallet->save();
        $cpSetting = [];
        $club_point = BusinessSetting::where('type', 'club_point_setting')->first();
        foreach (json_decode($club_point->value) as $key => $cp) {
            if($cp->point == $request->el){
                $cp->convert = 1;
            }
            $cpSetting[] = $cp;
        }

        // $user = Auth::user();
        // $user->balance = $user->balance + floatval($club_point->points / $club_point_convert_rate);
        // $user->save();
        $club_point->value = json_encode($cpSetting);
        if ($club_point->save()) {
            return 1;
        }
        else {
            return 0;
        }
    }
}
