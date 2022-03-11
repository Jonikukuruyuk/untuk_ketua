<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use App\ClubPointDetail;
use App\ClubPoint;
use App\Models\ClubPointAcumulate;
use App\Models\ClubPointSetting;
use App\Models\ClubPointUsers;
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
            
        $club_points_setting = ClubPointSetting::all();
        
        foreach ($club_points as $key => $cp) {
            $club_point_users = ClubPointUsers::select('club_point_setting_id')->where('user_id',$cp->user_id)->get();
            $cpu = [];
            $convert = '';
            
            foreach($club_point_users as $c){
                $cpu[] = $c->club_point_setting_id;
            }

            foreach ($club_points_setting as $key => $cps) {
                if(in_array($cps->id, $cpu)){
                    $convert .= $cps->hadiah.", ";
                }
            }
            
            $cp->convert = substr($convert,0,-2);
            $cp->total = $club_point_users->count();
        }

        return view('club_points.index', compact('club_points'));
    }

    public function userpoint_index()
    {
        // $club_points = ClubPoint::where('user_id', Auth::user()->id)->latest()->paginate(15);
        $club_points = ClubPointSetting::all();
        $cpu = ClubPointUsers::where('user_id',Auth::id())->get();
        $user_club_point = ClubPoint::select(DB::raw('sum(points) as points'))->where('user_id', Auth::id())->first()->points;
        $lastPoint = Auth::user()->point;
        $point = $user_club_point - $lastPoint;

        foreach ($club_points as $key => $cps) {
            $cps->convert = 0;
            foreach ($cpu as $c) {
                if($cps->id == $c->club_point_setting_id){
                    $cps->convert = 1;
                }
            }
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
        $cpsCount = ClubPointSetting::count();
        try {
            DB::beginTransaction();
            if ($cpsCount > 0) {
                for ($i=1; $i <= 8; $i++) { 
                    $cps = ClubPointSetting::find($i);
                    $cps->hadiah = $request->hadiah[$i-1];
                    $cps->point = $request->point[$i-1];
                    $cps->save();
                }
            } else {
                for ($i=1; $i <= 8; $i++) { 
                    $cps = new ClubPointSetting;
                    $cps->hadiah = $request->hadiah[$i-1];
                    $cps->point = $request->point[$i-1];
                    $cps->save();
                }
            }
            DB::commit();
            flash(translate('Point convert rate has been updated successfully'))->success();
        } catch (\Throwable $th) {
            DB::rollBack();
            flash(translate($th->getMessage()))->error();
        }

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
    
    public function club_point_reset($id){
        try {
            DB::beginTransaction();
            $user = User::find(decrypt($id));
            $user->point = $user->point + env('MAX_POINT');
            if($user->save()){
                ClubPointUsers::where('user_id', decrypt($id))->delete();
            }
            flash(translate('Point convert user has been reset successfully'))->success();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            flash(translate('Point convert user failed'))->error();
        }
        return redirect()->route('club_points.index');
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
        $club_point_users = ClubPointUsers::where('user_id',Auth::id())->where('club_point_setting_id',$request->el)->first();

        if ($club_point_users == null) {
            $cpu = new ClubPointUsers;
            $cpu->user_id = Auth::id();
            $cpu->club_point_setting_id = $request->el;
            $cpu->save();
        }

        // $user = Auth::user();
        // $user->balance = $user->balance + floatval($club_point->points / $club_point_convert_rate);
        // $user->save();
        if ($cpu->save()) {
            return 1;
        }
        else {
            return 0;
        }
    }
}
