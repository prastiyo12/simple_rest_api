<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Orders;
use Validator;
use Mail;
use App\Mail\DemoMail;
use App\Mail\DemoMailAdministrator;

class UserController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $data = User::leftJoin('orders','users.id','=','orders.user_id')
                ->where('active','=', true)
                ->select(
                    'users.id',
                    'users.email',
                    'users.name',
                    'users.created_at',
                    DB::raw("count(orders.user_id) as orders_count")
                )
                ->groupBy('users.id', 'users.email', 'users.name', 'users.created_at');
            
            if($request->get('search')){
                $data->whereRaw("lower(users.email) like '%".strtolower($request->get('search'))."%'");
                $data->orWhereRaw("lower(users.name) like '%".strtolower($request->get('search'))."%'");
            }

            //ORDER BY
            if($request->get('sortBy')){
                if($request->get('sortBy') == 'created_at'){
                    $data->orderBy('users.created_at', 'ASC');
                }else{
                    $data->orderBy($request->get('sortBy'), 'ASC');
                }
            }else{
                $data->orderBy('users.created_at', 'ASC');
            }

            //PAGINATION
            $page = $request->get('page') != '' ? (int)$request->get('page') : 1;
            $rows = 10;
            $total = $data->count();
            $start = ($page > 1) ? $page : 0;
            $start = ($total <= $rows) ? 0 : $start;
            $pages = ceil($total / $rows);
            $data->offset($start);
            $data->limit($rows);
            $grid = $data->get();

            $res["page"] = $pages;
            $res["users"] = $grid;
            return response()->json($res, 200);
        }catch (\Exception $e) {
            $res['code'] = 500;
            $res['message'] = $e->getMessage();
            return response()->json($res, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'name' => 'required|string|min:3|max:50'
        ]);

        // Validasi mandatory field
        if ($validator->fails()) {
            $fields = '';
            foreach($validator->errors()->all() as $key => $value){
                $fields .=  ' '.$value.', ';
            }
            $res['code'] = 400;
            $res['message'] = $fields;
            return response()->json($res, 400);
        }

        try{
            DB::beginTransaction();
            $user = new User;
            $user->email = $request->input('email');
            $user->name = $request->input('name');
            $user->password = $request->input('password');
            $user->created_at = date('Y-m-d H:i:s');
           
            if($user->save()){
                //Send email to user confirming their account creation
                $mailDataUser = [
                    'title' => 'SIMPLE REST API BY LARAVEL',
                    'name' => $user->name
                ];
                \Mail::to($request->input('email'))->send(new DemoMail($mailDataUser));

                //Send email to the system administrator notifiying them of the new usser
                $mailDataAdmin = [
                    'title' => 'SIMPLE REST API BY LARAVEL',
                    'email' => $user->email,
                    'name'  => $user->name,
                ];
                \Mail::to(env('MAIL_ADMIN',''))->send(new DemoMailAdministrator($mailDataAdmin));
                DB::commit();
                return response()->json($user, 200);
            }else{
                $res['code'] = 500;
                $res['message'] = 'User Not Saved.';
                return response()->json($res, 500);
            }
        }catch(\Exception $e){
            $res['code'] = 500;
            $res['message'] = $e->getMessage();
            return response()->json($res, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
